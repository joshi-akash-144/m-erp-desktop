@php
$formMode = $modalData['form_mode'] ?? 'create'; // 'create' | 'edit' | 'view'
$title = $modalData['title'] ?? 'Add Tds Category';
$uuid = $modalData['uuid'] ?? '';
$data = $modalData['data'] ?? null;
$parentGroups = $modalData['parentGroups'] ?? [];

$isPrimary = is_null($data->parent_id ?? null) ? 1 : 0;


$isView = $formMode === 'view';
@endphp

<div class="modal fade master-modal" id="tds_category_modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="tds_category_modal" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-centered">
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
        <form id="tds_category_form"
              action="{{ $formMode === 'edit' ? route('tds-categories.update', $data->id) : route('tds-categories.store') }}"
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
          <!-- Category -->
          <div class="master-form-section">
            <div class="master-section-title "><i class="fa-solid fa-list text-primary"></i>&nbsp;Category Details</div>
            <div class="row g-2">
              <!-- Section -->
              <div class="col-lg-3 col-sm-12 col-md-12">
                  <label class="form-label required"><i class="fa-solid fa-book text-secondary"></i> Section</label>
                  <input type="text" name="section" id="section" class="form-control"
                        placeholder="{{ $isView ? '' : 'Enter Section' }}" value="{{ old('section', $data->section ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
              </div>

              <!-- Category Name -->
              <div class="col-lg-4 col-sm-12 col-md-12">
                <label class="form-label required"><i class="fa-solid fa-list text-secondary"></i> Category Name</label>
                <input type="text" name="category_name" id="category_name" class="form-control" placeholder="{{ $isView ? '' : 'Enter Category Name' }}"
                  value="{{ old('category_name', $data->category_name ?? '') }}" {{ $isView ? 'readonly' : 'required' }}>
              </div>
              
              <!-- Default Account -->
              <div class="col-lg-5 col-sm-12 col-md-12 mt-2">
                <label class="form-label"><i class="fa-solid fa-building-columns text-secondary"></i> Default Account for TDS Deduction</label>
                <select name="default_account_id" id="default_account_id" class="form-select select2-account" {{ $isView ? 'disabled' : '' }}>
                  <option value="">Select Default Account...</option>
                  @if(isset($modalData['accounts']))
                    @foreach($modalData['accounts'] as $account)
                      <option value="{{ $account->id }}" {{ ($data->default_account_id ?? '') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
                    @endforeach
                  @endif
                </select>
              </div>

<!-- Removed rate, type, applicable_to, description -->

</div>
              </div>

<!-- Payee Categories Detail Table -->
<div class="master-form-section mt-3">
  <div class="master-section-title "><i class="fa-solid fa-list text-primary"></i>&nbsp;Payee Category Details</div>
  <div class="table-responsive">
    <table class="table table-bordered table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th class="text-center" style="width: 50px;">Sr No.</th>
          <th>Payee Category</th>
          <th class="text-center" style="width: 150px;">Threshold Limit</th>
          <th class="text-center" style="width: 150px;">TDS (with Pan)%</th>
          <th class="text-center" style="width: 150px;">TDS (Without Pan)%</th>
          <th class="text-center" style="width: 50px;">
            @if(!$isView)
            <button type="button" class="btn btn-sm btn-primary add-payee-row non-selectable" tabindex="-1"
              title="Add Row"><i class="fa-solid fa-plus non-selectable"></i></button>
            @endif
          </th>
        </tr>
      </thead>
      <tbody id="payee_category_table_body">
        @php
        $details = $data ? $data->details : collect([]);
        $payeeCategoriesList = $modalData['payeeCategories'] ?? [];
        @endphp
        @if($details->count() > 0)
        @foreach($details as $index => $detail)
        <tr>
          <td class="text-center row-sr-no align-middle">{{ $index + 1 }}</td>
          <td>
            <select name="details[{{ $index }}][payee_category_id]" class="form-select select2-payee" {{ $isView
              ? 'disabled' : 'required' }}>
              <option value="">Select...</option>
              @foreach($payeeCategoriesList as $pc)
              <option value="{{ $pc->id }}" {{ $detail->payee_category_id == $pc->id ? 'selected' : '' }}>{{
                $pc->payee_category }}</option>
              @endforeach
            </select>
</td>
<td><input type="number" step="0.01" name="details[{{ $index }}][threshold_limit]" class="form-control text-end"
    value="{{ $detail->threshold_limit }}" {{ $isView ? 'readonly' : '' }}></td>
<td><input type="number" step="0.01" name="details[{{ $index }}][tds_with_pan]" class="form-control text-end"
    value="{{ $detail->tds_with_pan }}" {{ $isView ? 'readonly' : '' }}></td>
<td><input type="number" step="0.01" name="details[{{ $index }}][tds_without_pan]" class="form-control text-end"
    value="{{ $detail->tds_without_pan }}" {{ $isView ? 'readonly' : '' }}></td>
<td class="text-center align-middle">
  @if(!$isView)
  <button type="button" class="btn btn-sm btn-danger remove-payee-row non-selectable" tabindex="-1"><i
      class="fa-solid fa-trash non-selectable"></i></button>
  @endif
</td>
</tr>
@endforeach
@else
@if(!$isView)
@for($i = 0; $i < 3; $i++) <tr>
  <td class="text-center row-sr-no align-middle">{{ $i + 1 }}</td>
  <td>
    <select name="details[{{ $i }}][payee_category_id]" class="form-select select2-payee">
      <option value="">Select...</option>
      @foreach($payeeCategoriesList as $pc)
      <option value="{{ $pc->id }}">{{ $pc->payee_category }}</option>
      @endforeach
    </select>
</td>
<td><input type="number" step="0.01" name="details[{{ $i }}][threshold_limit]" class="form-control text-end" value="">
</td>
<td><input type="number" step="0.01" name="details[{{ $i }}][tds_with_pan]" class="form-control text-end" value=""></td>
<td><input type="number" step="0.01" name="details[{{ $i }}][tds_without_pan]" class="form-control text-end" value="">
</td>
<td class="text-center align-middle">
  <button type="button" class="btn btn-sm btn-danger remove-payee-row non-selectable" tabindex="-1"><i
      class="fa-solid fa-trash non-selectable"></i></button>
</td>
</tr>
@endfor
@endif
@endif
</tbody>
</table>
            </div>
</div>
      </div>
    </div>
</form>
</div>

<div class="modal-footer">
       <button type="button" class="btn btn-link non-selectable waves-effect" data-bs-dismiss="modal">Close</button>
        @if(!$isView)
        <button type="submit" form="tds_category_form" class="btn btn-primary form-save-btn waves-effect">
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


<script id="tds-category-modal-script">

(function () {    
  let validator;

$(document).ready(function () {


   const formEl = document.getElementById("tds_category_form");
 
    // Prevent native form submission (Enter key redirect fix)
    $(formEl).on("submit", function (e) {
        e.preventDefault();
    }); 
    
  // validation
  validator = new JustValidate("#tds_category_form", {
      validateBeforeSubmitting: true,
      focusInvalidField: true,
  });

   validator
    .addField("#section", [
      { rule: "required", errorMessage: "Section name is a required field" },
    ])
    .addField("#category_name", [
      {
        rule: "required",
        errorMessage: "Category Name name is a required field",
      },
    ])

    .onSuccess((event) => {
      event.preventDefault();
      submitFormAjax(document.getElementById("tds_category_form"));
    });

$("#category_name").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $(".form-save-btn").focus();
        }
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
    const $btn = $("#tds_category_modal .form-save-btn");

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
                showToast("success", message || (isEdit ? "Tds Category updated successfully!" : "Tds Category saved successfully!"));
                $('#tds_category_modal').modal('hide');
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
                    ${isEdit ? "Update Tds Category" : "Save Tds Category"}
                `);
            }

            isSubmitting = false;
            if (typeof hideLoader === "function") hideLoader();
        }
    });
  }

// Payee Category Detail Row Management
$(document).ready(function() {
let pcOptions = '';
@if(isset($modalData['payeeCategories']))
@foreach($modalData['payeeCategories'] as $pc)
pcOptions += '<option value="{{ $pc->id }}">{{ $pc->payee_category }}</option>';
@endforeach
@endif

function initPayeeSelect2(element) {
$(element).select2({
dropdownParent: $('#tds_category_modal'),
placeholder: 'Select...',
allowClear: true,
width: '100%',
theme: 'bootstrap-5'
});

// Ensure the pre-selected value is visually updated by Select2
if ($(element).val()) {
    $(element).trigger('change');
}
}

function initAccountSelect2(element) {
    $(element).select2({
        dropdownParent: $('#tds_category_modal'),
        placeholder: 'Select Default Account...',
        allowClear: true,
        width: '100%',
        theme: 'bootstrap-5'
    });
}

// Initialize existing rows
$('.select2-payee').each(function() {
    initPayeeSelect2(this);
});

$('.select2-account').each(function() {
    initAccountSelect2(this);
});

$(document).on('click', '.add-payee-row', function() {
const tbody = $('#payee_category_table_body');
const rowCount = tbody.find('tr').length;

const newRowHTML = `
<tr>
  <td class="text-center row-sr-no align-middle">${rowCount + 1}</td>
  <td>
    <select name="details[${rowCount}][payee_category_id]" class="form-select select2-payee" required>
      <option value="">Select...</option>
      ${pcOptions}
    </select>
  </td>
  <td><input type="number" step="0.01" name="details[${rowCount}][threshold_limit]" class="form-control text-end"
      value=""></td>
  <td><input type="number" step="0.01" name="details[${rowCount}][tds_with_pan]" class="form-control text-end" value="">
  </td>
  <td><input type="number" step="0.01" name="details[${rowCount}][tds_without_pan]" class="form-control text-end"
      value=""></td>
  <td class="text-center align-middle">
    <button type="button" class="btn btn-sm btn-danger remove-payee-row non-selectable" tabindex="-1"><i
        class="fa-solid fa-trash non-selectable"></i></button>
  </td>
</tr>
`;

const $newRow = $(newRowHTML);
tbody.append($newRow);
initPayeeSelect2($newRow.find('.select2-payee'));
updateRowIndexes();
});

$(document).on('click', '.remove-payee-row', function() {
if ($('#payee_category_table_body tr').length > 1) {
const $row = $(this).closest('tr');
$row.find('.select2-payee').select2('destroy');
$row.remove();
updateRowIndexes();
} else {
showToast("error", "At least one payee category detail is required.");
}
});

function updateRowIndexes() {
$('#payee_category_table_body tr').each(function(index) {
$(this).find('.row-sr-no').text(index + 1);
$(this).find('select, input').each(function() {
const name = $(this).attr('name');
if (name) {
$(this).attr('name', name.replace(/details\[\d+\]/, `details[${index}]`));
}
});
});
}
});
})();
</script>

</script>

