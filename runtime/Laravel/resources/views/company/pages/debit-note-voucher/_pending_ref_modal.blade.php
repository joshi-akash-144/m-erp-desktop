<div id="custom_overlay" style="
    position: fixed;
    top:0; left:0; width:100%; height:100%;
    background-color: rgba(0,0,0,0.85);
    z-index: 1060;
    display: none;">
</div>
<!-- OFFCANVAS (RIGHT SIDE) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="pending_ref_offcanvas"
    aria-labelledby="adjustment_offcanvas_label" data-bs-backdrop="false" data-bs-scroll="true"
    style="z-index:1061; width: 75%">

    <!-- HEADER -->
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="adjustment_offcanvas_label">Pending References</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div id="adjustment_offcanvas_error" class="text-danger fw-bold mb-1"></div>

    <div class="offcanvas-body p-2 position-relative">
        <!-- Loader -->
        <div id="offcanvas_loader" class="position-absolute top-50 start-50 translate-middle text-center d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-2 text-primary fw-semibold">
                Loading Pending Reference...
            </div>
        </div>

        <!-- Main content -->
        <div id="offcanvas_content">

            <div class="table-responsive" style="max-height: calc(100vh - 150px); overflow-y: auto;">
                <table class="table table-sm table-striped table-hover mb-0" id="pending_ref_table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:50px;">
                                <input type="checkbox" id="check_all_refs" class="form-check-input border border-1 border-dark-subtle">
                            </th>
                            <th class="text-center">Ref. Date</th>
                            <th>Ref No</th>
                            <th class="text-end">Pending Amount</th>
                            <th class="text-end">Bill Amount</th>
                            <th class="text-center">Dr/Cr</th>
                            <th class="text-end">File No</th>
                        </tr>
                    </thead>
                    <tbody id="pending_ref_table_body">
                        <!-- Dynamic rows inserted here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- FOOTER (APPLY BUTTON + SUMMARY FIXED) -->
    <div class="border-top p-2 bg-light d-flex justify-content-between align-items-center">

        <!-- Summary Left -->
        <div>
            <strong style="font-size: 1.0rem">Selected:</strong> <span id="selected_count" style="font-size: 1.2rem">0</span>
            &nbsp; | &nbsp;
            <strong style="font-size: 1.0rem">Total Amount:</strong> <span id="selected_total" style="font-size: 1.2rem">0</span>
        </div>

        <!-- Apply Button Right -->
        <button class="btn btn-primary" id="apply_selected_btn">
            Apply Selected References
        </button>
    </div>

</div>