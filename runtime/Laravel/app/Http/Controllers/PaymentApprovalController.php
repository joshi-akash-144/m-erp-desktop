<?php

namespace App\Http\Controllers;

use App\Models\PaymentVoucher;
use App\Models\Voucher;
use App\Services\PaymentVoucherService;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Throwable;

class PaymentApprovalController extends Controller
{
    protected PaymentVoucherService $service;

    public function __construct(PaymentVoucherService $service)
    {
        $this->middleware('permission:payment_approval.list')->only(['index']);
        $this->middleware('permission:payment_approval.update')->only('approve');
        $this->middleware('permission:payment_approval.delete')->only('destroy');

        $this->service = $service;
    }

    public function index(Request $request) {
        if ($request->ajax()) {
            try {
                $query = PaymentVoucher::with(['account'])
                    ->whereHas('voucher')
                    ->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id())
                    ->where('is_approved', 0);

                if (!empty($request->payment_date)) {
                    $date = date('Y-m-d', strtotime($request->payment_date));
                    $query->whereHas('voucher', function($v) use ($date) {
                        $v->whereDate('voucher_date', $date);
                    });
                }

                if (!empty($request->file_no)) {
                    $query->where('file_number', 'like', '%' . $request->file_no . '%');
                }

                $records = $query->get();

                // Step 1: collect voucher IDs from PaymentVoucher records
                $voucherIds = $records->pluck('voucher_id')->filter()->values()->toArray();

                // Step 2: fetch Voucher models (with creator) keyed by id
                $vouchers = Voucher::with(['creator'])
                    ->whereIn('id', $voucherIds)
                    ->get()
                    ->keyBy('id');

                // Step 3: map PaymentVoucher items using the voucher lookup
                $mappedData = $records->map(function($record) use ($vouchers) {
                    $voucher = $vouchers->get($record->voucher_id);
                    return [
                        'id'            => $record->id,
                        'ref_no'        => $voucher->voucher_serial ?? '',
                        'file_no'       => $record->file_number ?? '',
                        'account'       => [
                            'name' => ($record->account)->name ?? '',
                            'city' => ($record->account)->city ?? ''
                        ],
                        'amount'        => $record->paid_amount,
                        'payment_date'  => $voucher?->voucher_date ?? '',
                        'created_by'    => $voucher?->creator?->name ?? '',
                        'narration'     => $voucher?->narration ?? '',
                    ];
                });
            
                    // system permissions linked to current operational
                $permissions = userPermissions([
                    'payment_approval.view',
                    'payment_approval.update',
                    'payment_approval.delete',
                ], true);

                return response()->json([
                    'last_page' => 1,
                    'data'      => $mappedData,
                    'total'     => $records->count(),
                    'permissions'=> $permissions,
                ]);

            } catch (Throwable $e) {
                report($e);
                return response()->json([
                    'message' => 'Something went wrong',
                    'errors'  => $e->getMessage(),
                ], 500);
            }
        }

        return view("company.pages.payment-approve.index");
    }

    public function approve(Request $request) {
        try {
            $ids = $request->input('ids', []);
            if (!empty($ids)) {
                $paymentVouchers = PaymentVoucher::with('voucher.details.account')
                    ->whereIn('id', $ids)
                    ->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id())
                    ->get();

                foreach ($paymentVouchers as $pv) {
                    if ($pv->is_approved) continue; // Skip already approved

                    $voucher = $pv->voucher;
                    if (!$voucher) continue;


                }

                PaymentVoucher::whereIn('id', $ids)
                    ->where('company_id', company_id())
                    ->where('financial_year_id', financial_year_id())
                    ->update([
                        'is_approved' => 1,
                        'updated_at'  => now(),
                        'approved_by' => current_user_id(),
                        'approved_at' => now()
                    ]);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Approved successfully!',
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Something went wrong',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $paymentVoucher = PaymentVoucher::where('company_id', company_id())
                ->where('financial_year_id', financial_year_id())
                ->findOrFail($id);

            $voucherId = $paymentVoucher->voucher_id;

            if ($this->service->isVoucherLocked($voucherId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This voucher cannot be deleted because one or more of its references have already been settled by another payment or receipt.',
                ], 422);
            }

            $this->service->deleteVoucher([$voucherId]);

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully.',
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment.',
                'errors'  => $e->getMessage(),
            ], 500);
        }
    }

}
