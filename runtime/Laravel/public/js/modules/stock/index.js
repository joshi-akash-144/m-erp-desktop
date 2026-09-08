// ============================================================
//  STOCK STATUS – index.js
//  Tabulator-based UI with 2-level drill-down:
//    Item Wise → Month Wise → Date Wise
// ============================================================

(function () {
    "use strict";

    let stockTable      = null;
    let monthWiseTable  = null;
    let dateWiseTable   = null;

    // ---- Filter state ----
    let currentFilter = {};

    // ---- Overlay loader helpers ----
    function showLoader(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = "flex";
    }
    function hideLoader(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = "none";
    }

    // ---- Date badge helper ----
    function updateDateBadge() {
        const el = document.getElementById("stock_date_badge_text");
        if (!el) return;
        if (currentFilter.as_at_date) {
            el.textContent = "As At: " + currentFilter.as_at_date;
        } else if (currentFilter.start_date && currentFilter.end_date) {
            el.textContent = currentFilter.start_date + " to " + currentFilter.end_date;
        } else {
            el.textContent = "All Dates";
        }
    }

    // ---- Show modal via hidden trigger button (no bootstrap global needed) ----
    function showModal(id) {
        const triggerMap = {
            'stock_view_modal'          : 'trigger_month_wise_modal',
            'date_wise_stock_view_modal': 'trigger_date_wise_modal'
        };
        const btn = document.getElementById(triggerMap[id]);
        if (btn) btn.click();
    }

    // ---- Number formatter ----
    function fmt(val, decimals = 2) {
        const n = parseFloat(val);
        if (isNaN(n)) return (0).toLocaleString("en-IN", { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        return (n || 0).toLocaleString("en-IN", {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    // ---- Quantity formatter (3 decimal places minimum) ----
    function fmtQty(val) {
        const n = parseFloat(val);
        if (isNaN(n)) return "0.000";
        return (n || 0).toLocaleString("en-IN", {
            minimumFractionDigits: 3,
            maximumFractionDigits: 4
        });
    }

    // ---- Blue link formatter (matches screenshot style) ----
    function linkFormatter(label) {
        return `<span style="color:#206bc4;cursor:pointer;font-weight:600;text-decoration:none;"
                      onmouseover="this.style.textDecoration='underline'"
                      onmouseout="this.style.textDecoration='none'">${label}</span>`;
    }

    // ============================================================
    //  1.  ITEM-WISE STOCK TABLE  (main page)
    // ============================================================
    function initStockTable() {
        if (stockTable) { stockTable.setData(); return; }

        stockTable = new Tabulator("#stock_status_table", {
            layout         : "fitColumns",
            height         : "auto",
            maxHeight      : "70vh",
            rowHeight      : 32,
            classes        : ["table", "table-sm", "table-bordered"],
            initialSort    : [{ column: "item_name", dir: "asc" }],
            ajaxURL        : stockStatusListUrl,
            ajaxParams     : () => currentFilter,
            ajaxURLGenerator(url, _cfg, _params) {
                const qp = new URLSearchParams({ size: 9999 });
                if (currentFilter.as_at_date) {
                    qp.set("as_at_date", currentFilter.as_at_date);
                } else {
                    qp.set("start_date", currentFilter.start_date || "");
                    qp.set("end_date",   currentFilter.end_date   || "");
                }
                return `${url}?${qp}`;
            },
            ajaxResponse(_url, _params, response) {
                // Unwrap AjaxResponse wrapper → { data: [...], last_page: n }
                return response.data?.data ?? response.data ?? [];
            },
            placeholder: `
                <div class="text-center py-5">
                    <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No stock data found</h3>
                    <p class="text-muted">Try adjusting your date filters</p>
                </div>`,
            columns: [
                {
                    title: "#", formatter: "rownum", width: 80  ,
                    hozAlign: "center", headerHozAlign: "center"
                },
                {
                    title: "Item Name", field: "item_name", minWidth: 220,
                    headerSort: true,
                    sorter: "string",
                    formatter(cell) {
                        const row = cell.getRow().getData();
                        return linkFormatter(row.item_name || "—");
                    },
                    cellClick(_e, cell) {
                        const row = cell.getRow().getData();
                        openMonthWiseModal(row.item_id, row.item_name);
                    }
                },
                { title: "Unit",     field: "unit_name", width: 150,  hozAlign: "center" },
                {
                    title: "Quantity", field: "quantity", width: 200, hozAlign: "right",
                    formatter: c => fmtQty(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmtQty(c.getValue())}</b>`
                },
                {
                    title: "Avg Rate", field: "rate", width: 250, hozAlign: "right",
                    formatter: c => fmt(c.getValue())
                },
                {
                    title: "Amount", field: "amount", width: 250, hozAlign: "right",
                    formatter: c => fmt(Math.abs(c.getValue() || 0)),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmt(Math.abs(c.getValue() || 0))}</b>`
                }
            ]
        });

        stockTable.on("dataLoading",   () => showLoader("stock_inner_loader"));
        stockTable.on("dataLoaded",    () => hideLoader("stock_inner_loader"));
        stockTable.on("dataLoadError", () => {
            hideLoader("stock_inner_loader");
            showToast?.("error", "Failed to load stock data.");
        });
    }

    // ============================================================
    //  2.  MONTH-WISE STOCK MODAL  (direct AJAX — same pattern as trial balance)
    // ============================================================
    let mwCurrentItemId   = null;
    let mwCurrentItemName = null;

    function openMonthWiseModal(itemId, itemName) {
        mwCurrentItemId   = itemId;
        mwCurrentItemName = itemName;

        // Reset & show loader before opening modal
        $("#mw_stock_modal_title").html(
            `<i class="fa-solid fa-calendar-days me-2 text-primary"></i> Month Wise Stock`
        );
        $("#month_wise_stock_status_table").hide();
        $("#mw_inner_loader").css("display", "flex");
        $("#mw_stock_totals_bar").hide();
        $("#mw_stock_date_badge").hide();

        showModal("stock_view_modal");
        mwLoadData(itemId);
    }

    function mwLoadData(itemId) {
        const params = { item_id: itemId, size: 9999 };
        if (currentFilter.as_at_date) {
            params.as_at_date = currentFilter.as_at_date;
        } else {
            params.start_date = currentFilter.start_date || "";
            params.end_date   = currentFilter.end_date   || "";
        }

        $.ajax({
            url   : monthWiseStockStatusUrl,
            method: "GET",
            data  : params,
            success(res) {
                $("#mw_inner_loader").css("display", "none");
                $("#month_wise_stock_status_table").show();

                if (!res.success) {
                    showToast?.("error", res.message || "No month-wise data found.");
                    return;
                }

                const data   = res.data  || {};
                const months = data.data || [];

                // Header title
                if (data.item_name) {
                    $("#mw_stock_modal_title").html(
                        `<i class="fa-solid fa-calendar-days me-2 text-primary"></i> Month Wise &mdash; <span class="fw-normal">${data.item_name}</span>`
                    );
                }

                // Date badge
                let badgeText = "All Dates";
                if (currentFilter.as_at_date) {
                    badgeText = "As At: " + currentFilter.as_at_date;
                } else if (currentFilter.start_date && currentFilter.end_date) {
                    badgeText = currentFilter.start_date + " — " + currentFilter.end_date;
                }
                $("#mw_stock_date_text").text(badgeText);
                $("#mw_stock_date_badge").show();

                // Build Tabulator with Opening row at top, Closing row at bottom
                const openingStock = data.opening_stock ?? 0;
                const closingStock = data.closing_stock ?? 0;
                const openingAmount = data.opening_amount ?? 0;
                const closingAmount = data.closing_amount ?? 0;
                const tableRows = [
                    { month_name_year: 'Opening Balance', qty_in: null, amount_in: null, qty_out: null, amount_out: null, balance: openingStock, balance_amount: openingAmount, _opening: true },
                    ...months,
                    { month_name_year: 'Closing Balance', qty_in: null, amount_in: null, qty_out: null, amount_out: null, balance: closingStock, balance_amount: closingAmount, _closing: true },
                ];
                mwBuildTable(tableRows);

                // Totals footer
                $("#mw_opening_qty").text(fmtQty(data.opening_stock ?? 0));
                $("#mw_opening_amount").text(fmt(data.opening_amount ?? 0));
                $("#mw_total_in").text(fmtQty(data.total_qty_in    ?? 0));
                $("#mw_total_out").text(fmtQty(data.total_qty_out   ?? 0));
                $("#mw_closing_qty").text(fmtQty(data.closing_stock ?? 0));
                $("#mw_stock_totals_bar").show();
            },
            error() {
                $("#mw_inner_loader").css("display", "none");
                showToast?.("error", "Failed to load month-wise data.");
            }
        });
    }

    function mwBuildTable(rows) {
        if (monthWiseTable) {
            monthWiseTable.setData(rows);
            return;
        }

        const isSpecial = cell => {
            const d = cell.getRow().getData();
            return d._opening || d._closing;
        };

        monthWiseTable = new Tabulator("#month_wise_stock_status_table", {
            data   : rows,
            layout : "fitColumns",
            height : "450px",
            placeholder: `
                <div class="text-center py-4">
                    <i class="fa-solid fa-calendar-xmark fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No month-wise data found</p>
                </div>`,
            rowFormatter(row) {
                const d   = row.getData();
                const el  = row.getElement();
                if (d._opening) {
                    el.style.background  = "#f1f3f5";
                    el.style.fontWeight  = "700";
                    el.style.borderBottom = "1px solid #dee2e6";
                } else if (d._closing) {
                    el.style.background  = "#e8f4ff";
                    el.style.fontWeight  = "700";
                    el.style.borderTop   = "2px solid #206bc4";
                }
            },
            columns: [
                {
                    title: "#", width: 55, hozAlign: "center", headerHozAlign: "center", headerSort: false,
                    formatter(cell) {
                        if (isSpecial(cell)) return "";
                        return cell.getRow().getPosition(true);
                    }
                },
                {
                    title: "Month", field: "month_name_year", minWidth: 160, headerSort: false,
                    formatter(cell) {
                        const d = cell.getRow().getData();
                        if (d._opening) return `<span class="fw-bold text-secondary"><i class="fa-solid fa-box-open me-1"></i>${cell.getValue()}</span>`;
                        if (d._closing) return `<span class="fw-bold text-primary"><i class="fa-solid fa-boxes-stacked me-1"></i>${cell.getValue()}</span>`;
                        return linkFormatter(cell.getValue() || "—");
                    },
                    cellClick(_e, cell) {
                        const d = cell.getRow().getData();
                        if (!d._opening && !d._closing) openDateWiseModal(d.item_id, d.month_name_year);
                    }
                },
                {
                    title: "Qty In", field: "qty_in", width: 120, hozAlign: "right", headerSort: false,
                    formatter: c => isSpecial(c) ? "" : fmtQty(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmtQty(c.getValue())}</b>`
                },
                {
                    title: "Amount In", field: "amount_in", width: 200, hozAlign: "right", headerSort: false,
                    formatter: c => isSpecial(c) ? "" : fmt(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmt(c.getValue())}</b>`
                },
                {
                    title: "Qty Out", field: "qty_out", width: 120, hozAlign: "right", headerSort: false,
                    formatter: c => isSpecial(c) ? "" : fmtQty(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmtQty(c.getValue())}</b>`
                },
                {
                    title: "Amount Out", field: "amount_out", width: 200, hozAlign: "right", headerSort: false,
                    formatter: c => isSpecial(c) ? "" : fmt(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmt(c.getValue())}</b>`
                },
                {
                    title: "Balance Qty", field: "balance", width: 130, hozAlign: "right", headerSort: false,
                    formatter: c => fmtQty(c.getValue())
                },
                {
                    title: "Balance Amount", field: "balance_amount", width: 200, hozAlign: "right", headerSort: false,
                    formatter: c => fmt(c.getValue())
                }
            ]
        });
    }

    // ============================================================
    //  3.  DATE-WISE STOCK MODAL  (direct AJAX — same pattern as month-wise)
    // ============================================================
    let dwCurrentItemId       = null;
    let dwCurrentStartDate    = "";
    let dwCurrentEndDate      = "";
    let dwOpenedFromMonthWise = false; // flag: DW was opened from inside MW modal

    function monthYearToDates(monthYearStr) {
        const d    = new Date(monthYearStr + " 1");
        const y    = d.getFullYear();
        const m    = d.getMonth();
        const pad  = n => String(n).padStart(2, "0");
        const last = new Date(y, m + 1, 0).getDate();
        return {
            start: `${pad(1)}-${pad(m + 1)}-${y}`,
            end  : `${pad(last)}-${pad(m + 1)}-${y}`
        };
    }

    function openDateWiseModal(itemId, monthYearStr) {
        const dates = monthYearToDates(monthYearStr);
        let start   = dates.start;  // DD-MM-YYYY full month start
        let end     = dates.end;    // DD-MM-YYYY full month end

        // Cap start/end against the active filter so partial months stay correct
        if (currentFilter.as_at_date) {
            // as_at_date: end of any month is capped to the as_at_date
            if (formatDateToYMD(end) > formatDateToYMD(currentFilter.as_at_date)) {
                end = currentFilter.as_at_date;
            }
        } else if (currentFilter.start_date && currentFilter.end_date) {
            // date range: clamp both ends to the global range
            if (formatDateToYMD(start) < formatDateToYMD(currentFilter.start_date)) {
                start = currentFilter.start_date;
            }
            if (formatDateToYMD(end) > formatDateToYMD(currentFilter.end_date)) {
                end = currentFilter.end_date;
            }
        }

        dwCurrentItemId    = itemId;
        dwCurrentStartDate = start;
        dwCurrentEndDate   = end;

        $("#dw_start_date").val(start);
        $("#dw_end_date").val(end);

        $("#dw_stock_modal_title").html(
            `<i class="fa-solid fa-calendar-day me-2 text-primary"></i> Date Wise Stock`
        );
        $("#date_wise_stock_status_table").hide();
        showLoader("dw_inner_loader");
        $("#dw_stock_totals_bar").hide();
        $("#dw_stock_date_badge").hide();

        // Open DW on top of MW (stacked) — detect MW state via CSS class, no bootstrap global needed
        dwOpenedFromMonthWise = document.getElementById("stock_view_modal")?.classList.contains("show") ?? false;
        showModal("date_wise_stock_view_modal");

        dwLoadData(); // AJAX starts in parallel — data ready when modal opens
    }

    function dwLoadData() {
        showLoader("dw_inner_loader");
        $("#date_wise_stock_status_table").hide();
        $("#dw_stock_totals_bar").hide();
        $("#dw_stock_date_badge").hide();

        $.ajax({
            url   : dateWiseStockStatusUrl,
            method: "GET",
            data  : {
                item_id   : dwCurrentItemId,
                start_date: dwCurrentStartDate,
                end_date  : dwCurrentEndDate,
                size      : 9999
            },
            success(res) {
                hideLoader("dw_inner_loader");
                $("#date_wise_stock_status_table").show();

                if (!res.success) {
                    showToast?.("error", res.message || "No date-wise data found.");
                    return;
                }

                const d    = res.data  || {};
                const rows = d.data    || [];

                if (d.item_name) {
                    $("#dw_stock_modal_title").html(
                        `<i class="fa-solid fa-calendar-day me-2 text-primary"></i> Date Wise &mdash; <span class="fw-normal">${d.item_name}</span>`
                    );
                }

                $("#dw_stock_date_text").text(`${dwCurrentStartDate}  →  ${dwCurrentEndDate}`);
                $("#dw_stock_date_badge").show();

                // Opening bar above table
                $("#dw_opening_qty").text(fmtQty(d.opening_stock ?? 0));
                $("#dw_opening_amount").text(fmt(d.opening_amount ?? 0));
                $("#dw_opening_bar").show();

                dwBuildTable(rows);

                $("#dw_total_in").text(fmtQty(d.total_qty_in    ?? 0));
                $("#dw_total_out").text(fmtQty(d.total_qty_out  ?? 0));
                $("#dw_closing_qty").text(fmtQty(d.closing_stock ?? 0));
                $("#dw_stock_totals_bar").show();
            },
            error() {
                hideLoader("dw_inner_loader");
                showToast?.("error", "Failed to load date-wise data.");
            }
        });
    }

    function dwBuildTable(rows) {
        if (dateWiseTable) { dateWiseTable.setData(rows); return; }
        dateWiseTable = new Tabulator("#date_wise_stock_status_table", {
            data  : rows,
            layout: "fitColumns",
            height: "calc(100vh - 260px)",
            placeholder: `
                <div class="text-center py-4">
                    <i class="fa-solid fa-calendar-xmark fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No transactions found for this period</p>
                </div>`,
            columns: [
                {
                    title: "Date", field: "voucher_date", width: 120, hozAlign: "center", headerSort: false,
                    formatter: c => formatDateToDMY(c.getValue() || "")
                },
                { title: "Voucher Type",  field: "voucher_type",         minWidth: 130,                   headerSort: false },
                { title: "Vch No.",       field: "voucher_bill_no",      width: 140,  hozAlign: "center", headerSort: false },
                { title: "Sales Inv No.", field: "sales_invoice_number", width: 140,  hozAlign: "center", headerSort: false },
                { title: "Party Name",    field: "supplier_name",        minWidth: 180,                   headerSort: false },
                {
                    title: "Qty In",  field: "quantity_in",  width: 110, hozAlign: "right", headerSort: false,
                    formatter: c => fmtQty(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmtQty(c.getValue())}</b>`
                },
                {
                    title: "Qty Out", field: "quantity_out", width: 110, hozAlign: "right", headerSort: false,
                    formatter: c => fmtQty(c.getValue()),
                    bottomCalc: "sum", bottomCalcFormatter: c => `<b>${fmtQty(c.getValue())}</b>`
                },
                {
                    title: "Balance", field: "balance", width: 120, hozAlign: "right", headerSort: false,
                    formatter: c => fmtQty(c.getValue())
                }
            ]
        });
    }

    // ============================================================
    //  FILTER BUTTONS (main page)
    // ============================================================
    $(function () {
        // ---- Date inputs (matching project-wide pattern) ----
        new DateInput("#start_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
        new DateInput("#end_date",     FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
        new DateInput("#as_at_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
        new DateInput("#dw_start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
        new DateInput("#dw_end_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

        // ---- Seed initial filter from As At Date (pre-selected on load) ----
        currentFilter = { as_at_date: $("#as_at_date").val() || "" };
        updateDateBadge();

        initStockTable();

        // ---- Filter mode toggle (Date Range ↔ As At Date) ----
        $('input[name="filter_mode"]').on("change", function () {
            if ($(this).val() === "as_at") {
                $("#range_fields").hide();
                $("#as_at_fields").show();
            } else {
                $("#range_fields").css("display", "flex"); // flex container
                $("#as_at_fields").hide();
            }
        });

        $("#filter_apply").on("click", function (e) {
            e.preventDefault();
            const mode = $('input[name="filter_mode"]:checked').val();
            if (mode === "as_at") {
                currentFilter = { as_at_date: $("#as_at_date").val() || "" };
            } else {
                currentFilter = {
                    start_date: $("#start_date").val() || "",
                    end_date  : $("#end_date").val()   || ""
                };
            }
            updateDateBadge();
            stockTable?.setData();
        });

        $("#filter_clear").on("click", function (e) {
            e.preventDefault();
            $("#start_date").val("");
            $("#end_date").val("");
            // Reset to As At Date mode with today's date
            const today = new Date();
            const dd = String(today.getDate()).padStart(2, "0");
            const mm = String(today.getMonth() + 1).padStart(2, "0");
            const yyyy = today.getFullYear();
            const todayStr = `${dd}-${mm}-${yyyy}`;
            $("#as_at_date").val(todayStr);
            $('input[name="filter_mode"][value="as_at"]').prop("checked", true);
            $("#range_fields").hide();
            $("#as_at_fields").show();
            currentFilter = { as_at_date: todayStr };
            updateDateBadge();
            stockTable?.setData();
        });

        // ---- Search bar ----
        $("#stock_search").on("input", function () {
            if (!stockTable) return;
            const q = $(this).val().trim();
            $("#stock_search_clear").toggle(q.length > 0);
            q ? stockTable.setFilter("item_name", "like", q) : stockTable.clearFilter();
        });

        $("#stock_search_clear").on("click", function () {
            $("#stock_search").val("").trigger("input");
        });

        // Month-wise modal: reset UI on close, redraw Tabulator on open
        const mwModal = document.getElementById("stock_view_modal");
        mwModal?.addEventListener("hidden.bs.modal", function () {
            if (monthWiseTable) { monthWiseTable.destroy(); monthWiseTable = null; }
            $("#mw_stock_totals_bar").hide();
            $("#mw_stock_date_badge").hide();
            $("#month_wise_stock_status_table").show();
        });
        mwModal?.addEventListener("shown.bs.modal", function () {
            if (monthWiseTable) monthWiseTable.redraw(true);
        });

        // Stacked modal z-index: each new modal sits above the previous one
        $(document).on("show.bs.modal", ".modal", function () {
            const zBase = 1055;
            const depth = $(".modal.show").length;
            if (depth > 0) {
                $(this).css("z-index", zBase + depth * 20);
                setTimeout(() => {
                    $(".modal-backdrop").not(".modal-stacked-bd").last()
                        .css("z-index", zBase + depth * 20 - 5)
                        .addClass("modal-stacked-bd");
                }, 0);
            }
        });

        // Date-wise modal: filter buttons
        $(document)
            .off("click.dw_apply")
            .on("click.dw_apply", "#dw_filter_apply", function () {
                dwCurrentStartDate = $("#dw_start_date").val() || dwCurrentStartDate;
                dwCurrentEndDate   = $("#dw_end_date").val()   || dwCurrentEndDate;
                dwLoadData();
            });

        $(document)
            .off("click.dw_clear")
            .on("click.dw_clear", "#dw_filter_clear", function () {
                $("#dw_start_date").val(dwCurrentStartDate);
                $("#dw_end_date").val(dwCurrentEndDate);
            });

        // Date-wise modal: reset on close, redraw on open
        const dwModal = document.getElementById("date_wise_stock_view_modal");
        dwModal?.addEventListener("hidden.bs.modal", function () {
            if (dateWiseTable) { dateWiseTable.destroy(); dateWiseTable = null; }
            $("#dw_stock_totals_bar").hide();
            $("#dw_stock_date_badge").hide();
            $("#dw_opening_bar").hide();
            $("#date_wise_stock_status_table").show();
            // MW is still open behind DW — just redraw it
            if (dwOpenedFromMonthWise) {
                dwOpenedFromMonthWise = false;
                if (monthWiseTable) monthWiseTable.redraw(true);
            }
        });
        dwModal?.addEventListener("shown.bs.modal", function () {
            if (dateWiseTable) dateWiseTable.redraw(true);
        });

        // ============================================================
        //  PRINT / EXPORT HANDLERS  (all three screens)
        // ============================================================

        // ── Item-wise ──────────────────────────────────────────────
        $(document).on("click", "#export_print_btn", function (e) {
            e.preventDefault();
            printReport(stockPrintItemWiseUrl, {
                currentFilter: currentFilter,
                format: "print",
                orientation: "portrait"
            });
        });

        $(document).on("click", "#export_xlsx_item_wise_btn", function (e) {
            e.preventDefault();
            downloadExcel(stockExcelItemWiseUrl, { currentFilter: currentFilter });
        });

        // ── Month-wise ─────────────────────────────────────────────
        $(document).on("click", "#mw_stock_print_btn", function (e) {
            e.preventDefault();
            if (!mwCurrentItemId) return;
            printReport(stockPrintMonthWiseUrl, {
                currentFilter: { ...currentFilter, item_id: mwCurrentItemId },
                format: "print",
                orientation: "landscape"
            });
        });

        $(document).on("click", "#mw_stock_excel_btn", function (e) {
            e.preventDefault();
            if (!mwCurrentItemId) return;
            downloadExcel(stockExcelMonthWiseUrl, {
                currentFilter: { ...currentFilter, item_id: mwCurrentItemId }
            });
        });

        // ── Date-wise ──────────────────────────────────────────────
        $(document).on("click", "#export_print_date_wise_btn", function (e) {
            e.preventDefault();
            if (!dwCurrentItemId) return;
            printReport(stockPrintDateWiseUrl, {
                currentFilter: {
                    item_id   : dwCurrentItemId,
                    start_date: dwCurrentStartDate,
                    end_date  : dwCurrentEndDate
                },
                format: "print",
                orientation: "landscape"
            });
        });

        $(document).on("click", "#export_xlsx_date_wise_btn", function (e) {
            e.preventDefault();
            if (!dwCurrentItemId) return;
            downloadExcel(stockExcelDateWiseUrl, {
                currentFilter: {
                    item_id   : dwCurrentItemId,
                    start_date: dwCurrentStartDate,
                    end_date  : dwCurrentEndDate
                }
            });
        });
    });

}());
