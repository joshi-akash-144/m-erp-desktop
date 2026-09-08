@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Tax Category';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$parentGroups = $modalData['parentGroups'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="tax_category_modal" tabindex="-1"  data-bs-backdrop="static" data-bs-keyboard="false"  aria-labelledby="tax_category_modal" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
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
          {{-- <div class="d-flex align-items-center justify-content-start gap-3 py-2 px-1">
            <!-- Icon Circle -->
            <div class="d-flex justify-content-center align-items-center rounded-circle bg-primary bg-gradient text-white shadow-sm"
              style="width:42px; height:42px;">   
                @include('icons.database')
                {{-- @if($modalData['form_mode'] === 'create')           
                  {{-- <i class="fa-solid fa-plus"  style="font-size:19px"></i> 
                  @include('icons.plus', ['size' => 25])
                @elseif($modalData['form_mode'] === 'view') 
                  @include('icons.eye', ['size' => 25])
                @else
                  {{-- <i class="fa-solid fa-pen-to-square" style="font-size:15px"></i>
                  @include('icons.pencil', ['size' => 25])
                @endif 
            </div>          
            <!-- Title -->
            <div class="d-flex flex-column justify-content-center ">
              <h4 class="mb-0 fw-semibold text-dark" style="line-height:1.2;">{{ $title }}</h4>
               <small class="badge bg-teal text-teal-fg">Master Data Management</small> 
            </div>
          </div> --}}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="tax_category_form"
              action="{{ $formMode === 'edit' ? route('tax-categories.update', $data->id) : route('tax-categories.store') }}"
              method="POST"
              class="needs-validation"
              novalidate>

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
          <!-- Tax Category Details -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-list tax-primary"></i>&nbsp;Category Details</div>
            <div class="row g-2">
              <!-- Tax Category Name -->
              <div class="col-lg-6 col-sm-12 col-md-12">
                  <label class="form-label required"><i class="fa-solid fa-people-group text-secondary"></i> Name</label>
                  <input type="text" name="name" id="name" class="form-control"
                        placeholder="Enter Name"
                        value="{{ old('name', $data->name ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
              </div>
              <!-- Type -->
              <div class="col-lg-6 col-sm-12 col-md-12">
                  @php $groupTypes = config('constants.tax_category_types'); @endphp
                  <label class="form-label required"><i class="fa-solid fa-group-arrows-rotate text-secondary"></i> Type</label>
                  <select name="type" id="type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                    <option value="">Select type...</option>
                    @foreach ($groupTypes as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->type ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                    @endforeach
                  </select>
              </div>                                                                       
            </div>
          </div> 
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-list tax-primary"></i>&nbsp;GST Rate Detail</div>
              <div class="col-lg-6 col-sm-12 col-md-12">
                <div id="zero_tax_type_section">              
                  @php $groupTypes = config('constants.zero_rate_tax_type'); @endphp
                  <label class="form-label required"><i class="fa-solid fa-group-arrows-rotate text-secondary"></i> Type</label>
                  <select name="zero_tax_type" id="zero_tax_type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                    <option value="">Select Zero Tax Type...</option>
                    @foreach ($groupTypes as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->zero_tax_type ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                    @endforeach
                  </select>
              </div>
              </div>
            <div class="row g-2">
              <!-- Tax Category Name -->
              <div class="col-lg-4 col-sm-12 col-md-12">
                <label class="form-label"><i class="fa-solid fa-percent text-secondary"></i> IGST</label>
                <input type="text" name="igst" id="igst" class="form-control" placeholder="{{ $isView ? '' : 'Integrated Tax (IGST) %' }}"
                  value="{{ old('igst', $data->igst ?? 00) }}" {{ $isView ? 'readonly' : 'required' }}>
              </div>           
              <div class="col-lg-4 col-sm-12 col-md-12">
                <label class="form-label"><i class="fa-solid fa-percent text-secondary"></i> CGST</label>
                <input type="text" name="cgst" id="cgst" class="form-control" placeholder="{{ $isView ? '' : 'Central Tax (CGST) %' }}"
                  value="{{ old('cgst', $data->cgst ?? 00) }}" {{ $isView ? 'readonly' : 'required' }}>
              </div>
              <div class="col-lg-4 col-sm-12 col-md-12">
                <label class="form-label"><i class="fa-solid fa-percent text-secondary"></i> SGST</label>
                <input type="text" name="sgst" id="sgst" class="form-control" placeholder="{{ $isView ? '' : 'State/UT Tax (SGST) %' }}"
                  value="{{ old('sgst', $data->sgst ?? 00) }}" {{ $isView ? 'readonly' : 'required' }}>
              </div>
            </div>
          </div>
      </div>
    </div>
</form>
</div>

<div class="modal-footer">
       <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>
        @if(!$isView)
        <button type="submit" form="tax_category_form" class="btn btn-primary form-save-btn waves-effect">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
               width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
               fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
            <path d="M9 12l2 2l4 -4" />
          </svg>
          @if ($formMode == 'edit')
            Update Category
            @else
            Save Category
          @endif
        </button>
        @endif
      </div>
    </div>
  </div>
</div>


<script id="tax-category-modal-script">

(function () {    
  let validator;

$(document).ready(function () {

    const formEl = document.getElementById("tax_category_form");

    // Prevent native form submission (Enter key redirect fix)
    $(formEl).on("submit", function (e) {
        e.preventDefault();
    });
  // validation
  validator = new JustValidate("#tax_category_form", {
      validateBeforeSubmitting: true,
      focusInvalidField: true,
  });

  validator
    .addField("#name", [
      { rule: "required", errorMessage: "Name is a required field" },
    ])
    .addField("#type", [
      { rule: "required", errorMessage: "Select Type is a required field" },
    ])
   
    .onSuccess((event) => {
      event.preventDefault();
      submitFormAjax(document.getElementById("tax_category_form"));
    });

    // Custom Enter Key Navigation
    $("#sgst").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $(".form-save-btn").focus();
        }
    });

    const modalEl = document.getElementById("tax_category_modal");
    const zeroTaxTypeSelect = modalEl.querySelector("#zero_tax_type");
    
    if (zeroTaxTypeSelect) {
        // Use jQuery for Select2 change event if select2 is initialized, 
        // otherwise native change event.
        $(zeroTaxTypeSelect).on("change", function() {
            toggleTaxInputs(modalEl, true); // user changed → allow reset
        });
    }
     
    toggleTaxInputs(modalEl, false); // initial load → preserve DB values 
 
});


function toggleTaxInputs(scopeEl, resetValues) {  
  if (!scopeEl) {
    console.error("Scope element is missing.");
    return;
  }

  const igstInput = scopeEl.querySelector("#igst");
  const cgstInput = scopeEl.querySelector("#cgst");
  const sgstInput = scopeEl.querySelector("#sgst");
  const zeroTaxTypeSelect = $(scopeEl.querySelector("#zero_tax_type")); // Get the jquery object

  if (!igstInput || !cgstInput || !sgstInput || !zeroTaxTypeSelect.length) {
    console.error("Missing required DOM elements:", {
      igst: !!igstInput,
      cgst: !!cgstInput,
      sgst: !!sgstInput,
      select: !!zeroTaxTypeSelect.length,
    });
    return;
  }

  const typeValue = zeroTaxTypeSelect.val();
  
  if (typeValue === "taxable") {
    // If taxable, inputs are editable
    igstInput.removeAttribute("readonly");
    cgstInput.removeAttribute("readonly");
    sgstInput.removeAttribute("readonly");
    
    igstInput.classList.remove("bg-light-lt");
    cgstInput.classList.remove("bg-light-lt");
    sgstInput.classList.remove("bg-light-lt");
  } else {   
    // If not taxable, inputs are readonly
    igstInput.setAttribute("readonly", true);
    cgstInput.setAttribute("readonly", true);
    sgstInput.setAttribute("readonly", true);
    // Only reset values to 0 when the user actively changes the type,
    // not on initial load (to preserve saved DB values in edit/view mode)
    if (resetValues) {
      igstInput.value = 0;
      cgstInput.value = 0;
      sgstInput.value = 0;
    }
  }
}


// ===================================================================
// SUBMIT HANDLER
// ===================================================================
let isSubmitting = false;

function submitFormAjax(form) {
    if (isSubmitting) return;
    isSubmitting = true;
       
    const $form = $(form);
    const $btn = $("#tax_category_modal .form-save-btn");

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
          console.log("data");


            if (success) {
                showToast("success", message || (isEdit ? "Tax Category updated successfully!" : "Tax Category saved successfully!"));
                table.setData();
                $('#tax_category_modal').modal('hide');
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
                    ${isEdit ? "Update Tax Category" : "Save Tax Category"}
                `);
            }

            isSubmitting = false;
            if (typeof hideLoader === "function") hideLoader();
        }
    });
  }


})();
</script>

