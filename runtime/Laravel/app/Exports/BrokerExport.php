<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BrokerExport implements FromView, WithEvents
{
    protected $company;
    protected $brokers;
    protected $headings;
    protected int $totalColumn;

    public function __construct($company, $brokers, $headings)
    {
        $this->company      = $company;
        $this->brokers      = $brokers;
        $this->headings     = $headings;
        $this->totalColumn  = 0;
    }

    public function view(): View
    {
        $rows = $this->brokers->map(function ($broker) {
            return [
                $broker->name,
                $broker->address_one,
                $broker->address_two,
                $broker->state?->name,
                $broker->country?->name,
                $broker->city,
                $broker->mobile_number,
                $broker->pan,
                $broker->email,
                $broker->postal_code,
                $broker->bank_name,
                $broker->bank_branch_name,
                "'".$broker->bank_account_number,
                $broker->bank_ifsc,
            ];
        });

        $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.broker.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Broker Report',
            'headings'    => $this->headings,
            'rows'        => $rows,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $totalColumn      = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn);
                $sheet            = $event->sheet->getDelegate();

                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 18, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['size' => 11, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                $sheet->getStyle("A4:{$lastColumnLetter}4")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
                ]);
    
                // right align columns G, J (Mobile Number, PIN)
                foreach(['G','J'] as $col){
                   $sheet->getStyle("{$col}4:{$col}4")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                   ]); 
                }                

                for ($i = 1; $i <= $totalColumn; $i++) {
                    $col   = Coordinate::stringFromColumnIndex($i);
                    $width = in_array($col, ['A', 'D', 'E']) ? 45 : (in_array($col, ['B', 'C']) ? 25 : 20);
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            },
        ];
    }
}
