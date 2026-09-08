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

class LedgerExport implements FromView, WithEvents
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
            'reportTitle' => $this->extraData['report_title'] ?? 'Ledger Report',
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

                // ====== Get last row with data ======
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle('E5:E'.$lastRow)->getNumberFormat()->setFormatCode('0.00 ;0.00 ;0.00');
                $sheet->getStyle('F5:F'.$lastRow)->getNumberFormat()->setFormatCode('0.00 ;0.00 ;0.00');
                $sheet->getStyle('G5:G'.$lastRow)->getNumberFormat()->setFormatCode('#,##0.00 "Dr";#,##0.00 "Cr";0.00');
                
                $sheet->getStyle('B5:B'.$lastRow)->getAlignment()->setWrapText(true);
                // ====== Auto-size Columns ======
                foreach (range('A', $lastColumnLetter) as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
