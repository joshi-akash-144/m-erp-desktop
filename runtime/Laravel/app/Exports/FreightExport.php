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

class FreightExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of AccountGroup models
     */
    protected \Illuminate\Support\Collection $freight;

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
     * @param \Illuminate\Support\Collection $freight
     * @param array $headings
     */
    public function __construct(object $company, \Illuminate\Support\Collection $freight, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->freight = $freight;
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
        // Map the flat array structure returned by FreightService::freightList()       
        $rows = collect($this->freight)->map(function ($freight) {
            return [
                $freight['account_name']     ?? '',
                $freight['bill_number']      ?? '',
                $freight['invoice_date']     ?? '',
                $freight['lr_number']        ?? '',
                $freight['vehicle_number']   ?? '',
                $freight['item_name']        ?? '',
                $freight['consignor']        ?? '',
                $freight['consignee']        ?? '',
                $freight['from_destination'] ?? '',
                $freight['to_destination']   ?? '',
                $freight['bag_count']        ?? '',
                $freight['net_weight']       ?? '',
                $freight['kms']              ?? '',
                $freight['rate']             ?? '',
                $freight['freight_amount']   ?? '',
            ];
        });

       $datePeriod = $this->datePeriod;
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;    
            $startDate = Carbon::parse($fy->start_date)->format('d-m-Y');
            $endDate   = Carbon::parse($fy->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
        }
                
        return view('company.pages.freight.export', [
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Freight Report',
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
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 15);

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
                $sheet->getStyle("A1:{$lastColumnLetter}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
                    
                 // ====== Date Period (Row 4) ======
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(15);
            

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],                    
                ]);                
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Dynamic Column Widths Based on Freight Headings (15 columns) ======
                $widthMap = [
                    'Bill To'          => 30,
                    'Bill No'          => 15,
                    'Invoice Date'     => 15,
                    'Lr No.'           => 15,
                    'Vehicle No.'      => 18,
                    'Item'             => 25,
                    'Consignor'        => 25,
                    'Consignee'        => 25,
                    'From Destination' => 20,
                    'To Destination'   => 20,
                    'Bag Count'        => 12,
                    'Net Weight'       => 14,
                    'KMS'              => 12,
                    'Freight Rate'     => 15,
                    'Freight'          => 15,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 15;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                // ====== Calculate rows ======
                $firstDataRow = 6;
                $rowsCount    = count($this->freight); // flat — one row per freight record
                $lastDataRow  = $firstDataRow + $rowsCount - 1;
                $totalRow     = $firstDataRow + $rowsCount;

                // ====== String columns — LEFT align (A B D E F G H I J) ======
                foreach (['A', 'B', 'D', 'E', 'F', 'G', 'H', 'I', 'J'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                // ====== Date column — CENTER align (C) ======
                $sheet->getStyle("C5:C{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ====== Numeric columns — RIGHT align (K L M N O) ======
                foreach (['K', 'L', 'M', 'N', 'O'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // ====== Number format 2 decimals for weight/rate/freight columns ======
                foreach (['L', 'M', 'N', 'O'] as $col) {
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                }

                // ====== Calculate total freight amount ======
                $totalAmount = $this->freight->sum('freight_amount');

                // ====== Total row — label in column N, value in column O ======
                $sheet->setCellValue("N{$totalRow}", "Total:");
                $sheet->getStyle("N{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("N{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValueExplicit("O{$totalRow}", $totalAmount, DataType::TYPE_NUMERIC);
                $sheet->getStyle("O{$totalRow}")->applyFromArray([
                    'font'         => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.00'],
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
            },
        ];
    }
}
