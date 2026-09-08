<?php

namespace App\Services\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractorDataTable
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
                    ->orWhere('city', 'like', '%' . $searchTerm . '%')
                    ->orWhere('id', 'like', '%' . $searchTerm . '%');
            });
        }

        // $filters = $request->input('filter', []);
        // if (is_array($filters)) {
        //     foreach ($filters as $field => $value) {
        //         if ($value !== null && $value !== '') {
        //             $query->where($field, 'like', '%' . $value . '%');
        //         }
        //     }
        // }

        $sort = $request->input('sort');
        if ($sort) {
            $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'name') : 'name';
            $sortDir = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('name', 'asc');
        }

        $page = max((int) $request->input('page', 1), 1);
        $sizeInput = $request->input('size', 10);
        
        $total = $query->count();

        if ($sizeInput === 'true' || (int) $sizeInput === 0) {
            $size = $total > 0 ? $total : 1;
        } else {
            $size = (int) $sizeInput;
        }

        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;

        $rows = $query->with('creator:id,name', 'updater:id,name')
            ->skip($start)
            ->take($size)
            ->get();

        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1;
            return $item;
        });

        // permissions
        $permissions = userPermissions([
            'contractor.view',
            'contractor.update',
            'contractor.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
        ]);
    }
}
