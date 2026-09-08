<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TdsEntryExport implements FromView, WithEvents
{
    protected $company;
    protected $tdsEntries;
    protected $headings;
    protected $filters;
    protected $totalColumn;
    
    protected $totalPaymentAmount = 0;
    protected $totalTdsAmount = 0;

    public function __construct($company, $tdsEntries, $headings, $filters)
    {
        $this->company = $company;
        $this->tdsEntries = $tdsEntries;
        $this->headings = $headings;
        $this->filters = $filters;
        $this->totalColumn = 0;
    }

    public function view(): View
    {
        $rows = $this->tdsEntries->flatMap(function ($entry) {
            $this->totalPaymentAmount += $entry->payment_amount;
            $this->totalTdsAmount += $entry->tds_amount;
            
            $row1 = [
                $entry->reference_no, // Ref.No.
                $entry->deductee_name, // Deductee Name
                $entry->payment_amount, // Payment Amt.
                $entry->payment_date ? $entry->payment_date->format('d-m-Y') : '', // Payment On
                $entry->tds_rate ? number_format((float)$entry->tds_rate, 2) : '0.00', // TDS %
                $entry->tds_amount, // TDS Amount
                '-', // Sur. %
                '-', // Surcharge Amt.
                '-', // Edu. Cess %
                '-', // Edu. Cess Amt.
                '-', // SHE Cess %
                '-', // SHE Cess Amt.
                $entry->tds_amount, // Total Deducted
                $entry->tax_deducted_on ? $entry->tax_deducted_on->format('d-m-Y') : ($entry->payment_date ? $entry->payment_date->format('d-m-Y') : ''), // Tax Deducted On
                '-', // Tax Deposited
                '-', // Tax Deposited On
                '-', // Challan No
                '-', // Cheque No
                '-', // Bank Name
            ];

            $row2 = [
                '-', // Ref.No.
                'PAN : ' . ($entry->pan_no ?? ''), // Deductee Name
                '-', // Payment Amt.
                '-', // Payment On
                '-', // TDS %
                '-', // TDS Amount
                '-', // Sur. %
                '-', // Surcharge Amt.
                '-', // Edu. Cess %
                '-', // Edu. Cess Amt.
                '-', // SHE Cess %
                '-', // SHE Cess Amt.
                '-', // Total Deducted
                '-', // Tax Deducted On
                '-', // Tax Deposited
                '-', // Tax Deposited On
                '-', // Challan No
                '-', // Cheque No
                '-', // Bank Name
            ];

            return [$row1, $row2];
        });

        $this->totalColumn = count($this->headings);

        return view('company.pages.fas.tds-entries.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'TDS Details (All References)',
            'headings'    => $this->headings,
            'rows'        => $rows,
            'filters'     => $this->filters,
            'totalPaymentAmount' => $this->totalPaymentAmount,
            'totalTdsAmount' => $this->totalTdsAmount,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn);
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'name' => 'Arial',
                        'color' => ['argb' => 'FF000000']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ]
                ]);
                
                // Add an orange top border to the company name row
                $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFFF7043']
                        ],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(22);

                // ====== Report Title Row (Row 2) ======
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'name' => 'Arial',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // ====== Category / From To Row (Row 4) ======
                $sheet->getStyle("A4:{$lastColumnLetter}5")->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'name' => 'Arial',
                        'bold' => true
                    ],
                ]);
                
                // ====== Headings Row (Row 6) ======
                $sheet->getStyle("A6:{$lastColumnLetter}6")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'name' => 'Arial',
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(18);

                // Apply borders and alignment to all data rows
                if ($highestRow >= 7) {
                    $sheet->getStyle("A7:{$lastColumnLetter}{$highestRow}")->applyFromArray([
                        'font' => [
                            'size' => 10,
                            'name' => 'Arial',
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FFCCCCCC'], // Light gray borders
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ]
                    ]);
                }
                
                // Bold the total row
                if (count($this->tdsEntries) > 0) {
                    $sheet->getStyle("A{$highestRow}:{$lastColumnLetter}{$highestRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ]
                    ]);
                }

                // Widths configuration based on screenshot
                $widths = [
                    'A' => 15, // Ref.No.
                    'B' => 35, // Deductee Name
                    'C' => 15, // Payment Amt.
                    'D' => 15, // Payment On
                    'E' => 10, // TDS %
                    'F' => 15, // TDS Amount
                    'G' => 10, // Sur. %
                    'H' => 15, // Surcharge Amt.
                    'I' => 15, // Edu. Cess %
                    'J' => 15, // Edu. Cess Amt.
                    'K' => 15, // SHE Cess %
                    'L' => 15, // SHE Cess Amt.
                    'M' => 15, // Total Deducted
                    'N' => 15, // Tax Deducted On
                    'O' => 15, // Tax Deposited
                    'P' => 15, // Tax Deposited On
                    'Q' => 15, // Challan No
                    'R' => 15, // Cheque No
                    'S' => 20, // Bank Name
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
