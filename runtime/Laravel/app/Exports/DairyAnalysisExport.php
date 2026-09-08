<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DairyAnalysisExport implements FromView, WithEvents
{
    protected $company;
    protected $rows;
    protected $headings;
    protected $totalColumn;

    public function __construct($company, $rows, $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->rows = $rows;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        return view('company.pages.dairy-analysis.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Dairy Analysis Register',
            'headings'    => $this->headings,
            'rows'        => $this->rows,
            'datePeriod' => $this->datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn);

                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle("A1:{$lastColumnLetter}1000")->getFont()->setName('Arial, Helvetica');
                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1"); // Company Name
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'name' => 'Arial',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getStyle("A1:{$lastColumnLetter}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

               $widths = [];

                for ($i = 1; $i <= $totalColumn; $i++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i);

                    if ($columnLetter == 'B') {
                        $widths[$i] = 40;
                    } elseif (in_array($columnLetter, ['A', 'C', 'D', 'E', 'F', 'G', 'H', 'I'])) {
                        $widths[$i] = 15;
                    } else {
                        $widths[$i] = 20; 
                    }
                }

                foreach ($widths as $index => $width) {
                    $columnLetter = Coordinate::stringFromColumnIndex($index);
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                // ====== GSTIN Row (Row 2) ======
                
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'size' => 11,
                        'name' => 'Arial, Helvetica',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // ====== Report Title Row (Row 3) ======
                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'name' => 'Arial, Helvetica',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                 // ====== Date Period (Row 4) ======
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'name' => 'Arial, Helvetica',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(15);

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => [
                        'bold' => true, 
                        'size' => 11,
                        'name' => 'Arial, Helvetica',
                    ],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Calculate rows ======
                $firstDataRow = 6;
                $rowsCount = count($this->rows);
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Alignment for Centered Columns (P.Date) ======
                $sheet->getStyle("E5:E{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ====== Alignment for Right Columns (Sr.No, S.Bill No, File No, P.Bill No, P.QTY, Rebate Amount, Bill Balance) ======
                foreach (['A', 'C', 'D', 'F', 'G', 'H', 'I'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Format numeric columns to 2 decimal places
                $rangeNumeric = "H{$firstDataRow}:I{$totalRow}";
                $sheet->getStyle($rangeNumeric)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            },
        ];
    }
}
