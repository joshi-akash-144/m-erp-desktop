<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Gstr1Export implements FromView, WithEvents, WithTitle
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

    public function title(): string
    {
        return 'Summary';
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

        $buildEmptyRow = function($label, $count = 0) {
            return [
                'section' => $label, 'count' => $count, 'invoice_value' => '0.00', 'taxable_amount' => '0.00',
                'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0'
            ];
        };

        $buildSubRow = function($label, $value, $separator = ':') use ($fmt) {
            return [
                'section' => $label, 'count' => $separator, 'invoice_value' => $fmt($value), 'taxable_amount' => '',
                'total_tax' => '', 'cgst' => '', 'sgst' => '', 'igst' => '', 'cess' => ''
            ];
        };

        $rows[] = $buildSummaryRow('4A B2B Regular', $data['b2b'] ?? []);
        $rows[] = $buildEmptyRow('4B - B2B Reverse charge');
        $rows[] = $buildSummaryRow('5A - B2CL (Large)', $data['b2cl'] ?? []);
        $rows[] = $buildSummaryRow('6A - Exports (with/without payment)', $data['exports'] ?? []);
        $rows[] = $buildEmptyRow('EXPWP');
        $rows[] = $buildEmptyRow('EXPWOP');
        $rows[] = $buildEmptyRow('');
        $rows[] = $buildEmptyRow('6B - Supplies made to SEZ unit');
        $rows[] = $buildEmptyRow('SEZWP');
        $rows[] = $buildEmptyRow('SEZWOP');
        $rows[] = $buildEmptyRow('');
        $rows[] = $buildEmptyRow('6C - Deemed Exports (DE)');
        $rows[] = $buildSummaryRow('7 - B2CS (Others)', $data['b2cs'] ?? [], true); 
        
        $nilTax = $data['nil_rated']['taxable_amount'] ?? 0;
        $rows[] = [ 'section' => '8 - Nil rated, Exempted and Non GST', 'count' => $fmtInt($data['nil_rated']['count'] ?? 0), 'invoice_value' => '-------', 'taxable_amount' => $fmt($nilTax), 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = [ 'section' => 'Nil Rated', 'count' => '0', 'invoice_value' => '0.00', 'taxable_amount' => $fmt($nilTax), 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = [ 'section' => 'Exempted', 'count' => '0', 'invoice_value' => '0.00', 'taxable_amount' => '0.00', 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = [ 'section' => 'Non GST', 'count' => '0', 'invoice_value' => '0.00', 'taxable_amount' => '0.00', 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = $buildEmptyRow('');

        $rows[] = $buildSummaryRow('9B - Credit/Debit Notes (Registered) - CDNR', $data['cdnr'] ?? []);
        $rows[] = $buildSummaryRow('9B - Credit/Debit Notes (Unregistered) - CDNUR', $data['cdnu'] ?? []);
        $rows[] = $buildEmptyRow('11A(1), 11A(2) - Tax Liability (Advances Recieved)');
        $rows[] = $buildEmptyRow('11B(1), 11B(2) - Adjustment of Advances');

        // Aggregate HSN
        $hsnTotals = ['count' => 0, 'invoice_value' => 0, 'taxable_amount' => 0, 'total_tax' => 0, 'cgst' => 0, 'sgst' => 0, 'igst' => 0, 'cess' => 0];
        if (!empty($data['hsn_summary'])) {
            $hsnTotals['count'] = count($data['hsn_summary']);
            foreach ($data['hsn_summary'] as $r) {
                $hsnTotals['invoice_value'] += (float)($r['invoice_value'] ?? 0);
                $hsnTotals['taxable_amount'] += (float)($r['taxable_amount'] ?? 0);
                $hsnTotals['total_tax'] += (float)($r['total_tax'] ?? 0);
                $hsnTotals['cgst'] += (float)($r['cgst'] ?? 0);
                $hsnTotals['sgst'] += (float)($r['sgst'] ?? 0);
                $hsnTotals['igst'] += (float)($r['igst'] ?? 0);
                $hsnTotals['cess'] += (float)($r['cess'] ?? 0);
            }
        }
        $rows[] = $buildSummaryRow('12 - HSN-wise Summary of Outward Supplies', $hsnTotals);
        
        $docSummary = $data['doc_summary'] ?? [];
        $rows[] = [ 'section' => '13 - Documents Issued', 'count' => 0, 'invoice_value' => '0.00', 'taxable_amount' => '0.00', 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = $buildSubRow('Total Docs', $docSummary['total_docs'] ?? 0);
        $rows[] = $buildSubRow('Cancelled Docs', $docSummary['cancelled_docs'] ?? 0);
        $rows[] = $buildSubRow('Net Issued Docs', $docSummary['net_issued'] ?? 0);

        $rows[] = [ 'section' => '14 - Supplies made through E-Commerce Operators', 'count' => 0, 'invoice_value' => '-------', 'taxable_amount' => '0.00', 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = $buildEmptyRow('Liable to collect tax u/s 52');
        $rows[] = $buildEmptyRow('Liable to pay tax u/s 9(5)');
        $rows[] = $buildEmptyRow('');
        $rows[] = $buildEmptyRow('');

        $taxLiability = $data['tax_liability'] ?? [];
        $rows[] = [ 'section' => 'Total Tax Liability', 'count' => '-', 'invoice_value' => '0.00', 'taxable_amount' => '0.00', 'total_tax' => '0.00', 'cgst' => '0.00', 'sgst' => '0.00', 'igst' => '0', 'cess' => '0' ];
        $rows[] = $buildSubRow('CGST', $taxLiability['cgst'] ?? 0, '-');
        $rows[] = $buildSubRow('SGST', $taxLiability['sgst'] ?? 0, '-');
        $rows[] = $buildSubRow('IGST', $taxLiability['igst'] ?? 0, '-');
        $rows[] = $buildSubRow('Cess', $taxLiability['cess'] ?? 0, '-');

        return view('company.pages.gst.gstr1.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'GSTR1',
            'datePeriod' => $this->datePeriod,
            'rows' => $rows,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
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
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A5:{$lastColumnLetter}{$highestRow}")->applyFromArray([
                    'font' => ['name' => 'Times New Roman', 'size' => 11],
                ]);
            },
        ];
    }
}
