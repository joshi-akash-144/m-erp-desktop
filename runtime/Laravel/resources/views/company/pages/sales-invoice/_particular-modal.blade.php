<div class="modal fade right-modal" id="particular_modal" tabindex="-1" data-row-id="">
  <div class="modal-dialog modal-md modal-dialog-slideout modal-dialog-top">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Freight Particular Entry Posting</h5>
        {{-- <button type="button" class="btn-close" data-bs-dismiss="modal"></button> --}}
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-12">
            <div class="mb-3 d-none" id="particular_div_dr">
              <label for="particular">Acc. to Post(Dr.)</label>
              <select name="particular_dr_id" class="form-select" id="particular_dr_id"></select>
            </div>

            <div class="mb-3 d-none" id="particular_div_cr">
              <label for="particular">Acc. to Post(Cr.)</label>
              <select name="particular_cr_id" class="form-select" id="particular_cr_id"></select>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== MODAL FOOTER WITH SAVE BUTTON ==================== -->
      <div class="modal-footer">

        <button type="button" class="btn btn-primary" id="save_particular_btn">
          <i class="fa-solid fa-floppy-disk me-1"></i>
          Save
        </button>
      </div>
      <!-- ===================================================================== -->

    </div>
  </div>
</div>



{{-- <div class="form-floating mb-3">
    <input type="text" class="form-control" id="particular" placeholder="Particular">
    <label for="particular">Particular</label>
  </div> --}}