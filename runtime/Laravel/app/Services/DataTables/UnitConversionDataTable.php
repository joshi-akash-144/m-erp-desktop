<?php

namespace App\Services\DataTables;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitConversionDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->has('filter_unit_conversion_id') && $request->input('filter_unit_conversion_id') !== '') {
            $query->where('id', $request->input('filter_unit_conversion_id'));
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                // 1. Search the main table's numeric ID directly (if the user types an ID number)
                $q->where('id', $searchTerm);

                // 2. Use orWhereHas to search the related table's 'name' column
                $q->orWhereHas('mainUnit', function ($relationQuery) use ($searchTerm) {
                    $relationQuery->where('name', 'like', '%' . $searchTerm . '%');
                });
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
        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;

        $rows = $query->with('creator:id,name', 'updater:id,name', 'mainUnit:id,name', 'subUnit:id,name')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        // ✅ Add row numbers manually
        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1; // constant numbering across pages
            return $item;
        });
 

        $permissions = userPermissions([
            'unit_conversion.view',
            'unit_conversion.update',
            'unit_conversion.delete',
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
