// ============================================================
//  PAYMENT RECEIVABLE — receivable.js
//  Mirrors payable.js pattern; ledger selects use in-memory
//  allLedgerAccounts injected from the blade.
// ============================================================

// Global counter to track bill selection order
let _selectionCounter = 0;

$(document).ready(function () {

    // ── 1. INITIALISE ───────────────────────────────────────
    initDateInput();
    bindSelect2();
    populateLedgerSelects();

    new DateInput("#receipt_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // check within financial year date
    $("#receipt_date").on("blur", function () {
        let invDate = formatDateToYMD($(this).val());
        if (!invDate) return;
        if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
            showToast(
                "error",
                `Date must be within Financial Year:<br>(${formatDateToDMY(
                    FINANCIAL_YEAR_START,
                )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
                9000,
            );
            $(this).val("");
        }
    });


    // ── 2. FILTER: Show ─────────────────────────────────────
    $('#filter_apply').on('click', function (e) {
        e.preventDefault();
        fetchReceivables();
    });

    // ── 3. FILTER: Reset ────────────────────────────────────
    $('#filter_reset').on('click', function () {
        // Clearing account_id triggers the #account_id change handler
        // which resets the table, amounts, ledger, outstanding, etc.
        $('#account_id').val(null).trigger('change');
    });

    // ── 4. SELECT ALL ───────────────────────────────────────
    $(document).on('change', '#all_check', function () {
        const checked = $(this).is(':checked');
        $(this).prop('indeterminate', false);

        if (checked) {
            // Assign selection order in DOM sequence
            _selectionCounter = 0;
            $('#payment_receivable_main_body .row-checkbox').each(function () {
                _selectionCounter++;
                $(this).prop('checked', true);
                $(this).closest('tr').data('selection-order', _selectionCounter);
            });
        } else {
            // Clear selection order from all rows
            _selectionCounter = 0;
            $('#payment_receivable_main_body .row-checkbox').each(function () {
                $(this).prop('checked', false);
                $(this).closest('tr').removeData('selection-order');
            });
        }

        $('#payment_receivable_main_body tr').toggleClass('table-primary bg-opacity-10', checked);
        calculateCheckedTotal();
    });

    // ── 5. INDIVIDUAL ROW CHECKBOX ──────────────────────────
    $(document).on('change', '#payment_receivable_main_body .row-checkbox', function () {
        const total   = $('#payment_receivable_main_body .row-checkbox').length;
        const checked = $('#payment_receivable_main_body .row-checkbox:checked').length;
        const $master = $('#all_check');
        const $row    = $(this).closest('tr');

        if (this.checked) {
            // Assign next order number when checked
            _selectionCounter++;
            $row.data('selection-order', _selectionCounter);
        } else {
            // Remove order when unchecked, then compact remaining numbers
            $row.removeData('selection-order');
            _recompactSelectionOrder();
        }

        if (checked === 0) {
            $master.prop('checked', false).prop('indeterminate', false);
        } else if (checked === total) {
            $master.prop('checked', true).prop('indeterminate', false);
        } else {
            $master.prop('checked', false).prop('indeterminate', true);
        }

        $row.toggleClass('table-primary bg-opacity-10', this.checked);
        calculateCheckedTotal();
    });

    // Removed updateSerialNumbers from here (moved to global scope)

    // ── 6. RECEIVE-AMOUNT EDITED → RECALCULATE ──────────────
    $(document).on('input', '#payment_receivable_main_body .receive-amount', function () {
        calculateCheckedTotal();
    });

    // ── 7. FILTER BILL-NO (client-side hide/show) ───────────
    $(document).on('input', '#filter_bill_no', function () {
        const query = $(this).val().toLowerCase().trim();
        $('#payment_receivable_main_body tr').each(function () {
            const billNo = $(this).find('td:nth-child(3)').text().toLowerCase();
            $(this).toggleClass('d-none', query.length > 0 && billNo.indexOf(query) === -1);
        });
    });

    // ── 8. FIND (tick checkbox on Enter) ────────────────────
    $(document).on('keydown change', '#find_bill_no', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.keyCode !== 13 && e.which !== 13) {
            return; // Ignore other keys
        }

        e.preventDefault();
        const query = $(this).val().toLowerCase().trim();
        if (!query) return;

        let found = false;
        $('#payment_receivable_main_body tr').each(function () {
            const $cb = $(this).find('.row-checkbox');
            // Skip empty placeholder rows
            if ($cb.length === 0) return;

            // Get exact bill number from data attribute to avoid formatting/whitespace issues
            const $refCell = $(this).find('.reference-number');
            let billNo = '';
            if ($refCell.length) {
                billNo = ($refCell.attr('data-reference-number') || $refCell.text()).toLowerCase().trim();
            } else {
                billNo = $(this).find('td:nth-child(3)').text().toLowerCase().trim();
            }

            // Strictly exact match only
            if (billNo === query) {
                if (!$cb.is(':checked')) {
                    $cb.prop('checked', true).trigger('change');
                }
                $(this)[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $(this).addClass('table-warning').delay(1500).queue(function () {
                    $(this).removeClass('table-warning').dequeue();
                });
                found = true;
                return false; // break loop on first exact match
            }
        });

        if (!found) {
            showToast('error', 'No exact bill found for: ' + query);
        }
        // Clear and refocus
        setTimeout(() => {
            $(this).val('').focus();
        }, 100);
    });

    // ── 9. ONLY SELECTED (Open Modal) ───────────────────────
    $(document).on('click', '#only_selected', function (e) {
        e.preventDefault();
        const $checkedRows = $('#payment_receivable_main_body .row-checkbox:checked').closest('tr');
        if ($checkedRows.length === 0) {
            showToast('info', 'Please select at least one bill.');
            return;
        }

        showLoader('Preparing selected bills…');

        // Small timeout so loader renders before the DOM work
        setTimeout(function () {
            // Sort rows by selection order before populating modal
            const sortedRows = $checkedRows.toArray().sort(function (a, b) {
                const orderA = $(a).data('selection-order') || 0;
                const orderB = $(b).data('selection-order') || 0;
                return orderA - orderB;
            });

            // Populate modal table
            const $modalTbody = $('#payment_receivable_table_body');
            $modalTbody.empty();

            let totalReceive = 0;

            sortedRows.forEach(function (row) {
                const $row = $(row);
                const srNo  = $row.find('td:nth-child(2)').text();
                const billNo = $row.find('td:nth-child(3)').text();
                const dt    = $row.find('td:nth-child(4)').text();
                const amt   = $row.find('td:nth-child(5)').text();
                const recv  = $row.find('td:nth-child(6) input').val() || $row.find('td:nth-child(6)').text();
                const drCr  = $row.find('td:nth-child(7)').text();

                // Accumulate receive total
                totalReceive += parseFloat((recv || '0').replace(/,/g, '')) || 0;

                const isEven   = (sortedRows.indexOf(row) % 2 === 0);
                const rowBg    = isEven ? '' : 'style="background:#f8faff;"';
                const drCrBadge = drCr.trim().toUpperCase() === 'DR'
                    ? `<span class="badge bg-danger-lt text-danger fw-semibold px-2">DR</span>`
                    : `<span class="badge bg-success-lt text-success fw-semibold px-2">CR</span>`;

                $modalTbody.append(`
                    <tr ${rowBg}>
                        <td class="text-center py-2 px-4">
                            <span class="badge bg-primary-lt text-primary fw-bold" style="min-width:28px;">${srNo}</span>
                        </td>
                        <td class="text-center fw-semibold py-2 px-4">${billNo}</td>
                        <td class="text-center text-muted py-2 px-4">${dt}</td>
                        <td class="text-end fw-bold py-2 px-4">${amt}</td>
                        <td class="text-end fw-bold text-primary py-2 px-4">${recv}</td>
                        <td class="text-center py-2 px-4">${drCrBadge}</td>
                    </tr>
                `);
            });

            // Update footer total
            const fmt = (typeof formatIndianNumber === 'function')
                ? formatIndianNumber(totalReceive.toFixed(2))
                : totalReceive.toFixed(2);
            $('#modal_total_receive').text(fmt);

            hideLoader();

            // Show modal
            $('#payment_receivable_modal').modal('show');
        }, 50);
    });

    // ── 10. CASCADE: bank → tds → rebate → premium → penalty → other ──
    //
    //  Rules:
    //   • Fires ONLY on Enter key
    //   • When a field is changed, ALL fields below it are cleared first,
    //     then remaining is calculated and cascade-fills the next field.
    //
    //  Accounting model (Receipt Voucher):
    //   Base    = total_amount  (bills selected — Cr side = customer)
    //   Bank    = Dr  → remaining fills TDS
    //   TDS     = Dr  → remaining fills Rebate
    //   Rebate  = Dr  → remaining fills Premium
    //   Premium = Cr  → ADDS back to remaining, fills Penalty
    //   Penalty = Dr  → remaining fills Other
    //   Other   = Dr or Cr (per other_transaction_type)

    function handleBankEnter() {
        const $bank = $('#bank_amount');
        const bank = parseLedgerAmt($bank.val());
        $bank.val(bank.toFixed(2));
        // Clear Dr fields below bank (keep premium — it is Cr, user-entered)
        $('#tds_amount, #rebate_amount, #penalty_amount, #other_amount').val('');
        cascadeFill('#tds_amount', calcRemaining(bank, 0, 0, 0, 0));
        recalcTotalOfAccount();
        
    }

    function handleTdsEnter() {
        const $tds = $('#tds_amount');
        const bank = parseLedgerAmt($('#bank_amount').val());
        const tds = parseLedgerAmt($tds.val());
        $tds.val(tds.toFixed(2));
        // Clear Dr fields below tds (keep premium — it is Cr, user-entered)
        $('#rebate_amount, #penalty_amount, #other_amount').val('');
        cascadeFill('#rebate_amount', calcRemaining(bank, tds, 0, 0, 0));
        recalcTotalOfAccount();
    }

    function handleRebateEnter() {
        const $rebate = $('#rebate_amount');
        const bank   = parseLedgerAmt($('#bank_amount').val());
        const tds    = parseLedgerAmt($('#tds_amount').val());
        const rebate = parseLedgerAmt($rebate.val());
        $rebate.val(rebate.toFixed(2));

        const base = parseLedgerAmt($('#total_amount').val());
        const net = parseFloat((base - bank - tds - rebate).toFixed(2));

        // Always clear fields below rebate
        $('#premium_amount, #penalty_amount, #other_amount').val('');

        if (net < 0) {
            // Dr side (bank+tds+rebate) already exceeds Cr base →
            // Premium (Cr) must absorb the deficit to balance
            cascadeFill('#premium_amount', Math.abs(net));
            // penalty stays 0 / empty
        } else {
            // Cr side still exceeds Dr → remaining flows to Penalty (Dr)
            cascadeFill('#penalty_amount', net);
        }

        recalcTotalOfAccount();
    }

    function handlePremiumEnter() {
        const $premium = $('#premium_amount');
        const bank    = parseLedgerAmt($('#bank_amount').val());
        const tds     = parseLedgerAmt($('#tds_amount').val());
        const rebate  = parseLedgerAmt($('#rebate_amount').val());
        const premium = parseLedgerAmt($premium.val());
        $premium.val(premium.toFixed(2));
        // Clear all fields below premium
        $('#penalty_amount, #other_amount').val('');
        // Premium is Cr — adds back to remaining
        cascadeFill('#penalty_amount', calcRemaining(bank, tds, rebate, premium, 0));
        recalcTotalOfAccount();
    }

    function handlePenaltyEnter() {
        const $penalty = $('#penalty_amount');
        const bank    = parseLedgerAmt($('#bank_amount').val());
        const tds     = parseLedgerAmt($('#tds_amount').val());
        const rebate  = parseLedgerAmt($('#rebate_amount').val());
        const premium = parseLedgerAmt($('#premium_amount').val());
        const penalty = parseLedgerAmt($penalty.val());
        $penalty.val(penalty.toFixed(2));
        // Clear field below penalty
        $('#other_amount').val('');
        cascadeFill('#other_amount', calcRemaining(bank, tds, rebate, premium, penalty));
        recalcTotalOfAccount();
    }

    function handleOtherEnter() {
        const $other = $('#other_amount');
        const val = parseLedgerAmt($other.val());
        $other.val(val.toFixed(2));
        recalcTotalOfAccount();
    }

    // ── 11. LEDGER SELECTS CHANGE → LOAD CLOSING BALANCE ────
    $(document).on('change', '#bank, #tds, #rebate, #premium, #penalty, #other', function () {
        const accountId = $(this).val();
        const $amtInput = $(this).closest('tr').find('input[type="text"]');
        if (!accountId) {
            $amtInput.val('');
            return;
        }
        // fetchClosingBalance(accountId, $amtInput);
    });

    // ── 12. OTHER DR/CR TYPE CHANGE → RECALCULATE ───────────
    $(document).on('change', '#other_transaction_type', function () {
        recalcTotalOfAccount();
    });

    // ── 13. RESET LEDGER TOTAL BUTTON ───────────────────────
    $(document).on('click', '.reset-ledger-btn', function () {
        $('#bank_amount, #tds_amount, #rebate_amount, #premium_amount, #penalty_amount, #other_amount').val('');
        $('#total_of_account').val('0.00');
        recalcTotalOfAccount(); // refresh Dr/Cr summary panel
    });

    // ── 13.5 ENTER & BLUR KEY SUPPORT ON AMOUNTS ────────────────
    $(document).on('blur keydown', '#bank_amount, #tds_amount, #rebate_amount, #premium_amount, #penalty_amount, #other_amount', function (e) {
        // If it's a keydown event, ONLY proceed if it's the Enter key
        if (e.type === 'keydown' && e.key !== 'Enter' && e.keyCode !== 13 && e.which !== 13) {
            return;
        }

        if (e.type === 'keydown') {
            e.preventDefault(); // Stop form submission on Enter
        }

        // Execute the specific cascade handler based on which input triggered
        const id = $(this).attr('id');
        if (id === 'bank_amount')     handleBankEnter();
        else if (id === 'tds_amount')     handleTdsEnter();
        else if (id === 'rebate_amount')  handleRebateEnter();
        else if (id === 'premium_amount') handlePremiumEnter();
        else if (id === 'penalty_amount') handlePenaltyEnter();
        else if (id === 'other_amount')   handleOtherEnter();

        // Advance focus only if triggered by Enter key
        if (e.type === 'keydown') {
            if (typeof moveFocusToNextField === 'function') {
                moveFocusToNextField($(this));
            } else {
                // Fallback: move to next visible, non-readonly input/select
                const focusables = $('input, select, textarea').not(':disabled').not('[readonly]').filter(':visible');
                const idx = focusables.index(this);
                if (idx > -1 && idx < focusables.length - 1) {
                    focusables.eq(idx + 1).focus();
                }
            }
        }
    });

    // ── 14. SAVE RECEIPT VOUCHER ─────────────────────────────
    $(document).on('click', '#create_payment_voucher', function (e) {
        e.preventDefault();
        saveReceiptVoucher();
    });

    // ── 15. ACCOUNT CHANGE → FULL RESET ─────────────────────
    $(document).on('change', '#account_id', function () {
        // Reset bill table
        clearTable();

        // Reset outstanding & bill count
        updateOutstanding(0);
        $('#total_bill_count').text(0);

        // Reset ledger balance
        $('#ledger_balance').val('');

        // Reset total amount
        $('#total_amount').val('0.00');

        // Reset all_check state
        $('#all_check').prop('checked', false).prop('indeterminate', false);

        // Reset ledger amounts
        $('#bank_amount, #tds_amount, #rebate_amount, #premium_amount, #penalty_amount, #other_amount').val('');

        // Reset total of account
        $('#total_of_account').val('0.00')
            .removeClass('text-success border-success text-danger border-danger')
            .addClass('text-primary border-primary bg-primary bg-opacity-10');

        // Reset Dr/Cr summary panel
        recalcTotalOfAccount();

        // Reset selection order counter
        _selectionCounter = 0;
    });

    $('#receipt_date').val(currentDate()).select();

});

// ============================================================
//  SAVE RECEIPT VOUCHER
// ============================================================
function saveReceiptVoucher() {
    // 1. Collect References (bills)
    const references = [];
    let baseAmountTotal = 0;

    // Collect checked rows and sort by user's selection order
    const checkedRows = $('#payment_receivable_main_body .row-checkbox:checked').map(function () {
        return $(this).closest('tr')[0];
    }).toArray().sort(function (a, b) {
        const orderA = $(a).data('selection-order') || 0;
        const orderB = $(b).data('selection-order') || 0;
        return orderA - orderB;
    });

    checkedRows.forEach(function (rowEl) {
        const $row = $(rowEl);

        const voucher_id = $row.data('voucher-id');
        const reference_id = $row.data('reference-id');
        const source_id = $row.data('source-id');
        const source_type = $row.data('source-type');
        const selectionOrder = $row.data('selection-order') || 0;

        const recvAmountStr = $row.find('.receive-amount').val();
        const recvAmount = parseFloat((recvAmountStr || '0').replace(/,/g, ''));

        if (recvAmount > 0) {
            references.push({
                order: selectionOrder,
                voucher_id: voucher_id,
                reference_id: reference_id,
                account_id: $row.data('account-id') || $('#account_id').val(),
                source_id: source_id,
                source_type: source_type,
                payment_amount: recvAmount
            });
            baseAmountTotal += recvAmount;
        }
    });

    if (references.length === 0) {
        showToast('error', 'Please select at least one bill and ensure it has a receive amount.');
        return;
    }

    // 2. Main Voucher Details
    const account_id = $('#account_id').val();
    if (!account_id) {
        showToast('error', 'Please select an Account (Customer).');
        return;
    }

    const rawDate = $('#receipt_date').val();
    if (!rawDate) {
        showToast('error', 'Please enter a valid Receipt Date.');
        return;
    }
    const receipt_date = formatDateToYMD(rawDate);

    // 3. Ledger Amounts & Accounts
    const bank_amount = parseLedgerAmt($('#bank_amount').val());
    const bank_account_id = $('#bank').val();

    const tds_amount = parseLedgerAmt($('#tds_amount').val());
    const tds_account_id = $('#tds').val();

    const rebate_amount = parseLedgerAmt($('#rebate_amount').val());
    const rebate_account_id = $('#rebate').val();

    const premium_amount = parseLedgerAmt($('#premium_amount').val());
    const premium_account_id = $('#premium').val();

    const penalty_amount = parseLedgerAmt($('#penalty_amount').val());
    const penalty_account_id = $('#penalty').val();

    const other_amount = parseLedgerAmt($('#other_amount').val());
    const other_account_id = $('#other').val();
    const other_transaction_type = $('#other_transaction_type').val(); // 'cr' or 'dr'

    // Validate if amount exists but account is not selected
    if (bank_amount > 0 && !bank_account_id) { showToast('error', 'Please select Bank Account.'); return; }
    if (tds_amount > 0 && !tds_account_id) { showToast('error', 'Please select TDS Account.'); return; }
    if (rebate_amount > 0 && !rebate_account_id) { showToast('error', 'Please select Rebate Account.'); return; }
    if (premium_amount > 0 && !premium_account_id) { showToast('error', 'Please select Premium Account.'); return; }
    if (penalty_amount > 0 && !penalty_account_id) { showToast('error', 'Please select Penalty Account.'); return; }
    if (other_amount > 0 && !other_account_id) { showToast('error', 'Please select Other Account.'); return; }

    // 4. Debit/Credit Balancing Check
    const otherCr = (other_transaction_type === 'cr') ? other_amount : 0;
    const otherDr = (other_transaction_type === 'dr') ? other_amount : 0;

    const drTotal = bank_amount + tds_amount + rebate_amount + penalty_amount + otherDr;
    const crTotal = baseAmountTotal + premium_amount + otherCr;
    const netDr = drTotal - crTotal;

    if (Math.abs(netDr) >= 0.01) {
        showToast('error', 'Cannot save! Debit and Credit sides are not balanced. Difference: ₹ ' + Math.abs(netDr).toFixed(2));
        return;
    }

    // 5. Submit via AJAX
    const payload = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        unique_request_id: $('#request_unique_id').val(),
        account_id: account_id,
        voucher_date: receipt_date,
        
        bank_id: bank_account_id,
        bank_amount: bank_amount.toFixed(2),
        
        tds_id: tds_account_id,
        tds_amount: tds_amount.toFixed(2),
        
        rebate_id: rebate_account_id,
        rebate_amount: rebate_amount.toFixed(2),
        
        premium_id: premium_account_id,
        premium_amount: premium_amount.toFixed(2),
        
        penalty_id: penalty_account_id,
        penalty_amount: penalty_amount.toFixed(2),
        
        other_id: other_account_id,
        other_amount: other_amount.toFixed(2),
        other_type: other_transaction_type,
        
        remarks: $('#narration').val() || null,
        
        references: references
    };

    const storeUrl = typeof receivableStoreUrl !== 'undefined' ? receivableStoreUrl : '';
    if (!storeUrl) {
        showToast('error', 'Store URL is not defined.');
        return;
    }

    const $btn = $('#create_payment_voucher');
    const originalText = $btn.html();

    $.ajax({
        url: storeUrl,
        type: 'POST',
        data: payload,
        beforeSend: function () {
            $btn.html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...').prop('disabled', true);
            showLoader('Saving Receipt Voucher...');
        },
        success: function (response) {
            if (response.success) {
                showToast('success', response.message || 'Receipt Voucher saved successfully!');
                setTimeout(() => {
                    // Redirect or reload
                    if (response.data && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        window.location.reload();
                    }
                }, 1000);
            } else {
                showToast('error', response.message || 'Failed to save voucher.');
            }
        },
        error: function (xhr) {
            if (typeof handleAjaxError === 'function') {
                handleAjaxError(xhr);
            } else {
                showToast('error', 'An error occurred while saving.');
            }
        },
        complete: function () {
            $btn.html(originalText).prop('disabled', false);
            hideLoader();
        }
    });
}


// ============================================================
//  INIT DATE INPUT
// ============================================================
function initDateInput() {
    new DateInput("#receipt_date", FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    // check within financial year date
    $("#receipt_date").on("blur", function () {
        let invDate = formatDateToYMD($(this).val());
        if (!invDate) return;
        if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
            showToast(
                "error",
                `Date must be within Financial Year:<br>(${formatDateToDMY(
                    FINANCIAL_YEAR_START,
                )} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
                9000,
            );
            $(this).val("");
        }
    });

    $('#receipt_date').focus();

}


// ============================================================
//  SELECT2 BINDINGS
// ============================================================
function bindSelect2() {
    // Customer account
    $('#account_id').select2({
        width: '100%',
        theme: 'bootstrap-5',
        placeholder: 'Select Account',
        allowClear: true,
    });

    // Enter key closes dropdown and moves focus
    $(document).on('select2:open', function (e) {
        const $sel = $(e.target);
        const $search = $sel.data('select2').$dropdown.find('.select2-search__field');
        $search.off('keydown.sel2enter').on('keydown.sel2enter', function (ev) {
            if (ev.which === 13) {
                ev.preventDefault();
                $sel.select2('close');
                if (typeof moveFocusToNextField === 'function') moveFocusToNextField($sel);
            }
        });
    });
}


// ============================================================
//  POPULATE LEDGER SELECTS FROM IN-MEMORY allLedgerAccounts
// ============================================================
function populateLedgerSelects() {
    if (typeof allLedgerAccounts === 'undefined' || !Array.isArray(allLedgerAccounts)) return;

    const options = allLedgerAccounts.map(function (acc) {
        return { id: acc.id, text: acc.name + (acc.code ? ' [' + acc.code + ']' : '') };
    });

    const ledgerSelectIds = ['#bank', '#tds', '#rebate', '#premium', '#penalty', '#other'];

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
            data: [{ id: '', text: '' }].concat(options),
        });
    });
    // Auto-select if a default value exists
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

                $('#bank')
                    .val(String(value.bank_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // TDS
            // ============================================================

            if (value.tds_ledger_id) {

                $('#tds')
                    .val(String(value.tds_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // REBATE
            // ============================================================

            if (value.rebate_ledger_id) {

                $('#rebate')
                    .val(String(value.rebate_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // PREMIUM
            // ============================================================

            if (value.premium_ledger_id) {

                $('#premium')
                    .val(String(value.premium_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // PENALTY
            // ============================================================

            if (value.penalty_ledger_id) {

                $('#penalty')
                    .val(String(value.penalty_ledger_id))
                    .trigger('change');
            }

            // ============================================================
            // OTHER
            // ============================================================

            if (value.other_ledger_id) {

                $('#other')
                    .val(String(value.other_ledger_id))
                    .trigger('change');
            }
        }
    });
}


// ============================================================
//  FETCH RECEIVABLES (AJAX)
// ============================================================
function fetchReceivables() {
    const accountId = $('#account_id').val();

    if (!accountId) {
        $('#account_id').focus();
        showToast('error', 'Please select an account first.');
        return;
    }

    closingBalance(accountId, closingBalanceUrl, function (result) {
        let data = result.data;

        let row = data[accountId];

        if (!row) {
            $("#ledger_balance").val("0.00 Dr");
            return;
        }

        let closing = row.closing;

        let formattedBalance = amountDirection(closing);


        $("#ledger_balance").val(formattedBalance);
    });

    $.ajax({
        url: paymentReceivablesUrl,
        type: 'GET',
        beforeSend: function () {
            showLoader('Loading Receivables…');
        },
        data: {
            account_id: accountId,
        },
        success: function (response) {
            if (response.success) {
                _selectionCounter = 0; // reset bill selection order for new account
                $('#payment_receivable_main_body').html(response.data.html);
                $('#unique_request_id').val(response.data.unique_request_id || '');
                $('#all_check').prop('checked', false).prop('indeterminate', false);
                calculateCheckedTotal();

                // Show total bill count
                const total = response.data.reference ? response.data.reference.length : 0;
                $('#total_bill_count').text(total);

                // Outstanding = sum of all pending_amount
                let outstanding = 0;
                if (Array.isArray(response.data.reference)) {
                    response.data.reference.forEach(function (ref) {
                        outstanding += parseFloat(ref.pending_amount) || 0;
                    });
                }
                updateOutstanding(outstanding);
            } else {
                showToast('error', response.message || 'Failed to load data.');
            }
        },
        error: function (xhr) {
            if (typeof handleAjaxError === 'function') {
                handleAjaxError(xhr);
            } else {
                showToast('error', 'An error occurred while loading receivables.');
            }
        },
        complete: function () {
            hideLoader();
        },
    });
}


// ============================================================
//  CALCULATE CHECKED TOTAL  → updates #total_amount
// ============================================================
function calculateCheckedTotal() {
    let total = 0;

    $('#payment_receivable_main_body .row-checkbox:checked').each(function () {
        const $row = $(this).closest('tr');
        const amt  = parseFloat($row.find('.receive-amount').val().replace(/,/g, '')) || 0;
        const direction = $row.data('direction');

        if (direction === 'debit') {
            total += amt;
        } else {
            total -= amt;
        }
    });


    const fmt = (typeof formatIndianNumber === 'function')
        ? formatIndianNumber(total.toFixed(2))
        : total.toFixed(2);

    $('#total_amount').val(fmt);

    // Update serial numbers sequentially
    updateSerialNumbers();
}

// ============================================================
//  UPDATE SERIAL NUMBERS BASED ON SELECTION ORDER
//  Each row stores its selection order in data('selection-order').
//  Checked rows show their order; unchecked rows show '-'.
// ============================================================
function updateSerialNumbers() {
    $('#payment_receivable_main_body tr').each(function () {
        const $cb    = $(this).find('.row-checkbox');
        if ($cb.length === 0) return; // skip placeholder rows

        const $srCol = $(this).find('td:nth-child(2)');
        if ($cb.is(':checked')) {
            const order = $(this).data('selection-order') || '';
            $srCol.text(order);
        } else {
            $srCol.text('-');
        }
    });
}

// ============================================================
//  RECOMPACT SELECTION ORDER
//  After an unchecked row is removed, renumber remaining
//  checked rows in their selection order (1, 2, 3…) and
//  reset the counter to match.
// ============================================================
function _recompactSelectionOrder() {
    // Collect all checked rows with their current order
    const checked = [];
    $('#payment_receivable_main_body tr').each(function () {
        const $cb = $(this).find('.row-checkbox');
        if ($cb.length === 0) return;
        if ($cb.is(':checked')) {
            checked.push({ $tr: $(this), order: $(this).data('selection-order') || 0 });
        }
    });

    // Sort by existing order to preserve relative sequence
    checked.sort(function (a, b) { return a.order - b.order; });

    // Re-assign 1-based sequential numbers
    checked.forEach(function (item, idx) {
        item.$tr.data('selection-order', idx + 1);
    });

    // Reset global counter to match new max
    _selectionCounter = checked.length;
}


// ============================================================
//  HELPERS
// ============================================================

/** Parse a ledger amount input (strip commas, fall back to 0) */
function parseLedgerAmt(val) {
    return Math.max(0, parseFloat((val || '').toString().replace(/,/g, '')) || 0);
}

/**
 * Calculate how much of base_amount is still unallocated after
 * bank + tds + rebate - premium + penalty (other handled separately).
 *
 * base  = #total_amount
 * bank  Dr, tds  Dr, rebate  Dr  → reduce remaining
 * premium Cr                     → increases remaining (added back)
 * penalty Dr                     → reduces remaining
 */
function calcRemaining(bank, tds, rebate, premium, penalty) {
    const base = parseLedgerAmt($('#total_amount').val());
    const remaining = base - bank - tds - rebate + premium - penalty;
    return Math.max(0, parseFloat(remaining.toFixed(2)));
}

/**
 * Set the target amount field to `value` only if the field is
 * currently empty or zero (don't overwrite a user-entered value).
 */
function cascadeFill(selector, value) {
    const $el = $(selector);
    const current = parseLedgerAmt($el.val());
    // Auto-fill only when field is blank / zero
    if (current === 0) {
        $el.val(value > 0 ? value.toFixed(2) : '');
    }
}


// ============================================================
//  RECALC "TOTAL OF ACCOUNT" ROW
//  Sums all ledger Dr entries and subtracts Cr entries.
//  Debit side  : Bank, TDS, Rebate, Penalty + Other(Dr)
//  Credit side : Customer (base/total_amount), Premium, Other(Cr)
//  total_of_account shows the net Dr side total so user can
//  verify it equals the base amount (Dr = Cr check on save).
// ============================================================
function recalcTotalOfAccount() {
    const base      = parseLedgerAmt($('#total_amount').val());
    const bank      = parseLedgerAmt($('#bank_amount').val());
    const tds       = parseLedgerAmt($('#tds_amount').val());
    const rebate    = parseLedgerAmt($('#rebate_amount').val());
    const premium   = parseLedgerAmt($('#premium_amount').val());
    const penalty   = parseLedgerAmt($('#penalty_amount').val());
    const other     = parseLedgerAmt($('#other_amount').val());
    const otherType = $('#other_transaction_type').val(); // 'cr' or 'dr'

    const otherCr = (otherType === 'cr') ? other : 0;
    const otherDr = (otherType === 'dr') ? other : 0;

    // Dr side: bank + tds + rebate + penalty + other(if Dr)
    // Cr side: base(bills) + premium + other(if Cr)
    const drTotal = bank + tds + rebate + penalty + otherDr;
    const crTotal = base + premium + otherCr;

    const netDr = drTotal - crTotal; // 0 = balanced

    // ── Total of Account field (net Dr excluding customer Cr) ──
    const selfDr = bank + tds + rebate + penalty + otherDr;  // ledger Dr only
    const selfCr = premium + otherCr;                         // ledger Cr only
    const netLedger = selfDr - selfCr;
    const fmt = (typeof formatIndianNumber === 'function')
        ? formatIndianNumber(Math.abs(netLedger).toFixed(2))
        : Math.abs(netLedger).toFixed(2);
    $('#total_of_account').val(fmt);

    // ── Visual feedback on total_of_account ──
    if (base > 0) {
        const balanced = Math.abs(netDr) < 0.01;
        $('#total_of_account')
            .toggleClass('text-success border-success', balanced)
            .toggleClass('text-danger border-danger', !balanced)
            .toggleClass('text-primary border-primary bg-primary bg-opacity-10', false);
    } else {
        $('#total_of_account')
            .removeClass('text-success border-success text-danger border-danger')
            .addClass('text-primary border-primary bg-primary bg-opacity-10');
    }

    // ── Update live Dr/Cr summary panel ──
    const f = function(n) { return n.toFixed(2); };

    $('#summary_base_cr').text(f(base));
    $('#summary_premium_cr').text(f(premium));
    $('#summary_other_cr').text(f(otherCr));
    $('#summary_total_cr').text(f(crTotal));

    $('#summary_bank_dr').text(f(bank));
    $('#summary_tds_dr').text(f(tds));
    $('#summary_rebate_dr').text(f(rebate));
    $('#summary_penalty_dr').text(f(penalty));
    $('#summary_other_dr').text(f(otherDr));
    $('#summary_total_dr').text(f(drTotal));

    const diff = Math.abs(netDr);
    $('#summary_difference').text(f(diff));

    // ── Balance status badge ──
    const $badge = $('#balance_status_badge');
    const $diffRow = $('#summary_diff_row');

    if (base === 0) {
        $badge
            .removeClass('bg-success bg-danger')
            .addClass('bg-secondary')
            .html('<i class="fa-solid fa-circle-info me-1"></i> Enter amounts');
        $diffRow.removeClass('table-success table-danger table-warning');
    } else if (diff < 0.01) {
        $badge
            .removeClass('bg-secondary bg-danger')
            .addClass('bg-success')
            .html('<i class="fa-solid fa-circle-check me-1"></i> Balanced');
        $diffRow.removeClass('table-danger table-warning').addClass('table-success');
    } else {
        $badge
            .removeClass('bg-secondary bg-success')
            .addClass('bg-danger')
            .html('<i class="fa-solid fa-triangle-exclamation me-1"></i> Unbalanced');
        $diffRow.removeClass('table-success table-warning').addClass('table-danger');
    }
}


// ============================================================
//  UPDATE OUTSTANDING DISPLAY
// ============================================================
function updateOutstanding(amount) {
    const fmt = (typeof formatIndianNumber === 'function')
        ? formatIndianNumber(parseFloat(amount).toFixed(2))
        : parseFloat(amount).toFixed(2);

    $('#outstanding_amount').val(fmt);
}


// ============================================================
//  CLEAR TABLE
// ============================================================
function clearTable() {
    const colCount = 12;
    let rows = '';
    for (let i = 0; i <= 15; i++) {
        rows += '<tr class="bg-table">';
        for (let k = 0; k < colCount; k++) rows += '<td class="border-0">&nbsp;</td>';
        rows += '</tr>';
    }
    $('#payment_receivable_main_body').html(rows);
    $('#total_amount').val('0.00');
    $('#all_check').prop('checked', false).prop('indeterminate', false);
    $('#total_bill_count').text(0);
}


// ============================================================
//  SAVE RECEIPT VOUCHER
// ============================================================
function saveReceiptVoucher() {
    const accountId   = $('#account_id').val();
    const voucherDate = $('#receipt_date').val();   // correct field ID
    const bankId      = $('#bank').val();
    const remarks     = $('#remarks').val();

    if (!accountId)   { showToast('error', 'Please select an account.'); return; }
    if (!voucherDate) { showToast('error', 'Please enter a voucher date.'); return; }
    if (!bankId)      { showToast('error', 'Please select a bank account.'); return; }

    // ── Block if total amount is negative ────────────────────
    const totalAmtRaw = parseFloat(($('#total_amount').val() || '0').replace(/,/g, ''));
    if (totalAmtRaw < 0) {
        Swal.fire({
            title: 'Cannot Post Voucher!',
            html: `<div class="text-start">
                       <p>The total selected bill amount is <b class="text-danger">₹ ${totalAmtRaw.toFixed(2)}</b> (negative).</p>
                       <p>This happens when <b>Credit (CR) bills</b> selected exceed <b>Debit (DR) bills</b>.</p>
                       <p class="mb-0 text-muted">Please deselect credit entries or select matching debit invoices so the total is positive before posting.</p>
                   </div>`,
            icon: 'error',
            confirmButtonText: 'OK',
        });
        return;
    }

    // ── Debit / Credit balance check ────────────────────────
    const tdsId = $('#tds').val();
    const rebateId = $('#rebate').val();
    const premiumId = $('#premium').val();
    const penaltyId = $('#penalty').val();
    const otherId = $('#other').val();

    const base      = parseLedgerAmt($('#total_amount').val());
    const bank      = parseLedgerAmt($('#bank_amount').val());
    const tds       = parseLedgerAmt($('#tds_amount').val());
    const rebate    = parseLedgerAmt($('#rebate_amount').val());
    const premium   = parseLedgerAmt($('#premium_amount').val());
    const penalty   = parseLedgerAmt($('#penalty_amount').val());
    const other     = parseLedgerAmt($('#other_amount').val());
    const otherType = $('#other_transaction_type').val();

    //  Cr total = base amount (customer) + premium + other(Cr)
    const crTotal = base + (premiumId && premium > 0 ? premium : 0) + (otherType && otherId && otherType === 'cr' ? other : 0);
    //  Dr total = bank + tds + rebate + penalty + other(Dr)
    const drTotal = bank + (tdsId && tds > 0 ? tds : 0) + (rebateId && rebate > 0 ? rebate : 0) + (penaltyId && penalty > 0 ? penalty : 0) + (otherType && otherId && otherType === 'dr' ? other : 0);

    if (Math.abs(drTotal - crTotal) > 0.01) {
        Swal.fire({
            title: 'Debit & Credit Not Balanced!',
            html: `<div class="text-start">
                    <p>Voucher cannot be posted until Debit and Credit sides are equal.</p>
                    <table class="table table-sm table-bordered">
                        <tr><td><b>Credit Side</b><br><small class="text-muted">Bills + Premium + Other (Cr)</small></td><td class="text-end fw-bold text-success">₹ ${crTotal.toFixed(2)}</td></tr>
                        <tr><td><b>Debit Side</b><br><small class="text-muted">Bank + TDS + Rebate + Penalty + Other (Dr)</small></td><td class="text-end fw-bold text-danger">₹ ${drTotal.toFixed(2)}</td></tr>
                        <tr class="table-warning"><td><b>Difference</b></td><td class="text-end fw-bold">₹ ${Math.abs(drTotal - crTotal).toFixed(2)}</td></tr>
                    </table>
                    <p class="mb-0 text-muted">Please adjust the ledger amounts so both sides match before posting.</p>
                   </div>`,
            icon: 'warning',
            confirmButtonText: 'Fix It',
        });
        return;
    }

    const references = [];
    $('#payment_receivable_main_body .row-checkbox:checked').each(function () {
        const $tr = $(this).closest('tr');
        references.push({
            reference_id:  $tr.data('reference-id'),
            account_id:    $tr.data('account-id'),
            source_type:   $tr.data('source-type'),
            source_id:     $tr.data('source-id'),
            payment_amount: parseFloat($tr.find('.receive-amount').val().replace(/,/g, '')) || 0,
        });
    });

    if (references.length === 0) {
        showToast('error', 'Please select at least one bill to settle.');
        return;
    }

    const payload = JSON.stringify({
        account_id:        accountId,
        voucher_date:      (typeof formatDateToYMD === 'function') ? formatDateToYMD(voucherDate) : voucherDate,
        bank_id:           bankId,
        bank_amount: $('#bank_amount').val(),
        tds_id:            $('#tds').val(),
        tds_amount:        $('#tds_amount').val(),
        rebate_id:         $('#rebate').val(),
        rebate_amount:     $('#rebate_amount').val(),
        premium_id:        $('#premium').val(),
        premium_amount:    $('#premium_amount').val(),
        penalty_id:        $('#penalty').val(),
        penalty_amount:    $('#penalty_amount').val(),
        other_id:          $('#other').val(),
        other_amount:      $('#other_amount').val(),
        other_type:        $('#other_transaction_type').val(),
        remarks:           remarks,
        unique_request_id: $('#unique_request_id').val(),
        references:        references,
    });

    $.ajax({
        url: typeof paymentReceivableStoreUrl !== 'undefined' ? paymentReceivableStoreUrl : '',
        type: 'POST',
        data: payload,
        contentType: 'application/json',
        beforeSend: function () {
            showLoader('Saving receipt voucher…');
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    title: 'Success!',
                    text: response.message || 'Receipt voucher saved.',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function () {
                    fetchReceivables();
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Something went wrong.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        },
        error: function (xhr) {
            if (typeof handleAjaxError === 'function') handleAjaxError(xhr);
            else showToast('error', 'Failed to save receipt voucher.');
        },
        complete: function () {
            hideLoader();
        },
    });
}