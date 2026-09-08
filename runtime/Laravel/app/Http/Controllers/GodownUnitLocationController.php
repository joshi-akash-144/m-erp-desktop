<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Helpers\AjaxResponse;
use App\Models\User;
use App\Repositories\GodownRepository;
use App\Repositories\GodownUnitLocationRepository;
use App\Services\DataTables\GodownUnitLocationDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;

class GodownUnitLocationController extends Controller
{
    protected GodownUnitLocationRepository $repository;
    protected GodownRepository $godownRepository;
    protected GodownUnitLocationDataTable $dataTable;

    public function __construct(
        GodownUnitLocationRepository $repository, 
        GodownUnitLocationDataTable $dataTable,
        GodownRepository $godownRepository
    ) {
        $this->middleware('permission:godown_unit_location.list')->only(['index']);
        $this->middleware('permission:godown_unit_location.create')->only(['create', 'store']);
        $this->middleware('permission:godown_unit_location.update')->only(['edit', 'update']);
        $this->middleware('permission:godown_unit_location.delete')->only('destroy');

        $this->repository = $repository;
        $this->dataTable = $dataTable;
        $this->godownRepository = $godownRepository;
    }

    /** Display listing page */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        $godown_unit_locations = $this->repository->all(); 
        
        return view('company.pages.masters.godown-unit.index', compact('godown_unit_locations'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        $companyId = (int) session('company_id');
        
        $modalData = [
            'title'        => 'Add Godown Unit Location',
            'data'         => null,
            'destinations' => $this->godownRepository->getDestinations($companyId),
            'form_mode'    => 'create',
        ];

        return AjaxResponse::success(
            message: 'Modal loaded successfully',
            data: ['html' => view('company.pages.masters.godown-unit._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Store new record */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'destination_id' => 'required|exists:destinations,id',
                'godown_name'    => 'required|string|max:255',
                'godown_remark'  => 'nullable|string',
            ]);

            $validated['company_id'] = session('company_id');
            $validated['user_id']    = auth()->id();
            $validated['created_by'] = auth()->id();

            $record = $this->repository->create($validated);

            return AjaxResponse::success(message: 'Godown Unit Location saved successfully!', data: $record, code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message: 'Failed to save record', errors: $e->getMessage());
        }
    }

    /** Load Show Modal */
    public function show(Request $request, $id): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        $record = $this->repository->find($id);
        if (!$record) return AjaxResponse::error(message: 'Record not found');

        $companyId = (int) session('company_id');

        $modalData = [
            'title'        => 'View Godown Unit Location',
            'data'         => $record,
            'destinations' => $this->godownRepository->getDestinations($companyId),
            'form_mode'    => 'view',
        ];

        return AjaxResponse::success(
            message: 'Modal loaded successfully',
            data: ['html' => view('company.pages.masters.godown-unit._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Load Edit Modal */
    public function edit(Request $request, $id): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        $record = $this->repository->find($id);
        if (!$record) return AjaxResponse::error(message: 'Record not found');

        $companyId = (int) session('company_id');

        $modalData = [
            'title'        => 'Edit Godown Unit Location',
            'data'         => $record,
            'destinations' => $this->godownRepository->getDestinations($companyId),
            'form_mode'    => 'edit',
        ];

        return AjaxResponse::success(
            message: 'Modal loaded successfully',
            data: ['html' => view('company.pages.masters.godown-unit._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Update record */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'destination_id' => 'required|exists:destinations,id',
                'godown_name'    => 'required|string|max:255',
                'godown_remark'  => 'nullable|string',
            ]);

            $validated['updated_by'] = auth()->id();

            $record = $this->repository->find($id);
            if (!$record) return AjaxResponse::error(message: 'Record not found');

            $this->repository->update($record, $validated);

            return AjaxResponse::success(message: 'Godown Unit Location updated successfully!', data: $record, code: 200);
        } catch (Exception $e) {
            return AjaxResponse::error(message: 'Failed to update record', errors: $e->getMessage());
        }
    }

    /** Delete record */
    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        try {
            $record = $this->repository->find($id);
            if (!$record) return AjaxResponse::error(message: 'Record not found');

            $this->repository->delete($record);

            return AjaxResponse::success(message: 'Godown Unit Location deleted successfully!');
        } catch (Exception $e) {
            return AjaxResponse::error(message: 'Failed to delete record.', errors: $e->getMessage());
        }
    }
}
