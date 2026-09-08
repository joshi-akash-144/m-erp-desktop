<style>
    #ledger_setting_modal .modal-dialog {
        max-width: 700px !important;
    }

    #ledger_setting_modal .modal-content {
        border-radius: 10px;
    }

    #ledger_setting_modal .table td,
    #ledger_setting_modal .table th {
        padding: 6px;
        vertical-align: middle;
    }

    #ledger_setting_modal .table td:first-child {
        width: 120px;
        background: #f1f3f5;
        font-weight: 600;
        text-align: right;
    }

    #ledger_setting_modal .form-select,
    #ledger_setting_modal .form-control {
        border-radius: 0;
    }

    #ledger_setting_modal .dr-cr-select {
        width: 80px;
    }

    #ledger_setting_modal .amount-input {
        width: 120px;
    }

    #ledger_setting_modal .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>

<div class="modal fade" id="ledger_setting_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- HEADER -->
            <div class="modal-header py-2 border-bottom">
                <div>
                    <h5 class="modal-title mb-0">Ledger Settings</h5>
                    <small class="text-muted">Configure ledger mapping</small>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
                <form>
                    <table class="table table-bordered align-middle mb-0">
                        <tbody>
                            <tr>
                                <td>BANK:</td>
                                <td>
                                    <select class="form-select" name="bank_ledger_id" id="bank_ledger_id">
                                        <option value="">Select Ledger...</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td>TDS:</td>
                                <td>
                                    <select class="form-select" name="tds_ledger_id" id="tds_ledger_id">
                                        <option value="">Select Ledger...</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td>REBATE:</td>
                                <td>
                                    <select class="form-select" name="rebate_ledger_id" id="rebate_ledger_id">
                                        <option value="">Select Ledger...</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td>PREMIUM:</td>
                                <td>
                                    <select class="form-select" name="premium_ledger_id" id="premium_ledger_id">
                                        <option value="">Select Ledger...</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td>PENALTY:</td>
                                <td>
                                    <select class="form-select" name="penalty_ledger_id" id="penalty_ledger_id">
                                        <option value="">Select Ledger...</option>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <td>OTHER:</td>
                                <td>
                                    <select class="form-select" name="other_ledger_id" id="other_ledger_id">
                                        <option value="">Select Ledger...</option>
                                    </select>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </form>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer py-2">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    id="save_ledger_setting_btn">
                    Save Settings
                </button>

            </div>

        </div>
    </div>
</div>