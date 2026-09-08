<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class GodownTransporterExport implements FromView, WithEvents
{
    protected $company;
    protected $transporterData;
    protected $headings;
    protected $totalColumn = 0;
    protected $filters;

    public function __construct($company, $transporterData, array $headings, array $filters = [])
    {
        $this->company = $company;
        $this->transporterData = $transporterData;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->filters = $filters;
    }

    public function view(): View
    {
        $rows = $this->transporterData->map(function ($record, $index) {
            return [
                $index + 1,
                $record->vehicle_number ?? 'N/A',
                $record->transporter->name ?? 'N/A',
                $record->lr_number ?? 'N/A',
                $record->gross_weight ?? 0,
                $record->tare_weight ?? 0,
                $record->net_weight ?? 0,
                $record->net_weight_wt_bag ?? 0,
            ];
        });

        $currentYear = $this->company->currentFinancialYear;
        $startDate = !empty($this->filters['start_date'])
            ? Carbon::parse($this->filters['start_date'])->format('d-m-Y')
            : Carbon::parse($currentYear->start_date)->format('d-m-Y');
            
        $endDate = !empty($this->filters['end_date'])
            ? Carbon::parse($this->filters['end_date'])->format('d-m-Y')
            : Carbon::parse($currentYear->end_date)->format('d-m-Y');
            
        $datePeriod = "$startDate to $endDate";

        return view('company.pages.godown.transporter.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber' => $this->company->gst_number ?? '',
            'reportTitle' => 'Transporter Wise Weight Summary',
            'headings' => $this->headings,
            'rows' => $rows,
            'datePeriod' => $datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex($this->totalColumn);
                $lastRow = $sheet->getHighestRow();

                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ====== GSTIN Row (Row 2) ======
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // ====== Report Title Row (Row 3) ======
                $sheet->mergeCells("A3:{$lastColumn}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Date Period Title Row (Row 4) ======
                $sheet->mergeCells("A4:{$lastColumn}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(20);
                
                $widthMap = [
                    'SR NO'              => 8,
                    'VEHICLE NO'         => 18,
                    'TRANSPORTER NAME'   => 35,
                    'LR NUMBER'          => 18,
                    'GROSS WEIGHT'       => 18,
                    'TARE WEIGHT'        => 18,
                    'NET WEIGHT'         => 18,
                    'WITHOUT BAG WEIGHT' => 25,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 15;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }
                
                // ====== Calculate Totals ======
       
                $totalNet = $this->transporterData->sum('net_weight');
                $totalNetWtbags = $this->transporterData->sum('net_weight_wt_bag');
                
                $totalRow = $sheet->getHighestRow() + 1;
                
                // Set "Total:" Labels
                $sheet->setCellValue("F{$totalRow}", "Total:");
                
                // Set Total Values
                $sheet->setCellValue("G{$totalRow}", $totalNet);
                $sheet->setCellValue("H{$totalRow}", $totalNetWtbags);
                
                // Style the Total Row
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("G{$totalRow}:H{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // ====== Alignment for Numeric Columns (Headings + Data) ======
                $numericColumns = ['E', 'F', 'G', 'H'];
                foreach ($numericColumns as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
