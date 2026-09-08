// =============================================================
// GST Portal — E-Invoice & E-Way Bill Tabulator listing
// =============================================================

let einvTable  = null;
let ewbTable   = null;
let einvFilter = {};
let ewbFilter  = {};
let einvGrandTotal    = 0;
let einvFilteredTotal = 0;
let ewbGrandTotal     = 0;
let ewbFilteredTotal  = 0;

const EINV_FILTER_KEY = "gst_portal_einv_filter";
const EWB_FILTER_KEY  = "gst_portal_ewb_filter";
const EINV_WIDTH_KEY  = "gst_portal_einv_widths";
const EWB_WIDTH_KEY   = "gst_portal_ewb_widths";

// =============================================================
// UTILITY
// =============================================================

function fmtDate(val) {
    if (!val) return "—";
    // YYYY-MM-DD → DD-MM-YYYY
    const m = String(val).match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? `${m[3]}-${m[2]}-${m[1]}` : val;
}

function fmtAmount(val) {
    if (val == null || val === "") return "—";
    return Number(val).toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function badge(label, cls) {
    return `<span class="badge gst-badge-${cls} rounded-2 px-2 py-1">${label}</span>`;
}

function einvStatusBadge(row) {
    if (!row.e_invoice_id) return badge("Pending", "pending");
    if (row.e_invoice_status === "cancelled") return badge("Cancelled", "cancelled");
    return badge("Generated", "generated");
}

function ewbStatusBadge(row) {
    if (!row.e_way_bill_id) return badge("Pending", "pending");
    if (row.e_way_bill_status === "cancelled") return badge("Cancelled", "cancelled");
    return badge("Generated", "generated");
}

function normalizeDate(d) {
    if (!d || d === "__-__-____") return "";
    const [dd, mm, yyyy] = d.split("-");
    return yyyy?.length === 4 ? `${dd}-${mm}-${yyyy}` : "";
}

function getSavedWidths(key) {
    try { return JSON.parse(localStorage.getItem(key)) || {}; } catch { return {}; }
}

function saveWidths(table, key) {
    const w = {};
    table.getColumns().forEach(c => { if (c.getField()) w[c.getField()] = c.getWidth(); });
    localStorage.setItem(key, JSON.stringify(w));
}

function initSelect2(selector, placeholder) {
    $(selector).select2({ theme: "bootstrap-5", allowClear: true, placeholder, width: "100%" });
}

// Copy-to-clipboard helper
function copyText(text) {
    navigator.clipboard?.writeText(text).then(() => {
        showToast("success", "Copied to clipboard");
    });
}

// Spinner helpers
function showSpinner(cls) { $(`.${cls}`).removeClass("d-none"); }
function hideSpinner(cls) { $(`.${cls}`).addClass("d-none"); }

// =============================================================
// AJAX helper
// =============================================================
function ajaxPost(url, data, onSuccess, onError) {
    $.ajax({
        url,
        method: "POST",
        headers: { "X-CSRF-TOKEN": GST_PORTAL.csrfToken },
        contentType: "application/json",
        data: JSON.stringify(data),
        success: res => onSuccess(res),
        error: xhr => {
            const msg = xhr.responseJSON?.message || "Something went wrong.";
            showToast("error", msg);
            if (onError) onError(xhr);
        },
    });
}

// =============================================================
// E-INVOICE TABLE
// =============================================================

function getEinvColumns(sw) {
    return [
        {
            title: "#", field: "id",
            width: sw.id ?? 50,
            hozAlign: "center", headerHozAlign: "center",
            formatter: "rownum", headerSort: false, resizable: false,
        },
        {
            title: "Bill No", field: "reference_number",
            width: sw.reference_number ?? 120,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
        },
        {
            title: "Bill Date", field: "invoice_date",
            width: sw.invoice_date ?? 110,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => fmtDate(cell.getValue()),
        },
        {
            title: "Customer", field: "account_name",
            minWidth: 160, headerSort: false, resizable: true,
            formatter: cell => cell.getValue() || "—",
        },
        {
            title: "Amount", field: "grand_total",
            width: sw.grand_total ?? 120,
            hozAlign: "right", headerHozAlign: "right",
            headerSort: false, resizable: true,
            formatter: cell => `<span class="text-end d-block">${fmtAmount(cell.getValue())}</span>`,
        },
        {
            title: "Status", field: "e_invoice_status",
            width: sw.e_invoice_status ?? 110,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => einvStatusBadge(cell.getRow().getData()),
        },
        {
            title: "IRN", field: "irn",
            minWidth: 180, headerSort: false, resizable: true,
            formatter: cell => {
                const irn = cell.getValue();
                if (!irn) return `<span class="text-muted">—</span>`;
                const short = irn.substring(0, 16) + "…";
                return `<span class="irn-cell" title="${irn}">${short}</span>
                        <span class="ms-1 erp-btn-icon copy-irn text-muted" data-irn="${irn}" title="Copy IRN">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                        </span>`;
            },
        },
        {
            title: "Ack No", field: "ack_no",
            width: sw.ack_no ?? 130,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => cell.getValue() || "—",
        },
        {
            title: "Ack Date", field: "ack_dt",
            width: sw.ack_dt ?? 110,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => cell.getValue() || "—",
        },
        {
            title: "Actions", field: "actions",
            width: sw.actions ?? 130,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, frozen: true, resizable: false,
            formatter: cell => {
                const row = cell.getRow().getData();
                const hasIrn     = !!row.e_invoice_id;
                const isActive   = row.e_invoice_status === "active";
                const isCancelled = row.e_invoice_status === "cancelled";

                let html = `<div class="d-flex gap-2 align-items-center justify-content-center h-100">`;

                if (!hasIrn) {
                    // Generate IRN
                    html += `<span class="erp-btn-icon text-primary gen-irn"
                                data-id="${row.id}"
                                data-bill="${row.reference_number}"
                                title="Generate IRN">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                        </svg>
                        <span class="ms-1 small">Gen IRN</span>
                    </span>`;
                }

                if (isActive) {
                    // Cancel IRN
                    html += `<span class="erp-btn-icon text-danger cancel-irn"
                                data-irn="${row.irn}"
                                data-bill="${row.reference_number}"
                                title="Cancel IRN">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                        </svg>
                        <span class="ms-1 small">Cancel</span>
                    </span>`;
                }

                if (hasIrn) {
                    // View Details
                    html += `<span class="erp-btn-icon text-info view-irn"
                                data-irn="${row.irn}"
                                title="View IRN Details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
                            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
                        </svg>
                        <span class="ms-1 small">Details</span>
                    </span>`;
                }

                html += `</div>`;
                return html;
            },
        },
    ];
}

function buildEinvTable() {
    const sw = getSavedWidths(EINV_WIDTH_KEY);
    einvTable = new Tabulator("#einvoice_table", {
        layout: "fitColumns",
        height: "calc(100vh - 340px)",
        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-file-invoice fa-2x mb-2 opacity-50"></i>
            <p>No records found</p></div>`,
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [20, 50, 100, 200],
        paginationDataSent:     { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },
        ajaxURL: GST_PORTAL.eInvoiceListUrl,
        ajaxParams: () => einvFilter,
        ajaxURLGenerator: (url, cfg, params) => {
            const qp = new URLSearchParams({ ...einvFilter, page: params.page, size: params.size });
            return `${url}?${qp}`;
        },
        ajaxResponse(url, params, response) {
            einvFilteredTotal = response.total || 0;
            einvGrandTotal    = response.grand_total || 0;
            return response;
        },
        paginationCounter: function(pageSize, currentRow, _currentPage, totalRows, _totalPages) {
            const total = einvFilteredTotal || totalRows;
            if (!total) return "";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (einvGrandTotal > 0 && einvGrandTotal !== total) {
                text += ` (filtered from ${einvGrandTotal} total entries)`;
            }
            return text;
        },
        columns: getEinvColumns(sw),
    });
    einvTable.on("columnResized", () => saveWidths(einvTable, EINV_WIDTH_KEY));
}

// =============================================================
// E-WAY BILL TABLE
// =============================================================

function getEwbColumns(sw) {
    return [
        {
            title: "#", field: "id",
            width: sw.id ?? 50,
            hozAlign: "center", headerHozAlign: "center",
            formatter: "rownum", headerSort: false, resizable: false,
        },
        {
            title: "Bill No", field: "reference_number",
            width: sw.reference_number ?? 120,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
        },
        {
            title: "Bill Date", field: "invoice_date",
            width: sw.invoice_date ?? 110,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => fmtDate(cell.getValue()),
        },
        {
            title: "Customer", field: "account_name",
            minWidth: 160, headerSort: false, resizable: true,
            formatter: cell => cell.getValue() || "—",
        },
        {
            title: "Amount", field: "grand_total",
            width: sw.grand_total ?? 120,
            hozAlign: "right", headerHozAlign: "right",
            headerSort: false, resizable: true,
            formatter: cell => `<span class="text-end d-block">${fmtAmount(cell.getValue())}</span>`,
        },
        {
            title: "Status", field: "e_way_bill_status",
            width: sw.e_way_bill_status ?? 110,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => ewbStatusBadge(cell.getRow().getData()),
        },
        {
            title: "EWB No", field: "ewb_no",
            width: sw.ewb_no ?? 130,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => cell.getValue() || "—",
        },
        {
            title: "Valid Upto", field: "valid_upto",
            width: sw.valid_upto ?? 130,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => {
                const v = cell.getValue();
                if (!v) return "—";
                // Highlight if expired
                const isExpired = new Date(v) < new Date();
                return isExpired
                    ? `<span class="text-danger fw-semibold">${v}</span>`
                    : `<span class="text-success">${v}</span>`;
            },
        },
        {
            title: "Vehicle No", field: "vehicle_no",
            width: sw.vehicle_no ?? 110,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, resizable: true,
            formatter: cell => cell.getValue() || "—",
        },
        {
            title: "Actions", field: "actions",
            width: sw.actions ?? 150,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, frozen: true, resizable: false,
            formatter: cell => {
                const row = cell.getRow().getData();
                const hasEwb      = !!row.e_way_bill_id;
                const isActive    = row.e_way_bill_status === "active";
                const isCancelled = row.e_way_bill_status === "cancelled";

                let html = `<div class="d-flex gap-2 align-items-center justify-content-center h-100">`;

                if (!hasEwb || isCancelled) {
                    // Generate EWB
                    html += `<span class="erp-btn-icon text-success gen-ewb"
                                data-id="${row.id}"
                                data-bill="${row.reference_number}"
                                data-einv="${row.linked_e_invoice_id || ''}"
                                title="Generate E-Way Bill">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                        </svg>
                        <span class="ms-1 small">${isCancelled ? "Regen" : "Gen EWB"}</span>
                    </span>`;
                }

                if (isActive) {
                    // Cancel EWB
                    html += `<span class="erp-btn-icon text-danger cancel-ewb"
                                data-ewbno="${row.ewb_no}"
                                data-bill="${row.reference_number}"
                                title="Cancel E-Way Bill">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M18 6l-12 12"/><path d="M6 6l12 12"/>
                        </svg>
                        <span class="ms-1 small">Cancel</span>
                    </span>`;
                }

                html += `</div>`;
                return html;
            },
        },
    ];
}

function buildEwbTable() {
    const sw = getSavedWidths(EWB_WIDTH_KEY);
    ewbTable = new Tabulator("#ewaybill_table", {
        layout: "fitColumns",
        height: "calc(100vh - 340px)",
        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-truck fa-2x mb-2 opacity-50"></i>
            <p>No records found</p></div>`,
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [20, 50, 100, 200],
        paginationDataSent:     { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },
        ajaxURL: GST_PORTAL.eWayBillListUrl,
        ajaxParams: () => ewbFilter,
        ajaxURLGenerator: (url, cfg, params) => {
            const qp = new URLSearchParams({ ...ewbFilter, page: params.page, size: params.size });
            return `${url}?${qp}`;
        },
        ajaxResponse(url, params, response) {
            ewbFilteredTotal = response.total || 0;
            ewbGrandTotal    = response.grand_total || 0;
            return response;
        },
        paginationCounter: function(pageSize, currentRow, _currentPage, totalRows, _totalPages) {
            const total = ewbFilteredTotal || totalRows;
            if (!total) return "";
            const start = currentRow;
            const end   = Math.min(currentRow + pageSize - 1, total);
            let text = `Showing ${start} to ${end} of ${total} entries`;
            if (ewbGrandTotal > 0 && ewbGrandTotal !== total) {
                text += ` (filtered from ${ewbGrandTotal} total entries)`;
            }
            return text;
        },
        columns: getEwbColumns(sw),
    });
    ewbTable.on("columnResized", () => saveWidths(ewbTable, EWB_WIDTH_KEY));
}

// =============================================================
// FILTER LOGIC
// =============================================================

function applyEinvFilter() {
    einvFilter = {};
    const s = normalizeDate($("#einv_start_date").val());
    const e = normalizeDate($("#einv_end_date").val());
    if (s) einvFilter.start_date   = s;
    if (e) einvFilter.end_date     = e;
    const acc = $("#einv_account_id").val();
    if (acc) einvFilter.account_id = acc;
    const st = $("#einv_gst_status").val();
    if (st) einvFilter.gst_status  = st;
    localStorage.setItem(EINV_FILTER_KEY, JSON.stringify(einvFilter));
    einvTable && einvTable.setData();
}

function clearEinvFilter() {
    $("#einv_start_date, #einv_end_date").val("");
    $("#einv_account_id").val("").trigger("change");
    $("#einv_gst_status").val("");
    einvFilter = {};
    localStorage.removeItem(EINV_FILTER_KEY);
    einvTable && einvTable.setData();
}

function applyEwbFilter() {
    ewbFilter = {};
    const s = normalizeDate($("#ewb_start_date").val());
    const e = normalizeDate($("#ewb_end_date").val());
    if (s) ewbFilter.start_date   = s;
    if (e) ewbFilter.end_date     = e;
    const acc = $("#ewb_account_id").val();
    if (acc) ewbFilter.account_id = acc;
    const st = $("#ewb_gst_status").val();
    if (st) ewbFilter.gst_status  = st;
    localStorage.setItem(EWB_FILTER_KEY, JSON.stringify(ewbFilter));
    ewbTable && ewbTable.setData();
}

function clearEwbFilter() {
    $("#ewb_start_date, #ewb_end_date").val("");
    $("#ewb_account_id").val("").trigger("change");
    $("#ewb_gst_status").val("");
    ewbFilter = {};
    localStorage.removeItem(EWB_FILTER_KEY);
    ewbTable && ewbTable.setData();
}

// =============================================================
// CANCEL IRN
// =============================================================

$(document).on("click", ".cancel-irn", function () {
    const irn  = $(this).data("irn");
    const bill = $(this).data("bill");
    $("#cancel_irn_value").val(irn);
    $("#cancel_irn_reason").val("1");
    $("#cancel_irn_remark_wrap").hide();
    $("#cancel_irn_remark").val("");
    $("#cancelIrnModal").modal("show");
    $("#cancelIrnModal .modal-title").text(`Cancel IRN — ${bill}`);
});

$("#cancel_irn_reason").on("change", function () {
    $("#cancel_irn_remark_wrap").toggle($(this).val() === "4");
});

$("#cancel_irn_submit").on("click", function () {
    const irn    = $("#cancel_irn_value").val();
    const code   = $("#cancel_irn_reason").val();
    const remark = $("#cancel_irn_remark").val().trim();

    if (code === "4" && !remark) {
        showToast("warning", "Remark is required when reason is 'Other'.");
        return;
    }

    showSpinner("cancel-irn-spinner");
    ajaxPost(
        GST_PORTAL.cancelIrnUrl,
        { irn, cancelRsnCode: code, cancelRmrk: remark },
        res => {
            hideSpinner("cancel-irn-spinner");
            $("#cancelIrnModal").modal("hide");
            showToast("success", res.message || "IRN cancelled successfully.");
            einvTable && einvTable.setData();
        },
        () => hideSpinner("cancel-irn-spinner")
    );
});

// =============================================================
// VIEW IRN DETAILS
// =============================================================

$(document).on("click", ".view-irn", function () {
    const irn = $(this).data("irn");
    $("#irn_details_body").html(`<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>`);
    $("#irnDetailsModal").modal("show");

    const url = GST_PORTAL.irnDetailsUrl.replace(":irn", irn);
    $.get(url)
        .done(res => {
            const d = res.data || {};
            $("#irn_details_body").html(`
                <div class="table-responsive">
                    <table class="table table-sm table-bordered fs-4">
                        <tr><th class="w-40">IRN</th><td class="irn-cell">${d.irn || irn}</td></tr>
                        <tr><th>Ack No</th><td>${d.data?.AckNo || d.AckNo || "—"}</td></tr>
                        <tr><th>Ack Date</th><td>${d.data?.AckDt || d.AckDt || "—"}</td></tr>
                        <tr><th>Status</th><td>${d.data?.Status || "—"}</td></tr>
                        <tr><th>Signed QR</th>
                            <td><textarea class="form-control form-control-sm" rows="3" readonly>${d.data?.SignedQRCode || "—"}</textarea></td>
                        </tr>
                    </table>
                </div>
            `);
        })
        .fail(xhr => {
            const msg = xhr.responseJSON?.message || "Failed to fetch details.";
            $("#irn_details_body").html(`<div class="alert alert-danger">${msg}</div>`);
        });
});

// =============================================================
// COPY IRN
// =============================================================

$(document).on("click", ".copy-irn", function (e) {
    e.stopPropagation();
    copyText($(this).data("irn"));
});

// =============================================================
// GENERATE EWB
// =============================================================

$(document).on("click", ".gen-ewb", function () {
    const id   = $(this).data("id");
    const bill = $(this).data("bill");
    $("#gen_ewb_invoice_id").val(id);
    $("#gen_ewb_trans_mode").val("1");
    $("#gen_ewb_vehicle_type").val("R");
    $("#gen_ewb_vehicle_no, #gen_ewb_distance, #gen_ewb_transporter_id, #gen_ewb_trans_doc_no, #gen_ewb_trans_doc_date").val("");
    $("#generateEwbModal .modal-title").text(`Generate EWB — ${bill}`);
    $("#generateEwbModal").modal("show");
});

$("#gen_ewb_submit").on("click", function () {
    const vehicleNo  = $("#gen_ewb_vehicle_no").val().trim();
    const distance   = $("#gen_ewb_distance").val().trim();
    const transMode  = $("#gen_ewb_trans_mode").val();
    const vehicleType= $("#gen_ewb_vehicle_type").val();
    const transId    = $("#gen_ewb_transporter_id").val().trim();
    const transDocNo = $("#gen_ewb_trans_doc_no").val().trim();
    const transDocDate=$("#gen_ewb_trans_doc_date").val().trim();

    if (!vehicleNo) { showToast("warning", "Vehicle number is required."); return; }
    if (!distance)  { showToast("warning", "Distance is required."); return; }

    // Build a minimal EWB payload — real implementation should pull invoice data from backend
    const invoiceId = $("#gen_ewb_invoice_id").val();

    showSpinner("gen-ewb-spinner");
    $.ajax({
        url: GST_PORTAL.generateEwbUrl,
        method: "POST",
        headers: { "X-CSRF-TOKEN": GST_PORTAL.csrfToken },
        contentType: "application/json",
        data: JSON.stringify({
            sales_invoice_id: invoiceId,
            transMode, vehicleNo, vehicleType,
            transDistance: parseInt(distance),
            transporterId: transId,
            transDocNo, transDocDate,
            // remaining invoice payload fields (supplyType, docType etc.)
            // are expected to be resolved server-side from the sales_invoice_id
        }),
        success: res => {
            hideSpinner("gen-ewb-spinner");
            $("#generateEwbModal").modal("hide");
            showToast("success", res.message || "E-Way Bill generated.");
            ewbTable && ewbTable.setData();
        },
        error: xhr => {
            hideSpinner("gen-ewb-spinner");
            showToast("error", xhr.responseJSON?.message || "Generation failed.");
        },
    });
});

// =============================================================
// CANCEL EWB
// =============================================================

$(document).on("click", ".cancel-ewb", function () {
    const ewbNo = $(this).data("ewbno");
    const bill  = $(this).data("bill");
    $("#cancel_ewb_no_value").val(ewbNo);
    $("#cancel_ewb_reason").val("1");
    $("#cancel_ewb_remark_wrap").hide();
    $("#cancel_ewb_remark").val("");
    $("#cancelEwbModal .modal-title").text(`Cancel EWB — ${bill}`);
    $("#cancelEwbModal").modal("show");
});

$("#cancel_ewb_reason").on("change", function () {
    $("#cancel_ewb_remark_wrap").toggle($(this).val() === "4");
});

$("#cancel_ewb_submit").on("click", function () {
    const ewbNo  = $("#cancel_ewb_no_value").val();
    const code   = $("#cancel_ewb_reason").val();
    const remark = $("#cancel_ewb_remark").val().trim();

    if (code === "4" && !remark) {
        showToast("warning", "Remark is required when reason is 'Other'.");
        return;
    }

    showSpinner("cancel-ewb-spinner");
    ajaxPost(
        GST_PORTAL.cancelEwbUrl,
        { ewbNo, cancelCode: code, cancelRmrk: remark },
        res => {
            hideSpinner("cancel-ewb-spinner");
            $("#cancelEwbModal").modal("hide");
            showToast("success", res.message || "E-Way Bill cancelled.");
            ewbTable && ewbTable.setData();
        },
        () => hideSpinner("cancel-ewb-spinner")
    );
});

// =============================================================
// BOOTSTRAP
// =============================================================

$(function () {
    initSelect2(".select2-einv", "All Customers");
    initSelect2(".select2-ewb",  "All Customers");

    new DateInput("#einv_start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#einv_end_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#ewb_start_date",  FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#ewb_end_date",    FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Restore saved filters
    try { einvFilter = JSON.parse(localStorage.getItem(EINV_FILTER_KEY)) || {}; } catch { einvFilter = {}; }
    try { ewbFilter  = JSON.parse(localStorage.getItem(EWB_FILTER_KEY))  || {}; } catch { ewbFilter  = {}; }

    // Build E-Invoice table immediately (active tab)
    buildEinvTable();

    // Build E-Way Bill table when its tab is first shown
    let ewbBuilt = false;
    $("#ewaybill-tab").on("shown.bs.tab", function () {
        if (!ewbBuilt) {
            buildEwbTable();
            ewbBuilt = true;
        } else {
            ewbTable && ewbTable.setData();
        }
    });

    // Filter buttons
    $("#einv_filter_apply").on("click", applyEinvFilter);
    $("#einv_filter_clear").on("click", clearEinvFilter);
    $("#ewb_filter_apply").on("click",  applyEwbFilter);
    $("#ewb_filter_clear").on("click",  clearEwbFilter);
});
