<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VehicleExpenditureExport implements FromView, WithEvents
{
    protected object $company;
    protected array $reportData;
    protected array $columns;
    protected ?string $datePeriod;

    public function __construct(object $company, array $columns, array $reportData, ?string $datePeriod = null)
    {
        $this->company    = $company;
        $this->columns    = $columns;
        $this->reportData = $reportData;
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        return view('company.pages.driver-expense.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Vehicle Expenditure Report',
            'datePeriod'  => $this->datePeriod,
            'columns'     => $this->columns,
            'reportData'  => $this->reportData,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalColumns = count($this->columns);
                $lastColLetter = Coordinate::stringFromColumnIndex($totalColumns);

                // ====== Title Formatting ======
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                $sheet->mergeCells("A2:{$lastColLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                $sheet->mergeCells("A3:{$lastColLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(20);

                if ($this->datePeriod) {
                    $sheet->mergeCells("A4:{$lastColLetter}4");
                    $sheet->getStyle('A4')->applyFromArray([
                        'font' => ['italic' => true],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getRowDimension(4)->setRowHeight(18);
                }

                // ====== Headings (Row 5) ======
                $sheet->getStyle("A5:{$lastColLetter}5")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);
                
                // Set Column Widths (Vehicle name A -> wider, others -> number width)
                $sheet->getColumnDimension('A')->setWidth(25);
                for ($col = 2; $col <= $totalColumns; $col++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setWidth(15);
                }

                $firstDataRow = 6;
                $rowsCount = count($this->reportData);
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $lastDataRow + 1;

                if ($rowsCount > 0) {
                    $amountRange = "B{$firstDataRow}:{$lastColLetter}{$lastDataRow}";
                    $sheet->getStyle($amountRange)->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle($amountRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                
                // Formatting Totals Row
                $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                
                $totalsRange = "B{$totalRow}:{$lastColLetter}{$totalRow}";
                $sheet->getStyle($totalsRange)->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle($totalsRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            },
        ];
    }
}
