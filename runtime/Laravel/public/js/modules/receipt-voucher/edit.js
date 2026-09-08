$(document).ready(function () {
    $("#voucher_id").on("change", function () {
        const id = $(this).val();
        if (!id) {
            resetVoucherForm();
            return;
        }
        getVoucherDetails(id);
    });
});

function resetVoucherForm() {
    const manager = window.voucherTableManager;
    if (!manager) return;

    manager.dataStore.clear();

    $("#voucher_date").val("");
    $("#weekday").val("");
    $("#voucher_narration").val("");

    manager.rowManager.getAllRows().each(function () {
        const $row = $(this);
        $row.find(".account-id").val(null).trigger("change");
        $row.find(".dr-cr").val("");
        $row.find(".debit-amount").val("").prop("readonly", false).removeClass("cursor-not-allowed");
        $row.find(".credit-amount").val("").prop("readonly", false).removeClass("cursor-not-allowed");
        $row.find(".ledger-balance").val("");
    });

    manager.updateTotals();
    manager.referenceManager.refreshPreview();
}

function getVoucherDetails(voucherId) {
    $.ajax({
        url: editReceiptVoucher,
        beforeSend: function () {
            showLoader("Fetching Receipt Voucher Details...");
        },
        method: "GET",
        data: { voucher_id: voucherId },
        success: function (response) {
            hideLoader();
            setVoucherDetails(response);
        },
        error: function () {
            hideLoader();
            showToast("error", "Failed to fetch voucher details.");
        },
    });
}

function setVoucherDetails(response) {
    const { voucher, receipt, is_locked, locked_refs } = response.data;
    const manager = window.voucherTableManager;

    manager.dataStore.clear();

    manager.rowManager.getAllRows().each(function () {
        const $row = $(this);
        $row.find(".account-id").val(null).trigger("change");
        $row.find(".dr-cr").val("");
        $row.find(".debit-amount").val("").prop("readonly", false).removeClass("cursor-not-allowed");
        $row.find(".credit-amount").val("").prop("readonly", false).removeClass("cursor-not-allowed");
        $row.find(".ledger-balance").val("");
    });

    if (is_locked) {
        const refList = (locked_refs || [])
            .map((r) => `<li><b>${r.reference_number}</b> — settled: ${r.settled_amount}</li>`)
            .join("");

        Swal.fire({
            icon: "warning",
            title: "Voucher Locked",
            html: `This voucher cannot be edited because the following reference(s) have already been settled by a payment or receipt:<ul class="text-start mt-2">${refList}</ul>`,
            confirmButtonColor: "#d9534f",
            confirmButtonText: "OK",
        });

        $("#save_btn").prop("disabled", true).html('<i class="fa-solid fa-lock me-1"></i> Locked');
    } else {
        $("#save_btn").prop("disabled", false).html('<i class="fa-solid fa-pencil me-1"></i> Update');
    }

    const dateParts = (voucher.voucher_date || "").split("-");
    if (dateParts.length === 3) {
        $("#voucher_date").val(`${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`);
    }

    const jsDate = new Date(voucher.voucher_date);
    const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    if (!isNaN(jsDate.getDay())) {
        $("#weekday").val(days[jsDate.getDay()]);
    }

    $("#voucher_narration").val(voucher.narration || "");

    const allRows = manager.rowManager.getAllRows();
    const details = voucher.details || [];

    details.forEach((txn, index) => {
        const $row = allRows.eq(index);
        if (!$row.length) return;

        const accountData = accountMasterData.find((a) => a.id == txn.account_id);
        if (accountData) {
            const $select = $row.find(".account-id");
            $select.find("option").remove();
            const option = new Option(accountData.text, accountData.id, true, true);
            $select.append(option).trigger("change");
        }

        const debit  = parseFloat(txn.debit)  || 0;
        const credit = parseFloat(txn.credit) || 0;
        const drCr   = debit > 0 ? "DR" : "CR";
        const isDR   = drCr === "DR";

        $row.find(".dr-cr").val(drCr);
        $row.find(".debit-amount").prop("readonly", !isDR).toggleClass("cursor-not-allowed", !isDR);
        $row.find(".credit-amount").prop("readonly", isDR).toggleClass("cursor-not-allowed", isDR);

        if (debit > 0) {
            $row.find(".debit-amount").val(debit.toFixed(2));
        }
        if (credit > 0) {
            $row.find(".credit-amount").val(credit.toFixed(2));
        }
    });

    details.forEach((txn, index) => {
        if (!txn.refs || txn.refs.length === 0) return;

        const refs = txn.refs.map((r) => ({
            refId: r.ref_id || null,
            method: r.method,
            refName: r.ref_number,
            refAmt: r.ref_amount,
            file_no: r.file_no || null,
            transactionType: r.transaction_type,
        }));

        manager.dataStore.setReferences(index, refs);
    });

    manager.updateTotals();
    manager.referenceManager.refreshPreview();
}
