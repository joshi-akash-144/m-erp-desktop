<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DestinationExport implements FromView, WithEvents
{
    protected $company;
    protected $destinations;
    protected $headings;
    protected $totalColumn;

    public function __construct($company, $destinations, $headings)
    {
        $this->company = $company;
        $this->destinations = $destinations;
        $this->headings = $headings;
        $this->totalColumn = 0;
    }

    public function view(): View
    {
        $rows = $this->destinations->map(function ($destination) {
            return [
                $destination->name,
                $destination->contact_person_name,
                $destination->email,
                $destination->mobile_number ?? 'N/A',
                $destination->phone_number ?? 'N/A',
                $destination->kms ?? '0',
                $destination->address_one,
                $destination->address_two,
                $destination->country->name,
                $destination->state->name,
                $destination->city,
                $destination->district,
                $destination->taluka,
                $destination->postal_code,
            ];
        });

        $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.destination.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'       => $this->company->gst_number,
            'reportTitle' => 'Destination Report',
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
                        $widths[$i] = 30;
                    } elseif (in_array($columnLetter, ['C'])) {
                        $widths[$i] = 25;
                    } elseif (in_array($columnLetter, ['G', 'H'])) { // address_one and address_two
                        $widths[$i] = 25;
                    } elseif (in_array($columnLetter, ['F'])) { //kms
                        $widths[$i] = 10;
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
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);
            },
        ];
    }
}
