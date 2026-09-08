<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Services\FreightInvoice2Service;
use App\Http\Requests\StoreFreightInvoice2Request;
use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Services\FreightService;
use App\Services\CompanyService;
use App\Models\Freight;
use App\Exports\FreightInvoice2Export;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;

class FreightInvoice2Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, FreightInvoice2Service $freightInvoice2Service, MasterDataService $masterDataService)
    {
        if ($request->ajax()) {
            $filters = $request->only([
                'start_date',
                'end_date',
                'account_id',
                'invoice_serial',
            ]);

            $filters['company_id'] = company_id();
            $filters['financial_year_id'] = financial_year_id();
            $filters['page'] = (int) $request->input('page', 1);
            $filters['size'] = (int) $request->input('size', 50);

            $result = $freightInvoice2Service->freightInvoice2List($filters);
            return response()->json($result);
        }

        $customers = $masterDataService->getDebtors(company_id());

        return view('company.pages.freight-invoice2.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MasterDataService $masterService, FreightService $freightService)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $customers = $masterService->get('customers', $companyId);

        $ref       = (string) $freightService->getDefaultNextBillNumber($companyId, $financialYearId);

        return view('company.pages.freight-invoice2.create', compact('customers', 'ref'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFreightInvoice2Request $request, FreightInvoice2Service $freightInvoice2Service)
    {
        try {
            $companyId = company_id();
            $financialYearId = financial_year_id();

            $freight = $freightInvoice2Service->store($request->all(), $companyId, $financialYearId);

            return AjaxResponse::success(
                'Freight Invoice saved successfully.'
            );
        } catch (\Exception $e) {
            \Log::error('Freight Invoice 2 Store Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return AjaxResponse::error('Failed to save invoice: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterDataService $masterService, string $id)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $freight = Freight::with(['contractorItems.destination', 'contractorItems.vehicle', 'contractorItems.contractor', 'account'])
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->findOrFail($id);

        $customers = $masterService->get('customers', $companyId);
        $ref       = $freight->invoice_number;

        return view('company.pages.freight-invoice2.edit', compact('customers', 'ref', 'freight'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreFreightInvoice2Request $request, FreightInvoice2Service $freightInvoice2Service, string $id)
    {
        try {
            $companyId       = company_id();
            $financialYearId = financial_year_id();

            $data = $request->validated();
            
            // Allow total_amount to be overridden if provided
            if ($request->has('total_amount')) {
                $data['total_amount'] = $request->input('total_amount');
            }
            if ($request->has('uuid')) {
                $data['uuid'] = $request->input('uuid');
            }
            if ($request->has('narration')) {
                $data['narration'] = $request->input('narration');
            }

            $freight = $freightInvoice2Service->update($id, $data, $companyId, $financialYearId);

            return response()->json([
                'success' => true,
                'message' => 'Freight Invoice updated successfully.',
                'data'    => $freight,
            ]);

        } catch (\Exception $e) {
            \Log::error('Freight Invoice 2 Update Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function print(Request $request, FreightInvoice2Service $freightInvoice2Service, CompanyService $companyService)
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'account_id',
                'invoice_serial',
            ]);

            $filters['company_id'] = company_id();
            $filters['financial_year_id'] = financial_year_id();
            $filters['size'] = 10000; // Get all records for print

            // Fetch the grouped rows
            $response = $freightInvoice2Service->freightInvoice2List($filters);
            $data = $response['data'] ?? [];

            $company = $companyService->current(company_id());
            
            $startDate = $filters['start_date'] ?? null;
            $endDate = $filters['end_date'] ?? null;
            if ($startDate && $endDate) {
                $datePeriod = Carbon::parse($startDate)->format('d-m-Y') . ' to ' . Carbon::parse($endDate)->format('d-m-Y');
            } else {
                $fy = $company->currentFinancialYear;    
                $datePeriod = Carbon::parse($fy->start_date)->format('d-m-Y') . ' to ' . Carbon::parse($fy->end_date)->format('d-m-Y');
            }

            $tableConfig = [
                "columns" => [
                    ["label" => "Bill No", "class" => "text-start", "width" => "8%"],
                    ["label" => "Date", "class" => "text-center", "width" => "7%"],
                    ["label" => "Customer", "class" => "text-start", "width" => "13%"],
                    ["label" => "Item Date", "class" => "text-center", "width" => "7%"],
                    ["label" => "Code", "class" => "text-start", "width" => "6%"],
                    ["label" => "Society Name", "class" => "text-start", "width" => "12%"],
                    ["label" => "Route", "class" => "text-start", "width" => "7%"],
                    ["label" => "Bag", "class" => "text-end", "width" => "5%"],
                    ["label" => "Vehicle No.", "class" => "text-start", "width" => "7%"],
                    ["label" => "Vendor", "class" => "text-start", "width" => "7%"],
                    ["label" => "KMs", "class" => "text-end", "width" => "4%"],
                    ["label" => "Rate", "class" => "text-end", "width" => "5%"],
                    ["label" => "Amount", "class" => "text-end", "width" => "6%"],
                    ["label" => "Contractor", "class" => "text-start", "width" => "10%"],
                ],
            ];

            $html = view('company.pages.freight-invoice2.print', [
                'company'     => $company,
                'rows'        => $data,
                'tableConfig' => $tableConfig,
                'datePeriod'  => $datePeriod,
            ])->render();

            return AjaxResponse::success(
                message: 'Freight Invoice 2 fetched successfully',
                data: [
                    'html' => $html,
                ]
            );
        } catch (Throwable $e) {
            \Log::error('Freight Invoice 2 Print Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return AjaxResponse::error('Failed to generate print: ' . $e->getMessage());
        }
    }

    public function printInvoice(string $id)
    {
        try {
            $freight_data = Freight::with([
                'account.taxDetail',
                'consignor',
                // 'consignee',
                // 'toDestination',
                // 'vehicle',
                'contractorItems.destination',
                'contractorItems.vehicle',
                'contractorItems.contractor',
            ])->where('id', $id)->get();

            if ($freight_data->isEmpty()) {
                return AjaxResponse::error('Invoice not found.');
            }

            $company_data = Company::with('state')->find(company_id());

            $tableConfig = [
                "columns" => [
                    ["label" => "Date", "class" => "text-center", "width" => "10%"],
                    ["label" => "Society Name", "class" => "text-center", "width" => "20%"],
                    ["label" => "Route", "class" => "text-center", "width" => "8%"],
                    ["label" => "Bag", "class" => "text-center", "width" => "8%"],
                    ["label" => "Vehicle No.", "class" => "text-center", "width" => "8%"],
                    ["label" => "KM", "class" => "text-center", "width" => "7%"],
                    ["label" => "Rate", "class" => "text-center", "width" => "7%"],
                    ["label" => "Amount", "class" => "text-center", "width" => "10%"],
                    ["label" => "Contractor", "class" => "text-center", "width" => "22%"],
                ],
            ];

            $html = view('company.pages.freight-invoice2.freight-invoice2-print', compact('freight_data', 'company_data', 'tableConfig'))->render();

            return AjaxResponse::success('Freight invoice print ready.', ['html' => $html]);
        } catch (Throwable $e) {
            \Log::error('Freight Invoice 2 Item Print Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return AjaxResponse::error('Failed to generate print: ' . $e->getMessage());
        }
    }

    public function exportExcel(Request $request, FreightInvoice2Service $freightInvoice2Service, CompanyService $companyService)
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'account_id',
                'invoice_serial',
            ]);

            $filters['company_id'] = company_id();
            $filters['financial_year_id'] = financial_year_id();
            $filters['size'] = 10000; // Get all records for export

            // Fetch the grouped rows
            $response = $freightInvoice2Service->freightInvoice2List($filters);
            $freightInvoices = collect($response['data'] ?? []);

            $company = $companyService->current(company_id());
            
            $startDate = $filters['start_date'] ?? null;
            $endDate = $filters['end_date'] ?? null;
            if ($startDate && $endDate) {
                $datePeriod = Carbon::parse($startDate)->format('d-m-Y') . ' to ' . Carbon::parse($endDate)->format('d-m-Y');
            } else {
                $datePeriod = null;
            }

            $headings = [
                'Bill No', 'Date', 'Customer', 'Item Date', 'Code', 'Society Name', 
                'Route', 'Bag', 'Vehicle No.', 'Vendor', 'KMs', 'Rate', 'Amount', 'Contractor'
            ];

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            $companyNameSlug = Str::slug($company->print_name, '_');
            $fileName = "{$companyNameSlug}_freight_invoice2_" . now()->format('d_m_Y_His') . ".xlsx";

            Excel::store(
                new FreightInvoice2Export($company, $freightInvoices, $headings, $datePeriod),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success(
                message: 'Freight Invoice 2 Exported successfully',
                data: [
                    'file_url' => asset("storage/{$directory}/{$fileName}"),
                    'file_name' => $fileName,
                ]
            );
        } catch (Throwable $e) {
            \Log::error('Freight Invoice 2 Export Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return AjaxResponse::error('Failed to export: ' . $e->getMessage());
        }
    }
}
