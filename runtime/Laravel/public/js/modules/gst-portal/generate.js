// =============================================================
// GST Portal — E-WayBill / E-Invoice Generate Listing
// =============================================================

"use strict";

// ── State ───────────────────────────────────────────────────
let genTable          = null;
let tableFilter       = {};
let genFilteredTotal  = 0;
let genGrandTotal     = 0;

// selectedRows: { invoiceId: { type: 'both'|'ewb'|'irn', data: rowObj } }
const selectedRows = {};

let _allPagesSelected = false;

const FILTER_KEY    = "gst_gen_filter";
const PAGE_SIZE_KEY = "gst_gen_page_size";
const PAGE_NUM_KEY  = "gst_gen_page_num";

let _initialPageRestored = false;

// ── Utilities ────────────────────────────────────────────────

function fmtDate(val) {
    if (!val) return "—";
    return formatDateToDMY(String(val).substring(0, 10));
}

function initSelect2(selector, placeholder) {
    $(selector).select2({ theme: "bootstrap-5", allowClear: true, placeholder, width: "100%" });
}

// ── Selection Management ─────────────────────────────────────

function isPending(row) {
    return !row.ewb_id && !row.ei_id;
}

function defaultType(row) {
    if (!row.ewb_id && !row.ei_id) return "both";
    if (!row.ewb_id) return "ewb";
    if (!row.ei_id)  return "irn";
    return null;
}

function selectRow(id, type, data) {
    selectedRows[id] = { type, data };
    updateFooter();
}

function deselectRow(id) {
    delete selectedRows[id];
    _allPagesSelected = false;
    updateFooter();
}

function updateFooter() {
    const count        = Object.keys(selectedRows).length;
    const totalPending = genTable ? genTable.getRows().filter(r => isPending(r.getData())).length : 0;

    const $lbl = $("#sel_label").text(`${count} invoice${count !== 1 ? "s" : ""} selected`);
    const el   = $lbl[0];
    if (el) {
        if (count > 0) {
            el.style.setProperty("background-color", "#fff9c4", "important");
            el.style.setProperty("color",            "#000000", "important");
            el.style.setProperty("font-weight",      "700",     "important");
            el.style.setProperty("padding",          "2px 10px");
            el.style.setProperty("border-radius",    "4px");
        } else {
            el.removeAttribute("style");
        }
    }
    $("#btn_generate").prop("disabled", count === 0);

    const cb = document.getElementById("select_all_cb");
    if (cb) {
        if (count === 0) {
            cb.checked       = false;
            cb.indeterminate = false;
        } else if (totalPending > 0 && count >= totalPending) {
            cb.checked       = true;
            cb.indeterminate = false;
        } else {
            cb.checked       = false;
            cb.indeterminate = true;
        }
    }

    // ── "Select All Pages" tag ──
    const curPageRows   = genTable ? genTable.getRows().length : 0;
    const allCurPageSel = totalPending > 0 && count >= totalPending;
    const hasMorePages  = genFilteredTotal > curPageRows;
    let $tag = $("#sel_all_tag");

    if (allCurPageSel && hasMorePages) {
        if (_allPagesSelected) {
            $tag.html(`<span class="badge rounded-pill fw-semibold px-3 py-1"
                style="background:#d1fae5;color:#065f46;font-size:.78rem">
                &#10003; All ${count} invoices selected
                &nbsp;<a href="#" id="sel_clear_all" class="text-danger" style="font-size:.75rem">Clear</a>
            </span>`);
        } else {
            $tag.html(`<a href="#" id="sel_all_link"
                class="badge rounded-pill fw-semibold px-3 py-1 text-decoration-none"
                style="background:#fef9c3;color:#713f12;border:1px solid #fde68a;font-size:.78rem">
                &#8853; Select all ${genFilteredTotal} invoices
            </a>`);
        }
        $tag.show();
    } else {
        $tag.hide();
        if (count === 0) _allPagesSelected = false;
    }
}

function refreshCellForId(id) {
    if (!genTable) return;
    const rows = genTable.getRows();
    rows.forEach(r => {
        if (String(r.getData().id) === String(id)) {
            r.reformat();
        }
    });
}

// ── Table Columns ────────────────────────────────────────────

function getColumns() {
    return [
        {
            title: "<input type='checkbox' id='select_all_cb' title='Select / deselect all pending'>",
            field: "_cb",
            width: 60,
            headerSort: false,
            hozAlign: "center",
            headerHozAlign: "center",
            resizable: false,
            formatter: cell => {
                const row = cell.getRow().getData();
                if (!isPending(row)) return "";
                const checked = !!selectedRows[row.id];
                return `<div style="display:flex;align-items:center;justify-content:center;height:100%">
                    <input type="checkbox" class="row-cb form-check-input m-0"
                    data-id="${row.id}" ${checked ? "checked" : ""} style="cursor:pointer;border-color:#6c757d;border-width:1.5px">
                    </div>`;
            },
        },
        {
            title: "Sales-Inv. No.", field: "reference_number",
            width: 120,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
        },
        {
            title: "GRN No.", field: "grn_number",
            width: 95,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: c => c.getValue() || "—",
        },
        {
            title: "Sales-Inv. Date", field: "invoice_date",
            width: 118,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: c => fmtDate(c.getValue()),
        },
        {
            title: "EWB No.", field: "ewb_no",
            width: 130,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: c => {
                const v = c.getValue();
                return v
                    ? `<span class="num-ewb">${v}</span>`
                    : `<span class="text-muted small">—</span>`;
            },
        },
        {
            title: "IRN Ack No", field: "ack_no",
            width: 140,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: c => {
                const v = c.getValue();
                return v
                    ? `<span class="num-irn">${v}</span>`
                    : `<span class="text-muted small">—</span>`;
            },
        },
        {
            title: "Customer", field: "customer_name",
            minWidth: 170,
            headerSort: false, resizable: true,
            formatter: c => c.getValue() || "—",
        },
        {
            title: "Last Inv. date", field: "last_invoice_date",
            width: 118,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: c => fmtDate(c.getValue()),
        },
        {
            title: "Vehicle No.", field: "vehicle_number",
            width: 140,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: c => c.getValue() || "—",
        },
        {
            title: "Product.", field: "product_name",
            minWidth: 120,
            headerSort: false, resizable: true,
            formatter: c => c.getValue() || "—",
        },
        {
            title: "Generate",
            field: "_gen",
            width: 180,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: false,
            formatter: cell => {
                const row     = cell.getRow().getData();
                const hasEwb  = !!row.ewb_id;
                const hasIrn  = !!row.ei_id;
                const sel     = selectedRows[row.id];
                const curType = sel?.type || defaultType(row);

                // Scenario 1: Both pending → Generate IRN/E-way Bill
                if (!hasEwb && !hasIrn) {
                    return `<button class="type-btn both-btn ${curType === "both" ? "active" : ""}"
                        data-id="${row.id}" data-type="both" title="Generate IRN + E-way Bill">
                        Generate IRN/E-way Bill</button>`;
                }

                // Scenario 2: EWB done, IRN pending → IRN Only
                if (hasEwb && !hasIrn) {
                    return `<button class="type-btn irn-btn ${curType === "irn" ? "active" : ""}"
                        data-id="${row.id}" data-type="irn" title="Generate IRN Only">
                        IRN Only</button>`;
                }

                // Edge case: IRN done, EWB pending → EWB Only
                if (!hasEwb && hasIrn) {
                    return `<button class="type-btn ewb-btn ${curType === "ewb" ? "active" : ""}"
                        data-id="${row.id}" data-type="ewb" title="Generate E-Way Bill Only">
                        EWB Only</button>`;
                }

                // Scenario 3: Both generated → no generate button
                return `<span class="text-muted small">—</span>`;
            },
        },
        {
            title: "Actions",
            field: "_actions",
            width: 100,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, frozen: true, resizable: false,
            formatter: cell => {
                const row       = cell.getRow().getData();
                const ewbActive = !!(row.ewb_id && row.ewb_status === "active");
                const irnActive = !!(row.ei_id  && row.irn_status === "active");

                let html = `<div class="d-flex flex-wrap gap-1 justify-content-center align-items-center py-1">`;

                // Sales Bill print (always)
                // html += `<button class="act-btn act-btn-green btn-print-sales"
                //     data-id="${row.id}" title="Print Sales Bill">
                //     <i class="fa-solid fa-print"></i> Sales-Bill</button>`;

                // EWB Print (if EWB exists)
                if (row.ewb_no) {
                    html += `<button class="act-btn act-btn-teal btn-print-ewb"
                        data-ewbno="${row.ewb_no}" title="Print E-Way Bill">
                        <i class="fa-solid fa-print"></i> EWB-Print</button>`;
                }

                // Cancel EWB first (Scenario 3 — must cancel EWB before IRN)
                // if (ewbActive) {
                //     html += `<button class="act-btn act-btn-red btn-cancel-ewb"
                //         data-ewbno="${row.ewb_no}" data-bill="${row.reference_number}" title="Cancel E-Way Bill">
                //         <i class="fa-solid fa-ban"></i> Cancel EWB</button>`;
                // }

                // Cancel IRN only when EWB is no longer active (EWB cancelled or never existed)
                // if (irnActive && !ewbActive) {
                //     html += `<button class="act-btn act-btn-red btn-cancel-irn"
                //         data-irn="${row.irn}" data-bill="${row.reference_number}" title="Cancel IRN">
                //         <i class="fa-solid fa-ban"></i> Cancel IRN</button>`;
                // }

                html += `</div>`;
                return html;
            },
        },
    ];
}

// ── Build Tabulator table ────────────────────────────────────

function buildTable() {
    genTable = new Tabulator("#gen_table", {
        layout: "fitColumns",
        vertAlign: "middle",
        height: "550px",
        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-file-invoice fa-2x mb-2 opacity-50"></i>
            <p>No records found</p></div>`,
        pagination: true,
        paginationMode: "remote",
        paginationSize: parseInt(localStorage.getItem(PAGE_SIZE_KEY)) || 50,
        paginationSizeSelector: [20, 50, 100, 200,300,400,500,600,700],
        paginationDataSent:     { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },
        ajaxURL: GST_GEN.salesBillsJsonUrl,
        ajaxParams: () => tableFilter,
        ajaxURLGenerator: (url, cfg, params) => {
            const qp = new URLSearchParams({ ...tableFilter, page: params.page, size: params.size });
            return `${url}?${qp}`;
        },
        ajaxResponse(url, params, response) {
            genFilteredTotal = response.total || 0;
            genGrandTotal    = response.grand_total || 0;
            return response;
        },
        paginationCounter: function(pageSize, currentRow, _currentPage, totalRows, _totalPages) {
            const total = genFilteredTotal || totalRows;
            if (!total) return "";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (genGrandTotal > 0 && genGrandTotal !== total) {
                text += ` (filtered from ${genGrandTotal} total entries)`;
            }
            return text;
        },
        columns: getColumns(),
        footerElement: `<span id="sel_all_tag" style="display:none;margin-right:auto;line-height:1"></span>`,
    });

    genTable.on("pageSizeChanged", size => localStorage.setItem(PAGE_SIZE_KEY, size));

    genTable.on("pageLoaded", page => localStorage.setItem(PAGE_NUM_KEY, page));

    genTable.on("dataLoaded", () => {
        _allPagesSelected = false;
        updateFooter();

        if (!_initialPageRestored) {
            _initialPageRestored = true;
            const savedPage = parseInt(localStorage.getItem(PAGE_NUM_KEY)) || 1;
            if (savedPage > 1) genTable.setPage(savedPage);
        }
    });
}

// ── Filter Logic ─────────────────────────────────────────────

function applyFilter() {
    tableFilter = {};

    const date = $("#f_date").val().trim();
    if (date) {
        // Convert DD/MM/YYYY → DD-MM-YYYY for backend
        tableFilter.date = date.replace(/\//g, "-");
    }
    const invNo = $("#f_inv_no").val().trim();
    if (invNo) tableFilter.inv_no = invNo;

    // const grn = $("#f_grn").val().trim();
    // if (grn) tableFilter.grn = grn;

    const acc = $("#f_account").val();
    if (acc) tableFilter.account_id = acc;

    const item = $("#f_item").val();
    if (item) tableFilter.item_id = item;

    const status = $("#f_status").val();
    if (status) tableFilter.status = status;

    localStorage.setItem(FILTER_KEY, JSON.stringify(tableFilter));
    genTable && genTable.setData();
}

function clearFilter() {
    // $("#f_date, #f_inv_no, #f_grn").val("");
    $("#f_account").val("").trigger("change");
    $("#f_item").val("").trigger("change");
    $("#f_status").val("");
    tableFilter = {};
    localStorage.removeItem(FILTER_KEY);
    genTable && genTable.setData();
}

// ── Checkbox & Type-Toggle Interactions ──────────────────────

// Row checkbox clicked
$(document).on("change", ".row-cb", function () {
    const id  = $(this).data("id");
    const row = genTable?.getRows().find(r => String(r.getData().id) === String(id));
    if (!row) return;
    const rowData = row.getData();

    if (this.checked) {
        const type = selectedRows[id]?.type || defaultType(rowData);
        selectRow(id, type, rowData);
    } else {
        deselectRow(id);
    }
    row.reformat();
});

// Header select-all checkbox
$(document).on("change", "#select_all_cb", function () {
    const checked = this.checked;
    genTable?.getRows().forEach(r => {
        const rowData = r.getData();
        if (!isPending(rowData)) return;
        if (checked) {
            const type = selectedRows[rowData.id]?.type || defaultType(rowData);
            selectRow(rowData.id, type, rowData);
        } else {
            deselectRow(rowData.id);
        }
        r.reformat();
    });
});

// "Select All Pages" tag — select all
$(document).on("click", "#sel_all_link", function (e) {
    e.preventDefault();
    selectAllPages();
});

// "Clear All" link inside tag
$(document).on("click", "#sel_clear_all", function (e) {
    e.preventDefault();
    Object.keys(selectedRows).forEach(id => delete selectedRows[id]);
    _allPagesSelected = false;
    genTable && genTable.getRows().forEach(r => r.reformat());
    updateFooter();
});

function selectAllPages() {
    const total = genFilteredTotal || 9999;
    $.ajax({
        url: GST_GEN.salesBillsJsonUrl,
        method: "GET",
        data: { ...tableFilter, page: 1, size: total },
        beforeSend: () => showToast("info", "Fetching all invoices…"),
        success: res => {
            const rows = res.data || [];
            rows.forEach(row => {
                if (!isPending(row)) return;
                const type = selectedRows[row.id]?.type || defaultType(row);
                selectedRows[row.id] = { type, data: row };
            });
            _allPagesSelected = true;
            genTable && genTable.getRows().forEach(r => r.reformat());
            updateFooter();
            showToast("success", `All ${Object.keys(selectedRows).length} invoices selected.`);
        },
        error: () => showToast("error", "Failed to fetch all invoices."),
    });
}

// Type toggle button clicked — triggers immediate single-row generation
$(document).on("click", ".type-btn", function (e) {
    e.stopPropagation();
    const id   = $(this).data("id");
    const type = $(this).data("type");
    const row  = genTable?.getRows().find(r => String(r.getData().id) === String(id));
    if (!row) return;

    const typeLabel = { both: "IRN + E-way Bill", irn: "IRN Only", ewb: "E-Way Bill Only" };
    const rowData   = row.getData();

    Swal.fire({
        title: "Confirm Generation",
        html: `Generate <strong>${typeLabel[type] || type}</strong> for invoice <strong>${rowData.reference_number || id}</strong>?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Generate",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#0d6efd",
    }).then(result => {
        if (!result.isConfirmed) return;
        doGenerateItems([{ id: parseInt(id), type }]);
    });
});

// ── Generate Button ──────────────────────────────────────────

$("#btn_generate").on("click", function () {
    const count = Object.keys(selectedRows).length;
    if (!count) return;

    Swal.fire({
        title: "Confirm Generation",
        text: `Generate E-Waybill / E-Invoice for ${count} invoice${count !== 1 ? "s" : ""}?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, Generate",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#0d6efd",
    }).then(result => {
        if (!result.isConfirmed) return;
        doGenerate();
    });
});

function doGenerate() {
    const items = Object.entries(selectedRows).map(([id, s]) => ({
        id: parseInt(id),
        type: s.type,
    }));
    doGenerateItems(items);
}

function doGenerateItems(items) {
    Swal.fire({
        title: "Generating...",
        text: "Please wait while we process your invoices.",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading(),
    });

    $.ajax({
        url: GST_GEN.bulkGenerateUrl,
        method: "POST",
        headers: { "X-CSRF-TOKEN": GST_GEN.csrfToken },
        contentType: "application/json",
        data: JSON.stringify({ items }),
        success: res => {
            Swal.close();
            showResults(res.data?.results || []);
            (res.data?.results || []).forEach(r => {
                if (r.success) deselectRow(r.id);
            });
            genTable && genTable.setData();
        },
        error: xhr => {
            Swal.fire("Error", xhr.responseJSON?.message || "Generation failed.", "error");
        },
    });
}

// ── Results Modal ─────────────────────────────────────────────

function showResults(results) {
    const total     = results.length;
    const succeeded = results.filter(r => r.success).length;
    const failed    = total - succeeded;

    const typeLabel = { both: "IRN + EWB", irn: "IRN Only", ewb: "EWB Only" };

    // Summary banner
    let html = `
    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom bg-light">
        <span class="badge bg-success px-3 py-2 text-white" style="font-size:.8rem">
            &#10004; ${succeeded} Succeeded
        </span>
        <span class="badge text-white ${failed > 0 ? "bg-danger" : "bg-secondary"} px-3 py-2" style="font-size:.8rem">
            &#10008; ${failed} Failed
        </span>
        <span class="ms-auto text-muted small">${total} total processed</span>
    </div>
    <div class="table-responsive" style="max-height:420px;overflow-y:auto">
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light sticky-top">
            <tr>
                <th style="width:36px">#</th>
                <th>Bill No.</th>
                <th>Type</th>
                <th>IRN / Ack No.</th>
                <th>EWB No.</th>
                <th style="width:72px">Status</th>
                <th>Error / Note</th>
            </tr>
        </thead><tbody>`;

    results.forEach((r, idx) => {
        const irnTxt = r.irn_skipped
            ? `<span class="text-muted">${r.ack_no || r.irn || "—"} <em>(already existed)</em></span>`
            : (r.ack_no || r.irn || "—");
        const ewbTxt = r.ewb_skipped
            ? `<span class="text-muted">${r.ewb_no || "—"} <em>(already existed)</em></span>`
            : (r.ewb_no || "—");

        const hasIrn = r.irn !== undefined || r.ack_no !== undefined;
        const hasEwb = r.ewb_no !== undefined;
        const lbl    = typeLabel[r.type] || "—";
        const errMsg = !r.success ? (r.message || "Unknown error") : "";

        const rowCls = r.success ? "" : "table-danger";
        const stsCls = r.success ? "text-success fw-semibold" : "text-danger fw-semibold";
        const stsIco = r.success ? "&#10004;" : "&#10008;";

        // Split pipe-separated API validation errors into a bullet list
        let errHtml = "—";
        if (!r.success && errMsg) {
            const parts = errMsg.split("|").map(s => s.trim()).filter(Boolean);
            if (parts.length > 1) {
                errHtml = `<ul class="mb-0 ps-3" style="font-size:.72rem">` +
                    parts.map(p => `<li class="text-danger">${p}</li>`).join("") +
                    `</ul>`;
            } else {
                errHtml = `<span class="text-danger" style="font-size:.72rem">${errMsg}</span>`;
            }
            
            // If duplicate IRN error is detected, show Re-fetch button
            const lowerMsg = errMsg.toLowerCase();
            if (lowerMsg.includes('duplicate') && lowerMsg.includes('irn') || lowerMsg.includes('2150')) {
                errHtml += `<div class="mt-1"><button class="btn btn-sm btn-outline-primary btn-refetch-irn py-0 px-2" style="font-size:0.7rem;" data-id="${r.id}"><i class="fa-solid fa-rotate-right me-1"></i>Re-fetch IRN</button></div>`;
            }
        } else if (r.irn_skipped || r.ewb_skipped) {
            errHtml = `<span class="text-muted" style="font-size:.72rem">Already generated</span>`;
        }

        html += `<tr class="${rowCls}">
            <td class="text-muted text-center">${idx + 1}</td>
            <td class="fw-semibold">${r.ref || r.id}</td>
            <td><small class="text-secondary">${lbl}</small></td>
            <td><small>${hasIrn ? irnTxt : "—"}</small></td>
            <td><small>${hasEwb ? ewbTxt : "—"}</small></td>
            <td class="${stsCls} text-center"><small>${stsIco} ${r.success ? "OK" : "Fail"}</small></td>
            <td>${errHtml}</td>
        </tr>`;
    });

    html += `</tbody></table></div>`;
    $("#results_body").html(html);
    $("#resultsModal").modal("show");
}

// Redraw table when results modal closes so changes are visible immediately
$("#resultsModal").on("hidden.bs.modal", function () {
    genTable && genTable.setData();
});

// ── Print Handlers ───────────────────────────────────────────

$(document).on("click", ".btn-print-sales", function () {
    const id = $(this).data("id");
    window.open(`${GST_GEN.salesBillPrintUrl}${id}`, "_blank");
});

$(document).on("click", ".btn-print-ewb", function () {
    const ewbNo = $(this).data("ewbno");
    window.open(GST_GEN.printEwbUrl + ewbNo, "_blank");
});

// ── Cancel EWB ───────────────────────────────────────────────

$(document).on("click", ".btn-cancel-ewb", function () {
    const ewbNo = $(this).data("ewbno");
    const bill  = $(this).data("bill");
    $("#cewb_no_val").val(ewbNo);
    $("#cewb_bill_label").text(`Invoice: ${bill}`);
    $("#cewb_reason").val("1");
    $("#cewb_remark_wrap").hide();
    $("#cewb_remark").val("");
    $("#cancelEwbModal").modal("show");
});

$("#cewb_reason").on("change", function () {
    $("#cewb_remark_wrap").toggle($(this).val() === "4");
});

$("#cewb_submit").on("click", function () {
    const ewbNo  = $("#cewb_no_val").val();
    const code   = $("#cewb_reason").val();
    const remark = $("#cewb_remark").val().trim();
    if (code === "4" && !remark) { showToast("warning", "Remark required for Other reason."); return; }

    $(".cewb-spinner").removeClass("d-none");
    $.ajax({
        url: GST_GEN.cancelEwbUrl,
        method: "POST",
        headers: { "X-CSRF-TOKEN": GST_GEN.csrfToken },
        contentType: "application/json",
        data: JSON.stringify({ ewbNo, cancelCode: code, cancelRmrk: remark }),
        success: res => {
            $(".cewb-spinner").addClass("d-none");
            $("#cancelEwbModal").modal("hide");
            showToast("success", res.message || "E-Way Bill cancelled.");
            genTable && genTable.setData();
        },
        error: xhr => {
            $(".cewb-spinner").addClass("d-none");
            showToast("error", xhr.responseJSON?.message || "Cancellation failed.");
        },
    });
});

// ── Cancel IRN ───────────────────────────────────────────────

$(document).on("click", ".btn-cancel-irn", function () {
    const irn  = $(this).data("irn");
    const bill = $(this).data("bill");
    $("#cirn_val").val(irn);
    $("#cirn_bill_label").text(`Invoice: ${bill}`);
    $("#cirn_reason").val("1");
    $("#cirn_remark_wrap").hide();
    $("#cirn_remark").val("");
    $("#cancelIrnModal").modal("show");
});

$("#cirn_reason").on("change", function () {
    $("#cirn_remark_wrap").toggle($(this).val() === "4");
});

$("#cirn_submit").on("click", function () {
    const irn    = $("#cirn_val").val();
    const code   = $("#cirn_reason").val();
    const remark = $("#cirn_remark").val().trim();
    if (code === "4" && !remark) { showToast("warning", "Remark required for Other reason."); return; }

    $(".cirn-spinner").removeClass("d-none");
    $.ajax({
        url: GST_GEN.cancelIrnUrl,
        method: "POST",
        headers: { "X-CSRF-TOKEN": GST_GEN.csrfToken },
        contentType: "application/json",
        data: JSON.stringify({ irn, cancelRsnCode: code, cancelRmrk: remark }),
        success: res => {
            $(".cirn-spinner").addClass("d-none");
            $("#cancelIrnModal").modal("hide");
            showToast("success", res.message || "IRN cancelled.");
            genTable && genTable.setData();
        },
        error: xhr => {
            $(".cirn-spinner").addClass("d-none");
            showToast("error", xhr.responseJSON?.message || "Cancellation failed.");
        },
    });
});


// ── Re-fetch IRN ──────────────────────────────────────────────

$(document).on("click", ".btn-refetch-irn", function () {
    const btn = $(this);
    const invoiceId = btn.data("id");

    btn.prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Refetching...');

    $.ajax({
        url: GST_GEN.refetchIrnUrl,
        method: "POST",
        headers: { "X-CSRF-TOKEN": GST_GEN.csrfToken },
        contentType: "application/json",
        data: JSON.stringify({ invoice_id: invoiceId }),
        success: res => {
            showToast("success", res.message || "IRN refetched successfully.");
            btn.closest("tr").removeClass("table-danger").addClass("table-success");
            btn.closest("td").html(`<span class="text-success"><i class="fa-solid fa-check"></i> Refetched</span>`);
            // GenTable will update when modal is closed due to the hidden.bs.modal listener
        },
        error: xhr => {
            showToast("error", xhr.responseJSON?.message || "Failed to refetch IRN.");
            btn.prop("disabled", false).html('<i class="fa-solid fa-rotate-right me-1"></i>Re-fetch IRN');
        },
    });
});

// ── Bootstrap ────────────────────────────────────────────────

$(function () {
    initSelect2(".sel2-account", "--Select Customer--");
    initSelect2(".sel2-item",    "--Select Product--");
    initSelect2("#f_status",  "--Select Status--");

    // Date input
    new DateInput("#f_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Restore saved filters
    try { tableFilter = JSON.parse(localStorage.getItem(FILTER_KEY)) || {}; } catch { tableFilter = {}; }

    // Restore filter UI from saved state
    if (tableFilter.date)       $("#f_date").val(tableFilter.date);
    if (tableFilter.inv_no)     $("#f_inv_no").val(tableFilter.inv_no);
    // if (tableFilter.grn)        $("#f_grn").val(tableFilter.grn);
    if (tableFilter.account_id) $("#f_account").val(tableFilter.account_id).trigger("change");
    if (tableFilter.item_id)    $("#f_item").val(tableFilter.item_id).trigger("change");
    if (tableFilter.status)     $("#f_status").val(tableFilter.status);

    buildTable();

    // Filter buttons
    $("#filter_apply").on("click", applyFilter);
    $("#filter_clear").on("click", clearFilter);
});
