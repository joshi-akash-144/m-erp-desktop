// -------------------------------------------------------
// GRN MODULE — Purchase Invoice (Create & Edit)
// Shared by both create.js and edit.js
// -------------------------------------------------------

// ── STATE ───────────────────────────────────────────────
var grnData = null;
var originalTableHtml = '';

document.addEventListener('DOMContentLoaded', function () {
  originalTableHtml = document.querySelector('#item_table_body').innerHTML;
});

// ── BIND grn_id CHANGE ──────────────────────────────────
$(document).on('change', '#grn_id', function () {
  fetchGrn($(this).val());
});

// -------------------------------------------------------
// FETCH GRN DETAILS
// -------------------------------------------------------
function fetchGrn(grnId = null) {
  if (!grnId) {
    grnId = $('#grn_id').val();
  }

  if (!grnId) {
    clearGrn();
    return;
  }

  $('#grn_details_loader').removeClass('d-none');

  $.ajax({
    url: grnDetailsRoute,
    type: 'GET',
    data: { grn_id: grnId },
    beforeSend: function () {
      showLoader('Fetching GRN details…');
    },
    success: function (response) {
      grnData = response.data || null;
      if (grnData) {
        applyGrnToForm(grnData);
        _showRefetchBtn();
      } else {
        showToast('error', 'No GRN data returned');
      }
    },
    error: function () {
      grnData = null;
      showToast('error', 'Failed to fetch GRN details');
    },
    complete: function () {
      hideLoader();
      $('#grn_details_loader').addClass('d-none');
    },
  });
} 

// -------------------------------------------------------
// APPLY GRN DATA TO FORM  (used on grn_id change)
// ------------------------------------------ -------------
var gstType = '';
function applyGrnToForm(data) {
  console.log("data", data);

  gstType = data.gst_type;
  let grnOutDate = data.grn_out_date ? formatDateToDMY(data.grn_out_date) : null;
  let formatGstType = gstType === GST_TYPE.LOCAL ? "LOCAL" : "INTERSTATE";

    $('#account_id').val(data.account_id || '').trigger('change.select2');
    $('#account_id_city').val(data.account.city || '');
  $('#account_id_type').val(formatGstType || '');
    $('#broker_id').val(data.broker_id || '').trigger('change.select2');
    $('#vehicle_number').val(data.vehicle_number || '').prop('readonly', true);
    $('#reference_number').val(data.reference_number|| '').prop('readonly', true);
    if (data.grn_out_date) $('#invoice_date').val(grnOutDate);

    lockSelect2('#broker_id');
    lockSelect2('#account_id');
    if (data.details && data.details.length) {
      _fillItemRowsFromGrn(data.details);
    }
  getSupplierTurnOver(data.account_id);

  // Populate penalty bill sundry (code 1007) when creating a new invoice
  var formMode = $('#purchase_invoice_form').data('form-mode');
  if (formMode === 'create') {
    var penaltyAmount = data.penalty && parseFloat(data.penalty) > 0 ? parseFloat(data.penalty) : 0;
    if (typeof billSundryData !== 'undefined') {
      var penaltySundry = billSundryData.find(function (s) { return String(s.code) === '1007'; });
      if (penaltySundry) {
        $('.particular_id').each(function () {
          if (String($(this).val()) === String(penaltySundry.id)) {
            var row = $(this).data('row');
            if (penaltyAmount > 0) {
                $('#particular_value_' + row).val(penaltyAmount.toFixed(DECIMALS.AMOUNT));
            } else {
                $('#particular_value_' + row).val('0.00');
            }
          }
        });
        if (typeof BillSundry !== 'undefined') BillSundry.calculate();
      }
    }
  }
}

  // -------------------------------------------------------
  // FILL ITEM ROWS WITH GRN DATA
  // -------------------------------------------------------
  function _fillItemRowsFromGrn(details) {
  details.forEach(function (item, index) {
    var $row = $('#item_table_body').find('.item-row').eq(index);
    if (!$row.length) {
      var $firstRow = $('#item_table_body').find('.item-row').first();
      $row = $firstRow.clone();

      $row.find('.select2-container').remove();
      $row.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');

      $row.find('input, select, textarea').each(function () {
        if (this.name) {
          this.name = this.name.replace(/items\[\d+\]/, 'items[' + index + ']');
        }
      });

      $('#item_table_body').append($row);
      $row.find('.item_id, .destination_id, .condition_id').select2({ theme: 'bootstrap-5' });
    }


    $row.find('.item_id').val(item.item_id || '').trigger('change.select2');
    $row.find('.destination_id').val(item.destination_id || '').trigger('change.select2');
    $row.find('.condition_id').val(item.condition_id || '').trigger('change.select2');

    $row.find('.quantity').val(formatQty(item.quantity) || '').prop('readonly', true);
    $row.find('.party_quantity').val(formatQty(item.party_quantity) || '').prop('readonly', true);
    $row.find('.bag_count').val(item.bag_count || '').prop('readonly', true);
    $row.find('.unit_name').val(item?.item.unit.name || '').prop('readonly', true);
    $row.find('.rate').val(item.rate || '').prop('readonly', true);
    $row.find('.inclusive_rate').val(item.inclusive_rate || '').prop('readonly', true);
    $row.find('.amount').val(item.amount || '').prop('readonly', true);
    $row.find('.purchase_order_serial').val(item.purchase_order_serial || '');
    $row.find('.purchase_order_id').val(item.purchase_order_id || '');
    $row.find('.purchase_order_item_id').val(item.purchase_order_item_id || '');

    let localGstType = item?.item.purchase_type_local_id;
    let interstateGstType = item?.item.purchase_type_interstate_id;

    if (gstType == 'local' && localGstType) {
      $('#purchase_type_id').val(localGstType).trigger('change');
    }

    if (gstType == 'interstate' && interstateGstType) {
      $('#purchase_type_id').val(interstateGstType).trigger('change');
    }
  });

  lockSelect2('.item_id');
  lockSelect2('.destination_id');
  lockSelect2('.condition_id');

  // In edit mode, calculations are triggered only via the Refetch button — not on auto-fill
  var formMode = $('#purchase_invoice_form').data('form-mode');
  if (formMode !== 'edit') {
    calculateTotalQty();
    updateItemTotal();
  }
}

// -------------------------------------------------------
// CLEAR GRN → unlock selects and reset header fields
// -------------------------------------------------------
function clearGrn() {
  grnData = null;
  console.log('call this');

  _hideRefetchBtn();

  $('#account_id').val('').trigger('change.select2');
  $('#account_id_city').val('');
  $('#account_id_type').val('');
  $('#broker_id').val('').trigger('change.select2');
  $('#vehicle_number').val('').prop('readonly', false);
  $('#reference_number').val('').prop('readonly', false);

  $('#item_table_body .item-row:not(:first)').remove();

  $('.item_id').val('').trigger('change.select2');
  $('.destination_id').val('').trigger('change.select2');
  $('.condition_id').val('').trigger('change.select2');

  $('.bag_count').prop('readonly', false).val('');
  $('.party_quantity').prop('readonly', false).val('');
  $('.inclusive_rate').prop('readonly', false).val('');
  $('.rate').prop('readonly', false).val('');
  $('.quantity').prop('readonly', false).val('');
  $('.amount').val('');
  $('.unit_name').val('');
  $('.cgst_rate').val('--');
  $('.sgst_rate').val('--');
  $('.igst_rate').val('--');
  $('.purchase_order_serial').val('');
  $('.purchase_order_id').val('');
  $('.purchase_order_item_id').val('');

  if (typeof updateItemTotal === 'function') updateItemTotal();
  if (typeof calculateTotalQty === 'function') calculateTotalQty();

  unlockSelect2('.item_id');
  unlockSelect2('.destination_id');
  unlockSelect2('.condition_id');
  unlockSelect2('#broker_id');
  unlockSelect2('#account_id');

}

// -------------------------------------------------------
// REFETCH GRN — re-fetches the currently selected GRN
// -------------------------------------------------------
function refetchGrn() {
  var grnId = $('#grn_id').val();
  if (!grnId) {
    showToast('error', 'No GRN selected to refetch.');
    return;
  }

  var $btn = $('#btn_refetch_grn');
  $btn.prop('disabled', true).html('<i class="fa-solid fa-rotate fa-spin me-1"></i> Fetching…');

  $.ajax({
    url: grnDetailsRoute,
    type: 'GET',
    data: { grn_id: grnId },
    success: function (response) {
      grnData = response.data || null;
      if (grnData) {
        if (typeof _resetItemTable === 'function') _resetItemTable();
        applyGrnToForm(grnData);
        // Explicitly recalculate after refetch — required in edit mode where auto-calc is suppressed
        if (typeof calculateTotalQty === 'function') calculateTotalQty();
        if (typeof updateItemTotal === 'function') updateItemTotal();
        showToast('success', 'GRN data refreshed successfully.');
      } else {
        showToast('error', 'No GRN data returned.');
      }
    },
    error: function () {
      showToast('error', 'Failed to refetch GRN details.');
    },
    complete: function () {
      $btn.prop('disabled', false).html('<i class="fa-solid fa-rotate me-1"></i> Refetch GRN');
    }
  });
}

// ── Button visibility helpers ────────────────────────────
function _showRefetchBtn() {
  $('#btn_refetch_grn').removeClass('d-none');
}

function _hideRefetchBtn() {
  $('#btn_refetch_grn').addClass('d-none');
}
