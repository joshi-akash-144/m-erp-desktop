<?php

namespace App\Services\DataTables;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->has('filter_state_id') && $request->input('filter_state_id') !== '') {
            $query->where('id', $request->input('filter_state_id'));
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
        if ($size <= 0) {
            $size = $total > 0 ? $total : 10;
        }
        $lastPage = (int) ceil($total / $size);
        $rows = $query
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        $permissions = userPermissions([
            'state.view',
            'state.update',
            'state.delete',
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
