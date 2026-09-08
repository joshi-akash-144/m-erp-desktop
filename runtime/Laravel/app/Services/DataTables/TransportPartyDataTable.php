<?php

namespace App\Services\DataTables;

use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportPartyDataTable
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
                  ->orWhere('email', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mobile_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('gst_number', 'like', '%' . $searchTerm . '%');
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
        if ($size == 0) {
            $size = $total > 0 ? $total : 1;
        }
        $lastPage = (int) ceil($total / $size);
        $start = ($page - 1) * $size;

        $rows = $query->with(['creator:id,name', 'updater:id,name', 'state:id,name'])
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();

        $rows->transform(function ($item, $index) use ($start) {
            $item->no = $start + $index + 1;
            return $item;
        });

        $permissions = userPermissions([
            'transport_party.view',
            'transport_party.update',
            'transport_party.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
        ]);
    }
}
