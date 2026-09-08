<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use App\Helpers\AjaxResponse;
use App\Http\Requests\StoreDairyParameterRequest;
use App\Http\Requests\UpdateDairyParameterRequest;
use App\Models\DairyParameter;
use App\Repositories\CommonRepository;
use App\Repositories\DairyParameterRepository;
use App\Services\DataTables\DairyParameterDataTable;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class DairyParameterController extends Controller
{   
    protected DairyParameterRepository $repository;
    protected CommonRepository $common_repository;
    protected DairyParameterDataTable $dataTable;
    protected int $companyId;

    public function __construct(DairyParameterRepository $repository, DairyParameterDataTable $dataTable, CommonRepository $common_repository)
    {
        // Apply middleware for permissions
        $this->middleware('permission:dairy_parameter.list')->only(['index']);
        $this->middleware('permission:dairy_parameter.create')->only(['create', 'store']);
        $this->middleware('permission:dairy_parameter.update')->only(['edit', 'update']);
        $this->middleware('permission:dairy_parameter.delete')->only('destroy');

        $this->repository = $repository;
        $this->common_repository=$common_repository;
        $this->dataTable = $dataTable;

    }
    /** Show index page */
    public function index(Request $request): View | JsonResponse
    {
        if($request->ajax()){
            $query = $this->repository->query()->where('company_id', company_id());
            return $this->dataTable->getData($query);
        }

        // $parameter  =  DairyParameter::select('condition_id', 'id')->where('company_id', company_id())->get();

        return view('company.pages.masters.dairy-parameter.index');
    }


    /** Load Create Modal */
    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $element=$this->common_repository->getElement(company_id());
        $condition=$this->common_repository->getConditions(company_id());
        // dd($element->pluck('name','id'));
        // dd($condition->pluck('name','id'));
        $modalData = [
            'title'     => __('titles.dairy_parameter.add'),
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'element'=>$element,
            'condition'=>$condition,
            'dairy_parameter_length'=>5,
        ];
        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.dairy-parameter._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function store(StoreDairyParameterRequest $request): JsonResponse
    {   
        try {
            $data = $request;

            $parameterDetails = [];
            $from = $data['from'] ?? [];
            $to = $data['to'] ?? [];
            $difference = $data['difference'] ?? [];
            $rebate = $data['rebate'] ?? [];
            $premium = $data['premium'] ?? [];

            $count = count($from);
            for ($i = 0; $i < $count; $i++) {
                $parameterDetails[] = [
                    'from' => $from[$i] ?? null,
                    'to' => $to[$i] ?? null,
                    'difference' => $difference[$i] ?? null,
                    'rebate' => $rebate[$i] ?? null,
                    'premium' => $premium[$i] ?? 0,

                ];
            }

            $final = [
                'company_id' => company_id(), // set as needed
                'created_by' => current_user_id(),
                'condition_id' => $data['condition_id'] ?? null,
                'element_id' => $data['element_id'] ?? null,
                'guarantee' => $data['guarantee'] ?? null,
                'parameter_details' => $parameterDetails,
            ];
            // dd($final);
            // $validated = $request->validated();
            // dd($validated);
            $dairyParameter = $this->repository->create($final);

            return AjaxResponse::success(message: __('messages.dairy_parameter.created'),data: $dairyParameter,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

    /** Load View Modal */
    public function show(Request $request, DairyParameter $dairyParameter): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $dairyParameter->load(['parameterDetails']);
        $element=$this->common_repository->getElement(company_id());
        $condition=$this->common_repository->getConditions(company_id());
        // dd($dairyParameter->toArray());
        // dd(count($dairyParameter['parameterDetails']));
        $modalData = [
            'title'     => __('titles.dairy_parameter.view'),
            'uuid'      => null,
            'data'      => $dairyParameter,
            'form_mode' => 'view',
            'element'=>$element,
            'condition'=>$condition,
            'dairy_parameter_length'=>count($dairyParameter['parameterDetails']),
        ];
        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.dairy-parameter._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Load View Modal */
    public function edit(Request $request, DairyParameter $dairyParameter): JsonResponse
    {
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $dairyParameter->load(['parameterDetails']);
        $element=$this->common_repository->getElement(company_id());
        $condition=$this->common_repository->getConditions(company_id());
        // dd($dairyParameter->toArray());
        // dd(count($dairyParameter['parameterDetails']));
        $modalData = [
            'title'     => __('titles.dairy_parameter.edit'),
            'uuid'      => $dairyParameter->uuid,
            'data'      => $dairyParameter,
            'form_mode' => 'edit',
            'element'=>$element,
            'condition'=>$condition,
            'dairy_parameter_length'=>count($dairyParameter['parameterDetails']),
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.dairy-parameter._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    /** Update */
    public function update(UpdateDairyParameterRequest $request, DairyParameter $dairyParameter): JsonResponse
    {
        try {
            $data = $request;

            $parameterDetails = [];
            $id=$data['id']??[];
            $from = $data['from'] ?? [];
            $to = $data['to'] ?? [];
            $difference = $data['difference'] ?? [];
            $rebate = $data['rebate'] ?? [];
            $premium = $data['premium'] ?? [];

            $count = count($from);
            for ($i = 0; $i < $count; $i++) {
                $parameterDetails[] = [
                    'id'=>$id[$i] ?? null,
                    'from' => $from[$i] ?? null,
                    'to' => $to[$i] ?? null,
                    'difference' => $difference[$i] ?? null,
                    'rebate' => $rebate[$i] ?? null,
                    'premium' => $premium[$i] ?? 0,
                ];
            }

            $final = [
                'parameter_id'=> $dairyParameter->id,
                'condition_id' => $data['condition_id'] ?? null,
                'element_id' => $data['element_id'] ?? null,
                'guarantee' => $data['guarantee'] ?? null,
                'updated_by' => current_user_id(),
                'parameter_details' => $parameterDetails,
            ];
            // dd($final);
            $dairyParameter = $this->repository->update($dairyParameter,$final);
            
            return AjaxResponse::success(message: __('messages.dairy_parameter.updated'),data: $dairyParameter,code: 201);
        } catch (Exception $e) {
            return AjaxResponse::error(message:__('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }

    /** Delete */
    public function destroy(Request $request, DairyParameter $dairyParameter): JsonResponse
    {
        dd('Working In Progress');
       if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $dairyParameter->deleted_by = current_user_id();
            $dairyParameter->save();
            $this->repository->delete($dairyParameter);
            return AjaxResponse::success(message: __('messages.dairy_parameter.deleted'));
        } catch (Exception $e) {
            return AjaxResponse::error(message: __('messages.common.unexpected_error'),errors: $e->getMessage());
        }
    }
}
