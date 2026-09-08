<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Models\Account;
use App\Models\ManualCheque;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Repositories\ManualChequeRepository;

class ManualChequeController extends Controller
{
    protected $repository;

    public function __construct(ManualChequeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->only([
                "search",
            ]);

            $page = (int) $request->input('page', 1);
            $size = (int) $request->input('size', 15);

            $result = $this->repository->getManualChequeList($filters, $page, $size);

            return response()->json([
                'data' => $result['data'],
                'last_page' => $result['last_page']
            ]);
        }

        return view('company.pages.manual-cheque.index');
    }

    public function create()
    {
        $chequeFormats = \App\Models\ChequeMaster::where('status', 1)

            ->select('id', 'formate_name')
            ->orderBy('formate_name')
            ->whereIn('id', [2, 4, 5])
            ->get();

        return view('company.pages.manual-cheque.create', compact('chequeFormats'));
    }

    public function store(Request $request)
    {
        // Date parsing if it comes as dd-mm-yyyy or similar
        $chequeDateStr = $request->cheque_date;
        if (preg_match('/^\d{2}[\/\-]\d{2}[\/\-]\d{4}$/', $chequeDateStr)) {
            $chequeDateStr = \Carbon\Carbon::createFromFormat('d-m-Y', str_replace('/', '-', $chequeDateStr))->format('Y-m-d');
        }

        $request->merge(['cheque_date' => $chequeDateStr]);

        $request->validate([
            'cheque_format_id' => 'required',
            'amount'     => 'required|numeric|min:0',
            'cheque_date' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $uuid = $request->input('uuid');
            $manualCheque = ManualCheque::where('uuid', $uuid)->first();

            $data = [
                'company_id'       => company_id(),
                'financial_year_id' => financial_year_id(),
                'amount'           => $request->amount,
                'cheque_date'      => $request->cheque_date,
                'cheque_no'        => $request->cheque_no,
                'narration'        => $request->narration,
                'second_narration' => $request->second_narration,
                'cheque_format_id' => $request->cheque_format_id,
                'account_payee'    => $request->payee_pay ? 1 : 0,
            ];

            if ($request->payee_type === 'account' && $request->account_id) {
                $account = Account::find($request->account_id);
                $data['account_id'] = $request->account_id;
                $data['name'] = $account ? $account->name : null;
            } else {
                $data['account_id'] = null;
                $data['name'] = $request->name;
            }

            if ($manualCheque) {
                $data['updated_by'] = current_user_id();
                $manualCheque->update($data);
            } else {
                $data['uuid'] = $uuid;
                $data['created_by'] = current_user_id();
                $manualCheque = ManualCheque::create($data);
            }

            // Build cheque format array
            $master = \App\Models\ChequeMaster::with('properties')->find($manualCheque->cheque_format_id);
            if (!$master) {
                throw new \Exception("Cheque format not found.");
            }

            $amountWords = amountInWords($manualCheque->amount ?? 0);

            $cheque_data = [
                'cheque_date' => \Carbon\Carbon::parse($manualCheque->cheque_date)->format('d-m-Y'),
                'cheque_name' => $manualCheque->name ?? '',
                'cheque_amount' => $manualCheque->amount ?? 0,
                'cheque_amount_in_word' => $amountWords ?? '',
                'id' => $manualCheque->account_id ?? 0,
                'ac_payee_flag' => $manualCheque->account_payee ? 'y' : 'n',
                'show_or_not' => 0,
                'cheque_height' => '600px',
                'cheque_width' => '100%',
                'left_margin' => $master->left_margin ?? '',
                'top_margin' => $master->top_margin ?? '',
            ];

            $properties = $master->properties ?? [];

            foreach ($properties as $property) {
                $attributes = collect($property->getAttributes())
                    ->except([
                        'id',
                        'cheque_master_id',
                        'column_value',
                        'created_at',
                        'updated_at'
                    ])
                    ->toArray();

                $styleAttributes = implode('; ', array_map(
                    fn($k, $v) =>
                    convertToSpaceSeparated($k) . ': ' .
                        (
                            (in_array($k, ['top', 'left', 'width', 'height']) && is_numeric($v))
                            ? ($v * 100) . 'px'
                            : ((in_array($k, ['font_size']))
                                ? $v . 'px'
                                : $v)
                        ),
                    array_keys($attributes),
                    $attributes
                ));

                $key = convertToSnakeCase($property->column_value);
                $cheque_data[$key] = $styleAttributes;
            }

            $html = view('company.pages.payment-voucher.cheque_print', compact('cheque_data'))->render();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Manual Cheque saved successfully.',
                'html'    => $html
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAccounts(Request $request)
    {
        $search = $request->input('q');

        $query = Account::where('is_active', 1)
            ->where('company_id', company_id())
            ->where('is_hidden', 0);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->selectRaw("id, name, party_type")
            ->orderBy('name', 'asc')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $accounts
        ]);
    }

    public function getPayeeTotal(Request $request)
    {
        $accountId = $request->input('account_id');
        // dd($accountId);
        $name = $request->input('name');
        $query = ManualCheque::where('company_id', company_id())
            ->where('financial_year_id', financial_year_id());

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else if ($name) {
            $query->where('name', $name);
        } else {
            return response()->json(['total' => 0]);
        }

        $total = $query->sum('amount');
        return response()->json(['total' => $total]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $manualCheque = ManualCheque::findOrFail($id);
            $manualCheque->deleted_by = current_user_id();
            $manualCheque->save();
            $manualCheque->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Manual Cheque deleted successfully.'
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function printCheque($id)
    {
        try {
            $manualCheque = ManualCheque::findOrFail($id);

            // Build cheque format array
            $master = \App\Models\ChequeMaster::with('properties')->find($manualCheque->cheque_format_id);
            if (!$master) {
                throw new \Exception("Cheque format not found.");
            }

            $amountWords = amountInWords($manualCheque->amount ?? 0);

            $cheque_data = [
                'cheque_date' => \Carbon\Carbon::parse($manualCheque->cheque_date)->format('d-m-Y'),
                'cheque_name' => $manualCheque->name ?? '',
                'cheque_amount' => $manualCheque->amount ?? 0,
                'cheque_amount_in_word' => $amountWords ?? '',
                'id' => $manualCheque->account_id ?? 0,
                'ac_payee_flag' => $manualCheque->account_payee ? 'y' : 'n',
                'show_or_not' => 0,
                'cheque_height' => '600px',
                'cheque_width' => '100%',
                'left_margin' => $master->left_margin ?? '',
                'top_margin' => $master->top_margin ?? '',
            ];

            $properties = $master->properties ?? [];

            foreach ($properties as $property) {
                $attributes = collect($property->getAttributes())
                    ->except([
                        'id',
                        'cheque_master_id',
                        'column_value',
                        'created_at',
                        'updated_at'
                    ])
                    ->toArray();

                $styleAttributes = implode('; ', array_map(
                    fn($k, $v) =>
                    convertToSpaceSeparated($k) . ': ' .
                        (
                            (in_array($k, ['top', 'left', 'width', 'height']) && is_numeric($v))
                            ? ($v * 100) . 'px'
                            : ((in_array($k, ['font_size']))
                                ? $v . 'px'
                                : $v)
                        ),
                    array_keys($attributes),
                    $attributes
                ));

                $key = convertToSnakeCase($property->column_value);
                $cheque_data[$key] = $styleAttributes;
            }

            $html = view('company.pages.payment-voucher.cheque_print', compact('cheque_data'))->render();

            return response()->json([
                'status'  => true,
                'html'    => $html
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
