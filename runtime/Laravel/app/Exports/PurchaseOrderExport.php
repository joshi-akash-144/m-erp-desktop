<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class PurchaseOrderExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of AccountGroup models
     */
    protected \Illuminate\Support\Collection $purchaseOrders;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var int Total number of columns in the sheet
     */
    protected int $totalColumn = 0;

    /**
     * @var string|null Date period string
     */
    protected ?string $datePeriod;

    /**
     * Constructor
     *
     * @param object $company
     * @param \Illuminate\Support\Collection $purchaseOrders
     * @param array $headings
     * @param string|null $datePeriod
     */
    public function __construct(object $company, \Illuminate\Support\Collection $purchaseOrders, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->purchaseOrders = $purchaseOrders;
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
        $rows = collect($this->purchaseOrders)->flatMap(function ($purchaseOrder) {
            return collect($purchaseOrder['details'])->map(function ($detail, $detailIndex) use ($purchaseOrder) {
                $isFirstDetail = ($detailIndex === 0);
                
                return [
                    $isFirstDetail ? ($purchaseOrder['order_serial'] ?? $purchaseOrder['id']) : '',
                    $isFirstDetail && !empty($purchaseOrder['order_date']) ? Carbon::parse($purchaseOrder['order_date'])->format('d-m-Y') : '',
                    $isFirstDetail && !empty($purchaseOrder['due_date']) ? Carbon::parse($purchaseOrder['due_date'])->format('d-m-Y') : '',
                    $isFirstDetail ? ($purchaseOrder['account']['name'] ?? '') : '',
                    $isFirstDetail ? ($purchaseOrder['broker']['name'] ?? '') : '',
                    $isFirstDetail ? ($purchaseOrder['contract_number'] ?? '') : '',
                    $detail['item']['name'] ?? '',
                    $isFirstDetail ? ($purchaseOrder['destination']['name'] ?? '') : '',
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

        return view('company.pages.purchase-order.export', [
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Purchase Order Report',
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
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 13);

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

                 // ====== Date Period Title Row (Row 4) ======
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
                    'Po.No.' => 10,
                    'Date' => 18,
                    'Supplier Name' => 40,
                    'Broker Name' => 40,
                    'Destination' => 40,
                    'Last Date' => 18,
                    'Contract No' => 15,
                    'Product Name' => 35,
                    'Incl Rate' => 15,
                    'Rate' => 15,
                    'Order.Qty' => 15,
                    'Rec.Qty' => 15,
                    'Rem.Qty' => 15,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 20;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                // ====== Calculate rows ======
                $firstDataRow = 6;
                $rowsCount = count($this->purchaseOrders->flatMap(fn($po) => $po['details']));
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Calculate totals from collection (Recommended) ======
                $flatData = $this->purchaseOrders->flatMap(fn($po) => $po['details']);
                $totalOrderedQty = $flatData->sum('ordered_qty');
                $totalReceivedQty = $flatData->sum('received_qty');
                $totalBalance = $flatData->sum(fn($detail) => ($detail['ordered_qty'] ?? 0) - ($detail['received_qty'] ?? 0));

                // ====== Alignment for Numeric Columns ======
                foreach (['I', 'J', 'K', 'L', 'M'] as $col) {
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // --- ADDED CODE: Format Rate and Inclusive Rate Columns to 2 Decimal Places ---

                // Column I is 'Incl_Rate' and Column J is 'Rate'
                // Apply the '0.00' number format to all data cells in these columns and 000 for qty columns
                $rangeIRate = "I{$firstDataRow}:I{$lastDataRow}";
                $rangeJRate = "J{$firstDataRow}:J{$lastDataRow}";
                $rangeIOrderQty = "K{$firstDataRow}:K{$lastDataRow}";
                $rangeJRecQty = "L{$firstDataRow}:L{$lastDataRow}";
                $rangeKRemQty = "M{$firstDataRow}:M{$lastDataRow}";

                $sheet->getStyle($rangeIRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($rangeJRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($rangeIOrderQty)->getNumberFormat()->setFormatCode('0.000');
                $sheet->getStyle($rangeJRecQty)->getNumberFormat()->setFormatCode('0.000');
                $sheet->getStyle($rangeKRemQty)->getNumberFormat()->setFormatCode('0.000');

                // ====== SET TOTAL ROW ======
                $sheet->setCellValue("J{$totalRow}", "Total:");
                $sheet->getStyle("J{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("J{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Set calculated totals directly
                $sheet->setCellValueExplicit("K{$totalRow}", $totalOrderedQty, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("L{$totalRow}", $totalReceivedQty, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("M{$totalRow}", $totalBalance, DataType::TYPE_NUMERIC);

                // Format total row
                $sheet->getStyle("K{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.000'],
                ]);
               
            },
        ];
    }
}
