<?php

namespace App\Http\Controllers;


use App\Models\Transporter;
use App\Exports\TransporterExport;
use App\Services\CompanyService;
use App\Services\DataTables\TransporterDataTable;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class TransporterExportController extends Controller
{
    protected TransporterDataTable $dataTable;
    protected $companyService;
    
    public function __construct(TransporterDataTable $dataTable, CompanyService $companyService)
    {
        $this->dataTable = $dataTable;
        $this->companyService = $companyService;
    }

    public function print(Request $request)
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $filters = $request->input('currentFilter', []);

        $query = Transporter::where('company_id', company_id());

        if (!empty($filters['transporter_id'])) {
            $query->where('id', $filters['transporter_id']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('gstin', 'like', '%' . $searchTerm . '%')
                    ->orWhere('pan_no', 'like', '%' . $searchTerm . '%')
                    ->orWhere('mobile', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        // Fetch transporters and map properties to match what the print blade template expects
        $transporters = $query->orderBy('name')
            ->get()
            ->map(function ($transporter) {
                $transporter->gst_number = $transporter->gstin;
                $transporter->pan_number = $transporter->pan_no;
                $transporter->mobile_no = $transporter->mobile;
                // Map string city to an object with name attribute to match $group->city->name in the blade
                $transporter->city = (object) ['name' => $transporter->city];
                return $transporter;
            });

        $table = [
            "columns" => [
                ["label" => "No.", "class" => "text-center", "width" => "5%"],
                ["label" => "Name", "class" => "text-start ps-2", "width" => "30%"],
                ["label" => "GSTIN No.", "class" => "text-start ps-2", "width" => "20%"],
                ["label" => "PAN No.", "class" => "text-start ps-2", "width" => "15%"],
                ["label" => "Mobile No.", "class" => "text-end ps-2", "width" => "20%"],
                ["label" => "City", "class" => "text-end ps-2", "width" => "15%"],
            ],
            "body" => []
        ];

        $data = [
            'company'       => $company,
            'accountGroups' => $transporters,
            'tableConfig'   => $table,
            'orientation'   => $request->input('orientation', 'portrait'),
        ];

        $html = view('company.pages.masters.transporter.print', $data)->render();

        return AjaxResponse::success(
            message: 'Transporter Print HTML fetched successfully',
            data: [
                'html' => $html,
            ]
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

        $query = Transporter::where('company_id', company_id());

        if (!empty($filters['transporter_id'])) {
            $query->where('id', $filters['transporter_id']);
        }
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('gstin', 'like', '%' . $searchTerm . '%')
                    ->orWhere('pan_no', 'like', '%' . $searchTerm . '%')
                    ->orWhere('mobile', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        $transporters = $query->orderBy('name')->get();


        $headings = ['Name', 'GSTIN', 'PAN No','Contact Person' , 'Email' ,'Mobile No.','Phone No.','Address One','Address Two','City','State','Pincode', 'Bank Name', 'Bank Account Number', 'Bank IFSC Code'];


        $directory = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }


        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_transporters_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new TransporterExport($company, $transporters, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );


            return AjaxResponse::success(
                message: "Transporters Exported successfully ({$format})",
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
