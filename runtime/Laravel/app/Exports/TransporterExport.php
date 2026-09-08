<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TransporterExport implements FromView, WithEvents
{
    protected $company;
    protected $transporters;
    protected $headings;
    protected $totalColumn;

    public function __construct($company, $transporters, $headings)
    {
        $this->company = $company;
        $this->transporters = $transporters;
        $this->headings = $headings;
        $this->totalColumn = 0;
    }

    public function view(): View
    {
        $rows = $this->transporters->map(function ($transporter) {
            return [
                $transporter->name,
                $transporter->gstin,
                $transporter->pan_no,
                $transporter->contact_person,
                $transporter->email,
                $transporter->mobile,
                $transporter->phone,
                $transporter->address_line1,
                $transporter->address_line2,
                $transporter->city,
                $transporter->state,
                $transporter->postal_code,
                $transporter->bank_name,
                $transporter->bank_account_number,
                $transporter->bank_ifsc,
            ];
        });

        $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.transport-party.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Transport Party Report',
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
                $widths = [
                    'A' => 30, // Name
                    'B' => 20, // GSTIN
                    'C' => 18, // PAN No
                    'D' => 25, // Contact Person
                    'E' => 25, // Email
                    'F' => 18, // Mobile No.
                    'G' => 18, // Phone No.
                    'H' => 35, // Address One
                    'I' => 35, // Address Two
                    'J' => 15, // City
                    'K' => 15, // State
                    'L' => 15, // Pincode
                    'M' => 20, // Bank Name
                    'N' => 20, // Bank Account Number
                    'O' => 20, // Bank IFSC Code
                ];

                for ($i = 1; $i <= $totalColumn; $i++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i);
                    $width = $widths[$columnLetter] ?? 15;
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
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Headings Row (Row 4) ======
                $sheet->getStyle("A4:{$lastColumnLetter}4")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Times New Roman',
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ====== Align Pincode Column (Column L) to Right ======
                $sheet->getStyle('L4:L' . $sheet->getHighestRow())->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
                
                // ====== Align bank number Column (Column N) to Right ======
                $sheet->getStyle('N4:N' . $sheet->getHighestRow())->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
                // ====== Align Phone Number Column (Column G) to Right ======
                $sheet->getStyle('G4:G' . $sheet->getHighestRow())->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
                 // ====== Align Mobile Number Column (Column F) to Right ======
                $sheet->getStyle('F4:F' . $sheet->getHighestRow())->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
            },
        ];
    }   
}
