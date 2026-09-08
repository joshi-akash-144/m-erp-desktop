<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalesPurchaseAnalysisExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, WithCustomStartCell, WithEvents
{
    protected $company;
    protected $rows;
    protected $headings;
    protected $datePeriod;

    public function __construct($company, $rows, $headings, $datePeriod)
    {
        $this->company = $company;
        $this->rows = $rows;
        $this->headings = $headings;
        $this->datePeriod = $datePeriod;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function map($row): array
    {
        return [
            $row->buyer_po_number ?? '--',
            $row->sales_party_name ?? '--',
            $row->item_name ?? '--',
            $row->destination_name ?? '--',
            $row->so_total_qty ? number_format((float)$row->so_total_qty, 3, '.', '') : '0.000',
            $row->purchase_rate ? number_format((float)$row->purchase_rate, 2, '.', '') : '0.00',
            $row->sales_rate ? number_format((float)$row->sales_rate, 2, '.', '') : '0.00',
            $row->profit_loss_rate ? number_format((float)$row->profit_loss_rate, 2, '.', '') : '0.00',
            $row->profit_loss_amount ? number_format((float)$row->profit_loss_amount, 2, '.', '') : '0.00'
        ];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return 'Sales Purchase Analysis';
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2EFDA']
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Company Name Header
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', $this->company->name ?? '');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Report Title
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue('A2', 'Sales Purchase Analysis');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Date Period
                $sheet->mergeCells('A3:I3');
                $sheet->setCellValue('A3', 'Period: ' . $this->datePeriod);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Auto size columns
                foreach (range('A', 'I') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
                
                // Apply borders to all data
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle('A4:I' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);
            }
        ];
    }
}
