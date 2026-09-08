<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\BagsRate;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

use App\Services\DataTables\BagsRateDataTable;

class BagsRateController extends Controller
{
    protected BagsRateDataTable $dataTable;
    protected int $companyId;

    public function __construct(BagsRateDataTable $dataTable)
    {
        $this->middleware('permission:bags_rate.list')->only(['index']);
        $this->middleware('permission:bags_rate.create')->only(['create', 'store']);
        $this->middleware('permission:bags_rate.update')->only(['edit', 'update']);
        $this->middleware('permission:bags_rate.delete')->only('destroy');
        
        $this->dataTable = $dataTable;

        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = BagsRate::query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        return view('company.pages.masters.bags-rate.index');
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'         => 'Add Bags Rate',
            'uuid'          => Str::uuid(),
            'data'          => null,
            'form_mode'     => 'create',
        ];       
         return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bags-rate._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'bags_type' => 'required|string|max:255',
                'bags_rate' => 'required|numeric|min:0',
            ]);

            $validated['uuid'] = Str::uuid();
            $validated['company_id'] = company_id();
            $validated['status'] = 1;
            $validated['created_by'] = current_user_id();

            $bagsRate = BagsRate::create($validated);

            return AjaxResponse::success(message: 'Bags Rate created successfully!', data: $bagsRate, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    public function show(Request $request, BagsRate $bagsRate): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'         => 'View Bags Rate',
            'uuid'          => $bagsRate->uuid,
            'data'          => $bagsRate,
            'form_mode'     => 'view',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bags-rate._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function edit(Request $request, BagsRate $bagsRate): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        $modalData = [
            'title'         => 'Edit Bags Rate',
            'uuid'          => $bagsRate->uuid,
            'data'          => $bagsRate,
            'form_mode'     => 'edit',
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.bags-rate._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function update(Request $request, BagsRate $bagsRate): JsonResponse
    {
        try {
            $validated = $request->validate([
                'bags_type' => 'required|string|max:255',
                'bags_rate' => 'required|numeric|min:0',
            ]);

            $validated['updated_by'] = current_user_id();
            $bagsRate->update($validated);

            return AjaxResponse::success(message: 'Bags Rate updated successfully!', data: $bagsRate, code: 200);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    public function destroy(Request $request, BagsRate $bagsRate): JsonResponse
    {
        dd('working...');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $bagsRate->delete();
            return AjaxResponse::success(message: 'Bags Rate deleted successfully!');
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
}
