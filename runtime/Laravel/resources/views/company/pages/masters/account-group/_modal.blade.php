@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Account Group';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$parentGroups = $modalData['parentGroups'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="account_group_modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="account_group_modal" aria-hidden="true">
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
        <form id="account_group_form"
              action="{{ $formMode === 'edit' ? route('account-groups.update', $data->id) : route('account-groups.store') }}"
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
          <!-- Account Details -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-users me-2 text-primary"></i>Group Details</div>
            <div class="row g-2">
              <!-- Group Name -->
              <div class="col-lg-12 col-sm-12 col-md-12">
                  <label class="form-label required"><i class="fa-solid fa-people-group text-secondary"></i> Group Name</label>
                  <input type="text" name="name" id="name" class="form-control"
                        placeholder="Enter group name"
                        value="{{ old('name', $data->name ?? '') }}"
                        {{ $isView ? 'readonly' : 'required' }}>
              </div>
              <!-- Group Type -->
              <div class="col-lg-6 col-sm-12 col-md-12">
                  @php $groupTypes = config('constants.account_group_types'); @endphp
                  <label class="form-label required"><i class="fa-solid fa-group-arrows-rotate text-secondary"></i> Group Type</label>
                  <select name="type" id="type" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                    <option value="">Select type...</option>
                    @foreach ($groupTypes as $groupTypeId => $groupType)
                      <option value="{{ $groupTypeId }}" {{ ($data->type ?? '') == $groupTypeId ? 'selected' : '' }}>
                        {{ $groupType }}
                      </option>
                    @endforeach
                  </select>
              </div>
              <!-- Primary Group -->
              <div class="col-lg-6 col-sm-12 col-md-12">
                <label class="form-label required"><i class="fa-solid fa-people-group text-secondary"></i> Primary Group</label>
                <select name="is_primary" id="is_primary" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                  <option value="">Select option...</option>
                  <option value="1" {{ $isPrimary == 1 ? 'selected' : '' }}>Yes</option>
                  <option value="0" {{ $isPrimary == 0 ? 'selected' : '' }}>No</option>
                </select>
              </div>

              <!-- Under Group -->
              <div class="col-lg-12 col-sm-12 col-md-12" id="primary_group_div">
                <label class="form-label required"><i class="fa-solid fa-users-viewfinder text-secondary"></i> Under Group</label>
                <select name="parent_id" id="parent_id" class="form-select select2" {{ $isView ? 'disabled' : 'required' }}>
                  <option value="">Select parent group...</option>
                  @foreach ($parentGroups as $parentGroup)
                    <option value="{{ $parentGroup->id }}" {{ ($data->parent_id ?? '') == $parentGroup->id ? 'selected' : '' }}>
                      {{ $parentGroup->name }}
                    </option>
                  @endforeach
                </select>
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
        <button type="submit" form="account_group_form" class="btn btn-primary form-save-btn waves-effect">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-square-check"
               width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
               fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
            <path d="M3 3m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
            <path d="M9 12l2 2l4 -4" />
          </svg>
          @if ($formMode == 'edit')
            Update Group
            @else
            Save Group
          @endif
        </button>
        @endif
      </div>
    </div>
  </div>
</div>

<script id="account-group-modal-script">

(function () {    
  let validator;

  // validation
  validator = new JustValidate("#account_group_form", {
      validateBeforeSubmitting: true,
      focusInvalidField: true,
  });

  validator
    .addField("#name", [
      { rule: "required", errorMessage: "Group Name is a required field" },
    ])
    .addField("#type", [
      { rule: "required", errorMessage: "Group Type is a required field" },
    ])
    .addField("#is_primary", [
      {
        rule: "required",
        errorMessage: "Select Primary Group Option is required",
      },
    ])
    .addField("#parent_id", [
      {
        validator: (value) => {
          const isPrimary = document.querySelector("#is_primary")?.value;
          if (isPrimary === "0") return value.trim() !== "";
          return true;
        },
        errorMessage:
          "Parent Group is required when 'Primary Group = No' is selected",
      },
    ])
    .onSuccess((event) => {
      event.preventDefault();
      submitFormAjax(document.getElementById("account_group_form"));
  });

// ===================================================================
// SUBMIT HANDLER
// ===================================================================
let isSubmitting = false;

function submitFormAjax(form) {
    if (isSubmitting) return;
    isSubmitting = true;
  
    const $form = $(form);
    const $btn = $("#account_group_modal .form-save-btn");
    // const modalEl = document.querySelector("#account_group_modal");
    // const modal = bootstrap.Modal.getInstance(modalEl);

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
                showToast("success", message || (isEdit ? "Account Group updated successfully!" : "Account Group saved successfully!"));
                $('#account_group_modal').modal('hide');
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
                    ${isEdit ? "Update Account Group" : "Save Account Group"}
                `);
            }

            isSubmitting = false;
            if (typeof hideLoader === "function") hideLoader();            
        }
    });
  }


  
const modalEl = document.getElementById("account_group_modal");

if (!modalEl) {
  console.error("Modal element not found in response HTML");
  return;
}

modalEl.addEventListener("shown.bs.modal", function () {

    const isPrimary = document.getElementById("is_primary");
    const primaryGroupDiv = document.getElementById("primary_group_div");

    if (isPrimary && primaryGroupDiv) {
      const togglePrimaryGroupDiv = () => {
        const value = isPrimary.value;
        
        const shouldShow = value === "0" || value === "";
        primaryGroupDiv.style.display = shouldShow ? "block" : "none";
        togglePrimarySection(primaryGroupDiv, shouldShow);
      };

      togglePrimaryGroupDiv();
      $(isPrimary).on("change.select2", togglePrimaryGroupDiv);

    }

    const formMode = document.getElementById("form_mode")?.value;
    if (formMode === "view") {
      modalEl.classList.add("view-mode");

      // Disable all Select2 elements
      $(modalEl)
        .find(".select2")
        .each(function () {
          $(this).prop("disabled", true).trigger("change.select2");
        });
    }

    document.getElementById("name")?.focus();
  },
  { once: true }

);

})();

function togglePrimarySection(sectionEl, shouldShow) {
  sectionEl.style.display = shouldShow ? "block" : "none";

  sectionEl.querySelectorAll("input, select, textarea").forEach((el) => {
    const isSelect2 = $(el).hasClass("select2");

    if (isSelect2) {
      if (!shouldShow) {
        $(el).val("").trigger("change");
        $(el).prop("disabled", true);
      } else {
        $(el).prop("disabled", false);
      }
      return;
    }

    if (!shouldShow) el.value = "";
    el.disabled = !shouldShow;
  });
}

</script>

