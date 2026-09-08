<?php

namespace App\Services\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        $query->select('id','name', 'model', 'fuel_type', 'vehicle_owner_id', 'status', 'created_by', 'updated_by');

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')                  
                  ->orWhereHas('vehicleOwner', function($q2) use ($searchTerm) {
                      $q2->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }


        $sort = $request->input('sort');
        if ($sort) {
            $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'id') : 'id';
            $sortDir = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';
            if ($sortField === 'name') {               
                $query->orderBy('id', $sortDir);
            } else {               
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $page = max((int) $request->input('page', 1), 1);
        $size = (int) $request->input('size', 10);

        $total = $query->count();

        if ($size == 0) {
            $size = $total > 0 ? $total : 1;
        }

        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;
      

        $rows = $query->with('creator:id,name', 'updater:id,name', 'vehicleOwner:id,name')
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        // Add row numbers manually
        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1; 
            $item->owner_name = $item->vehicleOwner ? $item->vehicleOwner->name : '';
            // $item->driver_name = $item->driver ? $item->driver->account->name : '';
            return $item;
        });

        $permissions = userPermissions([
            'vehicle.view',
            'vehicle.update',
            'vehicle.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
        ]);
    }
}
