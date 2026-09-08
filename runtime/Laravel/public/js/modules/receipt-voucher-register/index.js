let table; 
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let activeVoucherId = null;


const FILTER_KEY = "receipt_voucher_register_filter";
const WIDTH_KEY = "receipt_voucher_register_col_widths";

const filterFields = [
    "start_date",
    "end_date",
    "account_id",
    "narration",    
];

$(document).ready(function () {
    bindSelect2();     
    // date input with validation
    new DateInput("#start_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);
    new DateInput("#end_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    restoreFilters();    
      table = new Tabulator("#receipt_voucher_register_table", {
        height: "550px", // dynamic height that fits most ERP screens
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
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300],
        paginationDataSent: {
        page: "page",
        size: "size",
    },  
    paginationDataReceived: {
      last_page: "last_page",
      data: "data",
      total: "total",
    },

    ajaxURL: (currentFilter.start_date && currentFilter.end_date) ? receiptVoucherRegisterListUrl : null,
    ajaxParams: () => currentFilter,
    ajaxURLGenerator: (url, config, params) => {
        const page = params.page || 1;
        const size = params.size || 100; // Setting requested size flow
        const queryParams = new URLSearchParams({ ...currentFilter, page, size });
        return `${url}?${queryParams.toString()}`;
    },
    ajaxResponse: (url, params, res) => {
        if (res.permissions) currentPermissions = res.permissions;
        
        const records = res.data || [];
        let lastVoucherId = null;

        // Safely suppresses repeated headers visually without data corruption
        records.forEach(row => {    
            if (row.row_type === 'transaction') {
                if (lastVoucherId === row.voucher_id) {
                    // Explicit display suppression for stacked rows
                    row.voucher_date = "";
                    row.voucher_number = "";
                } else {
                    lastVoucherId = row.voucher_id;
                }
            } else {
                // Narration resets context boundary
                lastVoucherId = null;
            }
        });

        return res;
    },
    columns: getTableColumns(savedColWidths),
  });
    // click to get voucher reference data
    table.on("rowClick", function(e, row) {
        const data = row.getData();
        const targetId = data.voucher_id
        if (targetId) {
            getVoucherReference(targetId);
        }
    });
    
    $(`<style>#receipt_voucher_register_table .tabulator-row { cursor: pointer !important; }</style>`).appendTo("head");

    // No additional setData needed, managed natively via ajaxURL in constructor if filters exist


    $("#filter_apply").on("click", function(e){
        e.preventDefault();             
        applyFilter();
        
    });

    $("#filter_clear").on("click", function (e) {
        e.preventDefault();
        clearFilter();
        applyFilter();
    });    

    // Export / Print
    $(document).on("click", ".dropdown-item[data-type]", function (e) {
        e.preventDefault();
        const type   = $(this).data("type");
        const route  = $(this).data("route");
        const format = $(this).data("format");
        const safeFilter = Object.keys(currentFilter).length ? currentFilter : {};

        if (type === "print"  && typeof printReport   === "function") printReport(route, { format, currentFilter: safeFilter });
        if (type === "excel"  && typeof downloadExcel === "function") downloadExcel(route, { currentFilter: safeFilter });
    });

});

function bindSelect2() {
        const selectIdArray = ['#account_id','#narration'];
        selectIdArray.forEach(element => {
            $(element).select2({
                theme: "bootstrap-5",
                allowClear: true,
                placeholder: "Select " + element.replace('#', '').replace('.', '').replace('_id', '') + " ...",
                width: null,
            });
        });

        $(document).on("select2:open", function (e) {
            const selectElement = $(e.target);
            const searchInput = selectElement
                .data("select2")
                .$dropdown.find(".select2-search__field");

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
            width: savedColWidths.date ?? 120,
            headerHozAlign: "center",
            hozAlign: "center",
            headerSort: false,
            formatter: (cell) =>
                cell.getValue() ? formatDateToDMY(cell.getValue()) : "",
        },
        {
            title: "Vch. No.",
            field: "voucher_number",
            width: savedColWidths.voucher_number ?? 150,
            hozAlign: "center",
            headerHozAlign:"center",
            headerSort: false,
        },
        {
            title: "Particulars",
            field: "account_name",
            headerSort: false,
            width: savedColWidths.account_name ?? 450,
            formatter: (cell, formatterParams, onRendered) => {
                const rowData = cell.getData();
                cell.getElement().setAttribute("title", rowData.account_name);
                if (rowData.row_type && rowData.row_type == "narration") {
                    return `<span style="color:#653818; font-style:italic">Narration: ${
                        rowData.account_name || ""
                    }</span>`;
                }
                return cell.getValue();
            },
        },
        {
            title: "Debit",
            field: "debit",
            width: savedColWidths.debit ?? 170,
            hozAlign: "right",
            headerHozAlign:"right",
            headerSort: false,
            formatter: (cell) =>
                parseFloat(cell.getValue())
                    ? formatIndianNumber(cell.getValue())
                    : "",
        },
        {
            title: "Credit",
            field: "credit",
            width: savedColWidths.credit ?? 170,
            hozAlign: "right",
            headerHozAlign:"right",
            headerSort: false,
            formatter: (cell) =>
                parseFloat(cell.getValue())
                    ? formatIndianNumber(cell.getValue())
                    : "",
        },
        // {
        //     title: "Action",
        //     field: "action",
        //     width: saved.action ?? 170,
        //     hozAlign: "center",
        //     headerSort: false,
        // },
    ];
}


function applyFilter() {
    currentFilter = {};
 
    $.each(filterFields, function (i, id) {
        var el    = $("#" + id).get(0);
        var value = getFieldValue(el);
 
        if (value) currentFilter[id] = value;
    });
  
    // process date fields
    $.each(["start_date", "end_date"], function (i, k) {
        if (currentFilter[k]) {
            var formatted = normalizeDate(currentFilter[k]);
            if (formatted) {
                currentFilter[k] = formatted;
            } else {
                delete currentFilter[k];
            }
        }
    });

    if (!currentFilter.start_date || !currentFilter.end_date) {
        showToast("error", "Please select both Start and End dates.");
        return;
    }
 
    localStorage.setItem(FILTER_KEY, JSON.stringify(currentFilter));
 
    if (table) table.setData(receiptVoucherRegisterListUrl);
}
 
 
function clearFilter() {
    $.each(filterFields, function (i, id) {
        var el = $("#" + id).get(0);
        setFieldValue(el, "");
    });
 
    currentFilter = {};
    localStorage.removeItem(FILTER_KEY);
 
    if (table) table.setData();
}
 
/** Restore filters on page load */
function restoreFilters() {
    var saved = localStorage.getItem(FILTER_KEY);
    if (!saved) return;
 
    currentFilter = JSON.parse(saved);
 
    $.each(currentFilter, function (id, value) {
        var el = $("#" + id).get(0);
        setFieldValue(el, value);
    });    
    
}

/**
 * Get value from DOM element
 * @param {HTMLElement} el 
 * @returns {string}
 */
function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

/**
 * Set value to DOM element and trigger change for Select2
 * @param {HTMLElement} el 
 * @param {any} value 
 */
function setFieldValue(el, value) {
    if (!el) return;
    var $el = $(el);
    $el.val(value);
    // If it's a select2 dropdown, trigger change to update UI
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
}

/**
 * Normalize date string to YYYY-MM-DD
 * @param {string} dateStr 
 * @returns {string}
 */
function normalizeDate(dateStr) {
    if (!dateStr || dateStr === "-") return "";
  
    // If it matches DD-MM-YYYY, return as is
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) return dateStr;    

    // If already YYYY-MM-DD, return as is
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;

    if (typeof formatDateToYMD === "function") {
        return formatDateToYMD(dateStr);
    }
    return dateStr;
}

/**
 * Fetch and display reference data for a clicked payment voucher
 */
function getVoucherReference(voucherNumber) {   
    if (activeVoucherId === voucherNumber) {
        return;
    }

    activeVoucherId = voucherNumber;

    // Show loader
    $("#ref_preview_table_body").html(
        `<tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border spinner-border-sm text-primary ms-3"></div>
                Loading...
            </td>
        </tr>`
    );

    $.ajax({
        url: getReceiptVoucherReference ,
        type: "GET",
        data: {
            voucher_number: voucherNumber,
            voucher_type: "receipt_voucher",
        },
        success: function (response) {
            let html = "";
            console.log(response);
            
            if (response.status === "success" && response.data && response.data.length > 0) {
                response.data.forEach(function(item) {
                    let refDate = item.ref_date ? formatDateToDMY(item.ref_date) : "";
                    let amount = item.pay_amount ? formatIndianNumber(item.pay_amount) : "";
                    html += `
                        <tr>
                            <td class="text-center">${item.voucher_number ?? ''}</td>
                            <td class="text-center">${item.ref_no ?? ''}</td>
                            <td class="text-center">${refDate}</td>
                            <td class="text-end">${amount}</td>
                            <td class="text-center">${item.payment_mode == 2 ? 'Dr' : 'Cr'}</td>
                        </tr>
                    `;
                });
            } else {
                html = `
                    <tr>
                        <td colspan="5" class="text-center text-muted fw-semibold py-3">
                            No reference data found.
                        </td>
                    </tr>
                `;
            }

            $("#ref_preview_table_body").html(html);
        },
        error: function (xhr, status, error) {
            $("#ref_preview_table_body").html(`
                <tr>
                    <td colspan="5" class="text-center text-danger fw-semibold py-3">
                        Failed to fetch reference data.
                    </td>
                </tr>
            `);
        }
    });
}