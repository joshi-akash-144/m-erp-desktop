<?php

namespace App\Services\DataTables;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountGroupDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        // --- Filters ---
        if ($request->filled('filter_group_id')) {
            $query->where('id', $request->input('filter_group_id'));
        }

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('id', 'like', '%' . $searchTerm . '%');
            });
        }

        $filters = $request->input('filter', []);
        if (is_array($filters)) {
            foreach ($filters as $field => $value) {
                if (!empty($value)) {
                    $query->where($field, 'like', '%' . $value . '%');
                }
            }
        }

        // --- Sorting ---
        $sort = $request->input('sort');
        if ($sort && is_array($sort) && !empty($sort[0]['field'])) {
            $sortField = $sort[0]['field'];
            $sortDir = $sort[0]['dir'] ?? 'asc';
            $query->orderBy($sortField, $sortDir);
        } else {
            // Default sorting
            $query->orderBy('parent_id', 'asc')
                ->orderBy('name', 'asc'); // order groups alphabetically
        }

        // --- Fetch data ---
        $rows = $query
            ->with(['parent:id,name', 'creator:id,name', 'updater:id,name'])
            ->get(['id', 'name', 'parent_id', 'status', 'created_by', 'updated_by']);

        // --- Build tree ---
        $treeData = $this->buildTree($rows);

        $permissions = userPermissions([
            'account_group.view',
            'account_group.update',
            'account_group.delete',
        ], true);

        return response()->json([
            'data' => $treeData,
            'permissions' => $permissions,
        ]);
    }


    /**
     * Build hierarchical tree from flat rows
     */
    private function buildTree($rows, $parentId = null)
    {
        $tree = [];

        foreach ($rows->where('parent_id', $parentId) as $item) {
            $children = $this->buildTree($rows, $item->id);
            $node = [
                'id' => $item->id,
                'name' => $item->name,
                'parent_id' => $item->parent_id,
                'status' => $item->status
            ];

            if (!empty($children)) {
                $node['_children'] = $children; // Tabulator expects `_children`
            }

            $tree[] = $node;
        }

        return $tree;
    }
}
