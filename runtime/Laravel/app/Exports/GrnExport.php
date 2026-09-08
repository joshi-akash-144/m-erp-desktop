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

class GrnExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of AccountGroup models
     */
    protected \Illuminate\Support\Collection $grn;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var int Total number of columns in the sheet
     */
    protected int $totalColumn = 0;

    /**
     * @var string|null Date period for the report
     */
    protected ?string $datePeriod;

    /**
     * Constructor
     *
     * @param object $company
     * @param \Illuminate\Support\Collection $grn
     * @param array $headings
     * @param string|null $datePeriod
     */
    public function __construct(object $company, \Illuminate\Support\Collection $grn, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->grn = $grn;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod = $datePeriod;
    }

    /**
     * Return the view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        $rows = collect($this->grn)->flatMap(function ($grn) {
            return collect($grn['details'])->map(function ($detail, $detailIndex) use ($grn) {
                $isFirstDetail = ($detailIndex === 0);

                return [
                    $isFirstDetail ? ($grn['grn_number'] ?? '') : '',
                    $isFirstDetail ? format_date($grn['grn_in_date']) : '',
                    $isFirstDetail ? format_date($grn['grn_out_date']) : '',
                    $isFirstDetail ? ($grn['reference_number'] ?? '') : '', // Bill no
                    $isFirstDetail ? ($detail['purchase_order_serial'] ?? '') : '', // Purchase order no
                    $isFirstDetail ? ($grn['account']['name'] ?? '') : '',
                    $isFirstDetail ? ($grn['broker']['name'] ?? '') : '',
                    // $grn['contract_number'],
                    $detail['item']['name'] ?? '',
                    $detail['destination']['name'] ?? '',
                    $detail['condition']['name'] ?? '',
                    $detail['party_quantity'] ?? '',
                    $detail['quantity'] ?? 0,
                    $detail['inclusive_rate'] ?? '',
                    $detail['rate'] ?? '',
                ];
            });
        });
    
        $datePeriod = $this->datePeriod;
        
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;    
            $startDate = format_date($fy->start_date);
            $endDate   = format_date($fy->end_date);
            $datePeriod = "$startDate to $endDate";
        }
            
        return view('company.pages.grn.export', [
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Grn Report',
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
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 14);
                
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
                    'Grn.No.' => 18 ,
                    'Grn In Date' => 18,
                    'Grn Out Date' => 18,
                    'Bill No.' => 15,
                    'P.O.No.' => 10,
                    'Supplier' => 40,
                    'Broker' => 20,
                    'Item' => 20,
                    'Destination' => 35,
                    'Condition' => 25,
                    'P.Qty.' => 15,
                    'Quantity' => 15,
                    'Incl Rate' => 15,
                    'Rate' => 15,                
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 20;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                // ====== Calculate rows ======
                $firstDataRow = 6;
                $rowsCount = count($this->grn->flatMap(fn($po) => $po['details']));
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Calculate totals from collection (Recommended) ======
                $flatData = $this->grn->flatMap(fn($po) => $po['details']);
                $totalQty = $flatData->sum('quantity');
                $totalPartyQty = $flatData->sum('party_quantity');
                // $totalBalance = $flatData->sum(fn($detail) => ($detail['ordered_qty'] ?? 0) - ($detail['received_qty'] ?? 0));

                // ====== Alignment for Numeric Columns ======
                foreach (['D', 'E', 'K', 'L', 'M','N'] as $col) {
                    $sheet->getStyle("{$col}4:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Column M is 'Incl Rate' and Column N is 'Rate'
                // Apply the '0.00' number format to all data cells in these columns
                $rangeIRate = "M{$firstDataRow}:M{$lastDataRow}";
                $rangeJRate = "N{$firstDataRow}:N{$lastDataRow}";

                $sheet->getStyle($rangeIRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($rangeJRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // ====== SET TOTAL ROW ======
                $sheet->setCellValue("J{$totalRow}", "Total:");
                $sheet->getStyle("J{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("J{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Set calculated totals directly
                $sheet->setCellValueExplicit("K{$totalRow}", $totalPartyQty, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("L{$totalRow}", $totalQty, DataType::TYPE_NUMERIC);
                // $sheet->setCellValueExplicit("M{$totalRow}", $totalBalance, DataType::TYPE_NUMERIC);

                // Format total row
                $sheet->getStyle("K{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.00'],
                ]);
            },
        ];
    }
}
