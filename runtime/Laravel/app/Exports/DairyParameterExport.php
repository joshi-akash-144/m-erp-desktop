<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DairyParameterExport implements FromView, WithEvents
{
    protected $company;
    protected $dairyParameters;
    protected $headings;
    protected $totalColumn;

    public function __construct($company, $dairyParameters, $headings)
    {
        $this->company = $company;
        $this->dairyParameters = $dairyParameters;
        $this->headings = $headings;
        $this->totalColumn = 0;
    }

    public function view(): View
    {
        $rows = $this->dairyParameters->map(function ($dairyParameter) {
            return [
                $dairyParameter->condition->name,
                $dairyParameter->element->name,
                $dairyParameter->guarantee,
            ];
        });

       $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.dairy-parameter.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'       => $this->company->gst_number,
            'reportTitle' => 'Dairy Parameters Report',
            'headings'    => $this->headings,
            'rows'        => $rows,
        ]);
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn);

                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle("A1:{$lastColumnLetter}1000")->getFont()->setName('Times New Roman');
                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1"); // Company Name
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'name' => 'Times New Roman',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);
               $widths = [];

                for ($i = 1; $i <= $totalColumn; $i++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i);

                    if (in_array($columnLetter, ['A', 'B'])) {
                        $widths[$i] = 45;
                    } elseif (in_array($columnLetter, ['C'])) {
                        $widths[$i] = 25;
                    } else {
                        $widths[$i] = 20; // default width for others
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
                        'name' => 'Times New Roman',
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
                        'name' => 'Times New Roman',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // ====== Headings Row (Row 3) ======
                $sheet->getStyle("A4:{$lastColumnLetter}4")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Times New Roman',
                    ],
                    'alignment'=>[
                        // 'horizontal'=>Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getStyle("C4")->applyFromArray([
                    'alignment'=>[
                        'horizontal'=>Alignment::HORIZONTAL_RIGHT
                    ]
                ]);
            },
        ];
    }
}
