$(function () {
    $('#payment_date').focus();
    // ── Select2 ──────────────────────────────────────────────────────
    $('#account_id').select2({ theme: 'bootstrap-5', placeholder: 'All Accounts', allowClear: true });
    $('#bank_id').select2({ theme: 'bootstrap-5', placeholder: 'Select Bank', allowClear: true });
    $('#ledger_id').select2({ theme: 'bootstrap-5', placeholder: 'Select Account', allowClear: true });
    $('#ledger_transaction_type').select2({ theme: 'bootstrap-5', placeholder: 'Select Type', allowClear: false });
    lockSelect2('#ledger_id');
    lockSelect2('#ledger_transaction_type');

    bindSelect2();

    // ── Date input ────────────────────────────────────────────────────
    new DateInput('#payment_date', FINANCIAL_YEAR_START, FINANCIAL_YEAR_END);

    $('#account_id').on('change', function () {
        let accountId = $(this).val();

        if (accountId) {
            unlockSelect2('#ledger_id');
            unlockSelect2('#ledger_transaction_type');
            if ($('#ledger_id').val()) {
                $('#ledger_amount').prop('disabled', false);
            } else {
                $('#ledger_amount').prop('disabled', true);
            }
        } else {
            $('#ledger_amount').val('0.00');
            $('#ledger_amount').prop('disabled', true);
            $('#ledger_id').val(null).trigger('change.select2');
            $('#ledger_transaction_type').val(null).trigger('change.select2');
            lockSelect2('#ledger_id');
            lockSelect2('#ledger_transaction_type');
        }
        
        recalculate();
    });

    $('#payment_date').on('blur', function () {
        const invDate = formatDateToYMD($(this).val());
        if (!invDate) return;
        if (!isWithinFY(invDate, FINANCIAL_YEAR_START, FINANCIAL_YEAR_END)) {
            showToast(
                'error',
                `Date must be within Financial Year:<br>(${formatDateToDMY(FINANCIAL_YEAR_START)} to ${formatDateToDMY(FINANCIAL_YEAR_END)})`,
                9000,
            );
            $(this).val('');
        }
    });

    // ── Search logic ──────────────────────────────────────────────────
    $('#general_search, #search_type').on('keyup change', function () {
        const value = $('#general_search').val().toLowerCase().trim();
        const searchType = $('#search_type').val();

        $('#ref_table_body tr').filter(function () {
            if ($(this).find('td').length === 1) return; // Skip placeholder rows
            
            let isMatch = false;

            if (value === '') {
                isMatch = true;
            } else if (searchType === 'equal') {
                $(this).find('td').each(function () {
                    if ($(this).text().toLowerCase().trim() === value) {
                        isMatch = true;
                        return false; // Break loop
                    }
                });
            } else {
                isMatch = $(this).text().toLowerCase().indexOf(value) > -1;
            }
            
            $(this).toggle(isMatch);
            
            if (!isMatch) {
                const $checkbox = $(this).find('.row-check');
                if ($checkbox.prop('checked')) {
                    $checkbox.prop('checked', false);
                    $(this).removeClass('active-row');
                }
            }
        });
        syncSelectAll();
        recalculate();
    });

    // ── Load data ─────────────────────────────────────────────────────
    $('#btn_show').on('click', loadReferences);

    function loadReferences() {
        const params = { account_id: $('#account_id').val() };

        $('#ref_table_body').html('<tr><td colspan="9" class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading…</td></tr>');

        $.get(listUrl, params, function (res) {
            if (!res.success) {
                $('#ref_table_body').html('<tr><td colspan="9" class="text-center text-danger">Failed to load data.</td></tr>');
                return;
            }
            
            if (res.data && res.data.length > 0) {
                res.data.sort((a, b) => String(a.particular || '').localeCompare(String(b.particular || '')));
            }
            
            renderTable(res.data);
        }).fail(function () {
            $('#ref_table_body').html('<tr><td colspan="9" class="text-center text-danger">Server error.</td></tr>');
        });
    }

    function renderTable(rows) {
        const $tbody = $('#ref_table_body').empty();
        resetSummary();

        if (!rows || rows.length === 0) {
            $tbody.html('<tr><td colspan="9" class="text-center text-muted py-4">No references found.</td></tr>');
            return;
        }

        rows.forEach(function (r, i) {
            const uColor = getUserColor(r.created_by);
            const isDebit = r.direction === 'debit';
            const drCr = isDebit ? 'Dr' : 'Cr';
            const drCrClass = isDebit ? 'text-danger' : 'text-success';

            const $tr = $(`<tr data-id="${r.id}" data-orig-amount="${r.pending_amount}" data-amount="${isDebit ? -r.pending_amount : r.pending_amount}" data-particular="${r.particular}" data-city="${r.city}" data-direction="${r.direction}">
                <td class="text-center"><input type="checkbox" class="row-check"></td>
                <td class="fw-bold">${r.reference_number}</td>
                <td>${r.reference_date}</td>
                <td class="text-end fw-bold ${drCrClass}">
                    <span class="amount-cell d-inline-block" contenteditable="true" style="min-width: 60px; outline: none; border-bottom: 1px dotted #999; cursor: text;">${parseFloat(r.pending_amount).toFixed(2)}</span>
                </td>
                <td class="text-end fw-bold">${parseFloat(r.amount).toFixed(2)}</td>
                <td class="text-center fw-bold ${drCrClass}">${drCr}</td>
                <td>${r.particular}</td>
                <td>${r.city}</td>
                <td><span class="badge bg-${uColor}-lt text-${uColor} border border-${uColor} fw-bold"><i class="fa-regular fa-user me-1"></i>${r.created_by}</span></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-view py-0 px-1 border-0 text-primary bg-transparent shadow-none" data-id="${r.id}" title="View Detail" style="font-size:0.78rem;line-height:1.4;">
                        ${icons?.view || '<i class="fa-solid fa-eye"></i>'}
                    </button>
                    <button class="btn btn-sm btn-delete py-0 px-1 ms-1 border-0 text-danger bg-transparent shadow-none" data-id="${r.id}" title="Delete Reference" style="font-size:0.78rem;line-height:1.4;">
                        ${icons?.delete || '<i class="fa-solid fa-trash-can"></i>'}
                    </button>
                </td>
            </tr>`);
            $tbody.append($tr);
        });

        bindCheckboxEvents();
        bindAmountEvents();
    }

    function bindAmountEvents() {
        $(document).off('focus.trp_amt', '.amount-cell').on('focus.trp_amt', '.amount-cell', function () {
            const el = this;
            const $tr = $(this).closest('tr');
            
            $('#ref_table_body tr').removeClass('table-secondary');
            $tr.addClass('table-secondary');

            setTimeout(() => {
                const range = document.createRange();
                range.selectNodeContents(el);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }, 10);
        });

        $(document).off('blur.trp_amt', '.amount-cell').on('blur.trp_amt', '.amount-cell', function () {
            $(this).closest('tr').removeClass('table-secondary');
            processAmountChange($(this));
        });

        $(document).off('keydown.trp_amt', '.amount-cell').on('keydown.trp_amt', '.amount-cell', function (e) {
            if (e.key === 'Enter' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                
                const $cells = $('.amount-cell:visible');
                const idx = $cells.index(this);
                
                let nextIdx = -1;
                if ((e.key === 'Enter' || e.key === 'ArrowDown') && idx < $cells.length - 1) {
                    nextIdx = idx + 1;
                } else if (e.key === 'ArrowUp' && idx > 0) {
                    nextIdx = idx - 1;
                }
                
                if (nextIdx !== -1) {
                    const nextEl = $cells.eq(nextIdx)[0];
                    nextEl.focus();
                    
                    setTimeout(() => {
                        const range = document.createRange();
                        range.selectNodeContents(nextEl);
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                    }, 0);
                } else if (e.key === 'Enter' || e.key === 'ArrowDown') {
                    $(this).blur();
                }
            }
        });
    }

    function processAmountChange($el) {
        const $tr = $el.closest('tr');
        const orig = parseFloat($tr.data('orig-amount')) || 0;
        
        // Remove formatting commas for proper parsing
        let val = $el.text().replace(/,/g, '').trim();
        let newAmt = 0;

        if (val.startsWith('-') || val.startsWith('+')) {
            newAmt = orig + parseFloat(val);
        } else {
            newAmt = parseFloat(val);
        }

        if (isNaN(newAmt) || newAmt < 0) {
            newAmt = orig;
        }
        if (newAmt > orig) {
            newAmt = orig;
        }

        $el.text(newAmt.toFixed(2));
        const isDebit = $tr.data('direction') === 'debit';
        $tr.data('amount', isDebit ? -newAmt : newAmt);
        
        recalculate();
    }

    // ── Checkbox logic ────────────────────────────────────────────────
    function bindCheckboxEvents() {
        $('#all_check').off('change').on('change', function () {
            const checked = $(this).prop('checked');
            $('.row-check').each(function () {
                const $tr = $(this).closest('tr');
                if ($tr.is(':visible')) {
                    $(this).prop('checked', checked);
                    $tr.toggleClass('table-primary', checked);
                }
            });
            recalculate();
        });

        $(document).off('change.trp', '.row-check').on('change.trp', '.row-check', function () {
            $(this).closest('tr').toggleClass('table-primary', this.checked);
            syncSelectAll();
            recalculate();
        });
    }

    function syncSelectAll() {
        let total = 0;
        let checked = 0;
        $('.row-check').each(function() {
            if ($(this).closest('tr').is(':visible')) {
                total++;
                if ($(this).prop('checked')) {
                    checked++;
                }
            }
        });
        $('#all_check').prop('checked', total > 0 && total === checked);
    }

    function recalculate() {
        let total = 0;
        const groupMap = {};

        $('.row-check:checked').each(function () {
            const $tr = $(this).closest('tr');
            const amt = parseFloat($tr.data('amount')) || 0;
            total += amt;
            
            const particular = $tr.data('particular');
            const city = $tr.data('city');
            
            if (!groupMap[particular]) {
                groupMap[particular] = { particular: particular, city: city, amount: 0 };
            }
            groupMap[particular].amount += amt;
        });

        const summaryRows = Object.values(groupMap);
        
        let ledgerAmt = parseFloat($('#ledger_amount').val()) || 0;
        
        if ($('#ledger_id').val() && !$('#ledger_id').prop('disabled') && !$('#ledger_id').closest('.select2-container').hasClass('disabled-select')) {
            let maxLedgerAmt = total > 0 ? total : 0;
            if (ledgerAmt > maxLedgerAmt) {
                ledgerAmt = maxLedgerAmt;
                $('#ledger_amount').val(ledgerAmt.toFixed(2));
            }
        } else {
            ledgerAmt = 0;
        }

        total -= ledgerAmt;

        $('#payment_total').val(fmt(total.toFixed(2)));
        
        summaryRows.sort((a, b) => String(a.particular || '').localeCompare(String(b.particular || '')));
        
        let sr = 1;
        summaryRows.forEach(r => r.sr = sr++);
        
        renderSummary(summaryRows);
    }

    function renderSummary(rows) {
        const $tbody = $('#summary_table_body').empty();
        if (rows.length === 0) { resetSummary(); return; }

        rows.forEach(function (r) {
            $tbody.append(`<tr>
                <td>${r.sr}</td>
                <td>${r.particular}</td>
                <td>${r.city}</td>
                <td class="text-end fw-bold">${fmt(r.amount.toFixed(2))}</td>
            </tr>`);
        });
    }

    function resetSummary() {
        $('#summary_table_body').html('<tr><td colspan="4" class="text-center text-muted">-- No selection --</td></tr>');
        $('#payment_total').val('0.00');
        $('#all_check').prop('checked', false);
    }

    // ── View Detail Modal ─────────────────────────────────────────────
    $(document).off('click.view', '.btn-view').on('click.view', '.btn-view', function () {
        const id  = $(this).data('id');
        const url = detailUrl.replace('__ID__', id);

        // reset
        ['#m_voucher_no','#m_voucher_date','#m_driver','#m_vehicle',
         '#m_pending','#m_expense_total','#m_narration','#m_created_by']
            .forEach(function(s){ $(s).text('—'); });
        $('#modal_entries_body').html('<tr><td colspan="12" class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin me-2"></i>Loading…</td></tr>');
        $('#m_total_bags, #m_total_weight, #m_total_amount').text('0.00');
        $('#m_total_trips').text('0');

        $('#detailModal').modal('show');

        $.get(url, function (res) {
            if (!res.success) {
                $('#modal_entries_body').html('<tr><td colspan="12" class="text-center text-danger">Failed to load.</td></tr>');
                return;
            }
            const d = res.data;
            $('#m_voucher_no').text(d.reference_number || '—');
            $('#m_voucher_date').text(d.voucher_date || '—');
            $('#m_driver').text(d.driver || '—');
            $('#m_vehicle').text(d.vehicle || '—');
            $('#m_pending').text(fmt(d.pending_amount));
            $('#m_expense_total').text(fmt(d.expense_total));
            $('#m_narration').text(d.narration || '—');
            $('#m_created_by').text(d.created_by || '—');

            const $tb = $('#modal_entries_body').empty();
            if (!d.items || d.items.length === 0) {
                $tb.html('<tr><td colspan="12" class="text-center text-muted">No items found.</td></tr>');
                return;
            }

            let tBags = 0, tWeight = 0, tTrips = 0, tAmount = 0;
            d.items.forEach(function (e, i) {
                tBags   += parseFloat(e.bags)   || 0;
                tWeight += parseFloat(e.weight) || 0;
                tTrips  += parseInt(e.trips)    || 0;
                tAmount += parseFloat(e.amount) || 0;
                $tb.append(`<tr>
                    <td>${i + 1}</td>
                    <td>${e.billing_date}</td>
                    <td>${e.expense_name}</td>
                    <td>${e.from}</td>
                    <td>${e.to}</td>
                    <td>${e.product}</td>
                    <td>${e.dc_lr}</td>
                    <td class="text-end">${fmt(e.bags)}</td>
                    <td class="text-end">${fmt(e.weight)}</td>
                    <td class="text-center">${e.trips}</td>
                    <td class="text-end fw-bold">${fmt(e.amount)}</td>
                    <td>${e.remark}</td>
                </tr>`);
            });

            $('#m_total_bags').text(fmt(tBags.toFixed(2)));
            $('#m_total_weight').text(fmt(tWeight.toFixed(2)));
            $('#m_total_trips').text(tTrips);
            $('#m_total_amount').text(fmt(tAmount.toFixed(2)));
        }).fail(function () {
            $('#modal_entries_body').html('<tr><td colspan="12" class="text-center text-danger">Server error.</td></tr>');
        });
    });

    // ── Delete Reference ──────────────────────────────────────────────
    $(document).off('click.delete', '.btn-delete').on('click.delete', '.btn-delete', function () {
        const id = $(this).data('id');
        const url = deleteUrl.replace('__ID__', id);

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure?',
            html: "This will <strong>only delete the reference</strong>, and will not delete the voucher.<br>This action cannot be undone.",
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: (icons?.delete || '<i class="fa-solid fa-trash-can me-1"></i>') + ' Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        if (res.success) {
                            showToast('success', res.message);
                            loadReferences();
                        } else {
                            showToast('error', res.message || 'Failed to delete reference.');
                        }
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Server error. Please try again.';
                        showToast('error', msg);
                    }
                });
            }
        });
    });

    // ── Pay button ────────────────────────────────────────────────────
    $('#btn_pay').on('click', function () {
        const $checked = $('.row-check:checked');
        if (!$checked.length)          { showToast('warning', 'Please select at least one reference.'); return; }
        if (!$('#bank_id').val())      { showToast('warning', 'Please select a bank.');                 return; }
        if (!$('#payment_date').val()) { showToast('warning', 'Please enter Payment Date.');            return; }

        const refIds = [];
        const payAmounts = {};
        const partyTotals = {};
        
        $checked.each(function () {
            const $tr = $(this).closest('tr');
            const id = $tr.data('id');
            const amt = parseFloat($tr.data('amount')) || 0;
            const particular = $tr.data('particular') || 'Unknown';
            
            refIds.push(id);
            payAmounts[id] = Math.abs(amt);
            
            if (!partyTotals[particular]) partyTotals[particular] = 0;
            partyTotals[particular] += amt;
        });

        for (let party in partyTotals) {
            if (partyTotals[party] < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Payment Amount',
                    html: `The net payment summary for <strong>${party}</strong> is negative (₹ ${fmt(partyTotals[party])}).<br>You cannot process a payment voucher for a negative amount.`,
                });
                return;
            }
        }

        const selectedCount = refIds.length;
        const totalAmt      = $('#payment_total').val();

        Swal.fire({
            icon             : 'question',
            title            : 'Confirm Payment',
            html             : `Are you sure you want to pay <strong>${selectedCount}</strong> reference(s) totalling <strong>₹ ${totalAmt}</strong>?`,
            showCancelButton : true,
            confirmButtonText: '<i class="fa-solid fa-check-circle me-1"></i> Yes, Pay Now',
            cancelButtonText : '<i class="fa-solid fa-times me-1"></i> Cancel',
            confirmButtonColor: '#2fb344',
            cancelButtonColor : '#6c757d',
            reverseButtons   : true,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            const currentUuid = $('#uuid').val();
            const $btn = $('#btn_pay').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing…');

            $.ajax({
                url   : storeUrl,
                method: 'POST',
                data  : {
                    _token       : $('meta[name="csrf-token"]').attr('content'),
                    uuid         : currentUuid,
                    bank_id      : $('#bank_id').val(),
                    payment_date : formatDateToYMD($('#payment_date').val()),
                    ref_ids      : refIds,
                    pay_amounts  : payAmounts,
                    ledger_id    : $('#ledger_id').val(),
                    ledger_transaction_type : $('#ledger_transaction_type').val(),
                    ledger_amount: parseFloat($('#ledger_amount').val()) || 0,
                    narration    : $('#narration').val().trim(),
                },
                success: function (res) {
                    if (res.success) {
                        // Release UUID — generate a fresh one for the next payment
                        $('#uuid').val(generateUUID());

                        Swal.fire({
                            icon             : 'success',
                            title            : 'Payment Successful',
                            text             : res.message || 'Payment processed successfully.',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#2fb344',
                        });
                        loadReferences();
                        resetSummary();
                        $('#all_check').prop('checked', false);
                    } else {
                        Swal.fire({
                            icon             : 'error',
                            title            : 'Payment Failed',
                            text             : res.message || 'Payment could not be processed.',
                            confirmButtonText: 'OK',
                        });
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Server error. Please try again.';
                    Swal.fire({
                        icon             : 'error',
                        title            : 'Error',
                        text             : msg,
                        confirmButtonText: 'OK',
                    });
                },
                complete: function () {
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-check-circle me-2"></i> Pay Selected');
                },
            });
        });
    });

    // ── Hold button ───────────────────────────────────────────────────
    $('#hold_bill').on('click', function (e) {
        e.preventDefault();
        const $checked = $('.row-check:checked');
        
        if ($checked.length === 0) {
            Swal.fire({
                title: 'Error!',
                text: 'Please select at least one reference to hold.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        const refIds = [];
        $checked.each(function () {
            refIds.push($(this).closest('tr').data('id'));
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to hold the selected references?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Hold it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const $btn = $('#hold_bill').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing…');
                $.ajax({
                    url: holdBillUrl,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        ref_ids: refIds
                    },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire(
                                'Hold!',
                                res.message || 'References have been put on hold.',
                                'success'
                            );
                            loadReferences();
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong', 'error');
                        }
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.message || 'Server error. Please try again.';
                        Swal.fire('Error', msg, 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html('<i class="fa-solid fa-hand-paper me-2"></i> Hold Bill');
                    }
                });
            }
        });
    });



    // ── Helper ────────────────────────────────────────────────────────
    function fmt(val) {
        const n = parseFloat(val);
        return isNaN(n) ? '0.00' : n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const userColors = ['primary', 'purple', 'pink', 'warning', 'danger', 'pink', 'info', 'indigo', 'teal', 'orange', 'azure', 'lime', 'cyan'];
    const userColorMap = {};
    let nextColorIdx = 0;

    function getUserColor(username) {
        if (!username) return 'secondary';
        if (!userColorMap[username]) {
            userColorMap[username] = userColors[nextColorIdx % userColors.length];
            nextColorIdx++;
        }
        return userColorMap[username];
    }

    // ── Ledger Events ───────────────────────────────────────────────────
    $('#ledger_id').on('change', function () {
        if ($(this).val()) {
            $('#ledger_amount').prop('disabled', false);
        } else {
            $('#ledger_amount').prop('disabled', true).val('0.00');
        }
        recalculate();
    });

    $('#ledger_amount').on('blur', function () {
        let val = parseFloat($(this).val()) || 0;
        if (val < 0) val = 0;
        $(this).val(val.toFixed(2));
        recalculate();
    });

});

// ── UUID generator (fallback for older browsers) ──────────────────────────────
function generateUUID() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
}


function bindSelect2() {
    
    var selects = ['#bank_id', '#ledger_id', '#ledger_transaction_type','#account_id' ];
    selects.forEach(function (el) {
         $(el).select2({
                theme: 'bootstrap-5',
        });
    });

    $(document).on('select2:open', function (e) {
        var $select     = $(e.target);
        var $search     = $select.data('select2').$dropdown.find('.select2-search__field');
        $search.off('keydown.select2Enter').on('keydown.select2Enter', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $select.select2('close');
                moveFocusToNextField($select);
            }
        });
    });

    
}