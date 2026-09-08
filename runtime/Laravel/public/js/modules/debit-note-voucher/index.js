let table;
let currentFilter = {};
let currentPermissions = {};
let savedColWidths = {};
let activeVoucherId = null;

const WIDTH_KEY = "debit_note_voucher_register_col_widths";
const PAGE_SIZE_KEY = "debit_note_voucher_page_size";
const PAGE_NUM_KEY  = "debit_note_voucher_page_num";

const filterFields = [
    "start_date",
    "end_date",
    "account_id",
    "narration",
    "full_narration",
    "voucher_no",
];

// Returns today's date if within the financial year, otherwise FY end
function FINANCIAL_YEAR_END_OR_TODAY() {
    const today = new Date().toISOString().slice(0, 10);
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

    // Prime filter so Tabulator fires immediately on mount
    currentFilter = {
        start_date: FINANCIAL_YEAR_START,
        end_date:   FINANCIAL_YEAR_END_OR_TODAY(),
        narration:  "0",
        show_deleted: 0,
    };

    table = new Tabulator("#debit_note_voucher_register_table", {
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
        
        ajaxURL: (currentFilter.start_date && currentFilter.end_date) ? journalVoucherListUrl : null,
        ajaxParams: () => currentFilter,
        ajaxURLGenerator: (url, config, params) => {
            currentFilter.start_date = formatDateToYMD(currentFilter.start_date)
            currentFilter.end_date = formatDateToYMD(currentFilter.end_date)
            
            const page = params.page || 1;
            const size = params.size || 50;
            const queryParams = new URLSearchParams({ ...currentFilter, page, size });
            return `${url}?${queryParams.toString()}`;
        },
        ajaxResponse: (url, params, res) => {
            if (res.permissions) currentPermissions = res.permissions;

            const records = res.data || [];
            let lastVoucherId = null;

            records.forEach(row => {
                const isTransaction = !row.row_type || row.row_type === "transaction";
                if (isTransaction) {
                    if (lastVoucherId === row.voucher_id) {
                        row.voucher_date   = "";
                        row.voucher_serial = "";
                        row.created_by_name = "";
                        row.updated_by_name = "";
                        row.deleted_by_name = "";
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
        rowFormatter: function(row) {
            const data = row.getData();
            if (data.deleted_at) {
                row.getElement().style.opacity = "0.6";
                row.getElement().style.backgroundColor = "#fef2f2";
            }
        },
    });

    table.on("pageLoaded", function(pageNo) {
        localStorage.setItem(PAGE_NUM_KEY, pageNo);
        localStorage.setItem(PAGE_SIZE_KEY, table.getPageSize());
    });

    // table.on("rowClick", function (e, row) {
    //     const data = row.getData();
    //     if (data.voucher_id) {
    //         getVoucherReference(data.voucher_id);
    //     }
    // });

    $(`<style>#debit_note_voucher_register_table .tabulator-row { cursor: pointer !important; }</style>`).appendTo("head");

    $("#filter_apply").on("click", function (e) {
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

        if (type === "print" && typeof printReport   === "function") printReport(route, { format, currentFilter: safeFilter });
        if (type === "excel" && typeof downloadExcel === "function") downloadExcel(route, { currentFilter: safeFilter });
    });
});

function bindSelect2() {
    const selectIdArray = ["#account_id", "#narration", "#full_narration","#voucher_no"];
    selectIdArray.forEach(element => {
        $(element).select2({
            theme: "bootstrap-5",
            allowClear: true,
            placeholder: "Select " + element.replace("#", "").replace(".", "").replace("_id", "") + " ...",
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
            width:  110,
            headerHozAlign: "center",
            hozAlign: "center",
            headerSort: false,
            formatter: (cell) => cell.getValue() ? formatDateToDMY(cell.getValue()) : "",
        },
        {
            title: "Vch. No.",
            field: "voucher_serial",
            width:  100,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
        },
        {
            title: "Particulars",
            field: "account_name",
            headerSort: false,
            width: 300,
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
            width: 150,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue()) ? formatIndianNumber(cell.getValue()) : "",
        },
        {
            title: "Credit",
            field: "credit",
            width: 150,
            hozAlign: "right",
            headerHozAlign: "right",
            headerSort: false,
            formatter: (cell) => parseFloat(cell.getValue()) ? formatIndianNumber(cell.getValue()) : "",
        },
        {
            title: "Created By",
            field: "created_by_name",
            width: 130,
            headerSort: false,
            formatter: (cell) => cell.getValue() ? `<span class="badge bg-success-lt">${cell.getValue()}</span>` : "",
        },
        {
            title: "Updated By",
            field: "updated_by_name",
            width: 130,
            headerSort: false,
            formatter: (cell) => cell.getValue() ? `<span class="badge bg-purple-lt">${cell.getValue()}</span>` : "",
        },
        {
            title: "Deleted By",
            field: "deleted_by_name",
            width: 130,
            headerSort: false,
            formatter: (cell) => cell.getValue() ? `<span class="badge bg-danger-lt">${cell.getValue()}</span>` : "",
        },
        // =======================
        // Actions
        // =======================
        {
            title: "Actions",
            field: "actions",
            width: 140,
            hozAlign: "center",
            headerHozAlign: "center",
            headerSort: false,
            frozen: true,
            formatter: (cell) => {
                const row = cell.getData();
                if (!row.voucher_serial) return "";
                
                if (row.deleted_at) {
                    return `<span class="text-muted fst-italic" style="font-size: 0.75rem;">Deleted</span>`;
                }

                const isSystemGenerated = !!row.is_system_generated;

                const canView   = currentPermissions.view   ?? false;
                const canUpdate = (currentPermissions.update ?? false) && !isSystemGenerated;
                const canPrint  = currentPermissions['print-voucher'] ?? false;
                const canDelete = (currentPermissions.delete ?? false) && !isSystemGenerated;

                const viewIcon   = icons.view;
                const editIcon   = icons.edit;
                const printIcon  = icons.print;
                const deleteIcon = icons.delete;

                let actions = '<div class="d-flex gap-2 justify-content-center">';
                if (canView) {
                    actions += `
                        <span class="erp-btn-icon view" data-id="${row.voucher_id}" title="View Debit Note Voucher">
                            ${viewIcon}
                        </span>`;
                }
                if (canUpdate) {
                    actions += `
                        <a href="${typeof journalVoucherEditUrl !== 'undefined' ? journalVoucherEditUrl : ''}?id=${row.voucher_id}" class="erp-btn-icon edit" title="Edit Debit Note Voucher">
                            ${editIcon}
                        </a>`;
                }
                if (canPrint) {
                    actions += `
                        <span class="erp-btn-icon print" data-id="${row.voucher_id}" title="Print Debit Note Voucher">
                            ${printIcon}
                        </span>`;
                }
                if (canDelete) {
                    actions += `
                        <span class="erp-btn-icon delete text-danger" data-id="${row.voucher_id}" title="Delete Debit Note Voucher">
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

    currentFilter.show_deleted = $("#show_deleted").is(":checked") ? 1 : 0;

    $.each(["start_date", "end_date"], function (i, k) {
        if (currentFilter[k]) {
            const formatted = normalizeDate(currentFilter[k]);
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

    if (!currentFilter.narration) currentFilter.narration = "0";

    if (table) table.setData(journalVoucherListUrl);
}

function clearFilter() {
    const defaultStart = formatDateToDMY(FINANCIAL_YEAR_START);
    const defaultEnd   = formatDateToDMY(FINANCIAL_YEAR_END_OR_TODAY());

    $("#start_date").val(defaultStart);
    $("#end_date").val(defaultEnd);
    setFieldValue($("#account_id").get(0), "");
    setFieldValue($("#narration").get(0), "0");
    setFieldValue($("#full_narration").get(0), "");
    setFieldValue($("#voucher_no").get(0), "");
    $("#show_deleted").prop("checked", false);

    activeVoucherId = null;

    currentFilter = {
        start_date: FINANCIAL_YEAR_START,
        end_date:   FINANCIAL_YEAR_END_OR_TODAY(),
        narration:  "0",
        voucher_no: "",
        show_deleted: 0,
    };

    $("#voucher_reference").html(
        `<tr>
            <td colspan="5" class="text-center text-muted fw-semibold py-3">
                Click on any row to view <span class="text-primary">reference details</span>
            </td>
        </tr>`
    );

    if (table) table.setData(journalVoucherListUrl);
}

function getFieldValue(el) {
    if (!el) return "";
    return $(el).val();
}

function setFieldValue(el, value) {
    if (!el) return;
    const $el = $(el);
    $el.val(value);
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.trigger("change");
    }
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

    $("#voucher_reference").html(
        `<tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border spinner-border-sm text-primary ms-3"></div>
                Loading...
            </td>
        </tr>`
    );

    const url = journalVoucherReferenceUrl.replace("__ID__", voucherId);

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
                        hasRefs       = true;
                        const type    = ref.method === "new_ref" ? "New Ref" : "Agst Ref";
                        const amount  = ref.ref_amount ? formatIndianNumber(ref.ref_amount) : "";
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
            $("#voucher_reference").html(
                `<tr>
                    <td colspan="5" class="text-center text-danger fw-semibold py-3">
                        Failed to fetch reference data.
                    </td>
                </tr>`
            );
        },
    });
}
// View Payment Voucher Details
$(document).on("click", ".view", function (event) {
  const voucherId = $(this).data("id");
  console.log(voucherId);

  const url = journalVoucherReferenceUrl.replace("__ID__", voucherId);

  $.ajax({
    url: url,
    type: "GET",
    beforeSend: function () {
      showLoader("Loading Debit Note Voucher...");
    },
    success: function (response) {
      if (!response.success || !response.data) {
        showToast(
          "error",
          response.message || "Failed to load journal voucher details.",
        );
        return;
      }

      const data = response.data;
      const voucher = data.voucher;

      // Populate Voucher Date and Number
      $("#view_voucher_date").text(formatDateToDMY(voucher.voucher_date));
      $("#view_voucher_number").text(voucher.voucher_serial || "--");
      $("#view_narration").text(voucher.narration || "--");

      const secNarration = voucher.secondary_narration || "";
      $("#view_secondary_narration").text(secNarration || "--");
      $("#view_secondary_narration_row").toggle(!!secNarration);

      // Populate Voucher Details Table
      let detailsHtml = "";
      let totalDebit = 0;
      let totalCredit = 0;
      let customerName = "";

      if (voucher.details && voucher.details.length > 0) {
        voucher.details.forEach((detail, index) => {
          const debit = parseFloat(detail.debit) || 0;
          const credit = parseFloat(detail.credit) || 0;
          totalDebit += debit;
          totalCredit += credit;

          if (detail.is_party_account) {
            customerName = detail.account_name;
          }

          detailsHtml += `
            <tr>
              <td class="text-center font-monospace">${index + 1}</td>
              <td>${detail.account_name || ""}</td>
              <td class="text-end font-monospace">${debit > 0 ? formatIndianNumber(debit) : ""}</td>
              <td class="text-end font-monospace">${credit > 0 ? formatIndianNumber(credit) : ""}</td>
            </tr>
          `;
        });
      } else {
        detailsHtml = `
          <tr>
            <td colspan="4" class="text-center text-muted py-3">No details available for this voucher.</td>
          </tr>
        `;
      }

      // Fallback for customer name if not found via is_party_account
      if (!customerName && voucher.details && voucher.details.length > 0) {
        const firstParty = voucher.details.find((d) => d.is_party_account);
        if (firstParty) {
          customerName = firstParty.account_name;
        } else {
          const firstDebit = voucher.details.find(
            (d) => (parseFloat(d.debit) || 0) > 0,
          );
          if (firstDebit) {
            customerName = firstDebit.account_name;
          }
        }
      }

      $("#view_customer_name").text(customerName || "--");
      $("#view_voucher_details_tbody").html(detailsHtml);
      $("#view_total_debit").text(formatIndianNumber(totalDebit));
      $("#view_total_credit").text(formatIndianNumber(totalCredit));

      // Populate Bill Break-Up Table
      let breakUpHtml = "";
      let breakUpIndex = 1;

      if (voucher.details && voucher.details.length > 0) {
        voucher.details.forEach((detail) => {
          if (detail.refs && detail.refs.length > 0) {
            detail.refs.forEach((ref) => {
              const method = ref.method === "new_ref" ? "New Ref" : "Agst Ref";
              const refAmount = parseFloat(ref.ref_amount) || 0;
              const drCr =
                ref.transaction_type ||
                (parseFloat(detail.debit) > 0 ? "Dr" : "Cr");
              const qtyVal = ref.qty ? parseFloat(ref.qty) : null;
              const pQtyVal = ref.p_qty ? parseFloat(ref.p_qty) : null;

              breakUpHtml += `
                <tr>
                  <td class="text-center font-monospace">${breakUpIndex++}</td>
                  <td>${method}</td>
                  <td>${ref.ref_number || ""}</td>
                  <td>${ref.ref_date ? formatDateToDMY(ref.ref_date) : ""}</td>
                  <td>${ref.show_date ? formatDateToDMY(ref.show_date) : ""}</td>
                  <td class="text-center">${drCr}</td>
                  <td class="text-end font-monospace">${qtyVal !== null ? formatQty(qtyVal) : ""}</td>
                  <td class="text-end font-monospace">${pQtyVal !== null ? formatQty(pQtyVal) : ""}</td>
                  <td class="text-end font-monospace">${formatIndianNumber(refAmount)}</td>
                  <td class="text-end font-monospace">${ref.cd ? formatIndianNumber(ref.cd) : "0.00"}</td>
                  <td class="text-end font-monospace">${ref.tds ? formatIndianNumber(ref.tds) : "0.00"}</td>
                  <td class="text-end font-monospace">${ref.premium ? formatIndianNumber(ref.premium) : "0.00"}</td>
                </tr>
              `;
            });
          }
        });
      }

      if (breakUpHtml === "") {
        $("#view_bill_break_up_section").hide();
      } else {
        $("#view_bill_break_up_section").show();
      }

      $("#view_bill_break_up_tbody").html(breakUpHtml);

      // Store active voucher ID and payment_voucher ID on modal
      $("#debit_note_voucher_modal").data("voucher-id", voucherId);
      $("#debit_note_voucher_modal").data(
        "debit-note-voucher-id",
        data?.journal?.id ?? null,
      );

      if (voucher.deleted_at) {
          $("#btn_entry_reverse").hide();
      } else {
          $("#btn_entry_reverse").show();
      }

      $("#debit_note_voucher_modal").modal("show");
    },
    error: function (xhr) {
      showToast("error", "Oops! Something went wrong. Try again later.");
      console.error(xhr.responseText);
    },
    complete: function () {
      hideLoader();
    },
  });
});


// Delete from table row
$(document).on("click", ".erp-btn-icon.delete", function () {
    const voucherId = $(this).data("id");
    if (!voucherId) return;
    deleteDebitNoteVoucher(voucherId);
});

// Delete / Entry-Reverse from modal
$(document).on("click", "#btn_entry_reverse", function () {
    const voucherId = $("#debit_note_voucher_modal").data("voucher-id");
    if (!voucherId) return;
    deleteDebitNoteVoucher(voucherId, true);
});

function deleteDebitNoteVoucher(voucherId, fromModal = false) {
    Swal.fire({
        title: "Delete Debit Note Voucher?",
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

        const url = journalVoucherDeleteUrl.replace(":id", voucherId);
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
                        text: res.message || "Debit Note Voucher deleted successfully.",
                        timer: 1800,
                        showConfirmButton: false,
                    }).then(() => {
                        activeVoucherId = null;
                        $("#voucher_reference").html(`<tr><td colspan="5" class="text-center text-muted fw-semibold py-3">Click on any row to view <span class="text-primary">reference details</span></td></tr>`);
                        if (table) table.setData(journalVoucherListUrl);
                    });
                } else {
                    Swal.fire({ icon: "error", title: "Cannot Delete", text: res.message || "Failed to delete." });
                }
            },
            error: function (xhr) {
                hideLoader();
                Swal.fire({ icon: "error", title: "Error", text: xhr.responseJSON?.message || "Failed to delete journal voucher." });
            },
        });
    });
}

// Individual Print Action
$(document).on("click", ".erp-btn-icon.print", function () {
    const id = $(this).data("id");
    window.open(journalVoucherIndividualPrintUrl + "/" + id, "_blank");
});

// Modal Print Action
$(document).on("click", "#btn_print_credit_note", function (e) {
    e.preventDefault();
    const id = $("#debit_note_voucher_modal").data("voucher-id");
    printReport(journalVoucherPrintReceiptUrl + "/" + id);
});