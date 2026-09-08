<?php

namespace App\Http\Controllers;

use App\Exports\ZoneWiseStatementExport;
use App\Helpers\AjaxResponse;
use App\Models\Company;
use App\Models\FreightInvoiceItemOrder;
use App\Models\Item;
use App\Services\FreightService;
use File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\MasterDataService;
use App\Services\FreightInvoiceService;
use App\Models\DairyImport;
use App\Models\DairyImportItem;
use App\Models\Freight;
use App\Models\FrightInvoiceItem;
use App\Models\Zone;

class FreightInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, MasterDataService $masterService, FreightInvoiceService $invoiceService)
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

            $result = $invoiceService->freightInvoiceList($filters);
            return response()->json($result);
        }

        $customers = $masterService->get('customers', company_id());

        return view('company.pages.freight-invoice.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(MasterDataService $masterService, FreightService $freightService)
    {
        $companyId = company_id();
        $items     = collect($masterService->get('items', $companyId));
        
        $savedOrders = FreightInvoiceItemOrder::where('company_id', $companyId)
            ->pluck('sort_order', 'item_id')
            ->toArray();

        $items = $items->sort(function($a, $b) use ($savedOrders) {
            $idA = is_array($a) ? $a['id'] : $a->id;
            $idB = is_array($b) ? $b['id'] : $b->id;
            $nameA = is_array($a) ? $a['name'] : $a->name;
            $nameB = is_array($b) ? $b['name'] : $b->name;
            
            $orderA = $savedOrders[$idA] ?? 999999;
            $orderB = $savedOrders[$idB] ?? 999999;
            
            if ($orderA === $orderB) {
                return strcasecmp($nameA, $nameB);
            }
            return $orderA <=> $orderB;
        })->values()->all();

        $customers = $masterService->get('customers', $companyId);
        $zones     = Zone::where('company_id', $companyId)->select('id', 'name')->get();

        $ref       = (string) $freightService->getDefaultNextBillNumber($companyId, financial_year_id());

        return view('company.pages.freight-invoice.create', compact('items', 'zones', 'customers', 'ref'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, FreightInvoiceService $service)
    {
        // Strip out blank/empty item rows before validation
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn($item) => !empty($item['item_id']))
                ->values()
                ->toArray(),
        ]);

        $data = $request->validate([
            'uuid'               => ['required', 'uuid'],
            'invoice_date'       => ['required', 'date'],
            'account_id'         => ['required', 'integer', 'exists:accounts,id'],
            'from_date'          => ['required', 'date'],
            'to_date'            => ['required', 'date', 'after_or_equal:from_date'],
            'narration'          => ['nullable', 'string', 'max:500'],
            'total_amount'       => ['required', 'numeric', 'min:0'],
            'dairy_import_ids'   => ['nullable', 'array'],
            'dairy_import_ids.*' => ['integer', 'exists:dairy_imports,id'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.item_id'    => ['required', 'integer', 'exists:items,id'],
            'items.*.zone_id'    => ['required', 'integer', 'exists:zones,id'],
            'items.*.quantity'   => ['required', 'numeric', 'gt:0'],
            'items.*.rate'       => ['required', 'numeric', 'gt:0'],
            'items.*.amount'     => ['required', 'numeric', 'gt:0'],
        ]);

        $companyId        = company_id();
        $financialYearId  = financial_year_id();

        $freight = $service->createFreightInvoice($data, $companyId, $financialYearId);

        return response()->json([
            'success' => true,
            'message' => 'Freight invoice created successfully.',
            'data'    => [
                'id'             => $freight->id,
                'invoice_number' => $freight->invoice_number,
            ],
        ]);
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Print a single freight invoice.
     */
    public function print(string $id)
    {
        $companyId = company_id();
        $savedOrders = FreightInvoiceItemOrder::where('company_id', $companyId)
            ->pluck('sort_order', 'item_id')
            ->toArray();

        $freight = Freight::with([
            'account.taxDetail',
            'items.item',
            'items.zone',
        ])->findOrFail($id);

        $freight->setRelation('items', $freight->items->sort(function($a, $b) use ($savedOrders) {
            $zoneA = $a->zone->name;
            $zoneB = $b->zone->name;
            $orderA = $savedOrders[$a->item_id];
            $orderB = $savedOrders[$b->item_id];
            return [$zoneA, $orderA] <=> [$zoneB, $orderB];
        })->values());

        $company     = Company::find(company_id());
        $companyName = $company->name ?? '';

        $html = view('company.pages.freight-invoice.freight-invoice', compact('freight', 'companyName', 'company'))->render();

        return AjaxResponse::success('Freight invoice print ready.', ['html' => $html]);
    }

    /**
     * Print zone-wise freight invoice data.
     */
    public function printZonewise(string $id)
    {
        $freight = Freight::findOrFail($id);
        $companyId = company_id();

        $savedOrders = FreightInvoiceItemOrder::where('company_id', $companyId)
            ->pluck('sort_order', 'item_id')
            ->toArray();

        $items = FrightInvoiceItem::with(['zone', 'item', 'vehicle', 'destination'])
            ->where('freight_id', $freight->id)
            ->get()
            ->sort(function($a, $b) use ($savedOrders) {
                $zoneA = $a->zone->name;
                $zoneB = $b->zone->name;
                $orderA = $savedOrders[$a->item_id];
                $orderB = $savedOrders[$b->item_id];
                return [$zoneA, $orderA] <=> [$zoneB, $orderB];
            })
            ->values();

        if ($items->isEmpty()) {
            return AjaxResponse::error('no rows in items');
        }

        $zonewise_data = [];

        foreach ($items as $item) {
            $zoneName = $item->zone->name ?? '';
            $productName = $item->item->name ?? '';

            if (!isset($zonewise_data[$zoneName])) {
                $zonewise_data[$zoneName] = [];
            }
            if (!isset($zonewise_data[$zoneName][$productName])) {
                $zonewise_data[$zoneName][$productName] = [];
            }

            $zonewise_data[$zoneName][$productName][] = [
                'Billing Date' => $item->billing_date ? date('d/m/Y', strtotime($item->billing_date)) : '-',
                'Customer PO No' => $item->customer_po_no ?? '-',
                'Name of Sold To Party' => $item->destination->name ?? '-',
                'Material Quantity' => $item->quantity ?? 0,
                'Vehicle Number' => $item->vehicle->name ?? '-',
            ];
        }

        ksort($zonewise_data);

        $html = view('company.pages.freight-invoice.zonewise-freight-invoice', compact('zonewise_data'))->render();

        return AjaxResponse::success('Zone-wise print ready.', ['html' => $html]);
    }

    public function exportZonewise(string $id): JsonResponse
    {
        try {
            $freight = Freight::findOrFail($id);
            $companyId = company_id();

            $savedOrders = FreightInvoiceItemOrder::where('company_id', $companyId)
                ->pluck('sort_order', 'item_id')
                ->toArray();

            $items = FrightInvoiceItem::with(['zone', 'item', 'vehicle', 'destination'])
                ->where('freight_id', $freight->id)
                ->get()
                ->sort(function($a, $b) use ($savedOrders) {
                    $zoneA = $a->zone->name;
                    $zoneB = $b->zone->name;
                    $orderA = $savedOrders[$a->item_id];
                    $orderB = $savedOrders[$b->item_id];
                    return [$zoneA, $orderA] <=> [$zoneB, $orderB];
                })
                ->values();

            if ($items->isEmpty()) {
                return AjaxResponse::error('no rows in items');
            }

            $zonewise_data = [];

            foreach ($items as $item) {
                $zoneName = $item->zone->name ?? '';
                $productName = $item->item->name ?? '';

                if (!isset($zonewise_data[$zoneName])) {
                    $zonewise_data[$zoneName] = [];
                }
                if (!isset($zonewise_data[$zoneName][$productName])) {
                    $zonewise_data[$zoneName][$productName] = [];
                }

                $zonewise_data[$zoneName][$productName][] = [
                    'Billing Date' => $item->billing_date ? date('d/m/Y', strtotime($item->billing_date)) : '-',
                    'Customer PO No' => $item->customer_po_no ?? '-',
                    'Name of Sold To Party' => $item->destination->name ?? '-',
                    'Material Quantity' => $item->quantity ?? 0,
                    'Vehicle Number' => $item->vehicle->name ?? '-',
                ];
            }

            $directory = 'master_reports';
            $directoryPath = storage_path("app/public/{$directory}");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }

            ksort($zonewise_data);

            $fileName = "zone_wise_statement_" . preg_replace('/[^A-Za-z0-9\-]/', '_', $freight->invoice_number) . "_" . now()->format('d_m_Y_His') . ".xlsx";

            \Maatwebsite\Excel\Facades\Excel::store(
                new ZoneWiseStatementExport($zonewise_data),
                "{$directory}/{$fileName}",
                'public',
                \Maatwebsite\Excel\Excel::XLSX
            );

            return AjaxResponse::success("Zone-wise Statement Exported successfully", [
                'file_url' => asset("storage/{$directory}/{$fileName}"),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            return AjaxResponse::error('Export failed', [$e->getMessage()]);
        }
    }

    public function getImportItemsData(Request $request): JsonResponse
    {
        $request->validate([
            'import_ids'   => ['required', 'array', 'min:1'],
            'import_ids.*' => ['integer'],
        ]);

        $companyId = company_id();

        $validIds = DairyImport::where('company_id', $companyId)
            ->where('import_type', DairyImport::DAIRY_FILE)
            ->whereIn('id', $request->import_ids)
            ->pluck('id');

        if ($validIds->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No valid imports found.'], 422);
        }

        $savedOrders = FreightInvoiceItemOrder::where('company_id', $companyId)
            ->pluck('sort_order', 'item_id')
            ->toArray();

        $rows = DairyImportItem::with(['product:id,name', 'zone:id,name,rate'])
            ->whereIn('dairy_import_id', $validIds)
            ->get()
            ->groupBy(fn($item) => $item->product_id . '_' . $item->zone_id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'product_id'   => $first->product_id,
                    'product_name' => $first->product?->name ?? '--',
                    'zone_id'      => $first->zone_id,
                    'zone_name'    => $first->zone?->name ?? '--',
                    'quantity'     => $group->sum('quantity'),
                    'rate'         => 0,
                ];
            })
            ->sort(function($a, $b) use ($savedOrders) {
                $zoneA = $a['zone_name'];
                $zoneB = $b['zone_name'];
                $orderA = $savedOrders[$a['product_id']];
                $orderB = $savedOrders[$b['product_id']];
                return [$zoneA, $orderA] <=> [$zoneB, $orderB];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function getDairyFileData(): JsonResponse
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $imports = DairyImport::with('product:id,name')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('import_type', DairyImport::DAIRY_FILE)
            ->where('is_used', false)
            ->orderBy('import_date')
            ->orderBy('id')
            ->get()
            ->map(fn($import) => [
                'id'           => $import->id,
                'import_date'  => $import->import_date
                    ? \DateTime::createFromFormat('Y-m-d', $import->import_date)->format('d-m-Y')
                    : '--',
                'product_id'   => $import->product_id,
                'product_name' => $import->product?->name ?? '--',
            ]);

        return response()->json([
            'success' => true,
            'data'    => $imports,
            'total'   => $imports->count(),
        ]);
    }
}
