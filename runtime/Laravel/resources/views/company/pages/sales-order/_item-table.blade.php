<table class="table table-sm align-middle border mb-0">
    <thead class="table-light text-nowrap">
        <tr>
            <th width="200" class="required">
                <i class="fa-solid fa-box text-secondary me-1"></i> Item Name
            </th>
            <th width="120">
                <i class="fa-solid fa-scale-balanced text-secondary me-1"></i> Unit
            </th>
            <th class="text-end required" width="120">
                <i class="fa-solid fa-cubes text-secondary me-1"></i> Qty
            </th>
            <th width="200">
                <i class="fa-solid fa-clipboard-list text-secondary me-1"></i> Destination
            </th>
            <th width="200">
                <i class="fa-solid fa-clipboard-list text-secondary me-1"></i> Condition Name
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

            <th class="text-end" width="120">
                <i class="fa-solid fa-percent text-secondary me-1"></i> Incl. Rate
            </th>
            <th class="text-end required" width="120">
                <i class="fa-solid fa-tags text-secondary me-1"></i> Rate
            </th>
            <th class="text-end" width="140">
                <i class="fa-solid fa-indian-rupee-sign text-secondary me-1"></i> Amount
            </th>
        </tr>

    </thead>

    <tbody id="item_table_body">
        <tr data-row-id="1" class="item-row">
            <td>
                <select name="items[0][item_id]" class="form-select form-select-sm  item_id">
                    <option value="">Select item ...</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="text" name="items[0][unit_name]" class="form-control form-control-sm unit_name"
                    value="--" readonly>
            </td>

            <td>
                <input type="text" name="items[0][quantity]" class="form-control form-control-sm text-end quantity"
                    placeholder="0.000">
            </td>
            <td>
                <select name="items[0][destination_id]" class="form-select form-select-sm  destination_id">
                    <option value="">Select destination ...</option>
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <select name="items[0][condition_id]" class="form-select form-select-sm  condition_id">
                    <option value="">Select condition ...</option>
                    @foreach ($conditions as $condition)
                        <option value="{{ $condition->id }}">{{ $condition->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][cgst_rate]"
                        class="form-control form-control-sm text-end cgst_rate" value="--" disabled>
                    <span class="input-icon-addon d-none cgst_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </td>
            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][sgst_rate]"
                        class="form-control form-control-sm text-end sgst_rate" value="--" disabled>
                    <span class="input-icon-addon d-none sgst_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
            </td>
            <td>
                <div class="input-icon">
                    <input type="text" name="items[0][igst_rate]"
                        class="form-control form-control-sm text-end igst_rate" value="--" disabled>
                    <span class="input-icon-addon d-none igst_loader">
                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                    </span>
                </div>
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
                    class="form-control form-control-sm text-end amount bg-light non-selectable" placeholder="0.00">
            </td>
        </tr>
    </tbody>
</table>
