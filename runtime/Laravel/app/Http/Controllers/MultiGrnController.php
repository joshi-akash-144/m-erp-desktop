<?php

namespace App\Http\Controllers;

use App\Models\MultiGrn;
use App\Services\LookupService;
use App\Services\MasterDataService;
use App\Services\MultiGrnService;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Helpers\AjaxResponse;
use App\Models\Account;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MultiGrnController extends Controller
{
    protected MultiGrnService $service;
    protected MasterDataService $masterService;
    protected LookupService $lookupService;

    public function __construct(MultiGrnService $service, MasterDataService $masterService, LookupService $lookupService)
    {
        $this->service = $service;
        $this->masterService = $masterService;
        $this->lookupService = $lookupService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return view('company.pages.multi-grn.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $formMode = 'create';
        $companyId = company_id();
        $customers = Account::where('party_type', 'customer')->where('company_id', $companyId)->pluck('name', 'id');
        return view('company.pages.multi-grn.create', compact('formMode', 'customers'));
    }


    // public function multiGrnImport(Request $request)
    // {
    //     $existing_columns = [
    //         "Inward No",
    //         "Material Doc No",
    //         "Truck Inward Date",
    //         "PO No",
    //         "TRUCKNO",
    //         "Material Desc",
    //         "Vendor Name",
    //         "Gross Wt",
    //         "Tare Wt",
    //         "Nt Wt with Bag",
    //         "Nt Wt wo Bag",
    //         "Noof bag",
    //         "Av wt bag",
    //         "Plant"
    //     ];

    //     $mapped_keys = [
    //         "inward_no",
    //         "material_doc_no",
    //         "truck_inward_date",
    //         "p_o_no",
    //         "truck_no",
    //         "material_desc",
    //         "vendor_name",
    //         "gross_wt",
    //         "tare_wt",
    //         "nt_wt_with_bag",
    //         "nt_wt_wo_bag",
    //         "no_of_bag",
    //         "av_wt_bag",
    //         "plant"
    //     ];

    //     try {
    //         // Elevate limits temporarily to absorb heavy memory footprint of Laravel-Excel parsing
    //         ini_set('memory_limit', '512M');
    //         set_time_limit(300);

    //         if (!$request->hasFile('excel_import_file')) {
    //             return AjaxResponse::error(message: 'Excel file is required. Please upload a valid file.', code: 422);
    //         }

    //         $attachments = $request->file('excel_import_file');

    //         $collection = Excel::toArray(new MultiGrnImport, $attachments);

    //         if (empty($collection) || empty($collection[0])) {
    //             return AjaxResponse::error(message: 'The Excel sheet appears to be empty.', code: 422);
    //         }

    //         $sheetData = $collection[0];
    //         $filteredRows = [];

    //         foreach ($sheetData as $row) {
    //             $filteredRow = array_values(array_filter($row, function ($value) {
    //                 return $value !== '' && !is_null($value);
    //             }));

    //             if (!empty($filteredRow)) {
    //                 // Excel handles dates as float offsets from 1899-12-30.
    //                 if (isset($filteredRow[2]) && is_numeric($filteredRow[2])) {
    //                     $filteredRow[2] = date('Y-m-d', strtotime('1899-12-30 +' . $filteredRow[2] . ' days'));
    //                 }
    //                 $filteredRows[] = $filteredRow;
    //             }
    //         }

    //         if (empty($filteredRows)) {
    //             return AjaxResponse::error(message: 'No valid rows found to parse.', code: 422);
    //         }

    //         // Check Headers
    //         $column_array = array_map(function ($val) {
    //             return str_replace('/', '', trim($val));
    //         }, $filteredRows[0]);

    //         if (count($column_array) < 14) {
    //             return AjaxResponse::error(message: 'Excel Sheet format mismatch. Expected at least 14 columns.', code: 422);
    //         }

    //         $diff = array_diff($existing_columns, $column_array);
    //         if (!empty($diff)) {
    //             return AjaxResponse::error(message: 'Excel Sheet structure does not match expected format. Missing headers: ' . implode(', ', $diff), code: 422);
    //         }

    //         // Map Rows
    //         $row_array = [];
    //         for ($i = 1; $i < count($filteredRows); $i++) {
    //             $row = $filteredRows[$i];
    //             $mappedRow = [];
    //             foreach ($mapped_keys as $idx => $key) {
    //                 if (isset($row[$idx])) {
    //                     $mappedRow[$key] = $row[$idx];
    //                 }
    //             }
    //             if (!empty($mappedRow)) {
    //                 $row_array[] = $mappedRow;
    //             }
    //         }

    //         if (empty($row_array)) {
    //             return AjaxResponse::error(message: 'No records found in sheet after headers.', code: 422);
    //         }

    //         $request->merge(['multi_grn_data' => $row_array]);


    //         dd($request);
    //         DB::beginTransaction();

    //         // Call internal store method
    //         $this->store($request);

    //         DB::commit();

    //         $vendor = null;
    //         if ($request->filled('account_id')) {
    //             $vendor = Account::find($request->account_id);
    //         }

    //         dd($row_array);

    //         $html = view('company.pages.multi-grn.print_table', [
    //             'row_array' => $row_array,
    //             'vendor'    => $vendor
    //         ])->render();

    //         return AjaxResponse::success(
    //             message: 'Multi GRN data processed successfully.',
    //             data: [
    //                 'rows' => $row_array,
    //                 'html' => $html
    //             ]
    //         );

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         report($e);
    //         return AjaxResponse::error(message: 'An error occurred during Excel import: ' . $e->getMessage(), code: 500);
    //     }
    // }

    public function multiGrnImport(Request $request)
    {
        $existing_column = [
            "Inward No",
            "Material Doc No",
            "Truck Inward Date",
            "PO No",
            "TRUCKNO",
            "Material Desc",
            "Vendor Name",
            "Gross Wt",
            "Tare Wt",
            "Nt Wt with Bag",
            "Nt Wt wo Bag",
            "Noof bag",
            "Av wt bag",
            "Plant"
        ];

        $column = array(
            "inward_no",
            "material_doc_no",
            "truck_inward_date",
            "p_o_no",
            "truck_no",
            "material_desc",
            "vendor_name",
            "gross_wt",
            "tare_wt",
            "nt_wt_with_bag",
            "nt_wt_wo_bag",
            "no_of_bag",
            "av_wt_bag",
            "plant"
        );

        // dd($request->all());
        $vendor = Account::find($request->account_id);

        try {
            if ($request->hasFile('excel_import_file')) {
                $attachments = $request->file('excel_import_file');

                $reader = IOFactory::createReaderForFile($attachments->getRealPath());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($attachments->getRealPath());
                $collection = [$spreadsheet->getActiveSheet()->toArray(null, true, true, false)];

                $column_array = [];
                $row_array = [];


                if (!empty($collection)) {
                    foreach ($collection as $collection_key => $data) {
                        if (!empty($data)) {

                            $filteredData = [];
                            $header = true;
                            foreach ($data as $row) {

                                $filteredRow = array_filter($row, function ($value) {
                                    return $value !== '' && !is_null($value);
                                });
                                if (!empty($filteredRow)) {
                                    if (!empty($filteredRow) && isset($filteredRow[2]) && is_numeric($filteredRow[2])) {
                                        $filteredRow[2] = date('Y-m-d', strtotime('1899-12-30 +' . ($filteredRow[2]) . ' days'));
                                    } else {
                                        if (!$header) {
                                            $filteredRow[2] = Carbon::createFromFormat('d.m.Y', trim($filteredRow[2]))->format('Y-m-d');
                                        }
                                    }

                                    if (!empty($filteredRow)) {
                                        $filteredData[] = $filteredRow;
                                    }
                                }
                                $header = false;
                            }
                        }
                    }
                }

                if (!empty($filteredData)) {
                    foreach ($filteredData as $collection_key => $data) {
                        $row = [];
                        if (!empty($data)) {

                            if ($collection_key == 0) {
                                if (!empty($filteredData)) {

                                    if (!empty($data)) {
                                        foreach ($data as $d_key => $value) {
                                            $data[$d_key] = str_replace('/', '', $value);
                                        }
                                    }
                                    $column_array = $data;
                                }
                            }
                        }
                    }
                }


                $column_count = count($column_array);
                if ($column_count == 14) {
                    if (empty(array_diff($existing_column, $column_array)) && empty(array_diff($column_array, $existing_column))) {

                        if (!empty($filteredData)) {
                            foreach ($filteredData as $collection_key => $data) {
                                $row = [];
                                if (!empty($data)) {

                                    if ($collection_key > 0) {
                                        if (!empty($filteredData)) {

                                            if (!empty($data)) {
                                                foreach ($data as $d_key => $value) {
                                                    if (!empty($value)) {
                                                        $row[$column[$d_key]] = $value;
                                                    }
                                                }
                                                $row_array[] = $row;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (!empty($row_array)) {

                            $request->merge(
                                ['multi_grn_data' => $row_array]
                            );


                            DB::beginTransaction();

                            // Call internal store method
                            $this->store($request);

                            DB::commit();

                            $html = view('company.pages.multi-grn.print_table', [
                                'row_array' => $row_array,
                                'vendor'    => $vendor
                            ])->render();

                            return AjaxResponse::success(
                                message: 'Multi GRN data processed successfully.',
                                data: [
                                    'rows' => $row_array,
                                    'html' => $html
                                ]
                            );
                        }
                    } else {
                        return AjaxResponse::error('Excel Sheet Not Match With format.', [], 422);
                    }
                } else {
                    return AjaxResponse::error('Excel Sheet Not Match With format.', [], 422);
                }
            } else {
                return AjaxResponse::error('Please upload a valid Excel file.', [], 422);
            }
        } catch (Exception $e) {
            DB::rollback();
            return AjaxResponse::error('Excel Sheet Not Match With format. ' . $e->getMessage(), [], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (isset($request->multi_grn_data) && !empty($request->multi_grn_data)) {

            $multi_grn_data = $request->multi_grn_data;
            $companyId = company_id();
            $fyId = financial_year_id();
            $userId = current_user_id();
            $importDate = date('Y-m-d');
            foreach ($multi_grn_data as $date_key => $data) {
                $data['created_by'] = $userId;
                $data['company_id'] = $companyId;
                $data['financial_year_id'] = $fyId;
                $data['multi_grn_import_date'] = $importDate;

                $exists = MultiGrn::where('company_id', $companyId)->where('financial_year_id', $fyId)
                    ->where('inward_no', $data['inward_no'])
                    ->exists();
                if (!$exists) {
                    MultiGrn::create($data);
                }
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MultiGrn $multiGrn)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MultiGrn $multiGrn)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MultiGrn $multiGrn)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MultiGrn $multiGrn)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function import()
    {
        return view('company.pages.multi-grn.import');
    }

    // importProcess
    public function importProcess(Request $request)
    {
        dd('importProcess', $request->all());
        try {
            $result = $this->service->importProcess($request);
            return $this->success($result);
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
