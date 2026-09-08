<table class="table table-sm align-middle border mb-0" id="item_table">
    <thead class="table-light text-nowrap">
        <tr>
            <th class="text-center" style="width:40px;">
                <i class="fa-solid fa-list-ol text-secondary"></i>
            </th>

            <th class="required" style="width:230px;">
                <i class="fa-solid fa-box text-secondary me-1"></i> Item Name
            </th>

            <th style="width:140px;">
                <i class="fa-solid fa-ruler-combined text-secondary me-1"></i> Unit
            </th>

            <th style="width:230px;">
                <i class="fa-solid fa-location-dot text-secondary me-1"></i> Destination Name
            </th>
            <th style="width:230px;">
                <i class="fa-solid fa-file-invoice text-secondary me-1"></i> Sales Order No.
                {{-- <span class="form-help bg-primary text-white rounded-circle px-2" data-bs-toggle="popover"
                    data-bs-placement="top" data-bs-html="true" data-bs-content="
                  Use the <strong>Space key</strong> to view the Sales Order list
                ">
                    <i class="fa-solid fa-circle-question"></i>
                </span> --}}
            </th>


            <th style="width:230px;">
                <i class="fa-solid fa-clipboard-check text-secondary me-1"></i> Condition Name
            </th>

            <th class="text-center" style="width:75px;">
                <i class="fa-solid fa-percent text-secondary"></i> CGST
            </th>

            <th class="text-center" style="width:75px;">
                <i class="fa-solid fa-percent text-secondary"></i> SGST
            </th>

            <th class="text-center" style="width:75px;">
                <i class="fa-solid fa-percent text-secondary"></i> IGST
            </th>

            <th class="text-center" style="width:100px;">
                <i class="fa-solid fa-boxes-stacked text-secondary"></i> Bags
            </th>

            <th class="text-end" style="width:140px;">
                <i class="fa-solid fa-weight-hanging text-secondary me-1"></i> P.Qty
            </th>

            <th class="text-end" style="width:140px;">
                <i class="fa-solid fa-weight-hanging text-secondary me-1"></i> Qty
            </th>

            <th class="text-end" style="width:160px;">
                <i class="fa-solid fa-money-bill-wave text-secondary me-1"></i> Incl. Rate
            </th>

            <th class="text-end" style="width:160px;">
                <i class="fa-solid fa-tag text-secondary me-1"></i> Rate
            </th>

            <th class="text-end" style="width:190px;">
                <i class="fa-solid fa-indian-rupee-sign text-secondary me-1"></i> Amount
            </th>
        </tr>


    </thead>

    <tbody id="item_table_body">
        <tr data-row-id="1" class="item-row">
            <td class="p-1 bg-white">
                <span class="erp-btn-icon delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon icon-tabler-trash">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M4 7l16 0" />
                        <path d="M10 11l0 6" />
                        <path d="M14 11l0 6" />
                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                    </svg>
                </span>
            </td>

            <td>
                <select name="items[0][item_id]" class="form-select form-select-sm  item_id">
                    <option value="">Select Item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][unit_name]" class="form-control form-control-sm unit_name bg-light border-1 border-secondary-subtle"
                        readonly placeholder="Unit Name" tabindex="-1">
                    <span class="input-icon-addon d-none unit_name_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </td>

            {{-- <td>
                <input type="text" name="items[0][order_number]" class="form-control form-control-sm order_number"
                    value="" placeholder="Space → PO List">
            </td> --}}

            <td>
                <select name="items[0][destination_id]" class="form-control form-control-sm destination_id">
                    <option value="">Select Destination</option>
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach
                </select>

            </td>

            <td>
                <select name="items[0][sales_order_id]" class="form-control form-control-sm sales_order_id">
                    <option value="">Select Sales Order</option>
                </select>
                <input type="hidden" name="items[0][sales_order_item_id]" class="sales_order_item_id">
                <input type="hidden" name="items[0][sales_order_serial]" class="sales_order_serial">
            </td>

            <td>
                <select name="items[0][condition_id]" class="form-select form-select-sm  condition_id">
                    <option value="">Select Condition</option>
                    @foreach ($conditions as $condition)
                        <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][cgst_rate]" class="form-control form-control-sm text-end cgst_rate bg-light border-1 border-secondary-subtle"
                        value="--" disabled>
                    <span class="input-icon-addon d-none cgst_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </td>
            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][sgst_rate]" class="form-control form-control-sm text-end sgst_rate bg-light border-1 border-secondary-subtle"
                        value="--" disabled>
                    <span class="input-icon-addon d-none sgst_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </td>
            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][igst_rate]" class="form-control form-control-sm text-end igst_rate bg-light border-1 border-secondary-subtle"
                        value="--" disabled>
                    <span class="input-icon-addon d-none igst_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </td>
            <td>
                <input type="text" name="items[0][bag_count]" class="form-control form-control-sm bag_count bag-count" value="">
            </td>
            <td>
                <input type="text" name="items[0][party_quantity]"
                    class="form-control form-control-sm text-end party_quantity" placeholder="0.000">
            </td>

            <td>
                <input type="text" name="items[0][quantity]" class="form-control form-control-sm text-end quantity"
                    placeholder="0.000">
            </td>

            <td>
                <input type="text" name="items[0][inclusive_rate]"
                    class="form-control form-control-sm text-end inclusive_rate" placeholder="0.00">
            </td>

            <td>
                <input type="text" name="items[0][rate]" class="form-control form-control-sm text-end rate"
                    placeholder="0.00">
            </td>

            <td>
                <input type="text" name="items[0][amount]"
                    class="form-control form-control-sm text-end amount non-selectable" placeholder="0.00">
            </td>

        </tr>
    </tbody>
</table>

<template id="item_row_template">
    <tr data-row-id="1" class="item-row">
        <td class="p-1 bg-white">
            <span class="erp-btn-icon delete">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon icon-tabler-trash">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                    <path d="M4 7l16 0" />
                    <path d="M10 11l0 6" />
                    <path d="M14 11l0 6" />
                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                </svg>
            </span>
        </td>

        <td>
            <select name="items[0][item_id]" class="form-select form-select-sm  item_id"></select>
        </td>

        <td>
            <div class="input-icon">
                <input type="text" name="items[0][unit_name]" class="form-control form-control-sm unit_name bg-light border-1 border-secondary-subtle"
                    readonly placeholder="Unit Name" tabindex="-1">
                <span class="input-icon-addon d-none unit_name_loader">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </span>
            </div>
        </td>

        {{-- <td>
            <input type="text" name="items[0][order_number]" class="form-control form-control-sm order_number"
                value="" placeholder="Space → PO List">
        </td> --}}

        <td>
            <select name="items[0][destination_id]" class="form-control form-control-sm destination_id">
            </select>

        </td>
        <td>
            <select name="items[0][sales_order_id]" class="form-select form-select-sm  sales_order_id"></select>
            <input type="hidden" name="items[0][sales_order_item_id]" class="sales_order_item_id">
            <input type="hidden" name="items[0][sales_order_serial]" class="sales_order_serial">
        </td>

        <td>
            <select name="items[0][condition_id]" class="form-select form-select-sm  condition_id">
            </select>
        </td>

        <td>
            <div class="input-icon">
                <input type="text" name="items[0][cgst_rate]" class="form-control form-control-sm text-end cgst_rate bg-light border-1 border-secondary-subtle"
                    value="--" disabled>
                <span class="input-icon-addon d-none cgst_loader">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </span>
            </div>
        </td>
        <td>
            <div class="input-icon">
                <input type="text" name="items[0][sgst_rate]" class="form-control form-control-sm text-end sgst_rate bg-light border-1 border-secondary-subtle"
                    value="--" disabled>
                <span class="input-icon-addon d-none sgst_loader">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </span>
            </div>
        </td>
        <td>
            <div class="input-icon">
                <input type="text" name="items[0][igst_rate]" class="form-control form-control-sm text-end igst_rate bg-light border-1 border-secondary-subtle"
                    value="--" disabled>
                <span class="input-icon-addon d-none igst_loader">
                    <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                </span>
            </div>
        </td>
        <td>
            <input type="text" name="items[0][bag_count]" class="form-control form-control-sm bag-count bag_count" value="">
        </td>
        <td>
            <input type="text" name="items[0][party_quantity]"
                class="form-control form-control-sm text-end party_quantity" placeholder="0.000">
        </td>

        <td>
            <input type="text" name="items[0][quantity]" class="form-control form-control-sm text-end quantity"
                placeholder="0.000">
        </td>

        <td>
            <input type="text" name="items[0][inclusive_rate]"
                class="form-control form-control-sm text-end inclusive_rate" placeholder="0.00">
        </td>

        <td>
            <input type="text" name="items[0][rate]" class="form-control form-control-sm text-end rate"
                placeholder="0.00">
        </td>

        <td>
            <input type="text" name="items[0][amount]"
                class="form-control form-control-sm text-end amount non-selectable" placeholder="0.00">
        </td>

    </tr>
</template>