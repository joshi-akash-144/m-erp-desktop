<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class SalesOrderExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of AccountGroup models
     */
    protected \Illuminate\Support\Collection $salesOrders;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var int Total number of columns in the sheet
     */
    protected int $totalColumn = 0;

    /**
     * Constructor
     *
     * @param object $company
     * @param \Illuminate\Support\Collection $salesOrders
     * @param array $headings
     */
    public function __construct(object $company, \Illuminate\Support\Collection $salesOrders, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->salesOrders = $salesOrders;
        $this->headings = $headings;
        $this->datePeriod = $datePeriod;
    }

    /**
     * Return the view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        $rows = collect($this->salesOrders)->flatMap(function ($salesOrder) {
            return collect($salesOrder['details'])->map(function ($detail, $detailIndex) use ($salesOrder) {
                $isFirstDetail = ($detailIndex === 0);

                return [
                    $isFirstDetail ? ($salesOrder['purchase_order_number'] ?? $salesOrder['id']) : '',
                    $isFirstDetail ? Carbon::parse($salesOrder['order_date'])->format('d-m-Y') : '',
                    $isFirstDetail ? Carbon::parse($salesOrder['due_date'])->format('d-m-Y') : '',
                    $isFirstDetail ? ($salesOrder['account']['name'] ?? '') : '',
                    $isFirstDetail ? ($salesOrder['broker']['name'] ?? '') : '',
                    $detail['item']['name'] ?? '',
                    $detail['inclusive_rate'] ?? '',
                    $detail['rate'] ?? '',
                    $detail['ordered_qty'] ?? 0,
                    $detail['received_qty'] ?? 0,
                    ($detail['ordered_qty'] - ($detail['received_qty'] ?? 0)) ?? 0,
                ];
            });
        });        
        $datePeriod = $this->datePeriod;
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;    
            $startDate = Carbon::parse($fy->start_date)->format('d-m-Y');
            $endDate   = Carbon::parse($fy->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
        }

        return view('company.pages.sales-order.export', [
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Sales Order Report',
            'headings' => $this->headings,
            'rows' => $rows,
            'datePeriod' => $datePeriod,
        ]);
    }

    /**
     * Register Excel events for formatting
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $totalColumn = count($this->headings);
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 11);

                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ====== GSTIN Row (Row 2) ======
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // ====== Report Title Row (Row 3) ======
                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Date Period Row (Row 4) ======
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Dynamic Column Widths Based on Headings ======
                $widthMap = [
                    'Po.No' => 12,
                    'Date' => 12,
                    'Last Date' => 12,
                    'Customer Name' => 35,
                    'Broker Name' => 25,
                    'Product Name' => 30,
                    'Incl Rate' => 15,
                    'Rate' => 15,
                    'Order Qty' => 15,
                    'Rec Qty' => 15,
                    'Rem Qty' => 15,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 20;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                $firstDataRow = 6;
                $flatData = $this->salesOrders->flatMap(fn($so) => $so['details'] ?? []);
                $rowsCount = $flatData->count();
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Calculate totals from collection (Recommended) ======
                $totalOrderedQty = $flatData->sum('ordered_qty');
                $totalReceivedQty = $flatData->sum('received_qty');
                $totalBalance = $flatData->sum(fn($detail) => ($detail['ordered_qty'] ?? 0) - ($detail['received_qty'] ?? 0));

                // ====== Alignment for Numeric Columns (G to K) ======
                foreach (['G', 'H', 'I', 'J', 'K'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // --- Format Rate and Inclusive Rate Columns to 2 Decimal Places ---
                // Column G is 'Incl Rate' and Column H is 'Rate' and Column I is 'Order Qty' and Column J is 'Rec Qty' and Column K is 'Rem Qty'
                $rangeIRate = "G{$firstDataRow}:G{$lastDataRow}";
                $rangeJRate = "H{$firstDataRow}:H{$lastDataRow}";
                $rangeIOrderQty = "I{$firstDataRow}:I{$lastDataRow}";
                $rangeJRecQty = "J{$firstDataRow}:J{$lastDataRow}";
                $rangeKRemQty = "K{$firstDataRow}:K{$lastDataRow}";

                $sheet->getStyle($rangeIRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($rangeJRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($rangeIOrderQty)->getNumberFormat()->setFormatCode('0.000');
                $sheet->getStyle($rangeJRecQty)->getNumberFormat()->setFormatCode('0.000');
                $sheet->getStyle($rangeKRemQty)->getNumberFormat()->setFormatCode('0.000');

                // ====== SET TOTAL ROW ======
                $sheet->setCellValue("H{$totalRow}", "Total:");
                $sheet->getStyle("H{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("H{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Set calculated totals directly (I, J, K)
                $sheet->setCellValueExplicit("I{$totalRow}", $totalOrderedQty, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("J{$totalRow}", $totalReceivedQty, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("K{$totalRow}", $totalBalance, DataType::TYPE_NUMERIC);

                // Format total row
                $sheet->getStyle("I{$totalRow}:K{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.000'],
                ]);
            },
        ];
    }
}
