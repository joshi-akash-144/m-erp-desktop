<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class JournalVoucherRegisterExport implements FromView, WithEvents
{
    protected object $company;
    protected Collection $voucherData;
    protected array $headings;
    protected int $totalColumn = 0;
    protected ?string $datePeriod;

    public function __construct(object $company, Collection $voucherData, array $headings, ?string $datePeriod = null)
    {
        $this->company     = $company;
        $this->voucherData = $voucherData;
        $this->headings    = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod  = $datePeriod;
    }

    public function view(): View
    {
        $rows = $this->voucherData->map(function ($item) {
            $isNarration = (($item['row_type'] ?? '') === 'narration');

            if ($isNarration) {
                return ['', 'Narration: ' . ($item['particulars'] ?? ''), '', '', ''];
            }

            return [
                $item['voucher_date'] ? Carbon::parse($item['voucher_date'])->format('d-m-Y') : '',
                $item['particulars'] ?? '',
                $item['voucher_number'] ?? '',
                (float) ($item['debit']  ?? 0) ?: '',
                (float) ($item['credit'] ?? 0) ?: '',
            ];
        });

        return view('company.pages.journal-voucher.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Journal Voucher Register',
            'headings'    => $this->headings,
            'rows'        => $rows,
            'datePeriod'  => $this->datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet            = $event->sheet->getDelegate();
                $lastColumnLetter = Coordinate::stringFromColumnIndex($this->totalColumn ?: 5);

                // Title rows formatting
                foreach ([1 => [true, 16], 2 => [false, 11], 3 => [true, 12], 4 => [false, null]] as $row => [$bold, $size]) {
                    $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
                    $style = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
                    if ($bold) $style['font']['bold'] = true;
                    if ($size) $style['font']['size'] = $size;
                    $sheet->getStyle("A{$row}")->applyFromArray($style);
                    $sheet->getRowDimension($row)->setRowHeight($row === 1 ? 25 : 18);
                }

                // Heading row (row 5)
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray(['font' => ['bold' => true]]);
                $sheet->getRowDimension(5)->setRowHeight(18);
                $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D5:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(45);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(18);

                $firstDataRow = 6;
                $rowsCount    = count($this->voucherData);
                $lastDataRow  = $firstDataRow + $rowsCount - 1;
                $totalRow     = $firstDataRow + $rowsCount;

                if ($rowsCount > 0) {
                    $sheet->getStyle("D{$firstDataRow}:E{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("D{$firstDataRow}:E{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$firstDataRow}:C{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Italic narration rows
                foreach ($this->voucherData as $index => $item) {
                    $item = (array) $item;
                    if (($item['row_type'] ?? '') === 'narration') {
                        $sheet->getStyle('B' . ($firstDataRow + $index))->applyFromArray([
                            'font' => ['italic' => true, 'color' => ['rgb' => '653818']],
                        ]);
                    }
                }

                // Total row
                $totalDebit  = $this->voucherData->sum('debit');
                $totalCredit = $this->voucherData->sum('credit');

                $sheet->setCellValue("C{$totalRow}", 'Total:');
                $sheet->getStyle("C{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValueExplicit("D{$totalRow}", $totalDebit,  DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("E{$totalRow}", $totalCredit, DataType::TYPE_NUMERIC);
                $sheet->getStyle("C{$totalRow}:E{$totalRow}")->applyFromArray(['font' => ['bold' => true], 'numberFormat' => ['formatCode' => '#,##0.00']]);
                $sheet->getStyle("D{$totalRow}:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}
