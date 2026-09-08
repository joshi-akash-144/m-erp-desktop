<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DaybookExport implements FromView, WithEvents
{
    protected object $company;
    protected Collection $daybooks;
    protected array $headings;
    protected int $totalColumn = 0;
    protected ?string $datePeriod;

    public function __construct(object $company, Collection $daybooks, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->daybooks = $daybooks;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $rows = $this->daybooks->map(function ($item) {
            // narration rows have row_type set
            if (isset($item->row_type)) {
                return [
                    '',
                    '',
                    '',
                    $item->narration ? 'Narration : ' . $item->narration : '',
                    '',
                    '',
                ];
            }

            return [
                $item->voucher_date ? Carbon::parse($item->voucher_date)->format('d/m/Y') : '',
                $item->voucher_type ?? '',
                $item->voucher_serial ?? '',
                $item->account_name ?? '',
                $item->debit ?? 0,
                $item->credit ?? 0,
            ];
        });

        return view('company.pages.daybook.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Daybook Report',
            'headings' => $this->headings,
            'rows' => $rows,
            'datePeriod' => $this->datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumnLetter = Coordinate::stringFromColumnIndex($this->totalColumn); // = F
    
                // ── Header rows ──────────────────────────────────────────
                foreach ([1, 2, 3, 4] as $row) {
                    $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
                }
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                foreach ([2, 3, 4] as $row) {
                    $sheet->getStyle("A{$row}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ── Headings row (row 5) ──────────────────────────────────
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ── Column widths ─────────────────────────────────────────
                $widthMap = [
                    'Date' => 14,
                    'Voucher Type' => 16,
                    'Vch / Ref No.' => 16,
                    'Particulars' => 35,
                    'Debit' => 20,
                    'Credit' => 20,
                ];
                foreach ($this->headings as $i => $heading) {
                    $letter = Coordinate::stringFromColumnIndex($i + 1);
                    $sheet->getColumnDimension($letter)->setWidth($widthMap[$heading] ?? 20);
                }

                // ── Data rows ─────────────────────────────────────────────
                $firstDataRow = 7; // row 6 is blank spacer after headings row
                $rowCount = $this->daybooks->count();
                $lastDataRow = $firstDataRow + $rowCount - 1;
                $totalRow = $lastDataRow + 1;

                // Right-align and format Debit (col E=5) and Credit (col F=6)
                foreach ([5, 6] as $colIndex) {
                    $letter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    $sheet->getStyle("{$letter}{$firstDataRow}:{$letter}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                // ── Italic style for narration rows ──────────────────────────
                for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                    $cellValue = $sheet->getCell("D{$row}")->getValue();
                    if ($cellValue && str_starts_with((string) $cellValue, 'Narration :')) {
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")
                            ->getFont()
                            ->setItalic(true);
                        $sheet->getStyle("A{$row}:{$lastColumnLetter}{$row}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                // ── Totals row ────────────────────────────────────────────
                $debitLetter = Coordinate::stringFromColumnIndex(5); // E
                $creditLetter = Coordinate::stringFromColumnIndex(6); // F
                $labelLetter = Coordinate::stringFromColumnIndex(4); // D
    
                $totalDebit = $this->daybooks->whereNull('row_type')->sum('debit');
                $totalCredit = $this->daybooks->whereNull('row_type')->sum('credit');

                $sheet->setCellValue("{$labelLetter}{$totalRow}", 'Total:');
                $sheet->getStyle("{$labelLetter}{$totalRow}")->getFont()->setBold(true);

                $sheet->setCellValueExplicit("{$debitLetter}{$totalRow}", $totalDebit, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("{$creditLetter}{$totalRow}", $totalCredit, DataType::TYPE_NUMERIC);

                $sheet->getStyle("{$debitLetter}{$totalRow}:{$creditLetter}{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '###0.00'],
                ]);
            },
        ];
    }
}
