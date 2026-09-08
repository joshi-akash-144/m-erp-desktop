<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\TransportParty;
use App\Services\DataTables\TransportPartyDataTable;
use App\Repositories\CommonRepository;
use App\Services\CompanyService;
use App\Exports\TransportPartyExport;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreTransportPartyRequest;
use App\Http\Requests\UpdateTransportPartyRequest;
use Throwable;

class TransportPartyController extends Controller
{
    protected CommonRepository $commonRepository;
    protected TransportPartyDataTable $dataTable;
    protected CompanyService $companyService;
    protected $companyId;

    public function __construct(
        CommonRepository $commonRepository, 
        TransportPartyDataTable $dataTable,
        CompanyService $companyService
    ) {
        $this->middleware('permission:transport_party.list')->only(['index']);
        $this->middleware('permission:transport_party.create')->only(['create', 'store']);
        $this->middleware('permission:transport_party.update')->only(['edit', 'update']);
        $this->middleware('permission:transport_party.delete')->only('destroy');

        $this->commonRepository = $commonRepository;        
        $this->dataTable = $dataTable;
        $this->companyService = $companyService;

        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = TransportParty::query()->where('company_id', company_id())->orderBy('name','asc');
            return $this->dataTable->getData($query);
        }

        return view('company.pages.masters.transport-party.index');
    }

    public function create(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);
        $modalData = [
            'title'     => 'Add Transport Party',
            'uuid'      => Str::uuid(),
            'data'      => null,
            'form_mode' => 'create',
            'states'    => $states,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.transport-party._modal', compact('modalData'))->render()],
            code: 200
        );
        
    }

    public function store(StoreTransportPartyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $validated['uuid'] = Str::uuid();
            $validated['company_id'] = company_id();
            $validated['created_by'] = current_user_id();

            $transportParty = TransportParty::create($validated);

           return AjaxResponse::success(
                message: __('messages.transport_parties.created'),
                data: $transportParty,
                code: 201
            );
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function show(Request $request, TransportParty $transportParty): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);
        $modalData = [
            'title'     => 'View Transport Party',
            'uuid'      => $transportParty->uuid,
            'data'      => $transportParty,
            'form_mode' => 'view',
            'states'    => $states,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.transport-party._modal', compact('modalData'))->render()],
            code: 200
        );       
    }

    public function edit(Request $request, TransportParty $transportParty): JsonResponse
    {
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }
    
        $states = $this->commonRepository->getStates(['name_with_gst_code as name', 'id']);
        $modalData = [
            'title'     => 'Edit Transport Party',
            'uuid'      => $transportParty->uuid,
            'data'      => $transportParty,    
            'form_mode' => 'edit',
            'states'    => $states,
        ];

        return AjaxResponse::success(
            message: __('messages.modal.load_success'),
            data: ['html' => view('company.pages.masters.transport-party._modal', compact('modalData'))->render()],
            code: 200
        );
    }

    public function update(UpdateTransportPartyRequest $request, TransportParty $transportParty): JsonResponse
    {
        $validated = $request->validated();

        try {
            $validated['updated_by'] = current_user_id();
            $transportParty->update($validated);

            return AjaxResponse::success(
                message: __('messages.transport_parties.updated'),
                data: $transportParty,
                code: 200
            );
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function destroy(Request $request, TransportParty $transportParty): JsonResponse
    {
        dd('working...');
        if (!$request->ajax()) {
            return AjaxResponse::error(message: __('messages.request.type'));
        }

        try {
            $transportParty->deleted_by = current_user_id();
            $transportParty->save();
            $transportParty->delete();
            return AjaxResponse::success(
                message: __('messages.transport_parties.deleted'),
                code: 200
            );
        } catch (Exception $e) {
            return AjaxResponse::error(
                message: __('messages.common.unexpected_error'),
                errors: $e->getMessage()
            );
        }
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());

        $filters = $request->input('currentFilter', []);
        
        $query = TransportParty::where('company_id', company_id());

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('gst_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('mobile_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        $records = $query->orderBy('name')->get();

        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start","width"=>"5%"],
                ["label" => "Name", "class" => "text-start ps-2","width"=>"20%"],
                ["label" => "State", "class" => "text-start","width"=>"15%"],
                ["label" => "City", "class" => "text-start","width"=>"15%"],
                ["label" => "Mobile Number", "class" => "text-start","width"=>"15%"],
                ["label" => "GST Number", "class" => "text-start","width"=>"15%"],
                ["label" => "Email", "class" => "text-start","width"=>"15%"],
            ],
        ];

        $data = [
            'company'     => $company,
            'records'     => $records,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.transport-party.print', $data)->render();

        return AjaxResponse::success(
            message: 'Consignor & Consignees fetched successfully',
            data: [
                'html' => $html,
            ]
        );
    }

    public function exportExcel(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);
        
        $query = TransportParty::where('company_id', company_id());

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('gst_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('mobile_number', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        $records = $query->orderBy('name')->get();
       
        $headings = [
            'Name',
            'State',
            'City',
            'Postal Code',
            'Mobile Number',
            'GST Number',
            'Address Line 1',
            'Address Line 2',
            'Email',
        ];

        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }

        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_transport_parties_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new TransportPartyExport($company, $records, $headings),                
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: "Transport Consignee Exported successfully ({$format})",
                data: [
                    'file_url' => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(
                message: 'Export failed',
                errors: [$e->getMessage()],
            );
        }
    }
}
