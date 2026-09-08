<?php

namespace App\Http\Controllers;

use App\Exports\DairyFileImportSampleExport;
use App\Models\Country;
use App\Models\DairyImport;
use App\Models\DairyImportItem;
use App\Models\Destination;
use App\Models\Item;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DairyFileImportController extends Controller
{
    private const DAIRY_FILE_HEADERS = [
        'Billing Date', 'Customer P.O. No', 'Sold to Party',
        'Name of Sold to Party', 'Material Quantity', 'Vehicle Number', 'Transport Zone',
    ];
    private const DAIRY_FILE_LAST_COL = 'G';

    public function index()
    {
        $zones = Zone::where('company_id', company_id())->where('status', true)->orderBy('name')->get();
        return view('company.pages.dairy-file-import.index', compact('zones'));
    }

    public function create() {}

    public function downloadSample(Request $request)
    {
        $type = $request->input('type');

        if ($type == 1) {
            $headers  = ['Billing Date', 'Customer P.O. No', 'Sold to Party', 'Name of Sold to Party', 'Material Quantity', 'Vehicle Number', 'Transport Zone'];
            $fileName = 'Dairy_File_Sample.xlsx';
        } elseif ($type == 2) {
            $headers  = ['Billing Date', 'Customer P.O. No', 'Sold to Party', 'Name of Sold to Party', 'Material Quantity', 'Vehicle Number', 'Material Description', 'Transporter Name', 'Transport Zone'];
            $fileName = 'Day_To_Day_File_Sample.xlsx';
        } else {
            return response()->json(['message' => 'Please select a file type first.'], 422);
        }

        return Excel::download(new DairyFileImportSampleExport($headers), $fileName);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_type'   => ['required', 'in:dairy_file,day_to_day'],
            'import_date' => ['required', 'date_format:Y-m-d'],
            'zone_ids'    => ['required', 'array', 'min:1'],
            'zone_ids.*'  => ['integer', 'exists:zones,id'],
            'import_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ], [
            'file_type.required'      => 'Please select a file type.',
            'file_type.in'            => 'File type must be Dairy File or Day to Day.',
            'import_date.required'    => 'Please select an import date.',
            'import_date.date_format' => 'Import date must be a valid date.',
            'zone_ids.required'       => 'Please select at least one zone.',
            'zone_ids.min'            => 'Please select at least one zone.',
            'import_file.required'    => 'Please select a file to import.',
            'import_file.mimes'       => 'Only Excel files (.xlsx, .xls) are allowed.',
        ]);

        $validated = $request->only(['file_type', 'import_date', 'zone_ids']);
        $file      = $request->file('import_file');

        if ($validated['file_type'] === 'dairy_file') {
            $fileHeaders = $this->readHeaderRow($file, self::DAIRY_FILE_LAST_COL);
            if ($fileHeaders !== self::DAIRY_FILE_HEADERS) {
                return response()->json([
                    'message' => 'Invalid file format. Column headers do not match. Please use the provided sample file.',
                ], 422);
            }

            $filename  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $productId = $this->matchProductByFilename($filename, company_id());

            if (!$productId) {
                return response()->json([
                    'message' => "File name '{$filename}' does not match any product in the Item Master.",
                ], 422);
            }

            // Same product already imported for this date — block before any file processing starts.
            $alreadyImported = DairyImport::where('company_id', company_id())
                ->where('financial_year_id', financial_year_id())
                ->where('import_type', DairyImport::DAIRY_FILE)
                ->where('product_id', $productId)
                ->where('import_date', $validated['import_date'])
                ->exists();

            if ($alreadyImported) {
                return response()->json([
                    'message'   => 'A Dairy File for this product has already been imported on this date.',
                    'duplicate' => true,
                ], 422);
            }

            return $this->importDairyFile($file, $validated, $productId);
        }

        return $this->importDayToDay($file, $validated);
    }

    // ── Import entry points ───────────────────────────────────────────────────

    private function importDairyFile(UploadedFile $file, array $data, int $productId): JsonResponse
    {
        return $this->runImport($file, $data, [
            'headers'          => self::DAIRY_FILE_HEADERS,
            'last_col'         => self::DAIRY_FILE_LAST_COL,
            'zone_col'         => 6,
            'product_col'      => null,  // product comes from filename, not a column
            'fixed_product_id' => $productId,
            'import_type'      => DairyImport::DAIRY_FILE,
        ]);
    }

    private function importDayToDay(UploadedFile $file, array $data): JsonResponse
    {
        return $this->runImport($file, $data, [
            'headers'          => [
                'Billing Date', 'Customer P.O. No', 'Sold to Party',
                'Name of Sold to Party', 'Material Quantity', 'Vehicle Number',
                'Material Description', 'Transporter Name', 'Transport Zone',
            ],
            'last_col'         => 'I',
            'zone_col'         => 8,
            'product_col'      => 6,    // Material Description column index
            'fixed_product_id' => null,
            'import_type'      => DairyImport::DAY_TO_DAY_FILE,
        ]);
    }

    // ── Core shared processing ────────────────────────────────────────────────

    private function runImport(UploadedFile $file, array $data, array $config): JsonResponse
    {
        $allRows = $this->readExcelRows($file, $config['last_col']);

        if (empty($allRows)) {
            return response()->json(['message' => 'The uploaded file is empty.'], 422);
        }

        $fileHeaders = array_map(fn($v) => trim((string) $v), $allRows[0]);
        if ($fileHeaders !== $config['headers']) {
            return response()->json([
                'message' => 'Invalid file format. Column headers do not match. Please use the provided sample file.',
            ], 422);
        }

        $companyId       = company_id();
        $financialYearId = financial_year_id();
        $userId          = current_user_id();

        // Preload lookups
        $vehicles     = Vehicle::where('company_id', $companyId)->pluck('id', 'name');
        $zones        = Zone::where('company_id', $companyId)->pluck('id', 'name');
        $destinations = Destination::where('company_id', $companyId)->pluck('id', 'name')
                            ->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        $defaultCountryId = Country::where('name', 'India')->value('id') ?? 1;

        // Only load items when per-row product lookup is needed (day-to-day)
        $items      = null;
        $productCol = $config['product_col'];
        if ($productCol !== null) {
            $items = Item::where('company_id', $companyId)->pluck('id', 'name')
                         ->mapWithKeys(fn($id, $name) => [strtolower($name) => $id]);
        }

        $zoneCol               = $config['zone_col'];
        $errorRows             = [];
        $importItems           = [];
        $pendingDestinations   = []; // lowercase name => original name, created only if the whole file validates

        // Only rows whose Transport Zone matches one of the zones selected on the form are processed.
        // Rows for any other zone are skipped entirely — no validation, no error, not imported.
        $selectedZoneIds   = array_map('intval', $data['zone_ids']);
        $selectedZoneNames = $zones->filter(fn($id) => in_array($id, $selectedZoneIds, true))->keys()->all();

        foreach (array_slice($allRows, 1) as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row, fn($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $billingDateRaw    = $row[0] ?? null;
            $customerPoNo      = $row[1] ?? null;
            $soldToParty       = $row[2] ?? null;
            $nameOfSoldToParty = trim((string) ($row[3] ?? ''));
            $materialQty       = $row[4] ?? null;
            $vehicleNumberRaw  = trim((string) ($row[5] ?? ''));
            $transportZoneRaw  = trim((string) ($row[$zoneCol] ?? ''));

            // Row has no date, zone, or vehicle — treat as a stray/blank row, not a validation error
            if ((string) $billingDateRaw === '' && $transportZoneRaw === '' && $vehicleNumberRaw === '') {
                continue;
            }

            // Row's zone was not one of the zones selected on the form — skip silently, no validation.
            if (!in_array($transportZoneRaw, $selectedZoneNames, true)) {
                continue;
            }

            $rowErrors = [];

            // ── Billing date — must be dd/mm/yyyy (e.g. 21/06/2026) ────────────
            $billingDate = $this->parseBillingDate($billingDateRaw);
            if ($billingDate === null) {
                $rowErrors[] = "Billing Date '{$billingDateRaw}' is invalid. Expected format dd/mm/yyyy (e.g. 21/06/2026).";
            }

            // ── Vehicle — normalize (GJ9AU5349 -> GJ09AU5349), validate format, match master ──
            $vehicleId = null;
            if ($vehicleNumberRaw === '') {
                $rowErrors[] = 'Vehicle Number is required.';
            } else {
                $vehicleNumber = $this->normalizeVehicleNumber($vehicleNumberRaw);
                if ($vehicleNumber === null) {
                    $rowErrors[] = "Vehicle Number '{$vehicleNumberRaw}' is not a valid format (expected e.g. GJ09AU5349).";
                } else {
                    $vehicleId = $vehicles[$vehicleNumber] ?? null;
                    if (!$vehicleId) {
                        $rowErrors[] = "Vehicle Number '{$vehicleNumber}' not found in Vehicle Master.";
                    }
                }
            }

            // ── Zone — must be present and match master ────────────────────────
            $zoneId = null;
            if ($transportZoneRaw === '') {
                $rowErrors[] = 'Transport Zone is required.';
            } else {
                $zoneId = $zones[$transportZoneRaw] ?? null;
                if (!$zoneId) {
                    $rowErrors[] = "Zone '{$transportZoneRaw}' not found in Zone Master.";
                }
            }

            // ── Product — only checked per-row for day-to-day imports ──────────
            $productId    = $config['fixed_product_id'];
            $materialDesc = null;
            if ($productCol !== null) {
                $materialDesc = trim((string) ($row[$productCol] ?? ''));
                if ($materialDesc !== '') {
                    $productId = $items[strtolower($materialDesc)] ?? null;
                    if (!$productId) {
                        $rowErrors[] = "Product '{$materialDesc}' not found in Item Master.";
                    }
                } else {
                    $rowErrors[] = 'Material Description is required.';
                }
            }

            // ── Destination — resolved by name now, only created after full validation passes ──
            $destinationKey = $nameOfSoldToParty !== '' ? strtolower($nameOfSoldToParty) : null;
            if ($destinationKey === null) {
                $rowErrors[] = 'Name of Sold to Party (destination) is required.';
            }

            if (!empty($rowErrors)) {
                $errorRows[] = [
                    'row'                   => $rowNumber,
                    'billing_date'          => $billingDateRaw,
                    'customer_po_no'        => $customerPoNo,
                    'sold_to_party'         => $soldToParty,
                    'name_of_sold_to_party' => $nameOfSoldToParty,
                    'material_quantity'     => $materialQty,
                    'vehicle_number'        => $vehicleNumberRaw,
                    'material_description'  => $materialDesc,
                    'transporter_name'      => '',
                    'transport_zone'        => $transportZoneRaw,
                    'errors'                => implode(' | ', $rowErrors),
                ];
                continue;
            }

            if (!isset($destinations[$destinationKey]) && !isset($pendingDestinations[$destinationKey])) {
                $pendingDestinations[$destinationKey] = $nameOfSoldToParty;
            }

            $importItems[] = [
                'billing_date'    => $billingDate,
                'customer_po_no'  => $customerPoNo,
                'sold_to_party'   => $soldToParty,
                'destination_key' => $destinationKey,
                'quantity'        => $materialQty,
                'vehicle_id'      => $vehicleId,
                'product_id'      => $productId,
                'zone_id'         => $zoneId,
            ];
        }

        // All-or-nothing: abort if any row has errors — nothing has been written to the database yet
        if (!empty($errorRows)) {
            $errorsHtml = view('company.pages.dairy-file-import.error-table', compact('errorRows'))->render();
            return response()->json([
                'message'     => count($errorRows) . ' row(s) had errors. No records were imported.',
                'imported'    => 0,
                'skipped'     => count($errorRows),
                'errors_html' => $errorsHtml,
            ], 422);
        }

        if (empty($importItems)) {
            return response()->json(['message' => 'No data rows found in the file.'], 422);
        }

        // Persist — destinations, parent and children must all succeed or none is saved
        DB::transaction(function () use ($companyId, $financialYearId, $data, $config, $userId, $importItems, $pendingDestinations, $defaultCountryId, &$destinations) {
            foreach ($pendingDestinations as $key => $name) {
                $dest = Destination::create([
                    'uuid'       => Str::uuid(),
                    'name'       => $name,
                    'company_id' => $companyId,
                    'country_id' => $defaultCountryId,
                    'is_active'  => true,
                    'created_by' => $userId,
                ]);
                $destinations[$key] = $dest->getKey();
            }

            $import = DairyImport::create([
                'uuid'              => Str::uuid(),
                'company_id'        => $companyId,
                'financial_year_id' => $financialYearId,
                'import_date'       => $data['import_date'],
                'import_type'       => $config['import_type'],
                'product_id'        => $config['fixed_product_id'],
                'created_by'        => $userId,
            ]);

            $now = now();
            DairyImportItem::insert(array_map(function ($item) use ($import, $destinations, $data, $now) {
                $item['dairy_import_id'] = $import->getKey();
                $item['to']              = $destinations[$item['destination_key']];
                unset($item['destination_key']);
                $item['import_date']     = $data['import_date'];
                $item['created_at']      = $now;
                $item['updated_at']      = $now;
                return $item;
            }, $importItems));
        });

        $importedCount = count($importItems);

        return response()->json([
            'message'     => "{$importedCount} row(s) imported successfully.",
            'imported'    => $importedCount,
            'skipped'     => 0,
            'duplicates'  => 0,
            'errors_html' => null,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function readHeaderRow(UploadedFile $file, string $lastCol): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();
        $headerRow   = $sheet->getRowIterator(1, 1)->current();

        $cells = [];
        foreach ($headerRow->getCellIterator('A', $lastCol) as $cell) {
            $cells[] = trim((string) $cell->getValue());
        }

        return $cells;
    }

    private function readExcelRows(UploadedFile $file, string $lastCol): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator('A', $lastCol) as $cell) {
                $value = $cell->getValue();
                if ($value !== null && ExcelDate::isDateTime($cell)) {
                    $dt    = ExcelDate::excelToDateTimeObject((float) $value);
                    $value = $dt->format('Y-m-d');
                }
                $cells[] = $value;
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private function parseBillingDate(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $raw = trim((string) $raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        if (is_numeric($raw)) {
            return date('Y-m-d', strtotime('1899-12-30 +' . (int) $raw . ' days'));
        }

        // Strict dd/mm/yyyy (e.g. 21/06/2026) — reject ambiguous/rolled-over dates
        if (!preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $raw, $m)) {
            return null;
        }
        [, $day, $month, $year] = $m;
        if (!checkdate((int) $month, (int) $day, (int) $year)) {
            return null;
        }

        return "{$year}-{$month}-{$day}";
    }

    /**
     * Normalizes and validates an Indian vehicle registration number.
     * Accepts GJ09AU5349 or GJ9AU5349 (single-digit RTO code gets zero-padded).
     * Returns null if the value does not match a valid plate format.
     */
    private function normalizeVehicleNumber(string $raw): ?string
    {
        $value = Str::upper(str_replace(' ', '', $raw));

        if (!preg_match('/^([A-Z]{2})(\d{1,2})([A-Z]{1,3})(\d{4})$/', $value, $m)) {
            return null;
        }

        return $m[1] . str_pad($m[2], 2, '0', STR_PAD_LEFT) . $m[3] . $m[4];
    }

    private function matchProductByFilename(string $filename, int $companyId): ?int
    {
        $needle = strtolower(trim($filename));

        return Item::where('company_id', $companyId)
            ->get(['id', 'name'])
            ->first(fn($item) => strtolower(trim($item->name)) === $needle)
            ?->id;
    }

    // ── Register: Dairy File ─────────────────────────────────────────────────

    public function dairyFileRegister()
    {
        $companyId = company_id();
        $zones     = Zone::where('company_id', $companyId)->where('status', true)->orderBy('name')->get();
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('name')->get();
        $items     = Item::where('company_id', $companyId)->orderBy('name')->get();

        return view('company.pages.dairy-file-import.dairy-file-register', compact('zones', 'vehicles', 'items'));
    }

    public function dairyFileRegisterList(Request $request): JsonResponse
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $query = DairyImport::withCount('items')
            ->with('product:id,name')
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('import_type', DairyImport::DAIRY_FILE)
            ->orderByDesc('id');

        if ($request->filled('import_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->import_date);
            if ($d) $query->whereDate('import_date', $d->format('Y-m-d'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->has('is_used') && $request->is_used !== '') {
            $query->where('is_used', (bool) $request->is_used);
        }

        $size    = (int) $request->get('size', 50);
        $imports = $query->orderByDesc('import_date')->orderByDesc('id')->paginate($size);

        $imports->getCollection()->transform(fn($import) => [
            'id'           => $import->id,
            'import_date'  => $import->import_date
                ? \DateTime::createFromFormat('Y-m-d', $import->import_date)->format('d-m-Y')
                : '--',
            'product_name' => $import->product?->name ?? '--',
            'items_count'  => $import->items_count,
            'is_used'      => (bool) $import->is_used,
        ]);

        return response()->json([
            'data'      => $imports->items(),
            'total'     => $imports->total(),
            'last_page' => $imports->lastPage(),
        ]);
    }

    public function dairyFileImportItems(int $id): JsonResponse
    {
        $import = DairyImport::where('company_id', company_id())
            ->where('import_type', DairyImport::DAIRY_FILE)
            ->with('product:id,name')
            ->findOrFail($id);

        $items = DairyImportItem::with(['vehicle:id,name', 'zone:id,name', 'destination:id,name'])
            ->where('dairy_import_id', $import->id)
            ->orderBy('billing_date')
            ->orderBy('id')
            ->get()
            ->map(fn($item) => [
                'id'             => $item->id,
                'billing_date'   => $item->billing_date
                    ? \DateTime::createFromFormat('Y-m-d', $item->billing_date)->format('d-m-Y')
                    : '--',
                'customer_po_no' => $item->customer_po_no ?? '--',
                'sold_to_party'  => $item->sold_to_party  ?? '--',
                'destination'    => $item->destination?->name ?? '--',
                'quantity'       => $item->quantity ?? 0,
                'vehicle_no'     => $item->vehicle?->name ?? '--',
                'zone'           => $item->zone?->name   ?? '--',
                'is_used'        => (bool) $item->is_used,
            ]);

        return response()->json([
            'import_date'  => $import->import_date
                ? \DateTime::createFromFormat('Y-m-d', $import->import_date)->format('d-m-Y')
                : '--',
            'product_name' => $import->product?->name ?? '--',
            'items'        => $items,
        ]);
    }

    public function dairyFileItemDestroy(int $id)
    {
        $item = DairyImportItem::whereHas('dairyImport', fn($q) => $q
            ->where('company_id', company_id())
            ->where('import_type', DairyImport::DAIRY_FILE)
        )->findOrFail($id);

        $importId = $item->dairy_import_id;
        $item->delete();

        // Remove parent if no items remain
        if (!DairyImportItem::where('dairy_import_id', $importId)->exists()) {
            DairyImport::find($importId)?->delete();
        }

        return response()->json(['message' => 'Item deleted successfully.']);
    }

    public function dairyFileImportDestroy(int $id)
    {
        // dd('working...');
        $import = DairyImport::where('company_id', company_id())
            ->where('import_type', DairyImport::DAIRY_FILE)
            ->findOrFail($id);

        DairyImportItem::where('dairy_import_id', $import->id)->delete();
        $import->delete();

        return response()->json(['message' => 'Import batch deleted successfully.']);
    }

    public function dairyFileBulkDelete(Request $request)
    {
        // dd('working...');
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $query = DairyImport::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('import_type', DairyImport::DAIRY_FILE);

        if ($request->filled('import_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->import_date);
            if ($d) $query->whereDate('import_date', $d->format('Y-m-d'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $importIds = $query->pluck('id');
        DairyImportItem::whereIn('dairy_import_id', $importIds)->delete();
        $query->delete();

        return response()->json([
            'message' => count($importIds) . ' import batch(es) deleted successfully.',
            'deleted' => count($importIds),
        ]);
    }

    // ── Register: Day-to-Day ─────────────────────────────────────────────────

    public function dayToDayRegister()
    {
        $companyId = company_id();
        $zones     = Zone::where('company_id', $companyId)->where('status', true)->orderBy('name')->get();
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('name')->get();
        $items     = Item::where('company_id', $companyId)->orderBy('name')->get();

        return view('company.pages.dairy-file-import.day-to-day-register', compact('zones', 'vehicles', 'items'));
    }

    public function dayToDayRegisterList(Request $request)
    {
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $query = DairyImportItem::with(['vehicle:id,name', 'product:id,name', 'zone:id,name', 'destination:id,name'])
            ->whereHas('dairyImport', fn($q) => $q
                ->where('company_id', $companyId)
                ->where('financial_year_id', $financialYearId)
                ->where('import_type', DairyImport::DAY_TO_DAY_FILE)
            );

        if ($request->filled('import_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->import_date);
            if ($d) {
                $query->whereDate('import_date', $d->format('Y-m-d'));
            }
        }
        if ($request->filled('start_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->start_date);
            if ($d) {
                $query->whereDate('billing_date', '>=', $d->format('Y-m-d'));
            }
        }
        if ($request->filled('end_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->end_date);
            if ($d) {
                $query->whereDate('billing_date', '<=', $d->format('Y-m-d'));
            }
        }
        if ($request->filled('zone_id'))    $query->where('zone_id', $request->zone_id);
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);
        if ($request->has('is_used') && $request->is_used !== '') {
            $query->where('is_used', (bool) $request->is_used);
        }

        $size    = (int) $request->get('size', 50);
        $entries = $query->orderByDesc('billing_date')->orderByDesc('id')->paginate($size);

        $entries->getCollection()->transform(fn($item) => [
            'id'             => $item->id,
            'import_date'    => $item->import_date  ? \DateTime::createFromFormat('Y-m-d', $item->import_date)->format('d-m-Y')  : '--',
            'billing_date'   => $item->billing_date ? \DateTime::createFromFormat('Y-m-d', $item->billing_date)->format('d-m-Y') : '--',
            'customer_po_no' => $item->customer_po_no ?? '--',
            'sold_to_party'  => $item->sold_to_party  ?? '--',
            'destination'    => $item->destination?->name ?? '--',
            'product'        => $item->product?->name     ?? '--',
            'quantity'       => $item->quantity            ?? 0,
            'vehicle_no'     => $item->vehicle?->name     ?? '--',
            'zone'           => $item->zone?->name        ?? '--',
            'is_used'        => (bool) $item->is_used,
        ]);

        return response()->json([
            'data'      => $entries->items(),
            'total'     => $entries->total(),
            'last_page' => $entries->lastPage(),
        ]);
    }

    public function dayToDayDestroy(int $id)
    {
        // dd('working...');
        $item = DairyImportItem::whereHas('dairyImport', fn($q) => $q->where('company_id', company_id()))
            ->findOrFail($id);

        $item->delete();

        return response()->json(['message' => 'Record deleted successfully.']);
    }

    public function dayToDayBulkDelete(Request $request)
    {
        // dd('working...');
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $query = DairyImportItem::whereHas('dairyImport', fn($q) => $q
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('import_type', DairyImport::DAY_TO_DAY_FILE)
        );

        if ($request->filled('import_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->import_date);
            if ($d) $query->whereDate('import_date', $d->format('Y-m-d'));
        }
        if ($request->filled('start_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->start_date);
            if ($d) $query->whereDate('billing_date', '>=', $d->format('Y-m-d'));
        }
        if ($request->filled('end_date')) {
            $d = \DateTime::createFromFormat('d-m-Y', $request->end_date);
            if ($d) $query->whereDate('billing_date', '<=', $d->format('Y-m-d'));
        }
        if ($request->filled('zone_id'))    $query->where('zone_id', $request->zone_id);
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);
        if ($request->has('is_used') && $request->is_used !== '') {
            $query->where('is_used', (bool) $request->is_used);
        }

        $deleted = $query->count();
        $query->delete();

        return response()->json(['message' => "{$deleted} record(s) deleted successfully.", 'deleted' => $deleted]);
    }

    public function show(DairyImport $dairyImport) {}

    public function edit(DairyImport $dairyImport) {}

    public function update(Request $request, DairyImport $dairyImport) {}

    public function destroy(DairyImport $dairyImport) {}
}
