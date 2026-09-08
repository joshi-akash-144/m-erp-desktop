<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DriverExport implements FromView, WithEvents
{
    protected $company;
    protected $drivers;
    protected $headings;
    protected $totalColumn;
    protected $obMap;

    public function __construct($company, $drivers, $headings, $obMap = null)
    {
        $this->company = $company;
        $this->drivers = $drivers;
        $this->headings = $headings;
        $this->totalColumn = 0;
        $this->obMap = $obMap ?? collect();
    }

    public function view(): View
    {
        $rows = $this->drivers->map(function ($driver) {
            $ob = $this->obMap->get($driver->account_id);
            $openingDrBalance = ($ob && $ob->debit > 0) ? $ob->debit : '0.00';           
            return [
                $driver?->account?->name ?? '',
                $driver?->account?->mobile_number ?? '',
                $driver?->account?->address_one ?? '',
                $driver?->account?->address_two ?? '',
                $driver?->account?->city ?? '',
                $driver?->account?->postal_code ?? '',
                $driver?->vehicle?->name ?? '',
                $openingDrBalance,
                $driver->license_number,
                $driver->license_category ? (config('constants.license_category')[$driver->license_category] ?? $driver->license_category) : '',
                $driver->license_issuing_authority,
                $driver->license_expiry_date_tr ? \Carbon\Carbon::parse($driver->license_expiry_date_tr)->format('d-m-Y') : '',
                $driver->license_expiry_date_nt ? \Carbon\Carbon::parse($driver->license_expiry_date_nt)->format('d-m-Y') : '',
                $driver->date_of_joining ? \Carbon\Carbon::parse($driver->date_of_joining)->format('d-m-Y') : '',
                $driver->adhara_number,
                $driver->religion,
                $driver->qualification,
                $driver->marital_status ? (config('constants.marital_status')[$driver->marital_status] ?? $driver->marital_status) : '',
                // $driver->blood_group ? (config('constants.blood_group')[$driver->blood_group] ?? $driver->blood_group) : '',
                $driver->salary,
                $driver?->account?->taxDetail?->pan ?? '',
                $driver->remarks,
            ];
        });

       $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : count($this->headings);

        return view('company.pages.masters.account.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'       => $this->company->gst_number,
            'reportTitle' => 'Driver Report',
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

                    if (in_array($columnLetter, ['A', 'E', 'G', 'R'])) {
                        $widths[$i] = 30; // Name, License, Authority, Remarks
                    } elseif (in_array($columnLetter, ['C', 'D'])) {
                        $widths[$i] = 20; // Balances
                    } else {
                        $widths[$i] = 18; // default width
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
                $sheet->getStyle("C4:D4")->applyFromArray([
                    'alignment'=>[
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'=> Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
