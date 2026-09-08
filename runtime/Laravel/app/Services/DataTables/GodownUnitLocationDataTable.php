<?php

namespace App\Services\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GodownUnitLocationDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        if ($request->has('godown_unit_location_id') && $request->input('godown_unit_location_id') !== '') {
            $query->where('id', $request->input('godown_unit_location_id'));
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('godown_name', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('destination', function($subQuery) use ($searchTerm) {
                      $subQuery->where('name', 'like', '%' . $searchTerm . '%');
                  })
                  ->orWhereHas('user', function($subQuery) use ($searchTerm) {
                      $subQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        $sort = $request->input('sort');
        if ($sort) {
            $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'id') : 'id';
            $sortDir = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';
            // Simple mapping for relationship fields if needed
            if ($sortField === 'destination_name') $sortField = 'destination_id';
            if ($sortField === 'username') $sortField = 'user_id';
            
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('id', 'asc');
        }

        $page = max((int) $request->input('page', 1), 1);
        $size = (int) $request->input('size', 10);

        $total = $query->count();
        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;

        $rows = $query->with(['destination:id,name', 'user:id,name'])
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1;
            $item->destination_name = $item->destination->name ?? 'N/A';
            $item->username = $item->user->name ?? 'N/A';
            return $item;
        });

        $permissions = userPermissions([
            'godown_unit_location.list',
            'godown_unit_location.update',
            'godown_unit_location.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
        ]);
    }
}
