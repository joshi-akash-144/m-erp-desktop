<?php

namespace App\Services\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyUserDataTable
{
    public function getData(Request $request): JsonResponse
    {
        $query = DB::table('company_users')
            ->join('companies', 'companies.id', '=', 'company_users.company_id')
            ->join('users', 'users.id', '=', 'company_users.user_id')
            ->leftJoin('users as assigners', 'assigners.id', '=', 'company_users.assigned_by')
            ->select([
                'company_users.company_id',
                'company_users.user_id',
                'company_users.status',
                'company_users.assigned_at',
                'companies.name as company_name',
                'companies.code as company_code',
                'users.name as user_name',
                'users.email as user_email',
                'assigners.name as assigned_by_name',
            ])
            ->whereNull('companies.deleted_at');

        if ($request->filled('filter_company_id')) {
            $query->where('company_users.company_id', $request->input('filter_company_id'));
        }

        if ($request->filled('filter_user_id')) {
            $query->where('company_users.user_id', $request->input('filter_user_id'));
        }

        if ($request->filled('filter_status') && $request->input('filter_status') !== '') {
            $query->where('company_users.status', (bool) $request->input('filter_status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('companies.name', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        $sort      = $request->input('sort');
        $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'companies.name') : 'companies.name';
        $sortDir   = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';

        $allowedSorts = ['company_name', 'user_name', 'assigned_at', 'status'];
        $sortMap = [
            'company_name' => 'companies.name',
            'user_name'    => 'users.name',
            'assigned_at'  => 'company_users.assigned_at',
            'status'       => 'company_users.status',
        ];

        $dbSortField = in_array($sortField, $allowedSorts) ? $sortMap[$sortField] : 'companies.name';
        $query->orderBy($dbSortField, $sortDir === 'desc' ? 'desc' : 'asc');

        $page  = max((int) $request->input('page', 1), 1);
        $size  = (int) $request->input('size', 50);
        $total = $query->count();
        $lastPage = (int) ceil($total / $size);

        $rows = $query->skip(($page - 1) * $size)->take($size)->get();

        return response()->json([
            'data'      => $rows,
            'last_page' => $lastPage,
            'total'     => $total,
        ]);
    }
}
