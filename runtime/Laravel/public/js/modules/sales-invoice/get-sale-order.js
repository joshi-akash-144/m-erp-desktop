// SALES INVOICE – SALE ORDER ITEM FETCH
// Equivalent of purchase-invoice/get-grn.js for the sales side

var saleOrderData = null;
var origSaleTableHtml = "";

document.addEventListener("DOMContentLoaded", function () {
  var tbody = document.querySelector("#item_table_body");
  if (tbody) origSaleTableHtml = tbody.innerHTML;
});

// ── BIND sales_order_id CHANGE ──────────────────────────────
$(document).on("focus", "#purchase_order_number", function() {
    $(this).data('old-val', $(this).val());
});

$(document).on("blur", "#purchase_order_number", function () {
  var purchaseOrderNumber = $(this).val();
  var oldVal = $(this).data('old-val') || '';
  
  if (purchaseOrderNumber === oldVal) {
      return;
  }
  $(this).data('old-val', purchaseOrderNumber);

  if (!purchaseOrderNumber) {
    unlinkSaleOrder();
    return;
  }
  fetchSaleOrder(purchaseOrderNumber);
});

// -------------------------------------------------------
// FETCH SALE ORDER DETAILS
// -------------------------------------------------------
function fetchSaleOrder(purchaseOrderNumber) {
  if (!purchaseOrderNumber) {
    clearSaleOrder();
    return;
  }

  $.ajax({
    url: salesOrderDetailsRoute.replace(":id", purchaseOrderNumber),
    type: "GET",
    beforeSend: function () {
      showLoader("Fetching Sale Order details…");
    },
    success: function (response) {
      var data = response.data || [];
      if (data.length === 1) {
        saleOrderData = data[0];
        applySaleOrderToForm(saleOrderData);
      } else if (data.length > 1) {
        showPoSelectionModal(data);
      } else {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'No Sale Order data returned'
        });
        unlinkSaleOrder();
      }
    },
    error: function (xhr) {
      saleOrderData = null;
      var msg = "Failed to fetch Sale Order details";
      if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
      }
      Swal.fire({
          icon: 'error',
          title: 'Not Found / Closed',
          text: msg
      });
      unlinkSaleOrder();
    },
    complete: function () {
      hideLoader();
    },
  });
}

function unlinkSaleOrder() {
    var formMode = $('#sales_invoice_form').data('form-mode');
    if (formMode === 'edit') {
        $('#sales_order_id').val('');
        unlockSelect2('#account_id');
        unlockSelect2('.item_id');
        unlockSelect2('.destination_id');
        unlockSelect2('.condition_id');
        $('#sale_type_id').removeClass('non-selectable');
    } else {
        clearSaleOrder();
    }
}

// -------------------------------------------------------
// APPLY SALE ORDER DATA TO FORM
// -------------------------------------------------------
function applySaleOrderToForm(data) {
  $('#sales_order_id').val(data.sales_order_id || '');
  $('#account_id').val(data.account_id).trigger('change');
  lockSelect2('#account_id');

  data.delivery_date
    ? $("#delivery_date").val(formatDateToDMY(data.delivery_date))
    : $("#delivery_date").val("");

  $("#kms").val(data.kms || 0);

  if (data.sales_type_id) {
    $("#sale_type_id")
      .val(data.sales_type_id)
      .trigger("change")
      .addClass("non-selectable");
  } else {
    $("#sale_type_id").val("").removeClass("non-selectable");
  }

  if (data.details && data.details.length) {
    _fillItemRowsFromSaleOrder(data.details);
  }
}

// -------------------------------------------------------
// FILL ITEM ROWS FROM SALE ORDER
// -------------------------------------------------------
function _fillItemRowsFromSaleOrder(details) {
  var $tbody = $("#item_table_body");

  $tbody.html(origSaleTableHtml);
  for (var i = 1; i < details.length; i++) {
    _addSaleItemRow(i);
  }

  // Init select2 on all freshly-restored rows before setting values
  $tbody.find('.item_id, .destination_id, .condition_id').each(function () {
    $(this).select2({ theme: 'bootstrap-5' });
  });

  details.forEach(function (item, index) {
    var $row = $tbody.find(".item-row").eq(index);
    if (!$row.length) return;

    $row.find('.item_id').val(item.item_id || '').trigger('change.select2');
    $row.find('.destination_id').val(item.destination_id || '').trigger('change.select2');
    $row.find('.condition_id').val(item.condition_id || '').trigger('change.select2');

    setTimeout(function () {
      $row.find(".unit_name").val(item.unit_name || "");
    }, 100);

    $row.find(".bag_count").val(item.bag_count || "");
    $row.find(".rate").val(item.rate ? parseFloat(item.rate).toFixed(DECIMALS.RATE) : "");
    $row.find(".inclusive_rate").val(
      item.inclusive_rate ? parseFloat(item.inclusive_rate).toFixed(DECIMALS.INCLUSIVE_RATE) : ""
    );
    $row.find(".amount").val(item.amount ? parseFloat(item.amount).toFixed(DECIMALS.AMOUNT) : "");

    if (!$row.find(".sales_order_id_hidden").length) {
      $row.append(
        '<input type="hidden" class="sales_order_id_hidden" name="items[' +
          index +
          '][sales_order_id]" value="">'
      );
    }
    $row.find(".sales_order_id_hidden").val(item.sales_order_id || "");
  });

  lockSelect2('.item_id');
  lockSelect2('.destination_id');
  lockSelect2('.condition_id');

  _recalculateAll();
}

// -------------------------------------------------------
// ADD A BLANK ITEM ROW (clone from first row)
// -------------------------------------------------------
function _addSaleItemRow(index) {
  var $first = $("#item_table_body .item-row").first();
  if (!$first.length) return;

  var $clone = $first.clone(true);
  var newIndex = index;

  $clone.attr("data-row-id", newIndex + 1);

  // Update name attributes
  $clone.find("[name]").each(function () {
    var name = $(this).attr("name");
    $(this).attr("name", name.replace(/\[\d+\]/, "[" + newIndex + "]"));
  });

  // Reset values
  $clone.find("input:not([type=hidden])").val("");
  $clone.find("select").val("").trigger("change.select2");

  $("#item_table_body").append($clone);
}

// -------------------------------------------------------
// CLEAR SALE ORDER (reset item table)
// -------------------------------------------------------
function clearSaleOrder() {
  saleOrderData = null;
  $('#sales_order_id').val('');

  unlockSelect2('#account_id');
  $('#account_id').val('').trigger('change');
  $('#sale_type_id').removeClass('non-selectable');

  if (origSaleTableHtml) {
    $("#item_table_body").html(origSaleTableHtml);
    $(
      "#item_table_body .item_id, #item_table_body .destination_id, #item_table_body .condition_id",
    ).each(function () {
      if (!$(this).hasClass("select2-hidden-accessible")) {
        $(this).select2({ theme: "bootstrap-5" });
      }
    });
  }
  _recalculateAll();
}

// -------------------------------------------------------
// RECALCULATE TOTALS AFTER ROW FILL
// -------------------------------------------------------
function _recalculateAll() {
  if (typeof calculateTotalQty === "function") calculateTotalQty();
  if (typeof updateItemTotal === "function") updateItemTotal();
}

// -------------------------------------------------------
// MULTIPLE PO ITEM SELECTION LOGIC
// -------------------------------------------------------
var flatPoDetails = [];
var selectedPoIndex = 0;

function showPoSelectionModal(data) {
  flatPoDetails = [];
  selectedPoIndex = 0;
  var $tbody = $("#poSelectionBody");
  $tbody.empty();

  data.forEach(function(po) {
    if (po.details && po.details.length > 0) {
      po.details.forEach(function(d) {
        var remainingQty = parseFloat(d.ordered_qty || 0) - parseFloat(d.received_qty || 0);
        flatPoDetails.push({
          parentPo: po,
          detail: d,
          remainingQty: remainingQty
        });
      });
    }
  });

  flatPoDetails.forEach(function(item, index) {
    var po = item.parentPo;
    var d = item.detail;
    var remainingQty = item.remainingQty;
    var poDate = po.po_date ? po.po_date : (po.delivery_date || '');
    if (poDate && poDate.indexOf('-') > -1) {
       // if not already in DMY, could format it if a helper exists, but let's just output it or use formatDateToDMY if it works on Y-m-d
       // formatDateToDMY exists in common JS, but let's assume it works.
       if (typeof formatDateToDMY === 'function') {
           poDate = formatDateToDMY(poDate);
       }
    }

    var isFirst = (index === 0);
    var row = '<tr class="po-select-row fs-3' + (isFirst ? ' po-row-selected' : '') + '" data-index="' + index + '">' +
      '<td class="po-row-indicator" style="width:36px;text-align:center;">' + (isFirst ? '&#9658;' : '') + '</td>' +
      '<td class="ps-2 text-muted" style="width:36px;">' + (index + 1) + '</td>' +
      '<td class="ps-1">' + (po.order_serial || '') + '</td>' +
      '<td>' + poDate + '</td>' +
      '<td>' + (d.item_name || '') + '</td>' +
      '<td class="text-end">' + parseFloat(d.ordered_qty || 0).toFixed(3) + '</td>' +
      '<td>' + (d.destination_name || '') + '</td>' +
      '<td class="text-end">' + remainingQty.toFixed(3) + '</td>' +
      '<td class="text-end">' + parseFloat(d.inclusive_rate || 0).toFixed(2) + '</td>' +
      '<td class="text-end pe-3">' + (po.delivery_days || 0) + '</td>' +
      '</tr>';
    $tbody.append(row);
  });

  $("#poSelectionModal").modal("show");

  // Focus modal to capture key events
  setTimeout(function() {
    $("#poSelectionModal").focus();
  }, 500);
}

// Click event for rows
$(document).on("click", ".po-select-row", function() {
  var index = $(this).data("index");
  selectPoFromModal(index);
});

// Keyboard navigation
$(document).on("keydown", function(e) {
  if ($("#poSelectionModal").is(":visible")) {
    var $rows = $(".po-select-row");
    if ($rows.length === 0) return;

    if (e.key === "ArrowDown") {
      e.preventDefault();
      selectedPoIndex++;
      if (selectedPoIndex >= $rows.length) selectedPoIndex = 0;
      updatePoSelectionUI();
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      selectedPoIndex--;
      if (selectedPoIndex < 0) selectedPoIndex = $rows.length - 1;
      updatePoSelectionUI();
    } else if (e.key === "Enter") {
      e.preventDefault();
      selectPoFromModal(selectedPoIndex);
    }
  }
});

function updatePoSelectionUI() {
  // Remove highlight + indicator from all rows
  $(".po-select-row").removeClass("po-row-selected");
  $(".po-select-row .po-row-indicator").html('');

  // Apply vivid highlight to the active row
  var $selectedRow = $(".po-select-row[data-index='" + selectedPoIndex + "']");
  $selectedRow.addClass("po-row-selected");
  $selectedRow.find(".po-row-indicator").html('&#9658;');

  if ($selectedRow.length) {
    $selectedRow[0].scrollIntoView({ block: "nearest", behavior: "smooth" });
  }
}

function selectPoFromModal(index) {
  var selected = flatPoDetails[index];
  if (selected) {
    var clonedPo = JSON.parse(JSON.stringify(selected.parentPo));
    clonedPo.details = [selected.detail];
    
    saleOrderData = clonedPo;
    applySaleOrderToForm(saleOrderData);
    $("#poSelectionModal").modal("hide");
    
    // Return focus to next field if needed
    $("#grn_number").focus();
  }
}
