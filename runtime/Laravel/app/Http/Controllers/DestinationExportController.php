<?php

namespace App\Http\Controllers;

use App\Exports\DestinationExport;
use App\Services\CompanyService;
use App\Helpers\AjaxResponse;
use App\Models\Destination;
use App\Repositories\DestinationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class DestinationExportController extends Controller
{
    protected DestinationRepository $repository;
    protected int $companyId;
    protected  $companyService;

    public function __construct(DestinationRepository $repository, CompanyService $companyService)
    {

        $this->middleware('permission:destination.print')->only('print');
        $this->middleware('permission:destination.export')->only(['export']);

        $this->repository = $repository;
        $this->companyService = $companyService;


        // $this->middleware(function ($request, $next) {
        //     company_id() = session('company_id');
        //     return $next($request);
        // });
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);

        $destinations = $this->repository->all(
            columns: ['*'],
            // with: ['parent'], // Eager load
            filters: ['company_id' => company_id()],
            orderBy: 'name',
            
            scopes: [
                function ($query) use ($filters) {
                    if (!empty($filters['filter_group_id'])) {
                        $query->where('id', $filters['filter_group_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('id', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ]
        );

        // Keep simple table structure for flexibility
        $tableConfig = [
            "columns" => [
                ["label" => "No.", "class" => "text-start", "width" => "5%"],
                ["label" => "Name", "class" => "text-start ps-2", "width" => "30%"],
                ["label" => "Contact Person Name", "class" => "text-start", "width" => "30%"],
                ["label" => "Mobile Number", "class" => "text-start", "width" => "20%"],
                ["label" => "Kms", "class" => "text-start", "width" => "15%"],               
            ]
        ];

        $data = [
            'company' => $company,
            'destinations' => $destinations,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.destination.print', $data)->render();

        return AjaxResponse::success(
            message: __('messages.destination.fetched'),
            data: ['html' => $html]
        );
    }

    public function exportExcel(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'xlsx');
    }

    public function exportCsv(Request $request): JsonResponse
    {
        return $this->exportByFormat($request, 'csv');
    }

    private function exportByFormat(Request $request, string $format): JsonResponse
    {
        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);

        $destinations = $this->repository->all(
            columns: ['name', 'contact_person_name', 'email', 'mobile_number', 'phone_number', 'kms', 'address_one', 'address_two', 'country_id', 'state_id', 'city', 'district', 'taluka', 'postal_code'],
            with: [],
            filters: ['company_id' => company_id()],
            orderBy: 'name',
            
            scopes: [
                function ($query) use ($filters) {
                    if (!empty($filters['filter_group_id'])) {
                        $query->where('id', $filters['filter_group_id']);
                    }
                    if (!empty($filters['search'])) {
                        $searchTerm = $filters['search'];
                        $query->where(function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('id', 'like', '%' . $searchTerm . '%');
                        });
                    }
                }
            ]
        );


        $headings = ['Name', 'Contact Person Name', 'Email', 'Mobile Number', 'Phone Number', 'Kms', 'Address One', 'Address Two', 'Country', 'State', 'City', 'District', 'Taluka', 'Postal Code'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_destination_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new DestinationExport($company, $destinations, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );


            return AjaxResponse::success(
                message: 'Destination Exported successfully ({$format})',
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
