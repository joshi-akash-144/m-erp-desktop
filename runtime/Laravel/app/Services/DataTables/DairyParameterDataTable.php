<?php

namespace App\Services\DataTables;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DairyParameterDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->has('filter_parameter_id') && $request->input('filter_parameter_id') !== '') {
            $query->where('id', $request->input('filter_parameter_id'));
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('guarantee', 'like', '%' . $searchTerm . '%')
                    ->orWhere('id', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('condition', function ($q2) use ($searchTerm) {
                        $q2->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('element', function ($q3) use ($searchTerm) {
                        $q3->where('name', 'like', '%' . $searchTerm . '%');
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
 
        $rows = $query->with( 'condition:id,name','element:id,name','creator:id,name', 'updater:id,name')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();
        // ✅ Add row numbers manually
        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1; // constant numbering across pages
            return $item;
        });
 
        $permissions = userPermissions([
            'dairy_parameter.view',
            'dairy_parameter.update',
            'dairy_parameter.delete',
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
