<!-- ================================
     DAIRY PURCHASE ORDER VIEW MODAL
================================ -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    #dairy_po_analysis_modal .modal-content {
        font-family: 'Inter', sans-serif;
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    #dairy_po_analysis_modal .modal-header {
        background: #f8f9fa;
        border-bottom: 1px solid #eaeaea;
        border-radius: 12px 12px 0 0;
        padding: 1.25rem 1.5rem;
    }

    #dairy_po_analysis_modal .modal-title {
        color: #111827;
        font-weight: 600;
        font-size: 1.15rem;
    }

    #dairy_po_analysis_modal .modal-header .btn-close {
        filter: none;
        opacity: 0.5;
    }

    #dairy_po_analysis_modal .modal-header .btn-close:hover {
        opacity: 1;
    }

    #dairy_po_analysis_modal .info-card {
        background: #ffffff;
        border: 1px solid #f1f3f5;
        border-radius: 10px;
        padding: 1.25rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
    }

    #dairy_po_analysis_modal .info-label {
        color: #6b7280;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    #dairy_po_analysis_modal .info-value {
        color: #111827;
        font-size: 0.95rem;
        font-weight: 600;
    }

    #dairy_po_analysis_modal .summary-section {
        background: #f9fafb;
        border-top: 1px solid #eaeaea;
        padding: 1.25rem 1.5rem;
    }

    #dairy_po_analysis_modal .summary-card {
        background: #ffffff;
        border-radius: 8px;
        padding: 1.25rem;
        border: 1px solid #f3f4f6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    #dairy_po_analysis_modal .summary-label {
        color: #6b7280;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    #dairy_po_analysis_modal .summary-value {
        color: #111827;
        font-size: 1.25rem;
        font-weight: 700;
    }
</style>
<div class="modal fade" id="dairy_po_analysis_modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 1300px;">
    <div class="modal-content" style="height: 90vh;">

            <!-- Header -->
            <div class="modal-header d-flex justify-content-between align-items-center">
                <h4 class="modal-title d-flex align-items-center mb-0">
                    <div class="bg-primary-lt text-primary rounded d-flex align-items-center justify-content-center me-3"
                        style="width: 32px; height: 32px;">
                        <i class="fa-solid fa-chart-line" style="font-size: 14px;"></i>
                    </div>
                    Dairy PO Profit & Loss Analysis
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

<div class="modal-body p-0 d-flex flex-column bg-white overflow-hidden">
    <!-- Info Section -->
    <div class="p-4 pb-2 flex-shrink-0 bg-white">
<div class="info-card mb-3">
            <div class="row g-4 align-items-center">
                <div class="col border-end">
                    <div class="info-label">Customer Account</div>
                    <div id="modal_account_name" class="info-value text-truncate" title="">-</div>
                </div>
                <div class="col border-end ps-4">
                    <div class="info-label">Dairy PO Number</div>
                    <div id="modal_dairy_po_number" class="info-value">-</div>
                </div>
                <div class="col border-end ps-4">
                    <div class="info-label">Item Name</div>
                    <div id="modal_item_name" class="info-value text-truncate" title="">-</div>
                </div>
                <div class="col border-end ps-4">
                    <div class="info-label">Destination</div>
                    <div id="modal_destination" class="info-value text-truncate" title="">-</div>
                </div>
<div class="col ps-4">
    <div class="info-label">Sales Rate</div>
    <div id="modal_sales_rate" class="info-value text-primary fs-3">-</div>
</div>
</div>
</div>
<div class="info-card">
    <div class="row g-2 align-items-center text-center">
        <div class="col border-end">
            <div class="info-label">PO Qty</div>
            <div id="modal_total_qty" class="info-value fw-bolder">-</div>
        </div>
        <div class="col border-end">
            <div class="info-label">Purchased Qty</div>
            <div id="modal_purchased_qty" class="info-value text-success fw-bold">-</div>
        </div>
        <div class="col border-end">
            <div class="info-label text-truncate" title="Billed Qty (From PO)">Billed (PO)</div>
            <div id="modal_billed_qty_po" class="info-value text-primary fw-bold">-</div>
        </div>
        <div class="col border-end">
            <div class="info-label text-truncate" title="Godown Qty (Dispatched)">Godown Qty</div>
            <div id="modal_godown_qty" class="info-value" style="color: #d97706;">-</div>
        </div>
        <div class="col border-end">
            <div class="info-label text-truncate" title="Total Billed Qty">Total Billed</div>
            <div id="modal_total_billed_qty" class="info-value text-primary fw-bold">-</div>
        </div>
        <div class="col">
            <div class="info-label text-truncate" title="Remaining PO Qty">Remaining Qty</div>
            <div id="modal_remaining_qty" class="info-value text-danger fw-bold">-</div>
        </div>
    </div>
</div>
</div>
<!-- Chart Container -->
<div id="dairy_po_chart_container" class="flex-grow-1 overflow-auto px-3 py-2 bg-white">
    <!-- Custom chart will be injected here via JS -->
</div>
<!-- Summary Footer -->
<div class="summary-section flex-shrink-0" id="dairy_po_footer_summary" style="display: none;">
    <div class="row g-3">
        <div class="col-md-7">
            <div class="summary-card h-100 d-flex flex-column justify-content-center">
                <div class="row w-100 text-center">
                    <div class="col-3 border-end">
                        <div class="summary-label">Sales Rate</div>
                        <div class="summary-value" id="summary_sales_rate">0</div>
</div>
<div class="col-3 border-end">
                        <div class="summary-label">Avg. Purchase Rate</div>
                        <div class="summary-value" id="summary_avg_purchase_rate">0</div>
</div>
<div class="col-3 border-end">
                        <div class="summary-label">Rate Difference</div>
                        <div class="summary-value" id="summary_diff_rate">0</div>
                    </div>
<div class="col-3">
                        <div class="summary-label">Result</div>
                        <div class="summary-value" id="summary_result_badge">-</div>
</div>
</div>
</div>
</div>
{{-- <div class="col-md-5">
    <div class="summary-card h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="summary-label mb-0">Total Sales</span>
            <span class="info-value fs-4" id="summary_total_sales">0</span>
                                </div>
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="summary-label mb-0">Total Purchase</span>
    <span class="info-value fs-4" id="summary_total_purchase">0</span>
</div>
<div class="d-flex justify-content-between align-items-center pt-3 border-top">
    <span class="summary-label mb-0 text-dark fw-bolder fs-6">Profit/Loss Amount</span>
    <div class="text-end">
        <div class="summary-value fs-3" id="summary_diff_amount">0</div>
        <div class="mt-1" id="summary_overall_percentage">
            <span class="badge bg-primary">0.0%</span>
        </div>
    </div>
</div> --}}
</div>
</div>
</div>
<!-- Footer -->
<div class="modal-footer bg-white border-top-0 pt-3 pb-3 pe-3 d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-sm btn-outline-success shadow-sm fw-medium px-3" id="export-detailed-excel-btn" style="border-radius: 6px; font-family: 'Inter', sans-serif;">
        <i class="fa-solid fa-file-excel me-1"></i> Export Excel
    </button>
    <button class="btn btn-sm btn-light shadow-sm fw-medium px-3" data-bs-dismiss="modal" style="border-radius: 6px; font-family: 'Inter', sans-serif;">
        Close
    </button>
</div>
</div> <!-- End modal-content -->
</div> <!-- End modal-dialog -->
</div> <!-- End modal -->