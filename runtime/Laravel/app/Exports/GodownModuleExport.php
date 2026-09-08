<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GodownModuleExport implements FromView, WithEvents
{
    protected $company;
    protected $godownData;
    protected $headings;
    protected $totalColumn = 0;
    protected $filters;

    public function __construct($company, $godownData, array $headings, array $filters = [])
    {
        $this->company = $company;
        $this->godownData = $godownData;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->filters = $filters;
    }

    public function view(): View
    {
        $rows = $this->godownData->map(function ($record, $index) {
            return [
                $index + 1,
                // $record->grn_serial ? 'GRN:' . $record->grn_serial : ($record->dc_serial ? 'DC:' . $record->dc_serial : ''),
                $record->grn_serial ?? '',
                Str::upper($record->in_out_status ?? ''),
                $record->vehicle_number,
                $record->account->name ?? '',
                $record->item->name ?? '',
                $record->partyDestination->name ?? '',
                $record->reference_number ?? $record->grn_serial ?? '',
                $record->challan_weight,
                $record->bag_count,
                $record->destination->name ?? '',
                $record->godownUnit->godown_name ?? '',
                $record->grn_date ? \App\Helpers\DateHelper::formatDate($record->grn_date, 'd-m-Y') : ($record->dc_date ? \App\Helpers\DateHelper::formatDate($record->dc_date, 'd-m-Y') : ''),
                $record->date_in && $record->date_in !== '-' ? $record->date_in : '',
                $record->time_in ?? '',
                $record->date_out && $record->date_out !== '-' ? $record->date_out : '',
                $record->time_out ?? '',
                $record->p_qty ?? '',
                $record->rate ?? '',
                $record->gross_weight,
                $record->tare_weight,
                $record->net_weight,
                $record->net_weight_wt_bag,
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
 

        return view('company.pages.godown.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber' => $this->company->gst_number ?? '',
            'reportTitle' => 'Godown Module Report',
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
                    'NO'                 => 10,
                    'IN/OUT'             => 10,
                    'VEHICLE NO'         => 15,
                    'ACCOUNT NAME'       => 45,
                    'ITEM NAME'          => 20,
                    'PARTY DESTINATION'  => 25,
                    'CHALLAN NO'         => 15,
                    'CHALLAN WEIGHT'     => 20,
                    'BAGS'               => 8,
                    'DESTINATION'        => 20,
                    'GODOWN'             => 15,
                    'DATE'               => 12,
                    'DATE IN'            => 12,
                    'TIME IN'            => 12,
                    'DATE OUT'           => 12,
                    'TIME OUT'           => 12,
                    'P.QTY'              => 10,
                    'RATE'               => 8,
                    'GROSS WEIGHT'       => 18,
                    'TARE WEIGHT'        => 18,
                    'NET WEIGHT'         => 15,
                    'WITHOUT BAG WEIGHT' => 25,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 15;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }
                
                // ====== Calculate Totals ======
                $totalChallanWeight = $this->godownData->sum('challan_weight');
                $totalBags = $this->godownData->sum('bag_count');
                $totalNetWeight = $this->godownData->sum('net_weight');
                $totalNetWeightWtbags = $this->godownData->sum('net_weight_wt_bag');
                
                $totalRow = $sheet->getHighestRow() + 1;
                
                // Set "Total:" Labels
                $sheet->setCellValue("A{$totalRow}", "Entries:");
                $sheet->setCellValue("H{$totalRow}", "Total:");
                $sheet->setCellValue("U{$totalRow}", "Total:");
                
                // Set Total Values
                $sheet->setCellValue("B{$totalRow}", count($this->godownData));
                $sheet->setCellValue("I{$totalRow}", $totalChallanWeight);
                $sheet->setCellValue("J{$totalRow}", $totalBags);
                $sheet->setCellValue("V{$totalRow}", $totalNetWeight);
                $sheet->setCellValue("W{$totalRow}", $totalNetWeightWtbags);
                
                // Style the Total Row
                $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("I{$totalRow}:J{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("U{$totalRow}:V{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("H{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("T{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // ====== Alignment for Numeric Columns (Headings + Data) ======
                $numericColumns = ['I', 'J', 'Q', 'R', 'S', 'T', 'U', 'V'];
                foreach ($numericColumns as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
