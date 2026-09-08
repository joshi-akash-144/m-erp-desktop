<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\MasterDataService;
use App\Helpers\AjaxResponse;
use App\Models\Item;
use App\Models\Penalty;
use Throwable;

class PenaltyController extends Controller
{
    protected MasterDataService $masterService;


    public function __construct(MasterDataService $masterService)
    {
        $this->masterService = $masterService;
    }

    public function index(Request $request)
    {    
        if ($request->ajax()) {           
            $companyId = company_id();
            
            $query = Penalty::with(['item.unit'])
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc');

            if ($request->filled('item_id')) {
                $query->where('item_id', $request->input('item_id'));
            }

            $perPage = (int)$request->input('size', 50);
            $penalties = $query->paginate($perPage);

            $data = collect($penalties->items())->map(function($penalty, $index) use ($penalties) {
                $item = $penalty->item;
                $unitName = $item && $item->unit ? ' (' . $item->unit->name . ')' : '';
                return [
                    'id' => $penalty->id,
                    'sr_no' => ($penalties->currentPage() - 1) * $penalties->perPage() + ($index + 1),
                    'item_name' => $item ? $item->name . $unitName : 'N/A',
                    'amount' => $penalty->amount,
                ];
            });

            $permissions = userPermissions([
                'penalty.view',
                'penalty.update',
                'penalty.delete',
            ], true);

            return response()->json([
                'last_page' => $penalties->lastPage(),
                'data' => $data,
                'total' => $penalties->total(),
                'permissions' => $permissions
            ]);
        }

        $companyId = company_id();
        $items = Item::with('unit')->where('company_id', $companyId)->orderBy('name')->get();

        return view('company.pages.penalty.index', compact('items'));
    }

    public function create(Request $request)
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $companyId = company_id();
        $items = Item::with('unit')->where('company_id', $companyId)->orderBy('name')->get();

        $modalData = [
            'title' => 'Add New Penalty',
            'data' => null,
            'form_mode' => 'create',
            'items' => $items,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.penalty._penalty_modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(Request $request)
    {
        try {
            $companyId = company_id();

            $validated = $request->validate([
                'item_id' => [
                    'required',
                    'exists:items,id',
                    Rule::unique('penalties')
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at'),
                ],
                'amount' => 'required|numeric|min:0',
            ], [
                'item_id.unique' => 'A penalty for this item already exists in this company.',
            ]);

            $validated['company_id']  = $companyId;
            $validated['created_by']  = auth()->id();

            $penalty = Penalty::create($validated);

            return AjaxResponse::success(
                message: 'Penalty saved successfully!',
                data: $penalty,
                code: 201
            );

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return AjaxResponse::error(
                message: $firstError ?? 'Validation failed.',
                errors: $e->errors()
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Failed to save penalty.',
                errors: $e->getMessage()
            );
        }
    }

    public function show(Request $request, Penalty $penalty)
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $companyId = company_id();
        $items = Item::with('unit')->where('company_id', $companyId)->orderBy('name')->get();

        $modalData = [
            'title' => 'View Penalty Details',
            'data' => $penalty,
            'form_mode' => 'view',
            'items' => $items,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.penalty._penalty_modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function edit(Request $request, Penalty $penalty)
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $companyId = company_id();
        $items = Item::with('unit')->where('company_id', $companyId)->orderBy('name')->get();

        $modalData = [
            'title' => 'Edit Penalty',
            'data' => $penalty,
            'form_mode' => 'edit',
            'items' => $items,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.penalty._penalty_modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function update(Request $request, Penalty $penalty)
    {
        try {
            $companyId = company_id();

            $validated = $request->validate([
                'item_id' => [
                    'required',
                    'exists:items,id',
                    Rule::unique('penalties')
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                        ->ignore($penalty->id),
                ],
                'amount' => 'required|numeric|min:0',
            ], [
                'item_id.unique' => 'A penalty for this item already exists in this company.',
            ]);

            $validated['updated_by'] = auth()->id();

            $penalty->update($validated);

            return AjaxResponse::success(
                message: 'Penalty updated successfully!',
                data: $penalty,
                code: 200
            );

        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            return AjaxResponse::error(
                message: $firstError ?? 'Validation failed.',
                errors: $e->errors()
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Failed to update penalty.',
                errors: $e->getMessage()
            );
        }
    }

    public function destroy(Request $request, Penalty $penalty)
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $penalty->delete();

            $penalty->update(['deleted_by' => current_user_id()]);
            
            return AjaxResponse::success(message: 'Penalty deleted successfully!');
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Failed to delete penalty.', 
                errors: $e->getMessage()
            );
        }
    }
}