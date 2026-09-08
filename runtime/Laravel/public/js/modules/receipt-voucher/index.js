let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let activeVoucherId = null;

const filterFields = ["start_date", "end_date", "account_id", "narration", "voucher_no"];
const PAGE_SIZE_KEY = "receipt_voucher_page_size";
const PAGE_NUM_KEY  = "receipt_voucher_page_num";

// Returns today if today is within the financial year, otherwise returns FY end
function FINANCIAL_YEAR_END_OR_TODAY() {
    const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
    return today <= FINANCIAL_YEAR_END ? today : FINANCIAL_YEAR_END;
}

$(document).ready(function () {
    bindSelect2();
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date",   FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // Set default dates: financial year start → today
    const defaultStart = formatDateToDMY(FINANCIAL_YEAR_START);
    const defaultEnd   = formatDateToDMY(FINANCIAL_YEAR_END_OR_TODAY());
    $("#start_date").val(defaultStart);
    $("#end_date").val(defaultEnd);

    // Prime the filter so Tabulator loads immediately
    currentFilter = {
        start_date: FINANCIAL_YEAR_START,
        end_date:   FINANCIAL_YEAR_END_OR_TODAY(),
        narration:  "0",
        voucher_no: "",
    };

    table = new Tabulator("#receipt_voucher_register_table", {
        height: "550px",
        layout: "fitColumns",
        placeholder: `
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon text-muted mb-3">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                    <path d="M10 12l4 4m0 -4l-4 4" />
                </svg>
                <h3 class="text-muted">No data found</h3>
                <p class="text-muted">Try adjusting your filters or search criteria</p>
            </div>
        `,

        pagination: true,
        paginationMode: "remote",
        paginationSize: parseInt(localStorage.getItem(PAGE_SIZE_KEY)) || 50,
        paginationInitialPage: parseInt(localStorage.getItem(PAGE_NUM_KEY)) || 1,
        paginationSizeSelector: [10, 30, 50, 100, 150, 200, 250, 300],
        paginationDataSent:     { page: "page", size: "size" },
        paginationDataReceived: { last_page: "last_page", data: "data", total: "total" },

        ajaxURL: receiptVoucherListUrl,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            currentFilter.start_date = formatDateToYMD(currentFilter.start_date);
            currentFilter.end_date   = formatDateToYMD(currentFilter.end_date);

            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },
        ajaxResponse: (url, params, res) => {
            if (res.permissions) currentPermissions = res.permissions;

            const records = res.data || [];
            let lastVoucherId = null;

            records.forEach((row) => {
                const isTransaction = !row.row_type || row.row_type === "transaction";
                if (isTransaction) {
                    if (lastVoucherId === row.voucher_id) {
                        row.voucher_date   = "";
                        row.voucher_serial = "";
                    } else {
                        lastVoucherId = row.voucher_id;
                    }
                } else {
                    lastVoucherId = null;
                }
            });

            return res;
        },
        columns: getTableColumns(savedColWidths),
    });

    table.on("pageLoaded", function(pageNo) {
        localStorage.setItem(PAGE_NUM_KEY, pageNo);
        localStorage.setItem(PAGE_SIZE_KEY, table.getPageSize());
    });

    table.on("rowClick", function (e, row) {
        const data = row.getData();
        if (data.voucher_id) {
            getVoucherReference(data.voucher_id);
        }
    });

    $(`<style>#receipt_voucher_register_table .tabulator-row { cursor: pointer !important; }</style>`).appendTo("head");

    $("#filter_apply").on("click", function (e) {
        e.preventDefault();
        applyFilter();
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
        applyFilter();
    });

    $(document).on("click", ".dropdown-item[data-type]", function (e) {
        e.preventDefault();
        const type   = $(this).data("type");
        const route  = $(this).data("route");
        const format = $(this).data("format");
        const safeFilter = Object.keys(currentFilter).length ? currentFilter : {};

        if (type === "print" && typeof printReport   === "function") printReport(route, { format, currentFilter: safeFilter });
        if (type === "excel" && typeof downloadExcel === "function") downloadExcel(route, { currentFilter: safeFilter });
    });
});

function bindSelect2() {
    const selectIdArray = ["#account_id", "#narration", "#voucher_no"];
    selectIdArray.forEach((element) => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace("#", "").replace("_id", "") + " ...",
            width: null,
        });
    });

    $(document).on("select2:open", function (e) {
        const selectElement = $(e.target);
        const searchInput = selectElement.data("select2").$dropdown.find(".select2-search__field");
        searchInput
            .off("keydown.select2Enter")
            .on("keydown.select2Enter", (event) => {
                if (event.which === 13) {
                    event.preventDefault();
                    selectElement.select2("close");
                    moveFocusToNextField(selectElement);
                }
            });
    });
}

function getTableColumns(savedColWidths = {}) {
    return [
        {
            title: "Date",
            field: "voucher_date",
            width: savedColWidths.voucher_date ?? 120,
            headerHozAlign: "center",
            hozAlign: "center",
            headerSort: false,
            formatter: (cell) => cell.getValue() ? formatDateToDMY(cell.getValue()) : "",
        },
        {
            title: "Vch. No.",
            field: "voucher_serial",
            width: savedColWidths.voucher_serial ?? 150,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
        },
        {
            title: "Particulars",
            field: "account_name",
            headerSort: false,
            width: savedColWidths.account_name ?? 450,
            formatter: (cell) => {
                const rowData = cell.getData();
                cell.getElement().setAttribute("title", rowData.account_name || "");
                if (rowData.row_type && rowData.row_type === "narration") {
                    return `<span style="color:#653818; font-style:italic">Narration: ${rowData.account_name || ""}</span>`;
                }
                return cell.getValue();
            },
        },
        {
            title: "Debit",
            field: "debit",
            width: savedColWidths.debit ?? 170,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue()) ? formatIndianNumber(cell.getValue()) : "",
        },
        {
            title: "Credit",
            field: "credit",
            width: savedColWidths.credit ?? 170,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue()) ? formatIndianNumber(cell.getValue()) : "",
        }, 
        // =======================
        // Actions
        // =======================
        {
            title: "Actions",
            field: "actions",
            width: 120,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            frozen: true,
            formatter: (cell) => {
                const row = cell.getData();
                if (!row.voucher_serial) return "";
                
                const canView   = currentPermissions.view   ?? false;
                const canPrint  = currentPermissions['print-voucher'] ?? false;
                const canDelete = currentPermissions.delete ?? false;

                const viewIcon   = icons.view;
                const printIcon  = icons.print;
                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 justify-content-center">';
                if (canView) {
                    actions += `
                        <span class="erp-btn-icon view" data-id="${row.voucher_id}" title="View Receipt Voucher">
                            ${viewIcon}
                        </span>`;
                }
                if (canPrint) {
                    actions += `
                        <span class="erp-btn-icon print" data-id="${row.voucher_id}" title="Print Receipt Voucher">
                            ${printIcon}
                        </span>`;
                }
                if (canDelete) {
                    actions += `
                        <span class="erp-btn-icon delete text-danger" data-id="${row.voucher_id}" title="Delete Receipt Voucher">
                            ${deleteIcon}
                        </span>`;
                }
                actions += "</div>";
                return actions;
            },
        },
    ];
}

function applyFilter() {
    currentFilter = {};

    $.each(filterFields, function (i, id) {
        const el    = $("#" + id).get(0);
        const value = getFieldValue(el);
        if (value) currentFilter[id] = value;
    });

    $.each(["start_date", "end_date"], function (i, k) {
        if (currentFilter[k]) {
            const formatted = normalizeDate(currentFilter[k]);
            if (formatted) currentFilter[k] = formatted;
            else delete currentFilter[k];
        }
    });

    if (!currentFilter.start_date || !currentFilter.end_date) {
        showToast("error", "Please select both Start and End dates.");
        return;
    }

    // narration is required by the backend — default to "0" if not selected
    if (!currentFilter.narration) currentFilter.narration = "0";

    if (table) table.setData(receiptVoucherListUrl);
}

function clearFilter() {
    const defaultStart = formatDateToDMY(FINANCIAL_YEAR_START);
    const defaultEnd   = formatDateToDMY(FINANCIAL_YEAR_END_OR_TODAY());

    $("#start_date").val(defaultStart);
    $("#end_date").val(defaultEnd);
    setFieldValue($("#account_id").get(0), "");
    setFieldValue($("#narration").get(0), "0");
    setFieldValue($("#voucher_no").get(0), "");

    activeVoucherId = null;

    currentFilter = {
        start_date: FINANCIAL_YEAR_START,
        end_date:   FINANCIAL_YEAR_END_OR_TODAY(),
        narration:  "0",
        voucher_no: "",
    };

    $("#voucher_reference").html(`
        <tr>
            <td colspan="5" class="text-center text-muted fw-semibold py-3">
                Click on any row to view <span class="text-primary">reference details</span>
            </td>
        </tr>
    `);

    if (table) table.setData(receiptVoucherListUrl);
}

function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

function setFieldValue(el, value) {
    if (!el) return;
    const $el = $(el);
    $el.val(value);
    if ($el.hasClass("select2-hidden-accessible")) $el.trigger("change");
}

function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return dateStr;
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
    if (typeof formatDateToYMD === "function") return formatDateToYMD(dateStr);
    return dateStr;
}

function getVoucherReference(voucherId) {
    if (activeVoucherId === voucherId) return;
    activeVoucherId = voucherId;

    $("#voucher_reference").html(`
        <tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border spinner-border-sm text-primary ms-3"></div>
                Loading...
            </td>
        </tr>
    `);

    const url = receiptVoucherReferenceUrl.replace("__ID__", voucherId);

    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            let html = "";

            if (response.success && response.data && response.data.voucher) {
                const voucher     = response.data.voucher;
                const voucherDate = voucher.voucher_date ? formatDateToDMY(voucher.voucher_date) : "";
                let   hasRefs     = false;

                (voucher.details || []).forEach(function (txn) {
                    (txn.refs || []).forEach(function (ref) {
                        hasRefs      = true;
                        const type   = ref.method === "new_ref" ? "New Ref" : "Agst Ref";
                        const amount = ref.ref_amount ? formatIndianNumber(ref.ref_amount) : "";
                        html += `
                            <tr>
                                <td class="text-center">${voucherDate}</td>
                                <td class="text-center">${type}</td>
                                <td class="text-center">${ref.ref_number ?? ""}</td>
                                <td class="text-end">${amount}</td>
                                <td class="text-center">${ref.transaction_type ?? ""}</td>
                            </tr>
                        `;
                    });
                });

                if (!hasRefs) {
                    html = `<tr><td colspan="5" class="text-center text-muted fw-semibold py-3">No reference data found.</td></tr>`;
                }
            } else {
                html = `<tr><td colspan="5" class="text-center text-muted fw-semibold py-3">No reference data found.</td></tr>`;
            }

            $("#voucher_reference").html(html);
        },
        error: function () {
            $("#voucher_reference").html(`
                <tr>
                    <td colspan="5" class="text-center text-danger fw-semibold py-3">
                        Failed to fetch reference data.
                    </td>
                </tr>
            `);
        },
    });

}

// View Receipt Voucher Details
$(document).on("click", ".erp-btn-icon.view", function () {
    const receiptVoucherId = $(this).data("id");
    const url = `${receiptVoucherViewUrl}/${receiptVoucherId}`;

    $.ajax({
        url: url,
        type: "GET",
        beforeSend: function () { showLoader("Loading Receipt Voucher..."); },
        success: function (response) {
            hideLoader();
            if (!response.success || !response.data) {
                showToast("error", response.message || "Failed to load receipt voucher details.");
                return;
            }

            const data    = response.data;
            const voucher = data.voucher;

            $("#view_voucher_date").text(formatDateToDMY(voucher.voucher_date));
            $("#view_voucher_number").text(voucher.voucher_serial || "--");
            $("#view_narration").text(voucher.narration || "--");

            let detailsHtml = "";
            let totalDebit  = 0;
            let totalCredit = 0;
            let customerName = "";

            (voucher.details || []).forEach((detail, index) => {
                const debit  = parseFloat(detail.debit)  || 0;
                const credit = parseFloat(detail.credit) || 0;
                totalDebit  += debit;
                totalCredit += credit;
                if (detail.is_party_account) customerName = detail.account_name;
                detailsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${index + 1}</td>
                        <td>${detail.account_name || ""}</td>
                        <td class="text-end font-monospace">${debit  > 0 ? formatIndianNumber(debit)  : ""}</td>
                        <td class="text-end font-monospace">${credit > 0 ? formatIndianNumber(credit) : ""}</td>
                    </tr>`;
            });

            if (!customerName && voucher.details?.length) {
                const p = voucher.details.find(d => d.is_party_account);
                const c = voucher.details.find(d => (parseFloat(d.credit) || 0) > 0);
                customerName = p?.account_name || c?.account_name || "";
            }

            $("#view_customer_name").text(customerName || "--");
            $("#view_voucher_details_tbody").html(detailsHtml || '<tr><td colspan="4" class="text-center text-muted py-3">No details available.</td></tr>');
            $("#view_total_debit").text(formatIndianNumber(totalDebit));
            $("#view_total_credit").text(formatIndianNumber(totalCredit));

            let breakUpHtml  = "";
            let breakUpIndex = 1;
            (voucher.details || []).forEach((detail) => {
                (detail.refs || []).forEach((ref) => {
                    const method    = ref.method === "new_ref" ? "New Ref" : "Agst Ref";
                    const refAmount = parseFloat(ref.ref_amount) || 0;
                    const drCr      = ref.transaction_type || (parseFloat(detail.debit) > 0 ? "Dr" : "Cr");
                    const qtyVal    = ref.qty ? parseFloat(ref.qty) : null;
                    breakUpHtml += `
                        <tr>
                            <td class="text-center font-monospace">${breakUpIndex++}</td>
                            <td>${method}</td>
                            <td>${ref.po_number || ""}</td>
                            <td>${ref.ref_number || ""}</td>
                            <td>${ref.ref_date       ? formatDateToDMY(ref.ref_date)       : ""}</td>
                            <td>${ref.delivery_date  ? formatDateToDMY(ref.delivery_date)  : ""}</td>
                            <td class="text-center">${drCr}</td>
                            <td>${ref.product     || ""}</td>
                            <td>${ref.destination || ""}</td>
                            <td class="text-end font-monospace">${qtyVal !== null ? formatQty(qtyVal) : ""}</td>
                            <td class="text-end font-monospace">${formatIndianNumber(refAmount)}</td>
                        </tr>`;
                });
            });

            if (breakUpHtml === "") {
                $("#view_bill_break_up_section").hide();
            } else {
                $("#view_bill_break_up_section").show();
            }
            $("#view_bill_break_up_tbody").html(breakUpHtml);
            $("#receipt_voucher_modal").data("voucher-id", receiptVoucherId);
            $("#receipt_voucher_modal").modal("show");
        },
        error: function () {
            hideLoader();
            showToast("error", "Oops! Something went wrong. Try again later.");
        },
    });
});

// Delete from table row
$(document).on("click", ".erp-btn-icon.delete", function () {
    const voucherId = $(this).data("id");
    if (!voucherId) return;
    deleteReceiptVoucher(voucherId);
});

// Delete / Entry-Reverse from modal
$(document).on("click", "#btn_entry_reverse", function () {
    const voucherId = $("#receipt_voucher_modal").data("voucher-id");
    if (!voucherId) return;
    deleteReceiptVoucher(voucherId, true);
});

function deleteReceiptVoucher(voucherId, fromModal = false) {
    Swal.fire({
        title: "Delete Receipt Voucher?",
        html: `This will permanently delete the voucher and:<br>
               <ul class="mt-2 mb-0" style="display:inline-block;text-align:left;">
                 <li>Reverse all reference allocations</li>
                 <li>Reopen any settled references</li>
               </ul><br>
               This action <strong>cannot be undone</strong>.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d63939",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, Delete",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (!result.isConfirmed) return;

        const url = receiptVoucherDeleteUrl.replace(":id", voucherId);
        showLoader("Deleting voucher...");

        $.ajax({
            url: url,
            type: "DELETE",
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
            success: function (res) {
                hideLoader();
                if (res.success) {
                    if (fromModal) $("#receipt_voucher_modal").modal("hide");
                    Swal.fire({
                        icon: "success",
                        title: "Deleted!",
                        text: res.message || "Receipt Voucher deleted successfully.",
                        timer: 1800,
                        showConfirmButton: false,
                    }).then(() => {
                        activeVoucherId = null;
                        $("#voucher_reference").html(`<tr><td colspan="5" class="text-center text-muted fw-semibold py-3">Click on any row to view <span class="text-primary">reference details</span></td></tr>`);
                        if (table) table.setData(receiptVoucherListUrl);
                    });
                } else {
                    Swal.fire({ icon: "error", title: "Cannot Delete", text: res.message || "Failed to delete." });
                }
            },
            error: function (xhr) {
                hideLoader();
                Swal.fire({ icon: "error", title: "Error", text: xhr.responseJSON?.message || "Failed to delete receipt voucher." });
            },
        });
    });
}

// Individual Print Action
$(document).on("click", ".erp-btn-icon.print", function () {
    const id = $(this).data("id");
    window.open(receiptVoucherIndividualPrintUrl + "/" + id, "_blank");
});

// Modal Print Action
$(document).on("click", "#btn_print_receipt", function (e) {
    e.preventDefault();
    const id = $("#receipt_voucher_modal").data("voucher-id");
    printReport(receiptVoucherPrintReceiptUrl + "/" + id);
});
