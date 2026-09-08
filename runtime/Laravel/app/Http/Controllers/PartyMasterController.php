<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\PartyMaster;
use App\Models\PartyMasterDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartyMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('company-selector.pages.party-master.index');
    }

    /**
     * AJAX list for Tabulator.
     */
    public function list(Request $request)
    {
        $search = $request->input('search');
        $page   = (int) $request->input('page', 1);
        $size   = (int) $request->input('size', 15);

        $query = PartyMaster::with(['details.company', 'details.account'])
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                                        ->orWhere('party_code', 'like', "%{$search}%"));

        $total   = $query->count();
        $records = $query->orderByDesc('id')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get()
            ->map(function ($pm) {
                return [
                    'id'         => $pm->id,
                    'party_code' => $pm->party_code,
                    'name'       => $pm->name,
                    'mappings'   => $pm->details->map(fn($d) => [
                        'company' => $d->company?->name ?? '-',
                        'account' => $d->account?->name  ?? '-',
                    ])->values(),
                ];
            });

        return response()->json([
            'data'      => $records,
            'last_page' => (int) ceil($total / $size),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = Company::active()->get();

        $companyId = company_id();

        $accountGroup = AccountGroup::where('code', 170)->pluck('id')->toArray(); // Debtors Only
        
        $accounts  = Account::where('is_active', 1)->whereIn('account_group_id', $accountGroup)->get()->groupBy('company_id');

        return view('company-selector.pages.party-master.create', compact('companies', 'accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // ── 1. Basic validation ───────────────────────────────────────────────
        $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'company_accounts'  => ['required', 'array', 'min:1'],
            'company_accounts.*'=> ['nullable', 'integer'],
        ], [
            'name.required'             => 'Party name is required.',
            'company_accounts.required' => 'Please map at least one company account.',
            'company_accounts.min'      => 'Please map at least one company account.',
        ]);
        // ── 2. Filter out empty / un-selected entries ─────────────────────────
        $mappings = collect($request->input('company_accounts', []))
            ->filter(fn($accountId) => !empty($accountId))
            ->map(fn($accountId, $companyId) => [
                'company_id' => (int) $companyId,
                'account_id' => (int) $accountId,
            ])
            ->values();

        if ($mappings->isEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Please select at least one account for any company.',
            ], 422);
        }

        // ── 3. Validate each company_id & account_id exist in DB ─────────────
        $validCompanyIds = Company::active()->pluck('id');
        $validAccountIds = Account::where('is_active', 1)->pluck('id');

        foreach ($mappings as $map) {
            if (!$validCompanyIds->contains($map['company_id'])) {
                return response()->json([
                    'status'  => false,
                    'message' => "Invalid company ID: {$map['company_id']}.",
                ], 422);
            }
            if (!$validAccountIds->contains($map['account_id'])) {
                return response()->json([
                    'status'  => false,
                    'message' => "Invalid account ID: {$map['account_id']}.",
                ], 422);
            }
        }

        // ── 4. Enforce: one company → one account per party master ────────────
        //    (no duplicate company_id in the submitted payload)
        $duplicateCompany = $mappings->duplicates('company_id');
        if ($duplicateCompany->isNotEmpty()) {
            return response()->json([
                'status'  => false,
                'message' => 'Each company can only have one account mapped per party master.',
            ], 422);
        }

        // ── 5. Enforce: an account_id must not already be used in any other
        //    party_master_details row (one account → one party master globally)
        $submittedAccountIds = $mappings->pluck('account_id')->toArray();
        $alreadyUsed = PartyMasterDetail::whereIn('account_id', $submittedAccountIds)
            ->pluck('account_id')
            ->toArray();

        if (!empty($alreadyUsed)) {
            $usedAccounts = Account::whereIn('id', $alreadyUsed)->pluck('name')->implode(', ');
            return response()->json([
                'status'  => false,
                'message' => "The following account(s) are already mapped to another party master: {$usedAccounts}.",
            ], 422);
        }

        // ── 6. Persist ────────────────────────────────────────────────────────
        DB::beginTransaction();
        try {
            $party = PartyMaster::create([
                'name' => trim($request->input('name')),
            ]);

            foreach ($mappings as $map) {
                PartyMasterDetail::create([
                    'party_master_id' => $party->id,
                    'company_id'      => $map['company_id'],
                    'account_id'      => $map['account_id'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Party master '{$party->name}' created successfully.",
                'data'    => ['party_master_id' => $party->id],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong while saving. Please try again.',
            ], 500);
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
        $party = PartyMaster::findOrFail($id);

        DB::beginTransaction();
        try {
            $party->details()->delete();
            $party->delete();
            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Party master '{$party->name}' deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Could not delete. Please try again.',
            ], 500);
        }
    }
}
