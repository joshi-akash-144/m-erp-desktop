// ============================================================
//  Account voucher modal
// ============================================================

function openLedgerSettingModal() {
    $('#ledger_setting_modal').modal('show');

    // LOAD DROPDOWNS FIRST
    populateLedgerSelectsModal();

    // THEN SET SELECTED VALUES
    setTimeout(function () {
        loadLedgerSettings();
    }, 100);
}


// ============================================================
//  POPULATE LEDGER SELECTS FROM IN-MEMORY allLedgerAccounts
// ============================================================

function populateLedgerSelectsModal() {
    if (typeof allLedgerAccounts === 'undefined' || !Array.isArray(allLedgerAccounts)) return;

    const options = allLedgerAccounts.map(function (acc) {
        return { id: acc.id, text: acc.name + (acc.code ? ' [' + acc.code + ']' : '') };
    });

    const ledgerSelectIds = ['#bank_ledger_id', '#tds_ledger_id', '#rebate_ledger_id', '#premium_ledger_id', '#penalty_ledger_id', '#other_ledger_id'];

    ledgerSelectIds.forEach(function (selector) {
        const $el = $(selector);
        if (!$el.length) return;

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            width: '100%',
            theme: 'bootstrap-5',
            allowClear: true,
            placeholder: 'Select Ledger…',
            dropdownParent: $('#ledger_setting_modal'),
            data: [{ id: '', text: '' }].concat(options),
        });
    });
}

function loadLedgerSettings() {
    $.ajax({
        url: settingVoucherShowUrl,
        type: 'GET',

        success: function (response) {
            if (!response.data || !response.data.value) {
                return;
            }

            let value = response.data.value;

            // ============================================================
            // BANK
            // ============================================================

            if (value.bank_ledger_id) {

                $('#bank_ledger_id')
                    .val(String(value.bank_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // TDS
            // ============================================================

            if (value.tds_ledger_id) {

                $('#tds_ledger_id')
                    .val(String(value.tds_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // REBATE
            // ============================================================

            if (value.rebate_ledger_id) {

                $('#rebate_ledger_id')
                    .val(String(value.rebate_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // PREMIUM
            // ============================================================

            if (value.premium_ledger_id) {

                $('#premium_ledger_id')
                    .val(String(value.premium_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // PENALTY
            // ============================================================

            if (value.penalty_ledger_id) {

                $('#penalty_ledger_id')
                    .val(String(value.penalty_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // OTHER
            // ============================================================

            if (value.other_ledger_id) {

                $('#other_ledger_id')
                    .val(String(value.other_ledger_id))
                    .trigger('change');
            }
        }
    });
}

// ============================================================
// SAVE / UPDATE LEDGER SETTINGS
// ============================================================

function saveLedgerSettings() {
    let payload = {

        value: {
            bank_ledger_id: $('#bank_ledger_id').val(),
            tds_ledger_id: $('#tds_ledger_id').val(),
            rebate_ledger_id: $('#rebate_ledger_id').val(),
            premium_ledger_id: $('#premium_ledger_id').val(),
            penalty_ledger_id: $('#penalty_ledger_id').val(),
            other_ledger_id: $('#other_ledger_id').val(),
        }
    };
    $.ajax({

        url: settingVoucherStoreUrl,
        type: 'POST',
        data: payload,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

        beforeSend: function () {
            $('#save_ledger_setting_btn')
                .prop('disabled', true)
                .html(`
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Saving...
                `);
        },

        success: function (response) {
            showToast('success', response.message);
            $('#ledger_setting_modal').modal('hide');
            populateLedgerSelects();
        },

        error: function (xhr) {
            console.log(xhr.responseText);
            showToast('error', 'Something went wrong');
        },

        complete: function () {
            $('#save_ledger_setting_btn')
                .prop('disabled', false)
                .html(`
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Save Settings
                `);
        }
    });
}

$(document).on('click', '#save_ledger_setting_btn', function () {

    saveLedgerSettings();

});