<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Gstr3bDetailExport implements FromView, WithEvents
{
    protected object $company;
    protected string $section;
    protected array $data;
    protected ?string $datePeriod;
    protected int $totalColumn = 0;

    public function __construct(object $company, string $section, array $data, ?string $datePeriod = null)
    {
        $this->company    = $company;
        $this->section    = $section;
        $this->data       = $data;
        $this->datePeriod = $datePeriod;
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

        return view('company.pages.gst.gstr3b.detail_export', [
            'companyName'   => $this->company->print_name ?? $this->company->name,
            'gstNumber'     => $this->company->gst_number,
            'reportTitle'   => $config['title'],
            'datePeriod'    => $this->datePeriod,
            'headings'      => $headings,
            'rows'          => $rows,
            'totals'        => $totals,
            'totalLabelCol' => $config['totalLabelCol'] ?? 1,
            'alignments'    => $config['alignments'],
        ]);
    }

    private function getSectionConfig(): array
    {
        $fmt = fn($v) => is_numeric($v) ? (float)$v : 0;
        $fmtRate = fn($v) => (float)$v > 0 ? (float)$v . '%' : '—';
        $fmtDate = fn($d) => !empty($d) ? format_date($d) : '—';

        switch ($this->section) {
            case '3.1_a':
            case '3.1_b':
            case '3.1_d':
            case '4_a_3':
            case '4_a_5':
            case '4_b_2':
            default:
                $sectionTitles = [
                    '3.1_a' => 'GSTR-3B — 3.1(a) Outward Taxable Supplies',
                    '3.1_b' => 'GSTR-3B — 3.1(b) Outward Taxable Supplies (Zero Rated)',
                    '3.1_c' => 'GSTR-3B — 3.1(c) Other Outward Supplies (Nil Rated / Exempt)',
                    '3.1_d' => 'GSTR-3B — 3.1(d) Inward Supplies Liable to Reverse Charge',
                    '3.2'   => 'GSTR-3B — 3.2 Inter-State Supplies to Unregistered Persons',
                    '4_a_1' => 'GSTR-3B — 4(A)(1) Import of Goods',
                    '4_a_2' => 'GSTR-3B — 4(A)(2) Import of Services',
                    '4_a_3' => 'GSTR-3B — 4(A)(3) Inward Supplies Liable to Reverse Charge',
                    '4_a_5' => 'GSTR-3B — 4(A)(5) All Other ITC (B2B Purchases)',
                    '4_b_2' => 'GSTR-3B — 4(B)(2) ITC Reversed - Others (Debit Notes / Returns)',
                    '5_nil' => 'GSTR-3B — 5. Inward Nil Rated / Exempted Supplies',
                ];

                if ($this->section === '3.1_c' || $this->section === '5_nil') {
                    return [
                        'title' => $sectionTitles[$this->section] ?? 'GSTR-3B — Nil Rated / Exempt Supplies',
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
                }

                if ($this->section === '4_a_1' || $this->section === '4_a_2') {
                    return [
                        'title' => $sectionTitles[$this->section] ?? 'GSTR-3B — Import Supplies',
                        'headings' => ['#', 'Party Name', 'Invoice / Note No', 'Date', 'Taxable Amount', 'IGST %', 'IGST Amount', 'CESS Amount', 'Total Tax Amount', 'Invoice Value'],
                        'alignments' => ['C', 'L', 'L', 'C', 'R', 'C', 'R', 'R', 'R', 'R'],
                        'totalLabelCol' => 4,
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
                            $fmt($r['taxable_amount'] ?? 0),
                            $fmtRate($r['igst_rate'] ?? 0),
                            $fmt($r['igst_amount'] ?? 0),
                            $fmt($r['cess_amount'] ?? 0),
                            $fmt($r['total_tax_amount'] ?? 0),
                            $fmt($r['invoice_value'] ?? 0),
                        ]
                    ];
                }

                return [
                    'title' => $sectionTitles[$this->section] ?? 'GSTR-3B — Section Detail',
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
