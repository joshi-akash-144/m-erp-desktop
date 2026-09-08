<?php

namespace App\Http\Controllers;

use App\Models\Reference;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Throwable;
use App\Services\MasterDataService;

class PaymentHoldController extends Controller
{
    protected MasterDataService $masterDataService;

    public function __construct(MasterDataService $masterDataService)
    {
        $this->middleware('permission:payment_hold.list')->only(['index']);
        $this->middleware('permission:payment_hold.update')->only('hold');

        $this->masterDataService = $masterDataService;
    }

    public function index(Request $request) {
        if ($request->ajax()) {
            try {
                $query = Reference::with(['account'])
                    ->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id())
                    ->where('is_closed', 0)
                    ->where('is_hold', 1);

                if ($request->account_id) {
                    $query->where('account_id', $request->account_id);
                }

                $records = $query->get();

                $mappedData = $records->map(function ($record) {
                    return [
                        'id'       => $record->id,
                        'ref_no'   => $record->reference_number ?? '',
                        'account'  => [
                            'name' => $record->account->name ?? '',
                            'city' => $record->account->city ?? '',
                        ],
                        'amount'   => floatval($record->pending_amount),
                        'bill_date' => $record->reference_date ?? '',
                    ];
                });

                return response()->json([
                    'last_page' => 1,
                    'data'      => $mappedData,
                    'total'     => $records->count(),
                ]);

            } catch (Throwable $e) {
                report($e);
                return response()->json([
                    'message' => 'Something went wrong',
                    'errors'  => $e->getMessage(),
                ], 500);
            }
        }
        $accounts = $this->masterDataService->get('accounts', company_id());
        return view("company.pages.payment-hold.index", compact('accounts'));
    }

    public function hold(Request $request) {
        try {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                Reference::whereIn('id', $ids)
                    ->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id())
                    ->update(['is_hold' => 0]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Hold successfully!',
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Something went wrong',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }
}
