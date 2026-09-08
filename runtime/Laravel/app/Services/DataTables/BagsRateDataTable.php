<?php

namespace App\Services\DataTables;

use Illuminate\Http\Request;

class BagsRateDataTable
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query)
    {
        $request = $this->request;

        $query->with(['creator', 'updater'])
              ->select('id', 'bags_type', 'bags_rate', 'status', 'created_by', 'updated_by');

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('bags_type', 'like', "%{$searchTerm}%")
                  ->orWhere('bags_rate', 'like', "%{$searchTerm}%");
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

        $size = (int) $request->input('size', 10);
        $page = max((int) $request->input('page', 1), 1);

        $total = $query->count();
        if ($size == 0) {
            $size = $total > 0 ? $total : 1;
        }
        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;       

        $rows = $query->skip(($page - 1) * $size)
            ->take($size)
            ->get();
            
        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1; 
            return $item;
        });
       
        $permissions = userPermissions([
            'bags_rate.view',
            'bags_rate.update',
            'bags_rate.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions
        ]);
    }
}
