// ============================================================
// LEDGER REPORT
// ============================================================

let table = null;
let currentFilter = {};

const FILTER_KEY = "ledger_filter";
const WIDTH_KEY  = "ledger_col_widths";

// ============================================================
// UTILS
// ============================================================
function getFieldValue(id) {
    const $el = $(`#${id}`);
    if (!$el.length) return "";
    if ($el.hasClass("select2-hidden-accessible")) return $el.val() || "";
    return ($el.val() || "").trim();
}

function setFieldValue(id, value) {
    const $el = $(`#${id}`);
    if (!$el.length) return;
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.val(value).trigger("change");
    } else {
        $el.val(value);
    }
}

function formatBalance(amount) {
    amount = parseFloat(amount || 0);
    if (amount === 0) return "0.00";
    const label = amount > 0 ? "Dr" : "Cr";
    return formatIndianNumber(Math.abs(amount)) + " " + label;
}

// ============================================================
// FILTER
// ============================================================
function validateDateRange() {
    const start = getFieldValue("start_date");
    const end   = getFieldValue("end_date");

    if (!isValidDateDMY(start) || !isValidDateDMY(end)) {
        showToast("error", "Start Date and End Date are required in DD-MM-YYYY format");
        return false;
    }

    const [sd, sm, sy] = start.split("-").map(Number);
    const [ed, em, ey] = end.split("-").map(Number);

    if (new Date(sy, sm - 1, sd) > new Date(ey, em - 1, ed)) {
        showToast("error", "End Date must be greater than Start Date");
        return false;
    }

    return true;
}

function buildCurrentFilter() {
    const f = {};

    const startRaw = getFieldValue("start_date");
    const endRaw   = getFieldValue("end_date");

    if (isValidDateDMY(startRaw)) f.start_date = formatDateToYMD(startRaw);
    if (isValidDateDMY(endRaw))   f.end_date   = formatDateToYMD(endRaw);

    const accountId     = getFieldValue("account_id");
    const voucherTypeId = getFieldValue("voucher_type_id");
    const ledgerBy      = getFieldValue("ledger_by");
    const narration     = getFieldValue("narration");
    const againstAccountId = getFieldValue("against_account_id");

    if (accountId)      f.account_id      = accountId;
    if (voucherTypeId)  f.voucher_type_id = voucherTypeId;
    if (ledgerBy)       f.ledger_by       = ledgerBy;
    if (narration !== "") f.narration     = narration;
    
    // Only apply against account if in single entry mode
    if (againstAccountId && ledgerBy === "single") {
        f.against_account_id = againstAccountId;
    }

    return f;
}

function applyFilter() {
    if (!validateDateRange()) return;

    currentFilter = buildCurrentFilter();

    localStorage.setItem(FILTER_KEY, JSON.stringify({
        start_date: getFieldValue("start_date"),
        end_date:   getFieldValue("end_date"),
        ledger_by:  getFieldValue("ledger_by"),
    }));

    if (table) {
        table.setData();
    }
}

function clearFilter() {
    setFieldValue("account_id", "");
    setFieldValue("voucher_type_id", "");
    setFieldValue("against_account_id", "");
    $("#start_date").val(formatDateToDMY(FINANCIAL_YEAR_START));
    $("#end_date").val(formatDateToDMY(FINANCIAL_YEAR_END));
    $("#ledger_by").val("single").trigger("change");
    $("#narration").val("0").trigger("change");

    localStorage.removeItem(WIDTH_KEY);
    currentFilter = {};

    $("#opening_balance").text("0.00");
    $("#closing_balance").text("0.00");

    if (table) table.clearData();
}

function restoreFilters() {
    const saved = JSON.parse(localStorage.getItem(FILTER_KEY) || "{}");
    if (saved.start_date) {
        $("#start_date").val(saved.start_date);
    } else {
        $("#start_date").val(formatDateToDMY(FINANCIAL_YEAR_START));
    }
    if (saved.end_date) {
        $("#end_date").val(saved.end_date);
    } else {
        $("#end_date").val(formatDateToDMY(FINANCIAL_YEAR_END));
    }
    if (saved.ledger_by) {
        $("#ledger_by").val(saved.ledger_by).trigger("change");
    }
}

// ============================================================
// COLUMN WIDTHS
// ============================================================
function saveColWidths() {
    const widths = {};
    table.getColumns().forEach(col => {
        const field = col.getField();
        if (field) widths[field] = col.getWidth();
    });
    localStorage.setItem(WIDTH_KEY, JSON.stringify(widths));
}

function getSavedWidths() {
    try { return JSON.parse(localStorage.getItem(WIDTH_KEY)) || {}; }
    catch { return {}; }
}

// ============================================================
// COLUMNS
// ============================================================
function getColumns(saved) {
    return [
        {
            title: "Date",
            field: "voucher_date",
            width: saved.voucher_date ?? 120,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            formatter: cell => {
                const d = cell.getValue();
                return d ? formatDateToDMY(d) : "";
            },
        },
        {
            title: "Voucher Type",
            field: "voucher_type",
            width: saved.voucher_type ?? 140,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            formatter: cell => {
                const v = cell.getValue();
                return v === "Narration" ? "" : (v || "");
            },
        },
        {
            title: "Voucher No. / Ref No.",
            field: "voucher_serial",
            width: saved.voucher_serial ?? 180,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            formatter: cell => {
                const data   = cell.getData();
                const serial = data.voucher_serial ?? "";
                const ref    = data.reference_number ?? "";
                if (!serial) return "";
                const display = ref ? `${serial} / ${ref}` : serial;
                
                const hasModal = data.voucher_id && modalSupportedTypeIds.includes(data.voucher_type_id);
                if (hasModal) {
                    return `<a href="javascript:void(0)" class="text-primary fw-bold text-decoration-none">${display}</a>`;
                }
                return display;
            },
            cellClick: function(e, cell) {
                const data = cell.getData();

                const hasModal = data.voucher_id && modalSupportedTypeIds.includes(data.voucher_type_id);
                if (hasModal) {
                    e.preventDefault();
                    
                    const voucherId = data.voucher_id;
                    const url = voucherModalUrl.replace(':id', voucherId);
                    
                    showLoader("Loading Voucher...");
                    $.ajax({
                        url: url,
                        type: "GET",
                        success: function (res) {
                            if (res.success && res.data && res.data.html && res.data.modal_id) {
                                $("#voucher_modal_container").html(res.data.html);
                                $(res.data.modal_id).modal('show');
                            } else {
                                showToast("error", res.message || "Failed to load modal HTML.");
                            }
                        },
                        error: function (xhr) {
                            showToast("error", 'Oops! Something went wrong. Try again later.');
                            console.error(xhr.responseText);
                        },
                        complete: function () {
                            hideLoader();
                        }
                    });
                }
            }
        },
        {
            title: "Particulars",
            field: "against_account_name",
            width: saved.against_account_name ?? 450,
            hozAlign: "left",
            headerSort: false,
            formatter: cell => {
                const data = cell.getData();
                if (data.voucher_type === "Narration") {
                    const narrationText = data.narration ? data.narration.replace(/\n/g, '<br>') : "";
                    return `<span style="color:#653818;font-style:italic">Narration: ${narrationText}</span>`;
                }
                return cell.getValue() || "";
            },
        },
        {
            title: "Debit",
            field: "debit",
            width: saved.debit ?? 160,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: cell => {
                const v = parseFloat(cell.getValue());
                return (!v || v === 0) ? "" : formatIndianNumber(v);
            },
            bottomCalc: values => values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0),
            bottomCalcFormatter: cell => {
                const v = cell.getValue() || 0;
                return `<div style="text-align:right;font-weight:600">${formatIndianNumber(v.toFixed(2))}</div>`;
            },
        },
        {
            title: "Credit",
            field: "credit",
            width: saved.credit ?? 160,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: cell => {
                const v = parseFloat(cell.getValue());
                return (!v || v === 0) ? "" : formatIndianNumber(v);
            },
            bottomCalc: values => values.reduce((sum, v) => sum + (parseFloat(v) || 0), 0),
            bottomCalcFormatter: cell => {
                const v = cell.getValue() || 0;
                return `<div style="text-align:right;font-weight:600">${formatIndianNumber(v.toFixed(2))}</div>`;
            },
        },
        {
            title: "Balance",
            field: "running_balance",
            width: saved.running_balance ?? 200,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: cell => {
                const v = cell.getValue();
                if (v === null || v === undefined || v === "") return "";
                const label = parseFloat(v) >= 0 ? " Dr" : " Cr";
                return formatIndianNumber(Math.abs(v)) + label;
            },
        },
    ];
}

// ============================================================
// TABLE
// ============================================================
function initTable(savedWidths) {
    table = new Tabulator("#ledger_table", {
        ajaxURL: ledgerListUrl,
        ajaxParams: () => currentFilter,
        ajaxConfig: "GET",
        ajaxResponse: (_url, _params, response) => {
            $("#opening_balance").text(formatBalance(response.opening_balance));
            $("#closing_balance").text(formatBalance(response.closing_balance));
            return response.data || [];
        },

        height: "calc(550px)",
        layout: "fitColumns",
        virtualDom: true,

        placeholder: `<div class="text-center py-5">
            <i class="fa-solid fa-file-invoice fa-3x text-muted mb-3 d-block"></i>
            <h4 class="text-muted">No records found</h4>
            <p class="text-muted small">Select an account and click Apply to load ledger data.</p>
        </div>`,

        pagination: false,
        columns: getColumns(savedWidths),
    });

    table.on("columnResized", saveColWidths);

    table.on("dataLoading", () => $("#scrollLoader").show());
    table.on("dataLoaded",  () => $("#scrollLoader").hide());
    table.on("dataLoadError", () => {
        $("#scrollLoader").hide();
        showToast("error", "Failed to load ledger data. Please try again.");
    });
}

// ============================================================
// SELECT2
// ============================================================
function bindSelect2() {
    $("#account_id").select2({
        theme: "bootstrap-5",
        placeholder: "Select Account...",
        allowClear: true,
        width: "100%",
        ajax: {
            url: masterRoutes.accounts,
            dataType: "json",
            delay: 250,
            data: params => ({ q: params.term || "" }),
            processResults: response => ({
                results: (response.data || []).map(item => ({
                    id: item.id,
                    text: item.name,
                })),
            }),
            cache: true,
        },
        minimumInputLength: 0,
    });

  $("#account_id").on("select2:open", function () {
        var searchInput = $(this)
            .data("select2")
            .$dropdown.find(".select2-search__field");

        searchInput.on("keydown", function (event) {
            if (event.which === 13) {
                let currentField = $("#account_id");
                currentField.select2("close");
                moveFocusToNextField(currentField);
            }
        });
    });

    $("#voucher_type_id").select2({
        theme: "bootstrap-5",
        placeholder: "All Voucher Types",
        allowClear: true,
        width: "100%",
        ajax: {
            url: masterRoutes.voucherTypes,
            dataType: "json",
            processResults: response => ({
                results: (response.data || []).map(item => ({
                    id: item.id,
                    text: item.name,
                })),
            }),
            cache: true,
        },
    });

    $("#ledger_by, #narration").select2({
        theme: "bootstrap-5",
        width: "100%",
        minimumResultsForSearch: Infinity,
    });

    $(document).on("select2:open", function (e) {
        const $sel    = $(e.target);
        const $search = $sel.data("select2").$dropdown.find(".select2-search__field");
        $search.off("keydown.select2Enter").on("keydown.select2Enter", function (ev) {
            if (ev.which === 13) {
                ev.preventDefault();
                $sel.select2("close");
            }
        });
    });

    $("#against_account_id").select2({
        theme: "bootstrap-5",
        placeholder: "All Accounts",
        allowClear: true,
        width: "100%",
        ajax: {
            url: masterRoutes.accounts,
            dataType: "json",
            delay: 250,
            data: params => ({ q: params.term || "" }),
            processResults: response => ({
                results: (response.data || []).map(item => ({
                    id: item.id,
                    text: item.name,
                })),
            }),
            cache: true,
        },
        minimumInputLength: 0,
    });

    $("#against_account_id").on("select2:open", function () {
        var searchInput = $(this)
            .data("select2")
            .$dropdown.find(".select2-search__field");

        searchInput.on("keydown", function (event) {
            if (event.which === 13) {
                let currentField = $("#against_account_id");
                currentField.select2("close");
                moveFocusToNextField(currentField);
            }
        });
    });

    // Toggle cross account filter visibility based on ledger mode
    $("#ledger_by").on("change", function () {
        if ($(this).val() === "single") {
            $("#against_account_div").show();
        } else {
            $("#against_account_div").hide();
            setFieldValue("against_account_id", "");
        }
    });

    // Trigger on load
    $("#ledger_by").trigger("change");
}

// ============================================================
// BOOTSTRAP
// ============================================================
$(function () {
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    bindSelect2();
    restoreFilters();

    const savedWidths = getSavedWidths();
    initTable(savedWidths);

    // Build initial filter from restored field values
    currentFilter = buildCurrentFilter();

    $("#filter_apply").on("click", applyFilter);
    $("#filter_clear").on("click", clearFilter);

    // Export / Print
    $(document).on("click", ".dropdown-item[data-type]", function () {
        const type   = $(this).data("type");
        const route  = $(this).data("route");
        const format = $(this).data("format");
        const safeFilter = Object.keys(currentFilter).length ? currentFilter : {};
        const accountId  = $("#account_id").val();
        if(!accountId){
            swal.fire({
                title: "Please Select Account",
                icon: "info",
                showCancelButton: false,
                confirmButtonColor: "#5cb85c",
                confirmButtonText: "Ok",
                allowOutsideClick: false,
                allowEscapeKey: false,
            })
            return false;
        }

        if (type === "print" && typeof printReport === "function") printReport(route, { format, currentFilter: safeFilter });
        if (type === "excel"  && typeof downloadExcel === "function") downloadExcel(route, { currentFilter: safeFilter });
    });

    // Auto-apply on page load if dates are valid
    setTimeout(() => {
        if (currentFilter.start_date && currentFilter.end_date) {
            applyFilter();
        }
    }, 100);
});
