<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Gstr1DetailExport implements FromView, WithEvents, WithTitle
{
    protected object $company;
    protected string $section;
    protected array $data;
    protected ?string $datePeriod;
    protected ?string $hsnCode;
    protected int $totalColumn = 0;

    public function __construct(object $company, string $section, array $data, ?string $datePeriod = null, ?string $hsnCode = null)
    {
        $this->company    = $company;
        $this->section    = $section;
        $this->data       = $data;
        $this->datePeriod = $datePeriod;
        $this->hsnCode    = $hsnCode;
    }

    public function title(): string
    {
        $map = [
            'b2b' => 'B2B',
            'b2cl' => 'B2CL',
            'b2cs' => 'B2CS',
            'cdnr' => 'CDNR',
            'cdnu' => 'CDNUR',
            'exports' => 'EXPORTS',
            'nil_rated' => 'NIL RATED',
            'hsn_summary' => 'HSN',
            'hsn_detail' => 'HSN Detail',
        ];
        return $map[$this->section] ?? strtoupper($this->section);
    }

    public function view(): View
    {
        $config = $this->getSectionConfig();
        $headings = $config['headings'];
        $this->totalColumn = count($headings);

        $rows = [];
        $totals = $config['totals'];

        foreach ($this->data as $index => $row) {
            $formattedRow = ($config['rowFormatter'])($row, $index + 1);
            $rows[] = $formattedRow;

            // Accumulate totals
            if (isset($config['accumulateTotals'])) {
                ($config['accumulateTotals'])($row, $totals);
            }
        }

        return view('company.pages.gst.gstr1.detail_export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => $config['title'],
            'datePeriod'  => $this->datePeriod,
            'headings'    => $headings,
            'rows'        => $rows,
            'totals'      => $totals,
            'totalLabelCol' => $config['totalLabelCol'] ?? 1,
            'alignments'  => $config['alignments'],
        ]);
    }

    private function getSectionConfig(): array
    {
        $fmt = fn($v) => is_numeric($v) ? (float)$v : 0;
        $fmtRate = fn($v) => (float)$v > 0 ? (float)$v . '%' : '—';
        $fmtDate = fn($d) => !empty($d) ? format_date($d) : '—';

        switch ($this->section) {
            case 'b2b':
                return [
                    'title' => 'GSTR-1 — B2B Invoices (4A, 4B, 4C, 6B, 6C)',
                    'headings' => ['#', 'Party Name', 'GSTIN', 'Invoice / Note No', 'Date', 'Place of Supply', 'RC', 'Taxable Amount', 'CGST %', 'CGST Amount', 'SGST %', 'SGST Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'L', 'C', 'L', 'C', 'R', 'C', 'R', 'C', 'R', 'C', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 6,
                    'totals' => ['taxable_amount' => 0, 'cgst_amount' => 0, 'sgst_amount' => 0, 'igst_amount' => 0, 'cess_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['cgst_amount']     += $fmt($r['cgst_amount'] ?? 0);
                        $tot['sgst_amount']     += $fmt($r['sgst_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['cess_amount']     += $fmt($r['cess_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['account_name'] ?? '—',
                        $r['gstin'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $r['place_of_supply'] ?? '—',
                        !empty($r['reverse_charge']) ? 'Y' : 'N',
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['cgst_rate'] ?? 0),
                        $fmt($r['cgst_amount'] ?? 0),
                        $fmtRate($r['sgst_rate'] ?? 0),
                        $fmt($r['sgst_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['cess_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'b2cl':
                return [
                    'title' => 'GSTR-1 — B2C Large Invoices (5A, 5B)',
                    'headings' => ['#', 'Party Name', 'Invoice / Note No', 'Date', 'Place of Supply', 'Taxable Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'C', 'L', 'R', 'C', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 5,
                    'totals' => ['taxable_amount' => 0, 'igst_amount' => 0, 'cess_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['cess_amount']     += $fmt($r['cess_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['account_name'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $r['place_of_supply'] ?? '—',
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['cess_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'b2cs':
                return [
                    'title' => 'GSTR-1 — B2C Small Details (7)',
                    'headings' => ['#', 'Place of Supply', 'Invoice / Note No', 'Date', 'Taxable Amount', 'CGST %', 'CGST Amount', 'SGST %', 'SGST Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'C', 'R', 'C', 'R', 'C', 'R', 'C', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 4,
                    'totals' => ['taxable_amount' => 0, 'cgst_amount' => 0, 'sgst_amount' => 0, 'igst_amount' => 0, 'cess_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['cgst_amount']     += $fmt($r['cgst_amount'] ?? 0);
                        $tot['sgst_amount']     += $fmt($r['sgst_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['cess_amount']     += $fmt($r['cess_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['place_of_supply'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['cgst_rate'] ?? 0),
                        $fmt($r['cgst_amount'] ?? 0),
                        $fmtRate($r['sgst_rate'] ?? 0),
                        $fmt($r['sgst_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['cess_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'cdnr':
                return [
                    'title' => 'GSTR-1 — Credit/Debit Notes (Registered) (9B)',
                    'headings' => ['#', 'Party Name', 'GSTIN', 'Invoice / Note No', 'Date', 'Place of Supply', 'RC', 'Taxable Amount', 'CGST %', 'CGST Amount', 'SGST %', 'SGST Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'L', 'C', 'L', 'C', 'R', 'C', 'R', 'C', 'R', 'C', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 6,
                    'totals' => ['taxable_amount' => 0, 'cgst_amount' => 0, 'sgst_amount' => 0, 'igst_amount' => 0, 'cess_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['cgst_amount']     += $fmt($r['cgst_amount'] ?? 0);
                        $tot['sgst_amount']     += $fmt($r['sgst_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['cess_amount']     += $fmt($r['cess_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['account_name'] ?? '—',
                        $r['gstin'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $r['place_of_supply'] ?? '—',
                        !empty($r['reverse_charge']) ? 'Y' : 'N',
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['cgst_rate'] ?? 0),
                        $fmt($r['cgst_amount'] ?? 0),
                        $fmtRate($r['sgst_rate'] ?? 0),
                        $fmt($r['sgst_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['cess_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'cdnu':
                return [
                    'title' => 'GSTR-1 — Credit/Debit Notes (Unregistered) (9B)',
                    'headings' => ['#', 'Party Name', 'Invoice / Note No', 'Date', 'Place of Supply', 'Taxable Amount', 'CGST %', 'CGST Amount', 'SGST %', 'SGST Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'C', 'L', 'R', 'C', 'R', 'C', 'R', 'C', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 5,
                    'totals' => ['taxable_amount' => 0, 'cgst_amount' => 0, 'sgst_amount' => 0, 'igst_amount' => 0, 'cess_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['cgst_amount']     += $fmt($r['cgst_amount'] ?? 0);
                        $tot['sgst_amount']     += $fmt($r['sgst_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['cess_amount']     += $fmt($r['cess_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['account_name'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $r['place_of_supply'] ?? '—',
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['cgst_rate'] ?? 0),
                        $fmt($r['cgst_amount'] ?? 0),
                        $fmtRate($r['sgst_rate'] ?? 0),
                        $fmt($r['sgst_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['cess_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'exports':
                return [
                    'title' => 'GSTR-1 — Exports Invoices (6A)',
                    'headings' => ['#', 'Party Name', 'Invoice / Note No', 'Date', 'Taxable Amount', 'IGST %', 'IGST Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'C', 'R', 'C', 'R', 'R', 'R'],
                    'totalLabelCol' => 4,
                    'totals' => ['taxable_amount' => 0, 'igst_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['account_name'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'nil_rated':
                return [
                    'title' => 'GSTR-1 — Nil Rated, Exempted and Non-GST (8)',
                    'headings' => ['#', 'Party Name', 'Invoice / Note No', 'Date', 'Place of Supply', 'Taxable Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'C', 'L', 'R', 'R'],
                    'totalLabelCol' => 5,
                    'totals' => ['taxable_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['taxable_amount'] += $fmt($r['taxable_amount'] ?? 0);
                        $tot['invoice_value'] += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['account_name'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $r['place_of_supply'] ?? '—',
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'hsn_detail':
                $hsnTitle = 'GSTR-1 — HSN Details' . ($this->hsnCode ? " (HSN: {$this->hsnCode})" : '');
                return [
                    'title' => $hsnTitle,
                    'headings' => ['#', 'HSN Code', 'Item Name', 'UQC', 'Qty', 'Party Name', 'Invoice / Note No', 'Date', 'Taxable Amount', 'CGST %', 'CGST Amount', 'SGST %', 'SGST Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                    'alignments' => ['C', 'L', 'L', 'C', 'R', 'L', 'L', 'C', 'R', 'C', 'R', 'C', 'R', 'C', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 4,
                    'totals' => ['qty' => 0, 'taxable_amount' => 0, 'cgst_amount' => 0, 'sgst_amount' => 0, 'igst_amount' => 0, 'cess_amount' => 0, 'total_tax_amount' => 0, 'invoice_value' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['qty']             += $fmt($r['qty'] ?? 0);
                        $tot['taxable_amount']   += $fmt($r['taxable_amount'] ?? 0);
                        $tot['cgst_amount']     += $fmt($r['cgst_amount'] ?? 0);
                        $tot['sgst_amount']     += $fmt($r['sgst_amount'] ?? 0);
                        $tot['igst_amount']     += $fmt($r['igst_amount'] ?? 0);
                        $tot['cess_amount']     += $fmt($r['cess_amount'] ?? 0);
                        $tot['total_tax_amount'] += $fmt($r['total_tax_amount'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['hsn_code'] ?? '—',
                        $r['item_name'] ?? '—',
                        $r['uqc'] ?? '—',
                        $fmt($r['qty'] ?? 0),
                        $r['account_name'] ?? '—',
                        $r['invoice_no'] ?? '—',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmtRate($r['cgst_rate'] ?? 0),
                        $fmt($r['cgst_amount'] ?? 0),
                        $fmtRate($r['sgst_rate'] ?? 0),
                        $fmt($r['sgst_amount'] ?? 0),
                        $fmtRate($r['igst_rate'] ?? 0),
                        $fmt($r['igst_amount'] ?? 0),
                        $fmt($r['cess_amount'] ?? 0),
                        $fmt($r['total_tax_amount'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                    ]
                ];

            case 'hsn_summary':
            default:
                return [
                    'title' => 'GSTR-1 — HSN-wise Summary of Outward Supplies',
                    'headings' => ['#', 'HSN Code', 'UQC', 'Total Qty', 'Total Value', 'Taxable Value', 'Integrated Tax', 'Central Tax', 'State/UT Tax', 'Cess'],
                    'alignments' => ['C', 'L', 'C', 'R', 'R', 'R', 'R', 'R', 'R', 'R'],
                    'totalLabelCol' => 3,
                    'totals' => ['total_qty' => 0, 'invoice_value' => 0, 'taxable_amount' => 0, 'igst' => 0, 'cgst' => 0, 'sgst' => 0, 'cess' => 0],
                    'accumulateTotals' => function($r, &$tot) use ($fmt) {
                        $tot['total_qty']       += $fmt($r['total_qty'] ?? 0);
                        $tot['invoice_value']   += $fmt($r['invoice_value'] ?? 0);
                        $tot['taxable_amount']  += $fmt($r['taxable_amount'] ?? 0);
                        $tot['igst']            += $fmt($r['igst'] ?? 0);
                        $tot['cgst']            += $fmt($r['cgst'] ?? 0);
                        $tot['sgst']            += $fmt($r['sgst'] ?? 0);
                        $tot['cess']            += $fmt($r['cess'] ?? 0);
                    },
                    'rowFormatter' => fn($r, $i) => [
                        $i,
                        $r['hsn_code'] ?? '—',
                        $r['uqc'] ?? '—',
                        $fmt($r['total_qty'] ?? 0),
                        $fmt($r['invoice_value'] ?? 0),
                        $fmt($r['taxable_amount'] ?? 0),
                        $fmt($r['igst'] ?? 0),
                        $fmt($r['cgst'] ?? 0),
                        $fmt($r['sgst'] ?? 0),
                        $fmt($r['cess'] ?? 0),
                    ]
                ];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalColumn = $this->totalColumn ?: 10;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn);

                // ── Header rows (1-4) ──────────────────────────────────
                foreach ([1, 2, 3, 4] as $row) {
                    $sheet->mergeCells("A{$row}:{$lastColumnLetter}{$row}");
                }

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'name' => 'Arial, Helvetica'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(16);

                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'name' => 'Arial, Helvetica'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(20);

                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(16);

                // ── Headings row (row 5) ──────────────────────────────────
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial, Helvetica'],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                    ],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(20);

                // Auto/custom widths & column alignments
                for ($col = 1; $col <= $totalColumn; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }

                $highestRow = $sheet->getHighestRow();

                // Apply borders and font to data table
                $sheet->getStyle("A5:{$lastColumnLetter}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']],
                    ],
                ]);

                // Style the Total row (last row)
                $sheet->getStyle("A{$highestRow}:{$lastColumnLetter}{$highestRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'name' => 'Arial, Helvetica'],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
                    ],
                ]);
                $sheet->getRowDimension($highestRow)->setRowHeight(20);
            },
        ];
    }
}
