<?php

namespace App\Services\DataTables;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PermissionDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('module', 'like', '%' . $searchTerm . '%');
            });
        }

        $sort = $request->input('sort');
        if ($sort) {
            $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'id') : 'id';
            $sortDir = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $page = max((int) $request->input('page', 1), 1);
        $size = (int) $request->input('size', 10);

        $total = $query->count();
        $lastPage = (int) ceil($total / $size);

        $rows = $query
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        $permissions = userPermissions([
            'permission.view',
            'permission.update',
            'permission.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
        ]);
    }
}


