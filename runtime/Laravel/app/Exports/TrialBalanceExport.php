<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TrialBalanceExport implements FromView, WithEvents
{
    protected $company;
    protected $view;
    protected $rows;
    protected $headings;
    protected $extraData;

    public function __construct($company, $view, $rows, $headings, $extraData = [])
    {
        $this->company = $company;
        $this->view = $view;
        $this->rows = $rows;
        $this->headings = $headings;
        $this->extraData = $extraData;
    }

    public function view(): View
    {
        return view($this->view, array_merge([
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => $this->extraData['report_title'] ?? 'Trial Balance Report',
            'headings' => $this->headings,
            'rows' => $this->rows,
        ], $this->extraData));
    }

    public function registerEvents(): array
    {
        return [
           AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalColumn = count($this->headings);
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn);

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

                // ====== Headings Row (Row 4) ======
                $sheet->getStyle("A4:{$lastColumnLetter}4")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ====== Date Row (Row 5) ======
                 $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(15);

                // ====== Get last row with data ======
                $lastRow = $sheet->getHighestRow();

                // ====== Dynamic right alignment for numeric columns ======
                if ($lastRow > 4) { // Only if there's data beyond headers
                    foreach (range('A', $lastColumnLetter) as $columnID) {
                        $isNumericColumn = false;
                        
                        // Check multiple rows to determine if column is numeric (more reliable)
                        $samplesToCheck = min(5, $lastRow - 4); // Check first 5 rows or all available
                        for ($row = 5; $row <= 4 + $samplesToCheck; $row++) {
                            $cellValue = $sheet->getCell("{$columnID}{$row}")->getValue();
                            
                            // Skip empty cells
                            if ($cellValue === null || $cellValue === '') {
                                continue;
                            }
                            
                            // Check if numeric (handles: 123, 123.45, "123", "123.45", "16,000.00")
                            $cleanValue = str_replace(',', '', $cellValue); // Remove commas
                            if (is_numeric($cleanValue)) {
                                $isNumericColumn = true;
                                break;
                            } else {
                                // If we find a non-numeric, non-empty value, it's not a numeric column
                                $isNumericColumn = false;
                                break;
                            }
                        }
                        
                        if ($isNumericColumn) {
                            // Apply right alignment to entire column
                            $sheet->getStyle("{$columnID}5:{$columnID}{$lastRow}")
                                ->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            
                            // Apply number format with 2 decimal places and thousand separator
                            $sheet->getStyle("{$columnID}5:{$columnID}{$lastRow}")
                                ->getNumberFormat()
                                ->setFormatCode('#,##0.00');
                        }
                    }
                }

                // ====== Auto-size Columns ======
                foreach (range('A', $lastColumnLetter) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
