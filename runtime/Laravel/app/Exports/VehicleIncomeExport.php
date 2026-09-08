<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VehicleIncomeExport implements FromView, WithEvents
{
    protected object $company;
    protected array $reportData;
    protected array $columns;
    protected ?string $datePeriod;
    protected ?string $reportType;

    public function __construct(object $company, array $columns, array $reportData, ?string $datePeriod = null, ?string $reportType = null)
    {
        $this->company    = $company;
        $this->columns    = $columns;
        $this->reportData = $reportData;
        $this->datePeriod = $datePeriod;
        $this->reportType = $reportType;
    }

    public function view(): View
    {
        return view('company.pages.vehicle-income.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Vehicle Income Report',
            'datePeriod'  => $this->datePeriod,
            'reportType'  => $this->reportType,
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

                // Set Default Font Family for entire sheet
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial');

                // ====== Title Formatting ======
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 16],
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
                    'font' => ['name' => 'Arial', 'bold' => true, 'size' => 12],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(20);

                if ($this->datePeriod || $this->reportType) {
                    $sheet->mergeCells("A4:D4");
                    $sheet->getStyle('A4:D4')->applyFromArray([
                        'font' => ['name' => 'Arial', 'bold' => true, 'size' => 11],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    
                    $sheet->mergeCells("E4:{$lastColLetter}4");
                    $sheet->getStyle("E4:{$lastColLetter}4")->applyFromArray([
                        'font' => ['name' => 'Arial', 'bold' => true, 'size' => 11],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_RIGHT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getRowDimension(4)->setRowHeight(18);
                }

                // ====== Headings (Row 5) ======
                $sheet->getStyle("A5:G5")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);
                
                // Set Column Widths (Adjust as needed for Vehicle Income)
                $sheet->getColumnDimension('A')->setWidth(15); // Voucher No
                $sheet->getColumnDimension('B')->setWidth(12); // Date
                $sheet->getColumnDimension('C')->setWidth(25); // Party Name
                $sheet->getColumnDimension('D')->setWidth(15); // Ref No
                $sheet->getColumnDimension('E')->setWidth(15); // Vehicle Number
                $sheet->getColumnDimension('F')->setWidth(15); // Type
                $sheet->getColumnDimension('G')->setWidth(15); // Amount

                $firstDataRow = 6;
                $rowsCount = count($this->reportData);
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $lastDataRow + 1;
                $alignRangeEnd = $rowsCount > 0 ? $lastDataRow : 5;

                // 1. Right Align: Voucher Number (A) and Amount (G) for both Header and Data
                $sheet->getStyle("A5:A{$alignRangeEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("G5:G{$alignRangeEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // 2. Center Align: Date (B) for both Header and Data
                $sheet->getStyle("B5:B{$alignRangeEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // 3. Left Align: Strings and Mixed Columns (C to F) for both Header and Data
                $sheet->getStyle("C5:F{$alignRangeEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($rowsCount > 0) {
                    $sheet->getStyle("G{$firstDataRow}:G{$lastDataRow}")->getNumberFormat()->setFormatCode('0.00');
                }
                
                // Formatting Totals Row
                $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);
                
                $sheet->getStyle("G{$totalRow}")->getNumberFormat()->setFormatCode('0.00');
            },
        ];
    }
}
