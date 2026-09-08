<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExpenseRegisterExport implements FromView, WithEvents
{
    protected object $company;
    protected Collection $expenseData;
    protected array $headings;
    protected int $totalColumn = 0;
    protected ?string $datePeriod;
    protected ?string $expenseAccountName;

    public function __construct(object $company, Collection $expenseData, array $headings, ?string $datePeriod = null)
    {
        $this->company     = $company;
        $this->expenseData = $expenseData;
        $this->headings    = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod  = $datePeriod;    
    }

    public function view(): View
    {
        $rows = $this->expenseData->map(function ($item) {
            return [
                $item['voucher_date'] ?? '',
                $item['voucher_serial'] ?? '',
                $item['party_name'] ?? '',
                $item['reference_number'] ?? '',
                $item['account_name'] ?? '',
                $item['expense_account'] ?? '',
                (float)($item['amount'] ?? 0) ?: '',
            ];
        });

        return view('company.pages.expense-register.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'All Expense Register',
            'headings'    => $this->headings,
            'rows'        => $rows,
            'datePeriod'  => $this->datePeriod            
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 5);

                // ====== Title Formatting ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(20);

                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ====== Headings (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Center / Right Headers ======
                $sheet->getStyle("A5:A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B5:B5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D5:D5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G5:G5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // ====== Column Widths ======
                $sheet->getColumnDimension('A')->setWidth(15); // Date
                $sheet->getColumnDimension('B')->setWidth(15); // Voucher No.
                $sheet->getColumnDimension('C')->setWidth(30); // Party Name
                $sheet->getColumnDimension('D')->setWidth(15); // Ref No.
                $sheet->getColumnDimension('E')->setWidth(30); // Vehicle / Account
                $sheet->getColumnDimension('F')->setWidth(35); // Expense Account
                $sheet->getColumnDimension('G')->setWidth(18); // Amount

                $firstDataRow = 6;
                $rowsCount = count($this->expenseData);
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // Align and number-format data cells
                if ($rowsCount > 0) {
                    $sheet->getStyle("G{$firstDataRow}:G{$lastDataRow}")
                        ->getNumberFormat()->setFormatCode('0.00');

                    // Right align amount
                    $sheet->getStyle("G{$firstDataRow}:G{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    
                    // Center align dates, voucher numbers & ref numbers
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B{$firstDataRow}:B{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$firstDataRow}:D{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Total row calculations
                $totalAmount = $this->expenseData->sum('amount');

                $sheet->setCellValue("F{$totalRow}", "Total:");
                $sheet->getStyle("F{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("F{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $sheet->setCellValueExplicit("G{$totalRow}", $totalAmount, DataType::TYPE_NUMERIC);

                $sheet->getStyle("F{$totalRow}:G{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '###.00'],
                ]);
                $sheet->getStyle("G{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
