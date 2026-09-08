<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Account;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AuditTrailController extends Controller
{
    public function index(): View
    {
        return view('company.pages.fas.audit-trails.index');
    }

    private function buildQuery(Request $request)
    {
        $companyId = company_id();
        $financialYearId = financial_year_id();

        $query = AuditTrail::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId);

        if ($request->filled('module')) {
            $mod = strtolower($request->module);
            $query->where(function($q) use ($request, $mod) {
                $q->where('module', $request->module)
                  ->orWhere('module', $mod)
                  ->orWhere('module', str_replace('_voucher', '', $mod));
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('performed_at', '>=', \Carbon\Carbon::createFromFormat('d-m-Y', $request->from_date)->format('Y-m-d'));
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('performed_at', '<=', \Carbon\Carbon::createFromFormat('d-m-Y', $request->to_date)->format('Y-m-d'));
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('user_name', 'like', '%' . $request->search . '%');
            });
        }

        return $query;
    }

    private function getHistoriesForGroup($module, $recordIds)
    {
        $history = AuditTrail::with('details')
            ->where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->where('module', $module)
            ->whereIn('voucher_id', $recordIds)
            ->orderBy('version', 'asc')
            ->get();
            
        return $history->groupBy('voucher_id');
    }

    private function buildStateToVersion($histories, $targetVersion)
    {
        $state = [];
        foreach ($histories as $log) {
            foreach ($log->details as $detail) {
                \Illuminate\Support\Arr::set($state, $detail->field_name, $detail->new_value);
            }
            if ($log->version == $targetVersion) break;
        }
        return $state;
    }

    public function list(Request $request): JsonResponse
    {
        $query = $this->buildQuery($request);
        
        // Instead of fetching all max IDs (which can exhaust memory and is slow),
        // we use a correlated NOT EXISTS subquery to only get the latest version for each module/voucher_id.
        $query->whereNotExists(function ($q) {
            $q->select(\DB::raw(1))
              ->from('audit_trails as at2')
              ->whereColumn('at2.company_id', 'audit_trails.company_id')
              ->whereColumn('at2.financial_year_id', 'audit_trails.financial_year_id')
              ->whereColumn('at2.module', 'audit_trails.module')
              ->whereColumn('at2.voucher_id', 'audit_trails.voucher_id')
              ->whereColumn('at2.id', '>', 'audit_trails.id');
        });

        $limit = $request->get('size', 100);
        $entries = $query->orderByDesc('performed_at')->orderByDesc('id')->paginate($limit);

        $groupedModules = $entries->groupBy('module');
        $allHistories = [];
        $voucherIds = [];
        foreach ($groupedModules as $module => $moduleEntries) {
            $vIds = $moduleEntries->pluck('voucher_id')->toArray();
            $allHistories[$module] = $this->getHistoriesForGroup($module, $vIds);
            $voucherIds = array_merge($voucherIds, $vIds);
        }
        
        $vouchers = \App\Models\Voucher::withTrashed()->whereIn('id', array_unique($voucherIds))->pluck('voucher_number', 'id');

        $entries->getCollection()->transform(function($e) use ($allHistories, $vouchers) {
            $histories = $allHistories[$e->module][$e->voucher_id] ?? collect();
            
            $firstState = $this->buildStateToVersion($histories, 1);
            $latestState = $this->buildStateToVersion($histories, $e->version);
            
            $orgValue = collect($firstState['details'] ?? [])->sum(function ($d) {
                return $d['debit'] ?? $d['debit_amount'] ?? 0;
            });
            $finalValue = collect($latestState['details'] ?? [])->sum(function ($d) {
                return $d['debit'] ?? $d['debit_amount'] ?? 0;
            });

            return [
                'id'           => $e->id, // this is passed to 'show'
                'date'         => $e->performed_at?->format('d-m-Y') ?? $e->created_at?->format('d-m-Y'),
                'time'         => $e->performed_at?->format('H:i:s') ?? $e->created_at?->format('H:i:s'),
                'action'       => ucfirst(strtolower($e->action)),
                'version'      => $e->version,
                'user_name'    => $e->user_name,
                'voucher_type' => ucwords(strtolower(str_replace('_', ' ', $e->module))),
                'voucher_no'   => $latestState['voucher_no'] ?? $vouchers[$e->voucher_id] ?? '—',
                'reference_number' => $latestState['reference_number'] ?? $e->reference_number ?? '—',
                'org_value'    => $orgValue,
                'final_value'  => $finalValue,
            ];
        });

        return response()->json([
            'last_page' => $entries->lastPage(),
            'data'      => $entries->items(),
        ]);
    }

    public function listSummary(Request $request): JsonResponse
    {
        $query = $this->buildQuery($request);
        $limit = $request->get('size', 100);
        $entries = $query->orderByDesc('performed_at')->orderByDesc('id')->paginate($limit);

        // Pre-fetch histories to rebuild state for each entry version
        $groupedModules = $entries->groupBy('module');
        $allHistories = [];
        $voucherIds = [];
        foreach ($groupedModules as $module => $moduleEntries) {
            $vIds = $moduleEntries->pluck('voucher_id')->toArray();
            $allHistories[$module] = $this->getHistoriesForGroup($module, $vIds);
            $voucherIds = array_merge($voucherIds, $vIds);
        }
        
        $vouchers = \App\Models\Voucher::withTrashed()->whereIn('id', array_unique($voucherIds))->pluck('voucher_number', 'id');

        $accountIds = [];
        $reconstructedStates = [];
        
        foreach ($entries as $e) {
            $histories = $allHistories[$e->module][$e->voucher_id] ?? collect();
            $currentState = $this->buildStateToVersion($histories, $e->version);
            $reconstructedStates[$e->id] = $currentState;
            
            foreach ($currentState['details'] ?? [] as $d) {
                if (isset($d['account_id'])) $accountIds[] = $d['account_id'];
            }
        }
        
        $accounts = Account::whereIn('id', array_unique($accountIds))->pluck('name', 'id');

        $flatData = [];
        foreach ($entries as $e) {
            $currentState = $reconstructedStates[$e->id];
            $details = $currentState['details'] ?? [];
            
            $finalValue = collect($details)->sum(function ($d) {
                return $d['debit'] ?? $d['debit_amount'] ?? 0;
            });
            
            // To get original value for this voucher, build version 1
            $histories = $allHistories[$e->module][$e->voucher_id] ?? collect();
            $firstState = $this->buildStateToVersion($histories, 1);
            $orgValue = collect($firstState['details'] ?? [])->sum(function ($d) {
                return $d['debit'] ?? $d['debit_amount'] ?? 0;
            });
            
            $date = $e->performed_at?->format('d-m-Y') ?? $e->created_at?->format('d-m-Y');
            $time = $e->performed_at?->format('H:i:s') ?? $e->created_at?->format('H:i:s');
            $action = ucfirst(strtolower($e->action));
            $voucherType = ucwords(strtolower(str_replace('_', ' ', $e->module)));
            $voucherNo = $currentState['voucher_no'] ?? $vouchers[$e->voucher_id] ?? '—';
            $referenceNumber = $currentState['reference_number'] ?? $e->reference_number ?? '—';
            $versionNum = $e->version;

            if (empty($details)) {
                $flatData[] = [
                    'id'           => $e->id,
                    'user_name'    => $e->user_name,
                    'date'         => $date,
                    'time'         => $time,
                    'action'       => $action,
                    'voucher_type' => $voucherType,
                    'voucher_no'   => $voucherNo,
                    'reference_number' => $referenceNumber,
                    'version'      => $versionNum,
                    'account_name' => '—',
                    'debit'        => 0,
                    'credit'       => 0,
                    'org_value'    => $orgValue,
                    'final_value'  => $finalValue,
                    '_is_first'    => true,
                ];
            } else {
                foreach ($details as $idx => $d) {
                    $flatData[] = [
                        'id'           => $e->id . '-' . $idx,
                        'user_name'    => $idx === 0 ? $e->user_name : '',
                        'date'         => $idx === 0 ? $date : '',
                        'time'         => $idx === 0 ? $time : '',
                        'action'       => $idx === 0 ? $action : '',
                        'voucher_type' => $idx === 0 ? $voucherType : '',
                        'voucher_no'   => $idx === 0 ? $voucherNo : '',
                        'reference_number' => $idx === 0 ? $referenceNumber : '',
                        'version'      => $idx === 0 ? $versionNum : '',
                        'account_name' => isset($d['account_id']) ? ($accounts[$d['account_id']] ?? 'Unknown') : '—',
                        'debit'        => $d['debit'] ?? $d['debit_amount'] ?? 0,
                        'credit'       => $d['credit'] ?? $d['credit_amount'] ?? 0,
                        'org_value'    => $idx === 0 ? $orgValue : null,
                        'final_value'  => $idx === 0 ? $finalValue : null,
                        '_is_first'    => $idx === 0,
                    ];
                }
            }
        }

        return response()->json([
            'last_page' => $entries->lastPage(),
            'data'      => $flatData,
        ]);
    }

    public function show($id): JsonResponse
    {
        $log = AuditTrail::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->findOrFail($id);
            
        $history = AuditTrail::with('details')
            ->where('company_id', company_id())
            ->where('financial_year_id', financial_year_id())
            ->where('module', $log->module)
            ->where('voucher_id', $log->voucher_id)
            ->orderBy('version', 'asc')
            ->get();
            
        $versionsData = [];
        $currentState = [];
        $accountIds   = [];

        // Reconstruct states sequentially
        // Arr::set() will build nested arrays from dotted keys like 'items.0.item_id'
        foreach ($history as $h) {
            foreach ($h->details as $detail) {
                \Illuminate\Support\Arr::set($currentState, $detail->field_name, $detail->new_value);
            }
            
            foreach ($currentState['details'] ?? [] as $d) {
                if (isset($d['account_id'])) $accountIds[] = $d['account_id'];
            }
            
            $versionsData[$h->version] = json_decode(json_encode($currentState), true);
        }

        $accounts = Account::whereIn('id', array_unique($accountIds))->pluck('name', 'id');
        $allFields = [];

        // Collect top-level keys — skip sections that are rendered row-by-row below
        $sectionKeys = ['details', 'items', 'bill_sundries', '__formatted_details', '__formatted_items', '__formatted_sundries'];

        foreach ($versionsData as $vData) {
            foreach ($vData as $k => $val) {
                if (in_array($k, $sectionKeys) || in_array($k, $allFields)) {
                    continue;
                }
                if (in_array($k, [
                    'id',
                    'company_id',
                    'financial_year_id',
                    'voucher_id',
                    'source_id',
                    'vehicle_id',
                    'account_id',
                    'consignor_id',
                    'consignee_id',
                    'from_destination_id',
                    'to_destination_id',
                    'purchase_type_id',
                    'sale_type_id',
                    'item_id',
                    'zone_id',
                ])) {
                    continue;
                }
                $allFields[] = $k;
            }
        }

        // Format details (journal/payment account lines) for each version
        foreach ($history as $h) {
            $v   = $h->version;
            $vd  = $versionsData[$v];

            // ── SECTION_VOUCHER_DETAILS → detail_N rows ─────────────────────
            $formattedDetails = [];
            if (!in_array($log->model_name, [\App\Models\PurchaseInvoice::class, \App\Models\SalesInvoice::class])) {
                foreach ($vd['details'] ?? [] as $idx => $d) {
                    $acctName = isset($d['account_name']) && !empty($d['account_name'])
                        ? $d['account_name']
                        : (isset($d['account_id']) ? ($accounts[$d['account_id']] ?? 'Unknown') : '—');

                    $debitVal  = $d['debit']  ?? $d['debit_amount']  ?? 0;
                    $creditVal = $d['credit'] ?? $d['credit_amount'] ?? 0;
                    $dc        = $debitVal > 0 ? 'Debit' : 'Credit';
                    $amt       = max($debitVal, $creditVal);
                    $dcClass   = strtolower($dc) === 'debit' ? 'text-primary' : 'text-success';

                    $fieldKey = "detail_{$idx}";
                    $formattedDetails[$fieldKey] = "<div class='fw-bold text-dark mb-1'>$acctName</div>"
                        . "<div class='small text-muted'><span class='fw-bold $dcClass'>$dc:</span> "
                        . "<span class='fw-bold text-dark'>" . number_format((float) $amt, 2, '.', '') . "</span></div>";

                    if (!in_array($fieldKey, $allFields)) {
                        $allFields[] = $fieldKey;
                    }
                }
            }
            $versionsData[$v]['__formatted_details'] = $formattedDetails;

            // ── SECTION_ITEM_ENTRIES → item_N rows ──────────────────────────
            $formattedItems = [];
            foreach ($vd['items'] ?? [] as $idx => $item) {
                $fieldKey = "item_{$idx}";

                // Prefer 'name' (new format), fallback to 'item_name' (old format), then generic label
                $itemName = !empty($item['name']) ? $item['name']
                    : (!empty($item['item_name']) ? $item['item_name'] : 'Item #' . ($idx + 1));

                $hsnVal = $item['hsn_sac_code'] ?? '';
                $hsnCode = !empty($hsnVal) ? "HSN: <b>{$hsnVal}</b> &nbsp; " : "";

                $qty  = $item['quantity'] ?? '—';
                $pQty = $item['party_quantity'] ?? '—';
                $rate = $item['rate']     ?? '—';
                $amt  = isset($item['amount']) ? number_format((float) $item['amount'], 2, '.', '') : '—';

                $unit    = !empty($item['item_unit_name']) ? "Unit: <b>{$item['item_unit_name']}</b> &nbsp; " : "";
                $bags    = isset($item['bag_count']) && (float)$item['bag_count'] > 0 ? "Bags: <b>{$item['bag_count']}</b> " . (!empty($item['bag_type']) ? "({$item['bag_type']})" : "") . " &nbsp; " : (isset($item['bags']) ? "Bags: <b>{$item['bags']}</b> &nbsp; " : "");
                $netWeight = isset($item['net_weight']) && (float)$item['net_weight'] > 0 ? "Net Wt: <b>{$item['net_weight']}</b> &nbsp; " : "";
                $kms     = isset($item['kms']) && (float)$item['kms'] > 0 ? "KMs: <b>{$item['kms']}</b> &nbsp; " : "";
                $zone    = !empty($item['zone_name']) ? "Zone: <b>{$item['zone_name']}</b> &nbsp; " : "";
                $incRate = isset($item['inclusive_rate']) ? "Inc Rate: <b>{$item['inclusive_rate']}</b> &nbsp; " : "";
                $cond    = !empty($item['condition_name']) ? "Cond: <b>{$item['condition_name']}</b> &nbsp; " : "";
                $dest    = !empty($item['destination_name']) ? "Dest: <b>{$item['destination_name']}</b> &nbsp; " : "";
                $order   = !empty($item['order_no']) ? "Order: <b>{$item['order_no']}</b> &nbsp; " : "";
                $cgst    = isset($item['cgst_rate']) ? "CGST: <b>{$item['cgst_rate']}%</b> &nbsp; " : "";
                $sgst    = isset($item['sgst_rate']) ? "SGST: <b>{$item['sgst_rate']}%</b> &nbsp; " : "";
                $igst    = isset($item['igst_rate']) ? "IGST: <b>{$item['igst_rate']}%</b> &nbsp; " : "";

                $partyQtyText = !empty($item['party_quantity']) ? "P.Qty: <b>{$pQty}</b> &nbsp; " : "";
                $qtyText = (isset($item['quantity']) && (float)$item['quantity'] > 0) ? "Qty: <b>{$qty}</b> &nbsp; " : "";

                $formattedItems[$fieldKey] = "<div class='fw-bold text-dark mb-1'>{$itemName}</div>"
                    . "<div class='small text-muted mb-1'>{$hsnCode}{$unit}{$zone}{$cond}{$dest}{$order}</div>"
                    . "<div class='small text-muted mb-1'>{$cgst}{$sgst}{$igst}</div>"
                    . "<div class='small text-muted'>{$partyQtyText}{$qtyText}{$bags}{$netWeight}{$kms}Rate: <b>{$rate}</b> &nbsp; {$incRate} Amt: <b>{$amt}</b></div>";

                if (!in_array($fieldKey, $allFields)) {
                    $allFields[] = $fieldKey;
                }
            }
            $versionsData[$v]['__formatted_items'] = $formattedItems;

            // ── SECTION_BILL_SUNDRIES → sundry_N rows ───────────────────────
            $formattedSundries = [];
            foreach ($vd['bill_sundries'] ?? [] as $idx => $s) {
                $fieldKey = "sundry_{$idx}";
                $name = $s['name']   ?? '—';
                $val  = isset($s['amount'])  ? number_format((float) $s['amount'],  2, '.', '') : '—';

                $formattedSundries[$fieldKey] = "<div class='fw-bold text-dark mb-1'>{$name}</div>"
                    . "<div class='small text-muted'>Amount: <b>{$val}</b></div>";

                if (!in_array($fieldKey, $allFields)) {
                    $allFields[] = $fieldKey;
                }
            }
            $versionsData[$v]['__formatted_sundries'] = $formattedSundries;
        }

        // Build comparison rows
        $rows = [];
        foreach ($allFields as $field) {
            // Humanise the field label
            if (preg_match('/^detail_(\d+)$/', $field)) {
                $fieldLabel = 'Account Detail';
            } elseif (preg_match('/^item_(\d+)$/', $field, $m)) {
                $fieldLabel = 'Item #' . ($m[1] + 1);
            } elseif (preg_match('/^sundry_(\d+)$/', $field, $m)) {
                $fieldLabel = 'Bill Sundry #' . ($m[1] + 1);
            } else {
                $fieldLabel = ucwords(str_replace('_', ' ', $field));
            }

            $row = ['field' => $fieldLabel, 'values' => []];
            $previousValue = null;

            foreach ($history as $h) {
                $v = $h->version;

                if (str_starts_with($field, 'detail_')) {
                    $val = $versionsData[$v]['__formatted_details'][$field] ?? '—';
                } elseif (str_starts_with($field, 'item_')) {
                    $val = $versionsData[$v]['__formatted_items'][$field] ?? '—';
                } elseif (str_starts_with($field, 'sundry_')) {
                    $val = $versionsData[$v]['__formatted_sundries'][$field] ?? '—';
                } else {
                    $val = $versionsData[$v][$field] ?? '—';
                }

                // Determine if it changed
                $isChanged = ($v > 1 && $val !== $previousValue);
                
                $formattedVal = $val;
                if (is_array($val)) {
                    // Should not happen for known sections, but safeguard
                    $formattedVal = json_encode($val);
                } elseif (is_string($val) && str_ends_with($field, 'date') && !empty($val)) {
                    try {
                        $formattedVal = \Carbon\Carbon::parse($val)->format('d-m-Y');
                    } catch (\Exception $e) {}
                }

                $row['values'][$v] = ['value' => $formattedVal, 'changed' => $isChanged];
                $previousValue = $val;
            }
            $rows[] = $row;
        }

        return response()->json([
            'success'  => true,
            'versions' => $history->pluck('version'),
            'rows'     => $rows,
        ]);
    }
}
