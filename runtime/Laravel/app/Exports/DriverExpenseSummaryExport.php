<?php

namespace App\Exports;

use App\Models\DriverExpense;
use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use DB;

class DriverExpenseSummaryExport implements FromCollection, WithHeadings, WithEvents, WithCustomStartCell
{
    protected $from;
    protected $to;
    protected $companyId;
    protected object $company;
    protected $blankRowIndices = [];
    protected $totalRowIndex;

    public function __construct($from, $to, $companyId)
    {
        $this->from = $from;
        $this->to = $to;
        $this->companyId = $companyId;
        $this->company = Company::find($companyId);
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function collection()
    {
        $expenses = DriverExpense::with(['account', 'voucher'])
            ->where('driver_expenses.company_id', $this->companyId)
            ->join('vouchers', 'vouchers.id', '=', 'driver_expenses.voucher_id')
            ->select('driver_expenses.*')
            ->when($this->from, function ($q) {
                $q->whereRaw('CAST(vouchers.voucher_serial AS UNSIGNED) >= ?', [$this->from]);
            })
            ->when($this->to, function ($q) {
                $q->whereRaw('CAST(vouchers.voucher_serial AS UNSIGNED) <= ?', [$this->to]);
            })
            ->orderBy(DB::raw('CAST(vouchers.voucher_serial AS UNSIGNED)'))
            ->get();

        $grouped = [];
        foreach ($expenses as $expense) {
            $driverName = $expense->account->name ?? '';
            $voucherNumber = $expense->voucher->voucher_number ?? '';
            $serial = $expense->voucher->voucher_serial ?? '';
            $key = $driverName . '|' . $voucherNumber;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'driver_name' => $driverName,
                    'voucher_number' => $voucherNumber,
                    'serial' => $serial,
                    'amount' => 0,
                ];
            }
            $grouped[$key]['amount'] += $expense->expense_total;
        }

        // Sort primarily by driver name, then by serial
        $groupedCollection = collect($grouped)->sort(function ($a, $b) {
            $driverCmp = strcmp($a['driver_name'], $b['driver_name']);
            if ($driverCmp === 0) {
                return ((int)$a['serial'] <=> (int)$b['serial']);
            }
            return $driverCmp;
        })->values();

        $exportData = collect();
        $previousDriver = null;
        $totalAmount = 0;
        $currentRowIndex = 6; // Row 5 is Headings

        foreach ($groupedCollection as $item) {
            if ($previousDriver !== null && $previousDriver !== $item['driver_name']) {
                $exportData->push([
                    'driver_name' => '',
                    'voucher_number' => '',
                    'amount' => '',
                ]);
                $this->blankRowIndices[] = $currentRowIndex;
                $currentRowIndex++;
            }

            // Display driver name only on the first row of their block to make it look like a grouped report
            $displayDriverName = ($previousDriver !== $item['driver_name']) ? $item['driver_name'] : '';

            $exportData->push([
                'driver_name' => $displayDriverName,
                'voucher_number' => $item['voucher_number'],
                'amount' => $item['amount'],
            ]);
            $totalAmount += $item['amount'];

            $previousDriver = $item['driver_name'];
            $currentRowIndex++;
        }

        // Separator before Total
        $exportData->push([
            'driver_name' => '',
            'voucher_number' => '',
            'amount' => '',
        ]);
        $this->blankRowIndices[] = $currentRowIndex;
        $currentRowIndex++;

        // Add Total row
        $exportData->push([
            'driver_name' => 'Total',
            'voucher_number' => '',
            'amount' => $totalAmount,
        ]);
        
        $this->totalRowIndex = $currentRowIndex;

        return $exportData;
    }

    public function headings(): array
    {
        return [
            'Driver Name',
            'Voucher Number',
            'Amount',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumnLetter = 'C';

                // ====== Company Name Row (Row 1) ======
                $sheet->setCellValue("A1", $this->company->print_name ?? $this->company->name ?? '');
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ====== GSTIN Row (Row 2) ======
                $sheet->setCellValue("A2", "GSTIN: " . ($this->company->gst_number ?? ''));
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // ====== Report Title Row (Row 3) ======
                $sheet->setCellValue("A3", "Driver Expense Summary Report");
                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Voucher Period (Row 4) ======
                $period = "Voucher No: ";
                if ($this->from && $this->to) {
                    $period .= $this->from . " to " . $this->to;
                } elseif ($this->from) {
                    $period .= $this->from . " onwards";
                } elseif ($this->to) {
                    $period .= "Up to " . $this->to;
                } else {
                    $period .= "All";
                }

                $sheet->setCellValue("A4", $period);
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(15);

                // ====== Headings style (Row 5) ======
                $sheet->getStyle('A5:C5')->getFont()->setBold(true);
                $sheet->getStyle('B5:C5')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);
                
                // Auto-size columns
                $sheet->getColumnDimension('A')->setWidth(35);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(20);

                // Number format for Amount
                $sheet->getStyle("C6:C{$this->totalRowIndex}")
                    ->getNumberFormat()
                    ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                // Make the blank rows gray
                foreach ($this->blankRowIndices as $rowIndex) {
                    $sheet->getStyle("A{$rowIndex}:C{$rowIndex}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE0E0E0'],
                        ]
                    ]);
                }
                
                if ($this->totalRowIndex) {
                    $sheet->getStyle("A{$this->totalRowIndex}:C{$this->totalRowIndex}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                        ]
                    ]);
                }
            },
        ];
    }
}
