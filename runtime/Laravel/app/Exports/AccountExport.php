<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AccountExport implements FromView, WithEvents
{
    protected $company;
    protected $accounts;
    protected $headings;
    protected $totalColumn;
    protected $obMap;

    public function __construct($company, $accounts, $headings, $obMap = null)
    {
        $this->company = $company;
        $this->accounts = $accounts;
        $this->headings = $headings;
        $this->totalColumn = 0;
        $this->obMap = $obMap ?? collect();
    }

    public function view(): View
    {
        $rows = $this->accounts->map(function ($account) {
            $ob = $this->obMap->get($account->id);
            $openingDrBalance = ($ob && $ob->debit > 0) ? $ob->debit : '0.00';
            $openingCrBalance = ($ob && $ob->credit > 0) ? $ob->credit : '0.00';
            return [
                $account->name,
                $account?->accountGroup?->name,
                $openingDrBalance,
                $openingCrBalance,
                $account->address_one,
                $account->address_two,
                $account?->country?->name,
                $account?->state?->name,
                $account->city,
                config('constants.type_of_dealer')[$account?->taxDetail?->type_of_dealer] ?? '',
                config('constants.filing_frequency')[$account?->taxDetail?->filing_frequency] ?? '',
                config('constants.tax_type')[$account?->taxDetail?->tax_type] ?? '',
                config('constants.gst_type')[$account?->taxDetail?->gst_type] ?? '',
                $account?->taxDetail?->gst_number,
                $account->mobile_number,
                $account->whatsapp_number,
                $account?->taxDetail?->pan,
                $account?->taxDetail?->tin,
                $account->email,
                $account?->preference?->station,
                $account->postal_code,
                strtoupper($account?->preference?->distance),
                $account?->preference?->contact_person,
                $account?->preference?->transport,
                config('constants.transport_modes')[$account?->preference?->transport_mode] ?? '',

                $account?->bankDetail?->bank_name,
                $account?->bankDetail?->bank_branch_name,
                "'".$account?->bankDetail?->bank_account_number,
                $account?->bankDetail?->bank_ifsc,
                config('constants.is_bill_wise')[$account->is_billwise] ?? '',
                $account?->taxDetail?->hsn_sac_code,
                config('constants.itc_eligibility')[$account?->taxDetail?->itc_eligibility] ?? '',
                config('constants.rcm_nature')[$account?->taxDetail?->rcm_nature] ?? '',

            ];
        });

       $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.account.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'       => $this->company->gst_number,
            'reportTitle' => 'Account Report',
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

                    if (in_array($columnLetter, ['A', 'B', 'E', 'F','T'])) {
                        $widths[$i] = 45;
                    } elseif (in_array($columnLetter, ['C', 'D','AA','AB','AC'])) {
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
