@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Payee Category Group';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
// $parentGroups = $modalData['parentGroups'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="payee_category_modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="payee_category_modal" aria-hidden="true">
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
        <form id="payee_category_form"
              action="{{ $formMode === 'edit' ? route('payee-categories.update', $data->id) : route('payee-categories.store') }}"
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
          <!-- Payee Categories Details -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-list"></i>&nbsp;Category</div>
            <div class="row g-2">
              <!-- Payee Category -->
              <div class="col-lg-12 col-sm-12 col-md-12">
                  <label class="form-label required"><i class="fa-solid fa-users"></i> Payee Category</label>
                  <input type="text" name="payee_category" id="payee_category" class="form-control"
                        placeholder="{{ $isView ? '' : 'Enter Payee Category' }}"
                        value="{{ old('payee_category', $data->payee_category ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
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
        <button type="submit" form="payee_category_form" class="btn btn-primary form-save-btn waves-effect">
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

<script id="payee-category-modal-script">

(function () {    
  let validator;

$(document).ready(function () {

  $(document).ready(function () {
    const formEl = document.getElementById("payee_category_form");
    // Prevent native form submission (Enter key redirect fix)
    $(formEl).on("submit", function (e) {
        e.preventDefault();
    });
  });

  // validation
  validator = new JustValidate("#payee_category_form", {
      validateBeforeSubmitting: true,
      focusInvalidField: true,
  });

    validator
    .addField("#payee_category", [
      { rule: "required", errorMessage: "Payee Category is a required field" },
    ])

    .onSuccess((event) => {
      event.preventDefault();
      submitFormAjax(document.getElementById("payee_category_form"));
    });

  $("#payee_category").on("keydown", function (e) {
    if (e.which === 13) {
        e.preventDefault();
        $(".form-save-btn").focus();
    }
  });

  return validator;
});


// ===================================================================
// SUBMIT HANDLER
// ===================================================================
let isSubmitting = false;

function submitFormAjax(form) {
    if (isSubmitting) return;
    isSubmitting = true;
  
    const $form = $(form);
    const $btn = $("#payee_category_modal .form-save-btn");

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
                showToast("success", message || (isEdit ? "Payee Category updated successfully!" : "Payee Category saved successfully!"));
                $('#payee_category_modal').modal('hide');
                table.setData();

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
                    ${isEdit ? "Update Payee Category" : "Save Payee Category"}
                `);
            }

            isSubmitting = false;
            if (typeof hideLoader === "function") hideLoader();
        }
    });
  }

})();
</script>

