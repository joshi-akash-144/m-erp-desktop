<?php

namespace App\Services\DataTables;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\OpeningBalanceService;

class DriverDataTable
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getData($query): JsonResponse
    {
        $request = $this->request;

        // Select only required columns from drivers table
        $query->select([
            'id', 
            // 'uuid', 
            'account_id',            
            'vehicle_id',
            'license_number', 
            'status', 
            'created_by', 
            'updated_by'
        ]);

        if ($request->has('search') && $request->input('search') !== '') {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('account', function($q2) use ($searchTerm) {
                    $q2->where('name', 'like', '%' . $searchTerm . '%');
                })
                ->orWhere('license_number', 'like', '%' . $searchTerm . '%');
            });
        }

        $sort = $request->input('sort');
        if ($sort) {
            $sortField = is_array($sort) ? ($sort[0]['field'] ?? 'id') : 'id';
            $sortDir = is_array($sort) ? ($sort[0]['dir'] ?? 'asc') : 'asc';
            
            if ($sortField === 'account_name') {               
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

        $rows = $query->with([
            'creator:id,name', 
            'updater:id,name', 
            'account:id,name',
            'vehicle:id,name',
        ])
            ->skip(($page - 1) * $size)
            ->take($size)
            ->get();
        $openingBalanceService = app(OpeningBalanceService::class);        
        $companyId = company_id();
        $financialYearId = financial_year_id();
        
        $rows->transform(function ($item, $index) use ($start, $openingBalanceService, $companyId, $financialYearId) {
            $item->no = $start + $index + 1; 
            
            // Map relation properties cleanly to avoid nested null errors in JS
            if ($item->account) {
                $item->account_name = $item->account->name;
                
                // Fetch opening balance from Voucher system via OpeningBalanceService
                $balanceInfo = $openingBalanceService->getOpeningBalanceForAccountId($companyId, $financialYearId, $item->account_id);
                $item->account->opening_balance = $balanceInfo['opening_balance'];
                $item->account->opening_type = $balanceInfo['opening_type'];
            } else {
                $item->name = '';
            }

            return $item;
        });
       
        $permissions = userPermissions([
            'driver.view',
            'driver.update',
            'driver.delete',
        ], true);

        return response()->json([
            'data' => $rows,
            'last_page' => $lastPage,
            'permissions' => $permissions,
            'total' => $total,
        ]);
    }
}
