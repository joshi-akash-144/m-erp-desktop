<?php

namespace App\Http\Controllers;

use App\Exports\BrokerExport;
use App\Models\Broker;
use App\Services\CompanyService;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class BrokerExportController extends Controller
{
    protected int $companyId;
    protected CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->middleware('permission:broker.print')->only('print');
        $this->middleware('permission:broker.export')->only(['exportExcel', 'exportCsv']);
        $this->companyService = $companyService;

        $this->middleware(function ($request, $next) {
            $this->companyId = (int) session('company_id');
            return $next($request);
        });
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
        ]);

        $company = $this->companyService->current($this->companyId);
        $filters = $request->input('currentFilter', []);

        $query = Broker::where('company_id', $this->companyId)
            ->status('active');

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mobile_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        $brokers = $query->orderBy('name', 'asc')->get();

        $table = [
            'columns' => [
                ['label' => 'No.',  'class' => 'text-start'],
                ['label' => 'Name', 'class' => 'text-start ps-2'],
                ['label' => 'City', 'class' => 'text-start'],
                ['label' => 'Mobile No.', 'class' => 'text-start'],
                ['label' => 'Email', 'class' => 'text-start'],
            ],
            'body' => [],
        ];

        $columnWidths = ['sr' => '5%', 'name' => '30%', 'city' => '10%', 'email' => '30%', 'mobile_no' => '15%'];

        foreach ($brokers as $key => $broker) {
            $table['body'][] = [
                ['value' => ($key + 1) . '.', 'class' => 'text-center', 'style' => "width:{$columnWidths['sr']};"],
                ['value' => str($broker->name)->limit(50), 'class' => 'text-start ps-2', 'style' => "width:{$columnWidths['name']};"],
                ['value' => str($broker->city)->limit(10) ?? '', 'class' => 'text-start ps-2', 'style' => "width:{$columnWidths['city']};"],
                ['value' => $broker->mobile_number ?? '', 'class' => 'text-start ps-2', 'style' => "width:{$columnWidths['mobile_no']};"],
                ['value' => str($broker->email)->limit(30) ?? '', 'class' => 'text-start ps-2', 'style' => "width:{$columnWidths['email']};"],
            ];
        }

        $html = view('company.pages.masters.broker.print', [
            'company'     => $company,
            'brokers'     => $brokers,
            'tableConfig' => $table,
            'orientation' => $request->input('orientation', 'portrait'),
        ])->render();

        return AjaxResponse::success(message: 'Brokers fetched successfully', data: ['html' => $html]);
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
        $company = $this->companyService->current($this->companyId);
        $filters = $request->input('currentFilter', []);

        $query = Broker::where('company_id', $this->companyId)
            ->with('state:id,name', 'country:id,name')
            ->status('active');

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mobile_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('city', 'like', '%' . $searchTerm . '%');
            });
        }

        $brokers = $query->orderBy('name', 'asc')->get();

        $headings = [
            'Name',
            'Address Line 1',
            'Address Line 2',
            'State',
            'Country',
            'City',
            'Mobile No.',
            'IT PAN',
            'Email',
            'PIN',
            'Bank Name',
            'Branch Name',
            'Account Number',
            'IFSC Code',
        ];

        $directory     = 'master_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }

        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName        = "{$companyNameSlug}_brokers_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new BrokerExport($company, $brokers, $headings),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: 'Brokers exported successfully',
                data: [
                    'file_url'  => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            return AjaxResponse::error(message: 'Export failed', errors: [$e->getMessage()]);
        }
    }
}
