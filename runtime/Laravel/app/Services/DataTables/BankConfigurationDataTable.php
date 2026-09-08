<?php

namespace App\Services\DataTables;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankConfigurationDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->has('filter_group_id') && $request->input('filter_group_id') !== '') {
            $query->where('id', $request->input('filter_group_id'));
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {

                $q->where('id', 'like', '%' . $searchTerm . '%');
                
                $q->orWhereHas('bankName', function ($sub) use ($searchTerm) {                    
                    $sub->where('bank_name', 'like', '%' . $searchTerm . '%');
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

        $rows = $query->with('creator:id,name', 'updater:id,name','bankName:id,bank_name')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        $permissions = userPermissions([
            'bank_configuration.view',
            'bank_configuration.update',
            'bank_configuration.delete',
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
