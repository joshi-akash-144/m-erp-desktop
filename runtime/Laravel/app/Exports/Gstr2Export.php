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
use PhpOffice\PhpSpreadsheet\Style\Border;

class Gstr2Export implements FromView, WithEvents
{
    protected object $company;
    protected array $data;
    protected ?string $datePeriod;
    protected int $totalColumn = 9;

    public function __construct(object $company, array $data, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->data = $data;
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $rows = [];
        $data = $this->data;

        $fmt = function($val) {
            if ($val === '' || $val === null || $val === '-') return $val;
            if (is_numeric($val) && $val == 0) return '0.00';
            if (is_numeric($val)) return formatIndianNumber((float)$val, 2);
            return $val;
        };

        $fmtInt = function($val) {
            if ($val === '' || $val === null || $val === '-') return $val;
            return (int)$val;
        };

        $buildSummaryRow = function($label, $row, $isText = false) use ($fmt, $fmtInt) {
            return [
                'section' => $label,
                'count' => $isText ? '-------' : $fmtInt($row['count'] ?? 0),
                'invoice_value' => $isText ? '-------' : $fmt($row['invoice_value'] ?? 0),
                'taxable_amount' => $fmt($row['taxable_amount'] ?? 0),
                'total_tax' => $fmt($row['total_tax'] ?? 0),
                'cgst' => $fmt($row['cgst'] ?? 0),
                'sgst' => $fmt($row['sgst'] ?? 0),
                'igst' => $fmt($row['igst'] ?? 0),
                'cess' => $fmt($row['cess'] ?? 0),
            ];
        };

        $buildSubRow = function($label, $value, $separator = '-') use ($fmt) {
            return [
                'section' => $label, 'count' => $separator, 'invoice_value' => $fmt($value), 'taxable_amount' => '',
                'total_tax' => '', 'cgst' => '', 'sgst' => '', 'igst' => '', 'cess' => ''
            ];
        };

        $rows[] = $buildSummaryRow('B2B Invoice - 3, 4A', $data['b2b'] ?? []);
        $rows[] = $buildSummaryRow('B2BUR Invoices - 4B', $data['b2bur'] ?? []);
        $rows[] = $buildSummaryRow('Credit/Debit Notes - 6C', $data['cdnr'] ?? []);
        $rows[] = $buildSummaryRow('Credit/Debit Notes Unregistered', $data['cdnu'] ?? []);
        $rows[] = $buildSummaryRow('Import of Goods/ Capitals', $data['import_goods'] ?? []);
        $rows[] = $buildSummaryRow('Import of Services - 4 C', $data['import_services'] ?? []);
        
        $nilTax = $data['nil_rated']['taxable_amount'] ?? 0;
        $rows[] = [ 'section' => 'Nil Rated Invoices - 7A, 7B', 'count' => $fmtInt($data['nil_rated']['count'] ?? 0), 'invoice_value' => '', 'taxable_amount' => $fmt($nilTax), 'total_tax' => '', 'cgst' => '', 'sgst' => '', 'igst' => '', 'cess' => '' ];

        // Aggregate HSN
        let:
        $hsnTotals = ['count' => 0, 'invoice_value' => 0, 'taxable_amount' => 0, 'total_tax' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0];
        $hsnList = $data['hsn_summary'] ?? [];
        if (!empty($hsnList)) {
            $hsnTotals['count'] = count($hsnList);
            foreach ($hsnList as $r) {
                $hsnTotals['invoice_value'] += (float)($r['invoice_value'] ?? 0);
                $hsnTotals['taxable_amount'] += (float)($r['taxable_amount'] ?? 0);
                $hsnTotals['total_tax'] += (float)($r['total_tax'] ?? 0);
                $hsnTotals['cgst'] += (float)($r['cgst'] ?? 0);
                $hsnTotals['sgst'] += (float)($r['sgst'] ?? 0);
                $hsnTotals['igst'] += (float)($r['igst'] ?? 0);
                $hsnTotals['cess'] += (float)($r['cess'] ?? 0);
            }
        }
        $rows[] = $buildSummaryRow('HSN-wise Summary of Inward Supplies', $hsnTotals);

        // ITC Summary
        $rows[] = ['section' => 'Total Input Tax Credit', 'count' => '-', 'invoice_value' => '', 'taxable_amount' => '', 'total_tax' => '', 'cgst' => '', 'sgst' => '', 'igst' => '', 'cess' => ''];
        $rows[] = $buildSubRow('CGST', $data['itc_summary']['cgst'] ?? 0);
        $rows[] = $buildSubRow('SGST', $data['itc_summary']['sgst'] ?? 0);
        $rows[] = $buildSubRow('IGST', $data['itc_summary']['igst'] ?? 0);
        $rows[] = $buildSubRow('CESS', $data['itc_summary']['cess'] ?? 0);
        $rows[] = $buildSubRow('Total', $data['itc_summary']['total'] ?? 0);

        return view('company.pages.gst.gstr2.export', [
            'companyName' => $this->company->print_name ?? $this->company->name ?? 'Company',
            'gstNumber'   => $this->company->gst_number ?? '',
            'datePeriod'  => $this->datePeriod,
            'rows'        => $rows,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumnLetter = Coordinate::stringFromColumnIndex($this->totalColumn); // = I

                // ── Header rows ──────────────────────────────────────────
                foreach ([1, 2, 3, 4] as $row) {
                    $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
                }
                
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                ]);

                // Increase row heights to fit larger fonts
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(18);
                $sheet->getRowDimension(3)->setRowHeight(20);
                $sheet->getRowDimension(4)->setRowHeight(16);

                // ── Headings row (row 5) ──────────────────────────────────
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman', 'underline' => true],
                    'borders' => [
                        'bottom' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                $sheet->getStyle("A5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("B5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C5:I5")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ── Column widths ─────────────────────────────────────────
                $widthMap = [
                    'A' => 45, // Section Name
                    'B' => 15, // No. of Records
                    'C' => 18, // Total Invoice Amt
                    'D' => 18, // Total Taxable Amt
                    'E' => 18, // Total Tax Liability
                    'F' => 18, // CGST
                    'G' => 18, // SGST
                    'H' => 18, // IGST
                    'I' => 15, // CESS
                ];
                
                foreach ($widthMap as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Apply global font to the rest of the document
                $sheet->getStyle("A5:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'size' => 11],
                ]);

                // Alignments & Number formatting for data rows
                for ($row = 5; $row <= $lastRow; $row++) {
                    $sectionName = (string) $sheet->getCell("A{$row}")->getValue();
                    $countVal = (string) $sheet->getCell("B{$row}")->getValue();

                    if ($countVal === '-') {
                        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                    } else {
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        for ($c = 3; $c <= $this->totalColumn; $c++) {
                            $colLetter = Coordinate::stringFromColumnIndex($c);
                            $cellVal = $sheet->getCell("{$colLetter}{$row}")->getValue();
                            if (is_numeric($cellVal)) {
                                $sheet->getStyle("{$colLetter}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                            }
                            $sheet->getStyle("{$colLetter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        }
                    }
                }
            },
        ];
    }
}
