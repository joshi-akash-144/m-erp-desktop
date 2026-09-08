// =============================================================
// GST Portal — Error Logs
// =============================================================

"use strict";

// ── State ───────────────────────────────────────────────────
let einvTable = null;
let ewbTable  = null;
let einvFilters = {};
let ewbFilters  = {};

/** Convert DD-MM-YYYY → YYYY-MM-DD */
function normalizeDate(d) {
    if (!d || d === "__-__-____") return "";
    var parts = d.split("-");
    var day = parts[0], month = parts[1], year = parts[2];
    return year && year.length === 4 ? (day + "-" + month + "-" + year) : "";
}

// ── Shared column definitions ─────────────────────────────
function errColumns() {
    return [
        { title: "ID",          field: "id",                width: 70,  hozAlign: "center", headerHozAlign: "center", headerSort: false },
        { title: "Bill No.",    field: "reference_number",  width: 120, hozAlign: "center", headerHozAlign: "center", headerSort: false },
        { title: "Bill Date",   field: "bill_date",         width: 110, hozAlign: "center", headerHozAlign: "center", headerSort: false },
        {
            title: "Error Code", field: "error_code", width: 110,
            hozAlign: "center", headerHozAlign: "center", headerSort: false,
            formatter: cell => {
                const v = cell.getValue();
                const cls = (!v || v === '—') ? 'none' : '';
                return `<span class="err-code ${cls}">${v || '—'}</span>`;
            },
        },
        {
            title: "Error Description", field: "error_description",
            minWidth: 260, headerSort: false,
            formatter: cell => `<span style="font-size:.78rem">${cell.getValue() || '—'}</span>`,
        },
        { title: "User",       field: "user_name",  width: 110, hozAlign: "center", headerHozAlign: "center", headerSort: false },
        { title: "Created At", field: "created_at", width: 190, hozAlign: "center", headerHozAlign: "center", headerSort: false },
        {
            title: "Actions", field: "_act", width: 80,
            hozAlign: "center", headerHozAlign: "center",
            headerSort: false, frozen: true,
            formatter: cell => {

                const viewIcon = icons.view;

                let actions = '<div class="d-flex align-items-center justify-content-center gap-2">';
                actions += `
                <span class="erp-btn-icon view" data-row='${JSON.stringify(cell.getRow().getData())}' title="View Detail">
                    ${viewIcon}
                </span>`;
                actions += "</div>";
                return actions;

            },
        },
    ];
}

// ── Build table helper ────────────────────────────────────
function buildErrTable(elId, service, filters) {
    return new Tabulator(`#${elId}`, {
        layout: "fitColumns",
        vertAlign: "middle",
        height: "calc(100vh - 400px)",
        placeholder: `<div class="text-center py-5 text-muted">
            <i class="fa-solid fa-circle-check fa-2x mb-2 text-success opacity-75"></i>
            <p>No errors found</p></div>`,
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [20, 50, 100],
        paginationDataSent:     { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },
        ajaxURL: ERR_LOG.jsonUrl,
        ajaxURLGenerator: (url, cfg, params) => {
            const qp = new URLSearchParams({
                service,
                page : params.page,
                size : params.size,
                ...filters(),
            });
            return `${url}?${qp}`;
        },
        columns: errColumns(),
    });
}

// ── Document Ready ───────────────────────────────────────────
$(function () {
    // ── E-Invoice Init ──
    einvTable = buildErrTable('einv_table', 'e_invoice', () => einvFilters);

    // ----- Date inputs -----
    new DateInput("#einv_from", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#einv_to", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // ----- E-Invoice filter events -----
    $("#einv_apply").on("click", function () {
        einvFilters = {};
        const ref  = $("#einv_ref").val().trim();
        const from = normalizeDate($("#einv_from").val());
        const to   = normalizeDate($("#einv_to").val());
        if (ref)  einvFilters.ref  = ref;
        if (from) einvFilters.from = from;
        if (to)   einvFilters.to   = to;
        einvTable.setData();
    });

    $("#einv_clear").on("click", function () {
        einvFilters = {};
        $("#einv_ref").val("");
        $("#einv_from, #einv_to").val("").removeClass("is-invalid");
        einvTable.setData();
    });

    // ── EWB tab lazy initialization ──
    const tabEwbEl = document.getElementById("tab-ewb");
    if (tabEwbEl) {
        tabEwbEl.addEventListener("shown.bs.tab", function () {
            if (ewbTable) return;
            ewbTable = buildErrTable('ewb_table', 'eway_bill', () => ewbFilters);

            // ----- Date inputs -----
            new DateInput("#ewb_from", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
            new DateInput("#ewb_to", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

            // ----- EWB filter events -----
            $("#ewb_apply").on("click", function () {
                ewbFilters = {};
                const ref  = $("#ewb_ref").val().trim();
                const from = normalizeDate($("#ewb_from").val());
                const to   = normalizeDate($("#ewb_to").val());
                if (ref)  ewbFilters.ref  = ref;
                if (from) ewbFilters.from = from;
                if (to)   ewbFilters.to   = to;
                ewbTable.setData();
            });

            $("#ewb_clear").on("click", function () {
                ewbFilters = {};
                $("#ewb_ref").val("");
                $("#ewb_from, #ewb_to").val("").removeClass("is-invalid");
                ewbTable.setData();
            });
        });
    }

    // ── Detail modal ──────────────────────────────────────────
    $(document).on("click", ".erp-btn-icon.view", function () {
        const row = JSON.parse($(this).attr("data-row"));

        $("#detail_subtitle").text(`Bill: ${row.reference_number}  |  ${row.created_at}`);
        $("#detail_error").text(row.error_description || '—');

        if (row.request_payload) {
            $("#detail_request").text(JSON.stringify(row.request_payload, null, 2));
            $("#detail_req_wrap").show();
        } else {
            $("#detail_req_wrap").hide();
        }

        if (row.response_payload) {
            $("#detail_response").text(JSON.stringify(row.response_payload, null, 2));
            $("#detail_res_wrap").show();
        } else {
            $("#detail_res_wrap").hide();
        }

        $("#detailModal").modal("show");
    });
});