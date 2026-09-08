<?php

namespace App\Http\Controllers;

use App\Exports\TdsEntryExport;
use App\Models\TdsEntry;
use App\Services\CompanyService;
use App\Services\TdsEntryService;
use App\Helpers\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use Carbon\Carbon;

class TdsEntryExportController extends Controller
{
    protected CompanyService $companyService;
    protected TdsEntryService $tdsEntryService;

    public function __construct(CompanyService $companyService, TdsEntryService $tdsEntryService)
    {
        $this->middleware('permission:tds_entry.print')->only('print');
        $this->middleware('permission:tds_entry.export')->only(['exportExcel', 'exportCsv']);

        $this->companyService = $companyService;
        $this->tdsEntryService = $tdsEntryService;
    }

    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:print,pdf',
            'orientation' => 'nullable|in:portrait,landscape',
        ]);

        $company = $this->companyService->current(company_id());
        $tdsEntries = $this->tdsEntryService->getFilteredQuery($request)->get();

        $tableConfig = [
            "columns" => [
                ["label" => "Date", "class" => "text-start", "width" => "10%"],
                ["label" => "Ref. Number", "class" => "text-start ps-2", "width" => "10%"],
                ["label" => "Deductee Name", "class" => "text-start", "width" => "35%"],
                ["label" => "PAN No.", "class" => "text-start", "width" => "10%"],
                // ["label" => "Section", "class" => "text-start", "width" => "10%"],
                ["label" => "Payment Amt", "class" => "text-end", "width" => "10%"],
                ["label" => "TDS Amt", "class" => "text-end", "width" => "15%"],
            ]
        ];

        $data = [
            'company' => $company,
            'tdsEntries' => $tdsEntries,
            'tableConfig' => $tableConfig,
            'orientation' => $request->input('orientation', 'landscape'), // landscape default for this table
            'filters' => $request->all(),
        ];

        $html = view('company.pages.fas.tds-entries.print', $data)->render();

        return AjaxResponse::success(
            message: 'TDS Entries fetched for print',
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
        $tdsEntries = $this->tdsEntryService->getFilteredQuery($request)->get();

        $headings = [
            'Ref.No.', 'Deductee Name', 'Payment Amt.', 'Payment On',  'TDS %', 'TDS Amount', 
            'Sur. %', 'Surcharge Amt.', 'Edu. Cess %', 'Edu. Cess Amt.', 'SHE Cess %', 'SHE Cess Amt.', 
            'Total Deducted', 'Tax Deducted On', 'Tax Deposited', 'Tax Deposited On', 'Challan No', 
            'Cheque No', 'Bank Name'
        ];

        $directory = 'transaction_reports';
        $directoryPath = storage_path("app/public/{$directory}");

        if (!File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0777, true, true);
        }

        $companyNameSlug = Str::slug($company->print_name, '_');
        $fileName = "{$companyNameSlug}_tds_entries_" . now()->format('d_m_Y_His') . ".{$format}";

        try {
            Excel::store(
                new TdsEntryExport($company, $tdsEntries, $headings, $request->all()),
                "{$directory}/{$fileName}",
                'public',
                $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: "TDS Entries Exported successfully ({$format})",
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
