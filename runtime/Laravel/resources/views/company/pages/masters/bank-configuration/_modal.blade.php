@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Bank Configuration';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$bankDetails = $modalData['bank_detail'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
$view_class_css = "form-control bg-light text-muted fw-bold border-1 border-secondary-subtle";
$view_class_css_select2 = "form-select select2 bg-light text-muted fw-bold border-1 border-secondary-subtle my-dropdown-class";
@endphp

<div class="modal fade master-modal" id="bank_configuration_modal"  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="bank_configuration_modal" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content master-modal-content">
      <div class="modal-header master-header">
        <div class="d-flex align-items-center gap-3">
          <div class="master-badge bg-primary-lt rounded fs-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-database"
              width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
              fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" />
              <path d="M4 6v6a8 3 0 0 0 16 0v-6" />
              <path d="M4 12v6a8 3 0 0 0 16 0v-6" />
            </svg>
          </div>
          <div>
            <h5 class="modal-title mb-0 font-monospace">{{ $title }}</h5>
            <small class="badge bg-teal text-teal-fg">Master Data Management</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

    <div class="modal-body">
          <form id="bank_configuration_form"
            action="{{ $formMode === 'edit' ? route('bank-configurations.update', $data->id) : route('bank-configurations.store') }}"
            method="POST" class="needs-validation" novalidate>
          
            @csrf
            @if($formMode === 'edit')
              @method('PUT')
            @else
              <input type="hidden" name="uuid" value="{{ $uuid }}">
            @endif
          
            <input type="hidden" id="form_mode" value="{{ $formMode }}">
              <div class="row g-2">
                <!-- Left Column -->
                <div class="col-md-12">
                  <!-- Account Details -->
                  <div class="master-form-section">
                    <div class="master-section-title ">
                      <i class="fa-solid fa-building-columns"></i> &nbsp;
                      Bank Configuration Details</div>
                    <div class="row g-3">
                    <!-- Bank Name -->
                    <div class="col-lg-4">
                      @php $bankDetails = config('constants.bank_name'); @endphp
                      <label class="form-label required"><i class="fa-solid fa-building-columns text-secondary"></i> &nbsp;Bank Name</label>
                      <select name="bank_id" id="bank_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select bank name...</option>
                        @foreach ($bankDetails as $groupTypeId => $groupType)
                          <option value="{{ $groupTypeId }}" {{ ($data->bank_id ?? '') == $groupTypeId ? 'selected' : '' }}>
                            {{ $groupType }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <!-- RTGS Name -->
                    <div class="col-lg-4">
                      @php $rtgsName = config('constants.rtgs_name'); @endphp
                      <label class="form-label required"><i class="fa-solid fa-file-lines text-secondary"></i>&nbsp;RTGS Name</label>
                      <select name="rtgs_id" id="rtgs_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select rtgs name...</option>
                        @foreach ($rtgsName as $groupTypeId => $groupType)
                          <option value="{{ $groupTypeId }}" {{ ($data->rtgs_id ?? '') == $groupTypeId ? 'selected' : '' }}>
                            {{ $groupType }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <!-- RTGS Name -->
                    <div class="col-lg-4">
                      @php $chequeName = config('constants.cheque_name'); @endphp
                      <label class="form-label required"><i class="fa-solid fa-money-check text-secondary"></i>&nbsp;Cheque Name</label>
                      <select name="cheque_id" id="cheque_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                        <option value="">Select cheque_name...</option>
                        @foreach ($chequeName as $groupTypeId => $groupType)
                          <option value="{{ $groupTypeId }}" {{ ($data->cheque_id ?? '') == $groupTypeId ? 'selected' : '' }}>
                            {{ $groupType }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  </div>             
              </div>
            </div>

      <div class="modal-footer master-modal-footer">
        <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>

        @if(!$isView)
          <button type="submit" form="bank_configuration_form" class="btn btn-primary form-save-btn waves-effect">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
                 width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none" />
              <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
              <path d="M9 12l2 2l4 -4" />
            </svg>
            @if ($formMode == 'edit')
              Update Configuration
            @else
              Save Configuration
            @endif
          </button>
        @endif
      </div>
    </div>
  </div>
</form>
</div>
</div>


<script id="bank-configuration-modal-script">

(function () {    
  let validator;

$(document).ready(function () {

  // validation
  validator = new JustValidate("#bank_configuration_form", {
      validateBeforeSubmitting: true,
      focusInvalidField: true,
  });

  validator
    .addField("#bank_id", [
      { rule: "required", errorMessage: "Please Select bank name" },
    ])
    .addField("#rtgs_id", [
      { rule: "required", errorMessage: "Please Select rtgs name" },
    ])
    .addField("#cheque_id", [
      { rule: "required", errorMessage: "Please Select cheque name ",},
    ])
    .onSuccess((event) => {
      event.preventDefault();
      submitFormAjax(document.getElementById("bank_configuration_form"));
    });
});


// ===================================================================
// SUBMIT HANDLER
// ===================================================================
let isSubmitting = false;

function submitFormAjax(form) {
    if (isSubmitting) return;
    isSubmitting = true;
  
    const $form = $(form);
    const $btn = $("#bank_configuration_modal .form-save-btn");   

    const method = ($form.data("method") || $form.attr("method") || "POST").toUpperCase();
    const url = $form.attr("action");


    const isEdit = method === "PUT" || url.toLowerCase().includes("update");

    // Button loading state
    if ($btn.length) {
        $btn.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            ${isEdit ? "Updating" : "Saving"} <span class="animated-dots"></span>
        `);
    }

    const formData = new FormData(form);

    // Handle billwise
    const isBillwiseValue = formData.get("is_billwise");
    if (!isBillwiseValue || isBillwiseValue == false || isBillwiseValue === '0') {
        formData.set("is_billwise", '0');
    } else {
        formData.set("is_billwise", '1');
    }

    if (isEdit) {
        formData.append("_method", "PUT");
    }
  console.log("method, url", method, url);

    // jQuery Ajax
    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
            const success = data.success;
            const message = data.message;
            // console.log("data");


            if (success) {
                showToast("success", message || (isEdit ? "Bank Configuration updated successfully!" : "Bank Configuration saved successfully!"));
                $('#bank_configuration_modal').modal('hide');
                if (typeof table !== "undefined" && table) {
                    table.replaceData();
                } else if (window.table) {
                    window.table.replaceData();
                }
            } else {
                showToast("error", message || "Something went wrong.");
            }
        },
        error: function (xhr) {
            console.error(xhr);
            const errMsg = xhr.responseJSON?.message || "Server error while submitting. Please try again.";
            showToast("error", errMsg);
        },
        complete: function () {
            // Reset button
            if ($btn.length) {
                $btn.prop("disabled", false).html(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-square-check">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                        <path d="M9 12l2 2l4 -4" />
                    </svg>
                    ${isEdit ? "Update Bank Configuration" : "Save Bank Configuration"}
                `);
            }

            isSubmitting = false;
            if (typeof hideLoader === "function") hideLoader();
        }
    });
  }

})();
</script>