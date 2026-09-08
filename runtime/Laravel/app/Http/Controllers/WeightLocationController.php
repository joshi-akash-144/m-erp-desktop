<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Helpers\AjaxResponse;
use App\Models\WeightLocation;
use App\Repositories\WeightLocationRepository;
use App\Services\DataTables\WeightLocationDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;

class WeightLocationController extends Controller
{
    protected WeightLocationRepository $repository;
    protected WeightLocationDataTable $dataTable;

    public function __construct(WeightLocationRepository $repository, WeightLocationDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:weight_location.list')->only(['index']);
        $this->middleware('permission:weight_location.create')->only(['create', 'store']);
        $this->middleware('permission:weight_location.update')->only(['edit', 'update']);
        $this->middleware('permission:weight_location.delete')->only('destroy');

        $this->repository = $repository;
        $this->dataTable = $dataTable;
    }

    /** Index Page and Ajax Data */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $this->repository->query();
            return $this->dataTable->getData($query);
        }
        
        $weight_locations = $this->repository->all(['godown_name as name', 'id']);
        return view('company.pages.masters.weight-location.index', compact('weight_locations'));
    }

    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        $modalData = [
            'title'     => 'Add Weight Location',
            'data'      => null,
            'form_mode' => 'create',
        ];

        return AjaxResponse::success(
            message: 'Modal loaded successfully',
            data: ['html' => view('company.pages.masters.weight-location._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Store a newly created resource in storage */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'godown_name' => 'required|string|max:255',
                'ip_address'  => 'required|string|max:45',
                'url'         => 'nullable|string|max:255',
                'status'      => 'nullable|boolean',
            ]);

            $validated['created_by'] = auth()->id();

            $weightLocation = $this->repository->create($validated);

            if ($request->ajax()) {
                return AjaxResponse::success(message: 'Weight Location saved successfully!', data: $weightLocation, code: 201);
            }

            return redirect()->route('weight_location.index');
        } catch (Exception $e) {
            return AjaxResponse::error(message: 'Failed to save Weight Location', errors: $e->getMessage());
        }
    }

    /** Load View Modal */
    public function show(Request $request, $id): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        $weightLocation = $this->repository->find($id);

        if (!$weightLocation) {
            return AjaxResponse::error(message: 'Weight Location not found');
        }

        $modalData = [
            'title'     => 'View Weight Location',
            'data'      => $weightLocation,
            'form_mode' => 'view',
        ];

        return AjaxResponse::success(
            message: 'Modal loaded successfully',
            data: ['html' => view('company.pages.masters.weight-location._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Load Edit Modal */
    public function edit(Request $request, $id): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: 'Invalid request type');
        }

        $weightLocation = $this->repository->find($id);

        if (!$weightLocation) {
            return AjaxResponse::error(message: 'Weight Location not found');
        }

        $modalData = [
            'title'     => 'Edit Weight Location',
            'data'      => $weightLocation,
            'form_mode' => 'edit',
        ];

        return AjaxResponse::success(
            message: 'Modal loaded successfully',
            data: ['html' => view('company.pages.masters.weight-location._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Update the specified resource in storage */
    public function update(Request $request, $id): JsonResponse|RedirectResponse
    {
        try {
            $validated = $request->validate([
                'godown_name' => 'required|string|max:255',
                'ip_address'  => 'required|string|max:45',
                'url'         => 'nullable|string|max:255',
                'status'      => 'nullable|boolean',
            ]);

            $validated['updated_by'] = auth()->id();

            $weightLocation = $this->repository->find($id);
            if (!$weightLocation) {
                return AjaxResponse::error(message: 'Weight Location not found');
            }
            $this->repository->update($weightLocation, $validated);

            if ($request->ajax()) {
                return AjaxResponse::success(message: 'Weight Location updated successfully!', data: $weightLocation, code: 200);
            }

            return redirect()->route('weight_location.index');
        } catch (Exception $e) {
            return AjaxResponse::error(message: 'Failed to update Weight Location', errors: $e->getMessage());
        }
    }

    /** Remove the specified resource from storage */
    // public function destroy(Request $request, $id): JsonResponse
    // {
    //     if (!$request->ajax()) {
    //         return AjaxResponse::error(message: 'Invalid request type');
    //     }

    //     try {
    //         $weightLocation = $this->repository->find($id);
    //         if (!$weightLocation) {
    //             return AjaxResponse::error(message: 'Weight Location not found');
    //         }
    //         $this->repository->delete($weightLocation);

    //         return AjaxResponse::success(message: 'Weight Location deleted successfully!');
    //     } catch (Exception $e) {
    //         return AjaxResponse::error(message: 'Failed to delete record.', errors: $e->getMessage());
    //     }
    // }
}