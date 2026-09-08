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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class PaymentVoucherRegisterExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var Collection Collection of voucher rows
     */
    protected Collection $voucherData;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var int Total number of columns in the sheet
     */
    protected int $totalColumn = 0;

    /**
     * @var string|null Date period for the report
     */
    protected ?string $datePeriod;

    /**
     * Constructor
     *
     * @param object $company
     * @param Collection $voucherData
     * @param array $headings
     * @param string|null $datePeriod
     */
    public function __construct(object $company, Collection $voucherData, array $headings, ?string $datePeriod = null)
    {
        $this->company     = $company;
        $this->voucherData = $voucherData;
        $this->headings    = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod  = $datePeriod;
    }

    /**
     * Return the view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        $rows = $this->voucherData->map(function ($item) {
            $rowType = $item['row_type'] ?? 'transaction';
            $isNarration = ($rowType === 'narration');
            
            if ($isNarration) {
                return [
                    '', // Date
                    'Narration: ' . ($item['particulars'] ?? ''), // Particulars
                    '', // Voucher No.
                    '', // Debit
                    ''  // Credit
                ];
            } else {
                $vDate = $item['voucher_date'] ? Carbon::parse($item['voucher_date'])->format('d-m-Y') : '';
                return [
                    $vDate,
                    $item['particulars'] ?? '',
                    $item['voucher_number'] ?? '',
                    (float)($item['debit'] ?? 0) ?: '',
                    (float)($item['credit'] ?? 0) ?: '',
                ];
            }
        });

        return view('company.pages.payment-voucher.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Payment Voucher Register',
            'headings'    => $this->headings,
            'rows'        => $rows,
            'datePeriod'  => $this->datePeriod,
        ]);
    }

    /**
     * Register Excel events for formatting
     *
     * @return array
     */
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
                $sheet->getStyle("D5:E5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("C5:C5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ====== Column Widths ======
                $sheet->getColumnDimension('A')->setWidth(15); // Date
                $sheet->getColumnDimension('B')->setWidth(45); // Particulars
                $sheet->getColumnDimension('C')->setWidth(15); // Voucher No.
                $sheet->getColumnDimension('D')->setWidth(18); // Debit
                $sheet->getColumnDimension('E')->setWidth(18); // Credit

                $firstDataRow = 6;
                $rowsCount = count($this->voucherData);
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // Align and number-format data cells
                if ($rowsCount > 0) {
                    $sheet->getStyle("D{$firstDataRow}:D{$lastDataRow}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("E{$firstDataRow}:E{$lastDataRow}")
                        ->getNumberFormat()->setFormatCode('#,##0.00');

                    // Right align data
                    $sheet->getStyle("D{$firstDataRow}:E{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    
                    // Center align dates & voucher numbers
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$firstDataRow}:C{$lastDataRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ====== Apply Italic to Narration Rows ======
                foreach ($this->voucherData as $index => $item) {
                    $currentRow = $firstDataRow + $index;
                    $item = (array) $item;
                    if (isset($item['row_type']) && $item['row_type'] === 'narration') {
                        $sheet->getStyle("B{$currentRow}")->applyFromArray([
                            'font' => ['italic' => true, 'color' => ['rgb' => '653818']],
                        ]);
                    }
                }

                // Total row calculations
                $totalDebit = $this->voucherData->sum('debit');
                $totalCredit = $this->voucherData->sum('credit');

                $sheet->setCellValue("C{$totalRow}", "Total:");
                $sheet->getStyle("C{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $sheet->setCellValueExplicit("D{$totalRow}", $totalDebit, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("E{$totalRow}", $totalCredit, DataType::TYPE_NUMERIC);

                $sheet->getStyle("C{$totalRow}:E{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '#,##0.00'],
                ]);
                $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
