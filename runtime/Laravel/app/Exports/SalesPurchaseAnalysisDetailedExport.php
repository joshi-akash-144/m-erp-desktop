<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesPurchaseAnalysisDetailedExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $company;
    protected $rows;
    protected $datePeriod;
    protected $poNumber;
    protected $parentRow;

    public function __construct($company, $rows, $datePeriod, $poNumber, $parentRow)
    {
        $this->company = $company;
        $this->rows = $rows;
        $this->datePeriod = $datePeriod;
        $this->poNumber = $poNumber;
        $this->parentRow = $parentRow;
    }

    public function view(): View
    {
        // Calculate totals dynamically from details if parent row amounts aren't enough,
        // but the modal uses the parent row explicitly.
        $totalSales = $this->parentRow ? floatval($this->parentRow->sales_amount) : 0;
        $totalPurchase = $this->parentRow ? floatval($this->parentRow->purchase_amount) : 0;
        $diffAmount = $this->parentRow ? floatval($this->parentRow->profit_loss_amount) : 0;

        $avgSalesRate = $this->parentRow ? floatval($this->parentRow->sales_rate) : 0;
        $avgPurchaseRate = $this->parentRow ? floatval($this->parentRow->purchase_rate) : 0;
        $diffRate = $avgSalesRate - $avgPurchaseRate;

        return view('company.pages.sales-purchase-analysis.export-detailed', [
            'company' => $this->company,
            'rows' => $this->rows,
            'datePeriod' => $this->datePeriod,
            'poNumber' => $this->poNumber,
            'parentRow' => $this->parentRow,
            'totalSales' => $totalSales,
            'totalPurchase' => $totalPurchase,
            'diffAmount' => $diffAmount,
            'avgSalesRate' => $avgSalesRate,
            'avgPurchaseRate' => $avgPurchaseRate,
            'diffRate' => $diffRate
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // General styling is mostly handled by HTML in FromView, but we can enforce some defaults
        return [
            // Ensure first 3 rows are bold
            1    => ['font' => ['bold' => true, 'size' => 14]],
            2    => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
