<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ChequeMaster;
use App\Helpers\AjaxResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class ChequeController extends Controller
{

    public function __construct()
    {
        // $this->middleware('permission:cheque.create')->only(['create', 'store']);
        // $this->middleware('permission:cheque.update')->only(['edit', 'update']);
        // $this->middleware('permission:cheque.delete')->only('destroy');

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if($request->ajax()) {
            $page = $request->input('page', 1);
            $size = $request->input('size', 50);
            $searchValue = $request->input('search');

            $query = ChequeMaster::where(function($q) {
                $q->where('company_id', company_id())
                  ->orWhere('company_id', 0)
                  ->orWhereNull('company_id');
            }); 

            // Searching
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('formate_name', 'LIKE', "%{$searchValue}%")
                      ->orWhere('status', 'LIKE', "%{$searchValue}%");
                });
            }

            $paginator = $query->orderBy('id', 'asc')->paginate($size, ['*'], 'page', $page);

            // Prepare data for Tabulator
            $formattedData = collect($paginator->items())->map(function ($item, $index) use ($paginator) {
                return [
                    'no' => ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1,
                    'id' => $item->id,
                    'formate_name' => $item->formate_name,
                    'is_default' => $item->is_default,
                    'status' => $item->status,
                    'company_id' => $item->company_id,
                ];
            });
            return response()->json([
                'current_page' => $paginator->currentPage(),
                'data' => $formattedData,
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'permissions' => [
                    'update' => true, // Replace with actual permission checks if needed
                    'delete' => true,
                ]
            ]);
        }
        
        return view('company.pages.setup.cheque.index');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cheque = new ChequeMaster(); // Empty model so blade can safely reference $cheque->properties ?? []
        $formMode = 'create';
        return view('company.pages.setup.cheque.create', compact('cheque', 'formMode'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $formData = $request->formData;
                $chequeProperties = $request->chequeProperties;

                // Create Master using Eloquent
                $uuid = $formData['unique_token'] ?? $request->uuid;

                // Check if this format already exists to preserve its code, otherwise generate a new one
                $existingMaster = ChequeMaster::where('uuid', $uuid)->first();
                $code = $existingMaster ? $existingMaster->code : ChequeMaster::nextCode();

                $master = ChequeMaster::updateOrCreate(
                    ['uuid' => $uuid],
                    [   
                        'code'          => $code,
                        'formate_name'   => $formData['formate_name'],
                        'top_margin'    => $formData['top_margin'] ?? 0,
                        'left_margin'   => $formData['left_margin'] ?? 0,
                        'cheque_height' => $formData['cheque_height'] ?? 300,
                        'cheque_width'  => $formData['cheque_width'] ?? 1000,
                        'is_default'    => $formData['is_default'] ?? 0,
                        'status'        => true,
                        'company_id'    => company_id(),
                        'created_by'    => auth()->id(),
                        'updated_by'    => auth()->id(),
                    ]
                );

                // Sync properties
                if (!empty($chequeProperties)) {
                    $this->syncProperties($master, $chequeProperties);
                }

                return AjaxResponse::success(
                    message: 'Cheque format saved successfully!',
                    data: $master
                );
            });
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: 'Failed to save cheque format.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChequeMaster $cheque)
    {
        $cheque->load('properties');
        $formMode = 'edit';
        return view('company.pages.setup.cheque.edit', compact('cheque', 'formMode'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChequeMaster $cheque)
    {
        try {
            return DB::transaction(function () use ($request, $cheque) {
                $formData = $request->formData;
                $chequeProperties = $request->chequeProperties;

                // Update Master
                $cheque->update([
                    'formate_name'   => $formData['formate_name'],
                    'top_margin'    => $formData['top_margin'] ?? 0,
                    'left_margin'   => $formData['left_margin'] ?? 0,
                    'cheque_height' => $formData['cheque_height'] ?? 300,
                    'cheque_width'  => $formData['cheque_width'] ?? 1000,
                    'is_default'    => $formData['is_default'] ?? 0,
                    'status'        => true,
                    'updated_by'    => auth()->id(),
                ]);

                // Sync properties
                if (!empty($chequeProperties)) {
                    $this->syncProperties($cheque, $chequeProperties);
                } else {
                    $cheque->properties()->delete();
                }

                return AjaxResponse::success(
                    message: 'Cheque format updated successfully!',
                    data: $cheque
                );
            });
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error(
                message: 'Failed to update cheque format.',
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChequeMaster $cheque)
    {
        try {
            if ($cheque->is_default) {
                return AjaxResponse::error('Default cheque format cannot be deleted.', 400);
            }

            // Check if this format is assigned to any bank accounts
            $isUsedInBank = Account::where('cheque_master_id', $cheque->id)->exists();
            if ($isUsedInBank) {
                return AjaxResponse::error('This format is assigned to one or more bank accounts and cannot be deleted.', 400);
            }

            $cheque->delete();
            return AjaxResponse::success('Cheque format deleted successfully!');
        } catch (Throwable $e) {
            report($e);
            return AjaxResponse::error('Failed to delete cheque format.', 500);
        }
    }

    /**
     * Sync properties for a cheque format.
     */
    private function syncProperties(ChequeMaster $master, array $chequeProperties)
    {
        $submittedIds = [];
        $valueMap = [
            'Date'            => 'date',
            'Account Name'    => 'account_name',
            'Amount'          => 'amount',
            'Amount In Words' => 'amount_in_words',
            'A/C Payee'       => 'ac_payee',
        ];

        foreach ($chequeProperties as $prop) {
            $columnValue = $prop['value'];
            if (isset($valueMap[$columnValue])) {
                $columnValue = $valueMap[$columnValue];
            }

            $propertyData = [
                'column_value' => $columnValue,
                'top'          => $prop['top'],
                'left'         => $prop['left'],
                'width'        => $prop['width'],
                'height'       => $prop['height'],
                'align_text'   => $prop['align_text'] ?? 'left',
                'font_name'    => $prop['font_name'] ?? 'Arial',
                'font_style'   => $prop['font_style'] ?? 'normal',
                'font_size'    => $prop['font_size'] ?? '12',
            ];

            if (isset($prop['id']) && is_numeric($prop['id'])) {
                $master->properties()->where('id', $prop['id'])->update($propertyData);
                $submittedIds[] = $prop['id'];
            } else {
                $newProperty = $master->properties()->create($propertyData);
                $submittedIds[] = $newProperty->id;
            }
        }

        $master->properties()->whereNotIn('id', $submittedIds)->delete();
    }
}
