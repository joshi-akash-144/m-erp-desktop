<?php

namespace App\Services\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('rate', 'like', '%' . $searchTerm . '%')
                  ->orWhere('remarks', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->filled('zone_id')) {
            $query->where('id', $request->input('zone_id'));
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
            $sortDir   = is_array($sort) ? ($sort[0]['dir']   ?? 'asc') : 'asc';
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('name', 'asc');
        }

        $page     = max((int) $request->input('page', 1), 1);
        $size     = (int) $request->input('size', 10);
        $total    = $query->count();
        $lastPage = (int) ceil($total / $size);
        $start    = ($page - 1) * $size;

        $rows = $query
            ->with('creator:id,name', 'updater:id,name')
            ->skip($start)
            ->take($size)
            ->get();

        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1;
            return $item;
        });

        $permissions = userPermissions([
            'zone.view',
            'zone.update',
            'zone.delete',
        ], true);

        return response()->json([
            'data'        => $rows,
            'last_page'   => $lastPage,
            'permissions' => $permissions,
            'total'       => $total,
        ]);
    }
}
