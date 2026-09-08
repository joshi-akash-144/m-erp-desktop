<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleExpense;
use App\Models\VehicleIncome;

class VehicleExpenditureService
{
    /*------------------------------------------------------------------
    | MAIN REPORT METHOD
    | Returns:
    |   columns     => [ { field, title, … }, … ]   (for Tabulator)
    |   reportRows  => [ { vehicle_name, income, exp_col1, …, profit_loss }, … ]
    ------------------------------------------------------------------*/
    public function getReport(int $companyId, int $financialYearId, array $filters = []): array
    {
        $fromDate  = $filters['from_date']  ?? null;
        $toDate    = $filters['to_date']    ?? null;
        $vehicleId = $filters['vehicle_id'] ?? null;

        // ── 1. All vehicles for this company ──────────────────────────
        $vehicleQuery = Vehicle::where('company_id', $companyId)->orderBy('name');
        if ($vehicleId) {
            $vehicleQuery->where('id', $vehicleId);
        }
        $vehicles = $vehicleQuery->get(['id', 'name'])->keyBy('id');

        // ── 2. Income per vehicle (SUM from vehicle_incomes) ──────────
        $incomeQuery = VehicleIncome::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->selectRaw('vehicle_id, SUM(amount) as total_income');

        if ($fromDate)  $incomeQuery->whereDate('voucher_date', '>=', $fromDate);
        if ($toDate)    $incomeQuery->whereDate('voucher_date', '<=', $toDate);
        if ($vehicleId) $incomeQuery->where('vehicle_id', $vehicleId);

        $incomeByVehicle = $incomeQuery
            ->groupBy('vehicle_id')
            ->pluck('total_income', 'vehicle_id');

        // ── 3. Expense totals per vehicle per expense account ─────────
        $expQuery = VehicleExpense::query()
            ->join('accounts as a', 'a.id', '=', 'vehicle_expenses.expense_account_id')
            ->where('vehicle_expenses.company_id', $companyId)
            ->where('vehicle_expenses.financial_year_id', $financialYearId)
            ->selectRaw('
                vehicle_expenses.vehicle_id,
                vehicle_expenses.expense_account_id,
                a.name  as account_name,
                SUM(vehicle_expenses.amount) as total_exp
            ');

        if ($fromDate)  $expQuery->whereDate('vehicle_expenses.voucher_date', '>=', $fromDate);
        if ($toDate)    $expQuery->whereDate('vehicle_expenses.voucher_date', '<=', $toDate);
        if ($vehicleId) $expQuery->where('vehicle_expenses.vehicle_id', $vehicleId);

        $expRows = $expQuery
            ->groupBy('vehicle_expenses.vehicle_id', 'vehicle_expenses.expense_account_id', 'a.name')
            ->orderBy('a.name')
            ->get();

        // ── 4. Collect all unique expense account columns ─────────────
        $expAccounts = [];
        foreach ($expRows as $row) {
            $expAccounts[$row->expense_account_id] = $row->account_name;
        }
        asort($expAccounts);

        // ── 5. Index expense data by [vehicle_id][account_id] ─────────
        $expMatrix = [];
        foreach ($expRows as $row) {
            $expMatrix[$row->vehicle_id][$row->expense_account_id] = (float) $row->total_exp;
        }

        // ── 6. Build rows ─────────────────────────────────────────────
        $reportRows = [];
        foreach ($vehicles as $vid => $vehicle) {
            $income = (float) ($incomeByVehicle[$vid] ?? 0);

            $row = [
                'vehicle_name' => $vehicle->name,
                'income'       => $income ? number_format($income, 2, '.', '') : '',
            ];

            $totalExp = 0;
            foreach ($expAccounts as $accId => $accName) {
                $amt      = (float) ($expMatrix[$vid][$accId] ?? 0);
                $row['exp_' . $accId] = $amt ? number_format($amt, 2, '.', '') : '';
                $totalExp += $amt;
            }

            $profitLoss        = $income - $totalExp;
            $row['total_expense'] = $totalExp ? number_format($totalExp, 2, '.', '') : '';
            $row['profit_loss']   = number_format($profitLoss, 2, '.', '');

            $reportRows[] = $row;
        }

        // ── 7. Build Tabulator column definitions ─────────────────────
        $columns = [
            [
                'field'      => 'vehicle_name',
                'title'      => 'Vehicle',
                'frozen'     => true,
                'minWidth'   => 130,
                'headerSort' => true,
            ],
            [
                'field'      => 'income',
                'title'      => 'Income',
                'hozAlign'   => 'right',
                'minWidth'   => 110,
                'headerSort' => false,
            ],
        ];

        $viewType = $filters['view_type'] ?? 'summary';

        if ($viewType === 'detail') {
            foreach ($expAccounts as $accId => $accName) {
                $columns[] = [
                    'field'      => 'exp_' . $accId,
                    'title'      => $accName,
                    'hozAlign'   => 'right',
                    'minWidth'   => 100,
                    'headerSort' => false,
                ];
            }
        }

        $columns[] = [
            'field'      => 'total_expense',
            'title'      => 'Total Exp.',
            'hozAlign'   => 'right',
            'minWidth'   => 110,
            'headerSort' => false,
        ];
        $columns[] = [
            'field'      => 'profit_loss',
            'title'      => 'Profit / Loss',
            'hozAlign'   => 'right',
            'minWidth'   => 120,
            'headerSort' => false,
        ];

        return compact('columns', 'reportRows');
    }
}
