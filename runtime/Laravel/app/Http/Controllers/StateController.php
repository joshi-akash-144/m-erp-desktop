<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Models\State;
use App\Repositories\StateRepository;
use App\Services\DataTables\StateDataTable;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class StateController extends Controller
{
     protected StateDataTable $dataTable;
    protected StateRepository $repository;
    // protected int $companyId;
    public function __construct(StateRepository $repository, StateDataTable $dataTable)
    {
        // Apply middleware for permissions
        $this->middleware('permission:state.list')->only(['index']);
        $this->middleware('permission:state.create')->only(['create', 'store']);
        $this->middleware('permission:state.update')->only(['edit', 'update']);
        $this->middleware('permission:state.delete')->only('destroy');
        
        $this->repository = $repository;
        $this->dataTable = $dataTable;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse   
    {
         if ($request->ajax()) {
            $query = $this->repository->query();
            return $this->dataTable->getData($query);
        }
        $states = State::select('name', 'id')->get();
        return view('company-selector.pages.administer.states.index',compact('states'));
    }

    /**
     * Show the form for creating a new resource.
     */
     public function create()
    {
        $modalData = [
            'title' => __('titles.user.add'),
            'data' => null,
            
            'form_mode' => 'create',
        ];
        $mode = 'create';
        return view('company-selector.pages.administer.states.form', compact('mode','modalData'));
    }


    /**
     * Store a newly created resource in storage.
     */
     public function store(StoreStateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $state = $this->repository->create($validated);


            return AjaxResponse::success(message: __('messages.state.created'), data: $state);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(State $state)
    {
        $mode = 'view';
        return view('company-selector.pages.administer.states.form', compact('state', 'mode'));
    }

    /**
     * Show the form for editing the specified resource.
     */
      public function edit(State $state)
    {
        $mode = 'edit';
        return view('company-selector.pages.administer.states.form', compact('state', 'mode'));
    }

    /**
     * Update the specified resource in storage.
     */
       public function update(UpdateStateRequest $request, State $state): JsonResponse
    {

        try {
            $validated = $request->validated();
            $updated = $this->repository->update($state, $validated);

            return AjaxResponse::success(message: __('messages.state.updated'), data: $updated);
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
    /**
     * Remove the specified resource from storage.
     */
      /** Delete */
    public function destroy(Request $request, State $state): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }


        try {
            $this->repository->delete($state);
            return AjaxResponse::success(message: __('messages.state.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'), errors: $e->getMessage());
        }
    }
}
