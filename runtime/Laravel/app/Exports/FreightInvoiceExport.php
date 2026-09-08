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

class FreightInvoiceExport implements FromView, WithEvents
{
    protected object $company;
    protected \Illuminate\Support\Collection $freightInvoices;
    protected array $headings;
    protected int $totalColumn = 0;
    protected ?string $datePeriod;

    public function __construct(object $company, \Illuminate\Support\Collection $freightInvoices, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->freightInvoices = $freightInvoices;
        $this->headings = $headings;
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $rows = collect($this->freightInvoices)->map(function ($freight) {
            if (isset($freight['is_total']) && $freight['is_total']) {
                return [
                    '',
                    '',
                    'Total',
                    '',
                    '',
                    $freight['quantity'] ?? '',
                    '',
                    $freight['total_amount'] ?? '',
                ];
            }

            return [
                $freight['invoice_serial']   ?? '',
                $freight['invoice_date']     ?? '',
                $freight['account_name']     ?? '',
                $freight['item_name']        ?? '',
                $freight['zone']             ?? '',
                $freight['quantity']         ?? '',
                $freight['rate']             ?? '',
                $freight['total_amount']     ?? '',
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
            'reportTitle' => 'Freight Invoice Register',
            'headings' => $this->headings,
            'rows' => $rows,
            'datePeriod' => $datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $totalColumn = count($this->headings);
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 8);

                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);
                    
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(15);
            

                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                ]);                
                $sheet->getRowDimension(5)->setRowHeight(18);

                $widthMap = [
                    'Bill No'          => 15,
                    'Date'             => 15,
                    'Customer'         => 35,
                    'Product'          => 25,
                    'Zone'             => 15,
                    'Qty.'             => 15,
                    'Rate'             => 15,
                    'Amount'           => 20,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 15;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                $firstDataRow = 6;
                $rowsCount    = count($this->freightInvoices);
                $lastDataRow  = $firstDataRow + $rowsCount - 1;
                $totalRow     = $lastDataRow + 1;

                foreach (['A', 'C', 'D', 'E'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $sheet->getStyle("B5:B{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                foreach (['F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $grandTotalQty = collect($this->freightInvoices)
                    ->filter(fn($f) => empty($f['is_total']))
                    ->sum(fn($f) => (float) str_replace(',', '', $f['quantity'] ?? 0));
                    
                $grandTotalAmount = collect($this->freightInvoices)
                    ->filter(fn($f) => empty($f['is_total']))
                    ->sum(fn($f) => (float) str_replace(',', '', $f['total_amount'] ?? 0));

                $sheet->setCellValue("E{$totalRow}", "Grand Total:");
                $sheet->getStyle("E{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $sheet->setCellValueExplicit("F{$totalRow}", $grandTotalQty, DataType::TYPE_NUMERIC);
                $sheet->getStyle("F{$totalRow}")->applyFromArray([
                    'font'         => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.00'],
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                $sheet->setCellValueExplicit("H{$totalRow}", $grandTotalAmount, DataType::TYPE_NUMERIC);
                $sheet->getStyle("H{$totalRow}")->applyFromArray([
                    'font'         => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.00'],
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
            },
        ];
    }
}
