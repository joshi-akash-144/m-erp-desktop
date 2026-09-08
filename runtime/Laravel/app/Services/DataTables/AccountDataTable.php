<?php

namespace App\Services\DataTables;

use App\Models\VoucherType;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;
        $query->where('is_hidden', false);
        if ($request->has('filter_type_id') && $request->input('filter_type_id') !== '') {
            $query->where('party_type', $request->input('filter_type_id'));
        }
        if ($request->has('account_group_id') && $request->input('account_group_id') !== '') {
            $query->where('account_group_id', $request->input('account_group_id'));
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('id', 'like', '%' . $searchTerm . '%');
            });
        }

        $filters = $request->input('filter', []);
        if (is_array($filters)) {
            foreach ($filters as $field => $value) {
                if ($value !== null && $value !== '') {
                    $query->where($field, 'like', '%' . $value . '%');
                }
            }
        }

        $sort = $request->input('sort');
        if ($sort) {
            $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'id') : 'id';
            $sortDir = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('id', 'asc');
        }

        $page = max((int) $request->input('page', 1), 1);
        $size = (int) $request->input('size', 10);

        $total = $query->count();

        if ($size == 0) {
            $size = $total > 0 ? $total : 1;
        }

        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;

        $rows = $query->with('accountGroup:id,name', 'taxDetail', 'creator:id,name', 'updater:id,name')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        // Bulk-load opening balances via direct join (avoids Eloquent whereHas soft-delete quirks)
        $accountIds      = $rows->pluck('id')->toArray();
        $companyId       = company_id();
        $financialYearId = financial_year_id();

        $obMap = DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->select('vt.account_id', 'vt.debit', 'vt.credit')
            ->whereIn('vt.account_id', $accountIds)
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->where('v.voucher_type_id', VoucherType::OPENING_BALANCE)
            ->where('v.is_opening', true)
            ->whereNull('v.deleted_at')
            ->get()
            ->keyBy('account_id');

        $rows->transform(function ($item, $index) use ($start, $obMap) {
            $item->no = $start + $index + 1;
            $ob = $obMap->get($item->id);
            $item->current_year_balance = $ob ? (object) [
                'opening_balance' => $ob->debit > 0 ? $ob->debit : $ob->credit,
                'opening_type'    => $ob->debit > 0 ? 'D' : 'C',
            ] : null;
            return $item;
        });

        $permissions = userPermissions([
            'account.view',
            'account.update',
            'account.delete',
        ], true);
        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
            'total_debit' => 0,
        ]);
    }
}
