<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Gstr3bExport implements FromView, WithEvents
{
    protected object $company;
    protected array $data;
    protected ?string $datePeriod;

    public function __construct(object $company, array $data, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->data = $data;
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $data = $this->data;
        $fmt = function($val) {
            if ($val === '' || $val === null || $val === '-') return '';
            if (is_numeric($val)) return formatIndianNumber((float)$val, 2);
            return $val;
        };

        $tables = [];

        // ── Table 3.1 ──
        $t31 = ['taxable_amount' => 0, 'igst' => 0, 'cgst' => 0, 'sgst' => 0, 'cess' => 0];
        foreach (['sec_3_1_a', 'sec_3_1_b', 'sec_3_1_c', 'sec_3_1_d', 'sec_3_1_e'] as $k) {
            if (!empty($data[$k])) {
                $t31['taxable_amount'] += (float) ($data[$k]['taxable_amount'] ?? 0);
                $t31['igst'] += (float) ($data[$k]['igst'] ?? 0);
                $t31['cgst'] += (float) ($data[$k]['cgst'] ?? 0);
                $t31['sgst'] += (float) ($data[$k]['sgst'] ?? 0);
                $t31['cess'] += (float) ($data[$k]['cess'] ?? 0);
            }
        }

        $tables['table_3_1'] = [
            ['label' => '(a) Outward txbl. supplies (other than zero rated, nil rated and exempted)', 'taxable_amount' => $fmt($data['sec_3_1_a']['taxable_amount'] ?? ''), 'igst' => $fmt($data['sec_3_1_a']['igst'] ?? ''), 'cgst' => $fmt($data['sec_3_1_a']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_3_1_a']['sgst'] ?? ''), 'cess' => $fmt($data['sec_3_1_a']['cess'] ?? '')],
            ['label' => '(b) Outward taxable supplies (zero rated)', 'taxable_amount' => $fmt($data['sec_3_1_b']['taxable_amount'] ?? ''), 'igst' => $fmt($data['sec_3_1_b']['igst'] ?? ''), 'cgst' => $fmt($data['sec_3_1_b']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_3_1_b']['sgst'] ?? ''), 'cess' => $fmt($data['sec_3_1_b']['cess'] ?? '')],
            ['label' => '(c) Other outward supp. (Nil rated, exempt)', 'taxable_amount' => $fmt($data['sec_3_1_c']['taxable_amount'] ?? ''), 'igst' => $fmt($data['sec_3_1_c']['igst'] ?? ''), 'cgst' => $fmt($data['sec_3_1_c']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_3_1_c']['sgst'] ?? ''), 'cess' => $fmt($data['sec_3_1_c']['cess'] ?? '')],
            ['label' => '(d) Inward supp. (liable to Rev. charge)', 'taxable_amount' => $fmt($data['sec_3_1_d']['taxable_amount'] ?? ''), 'igst' => $fmt($data['sec_3_1_d']['igst'] ?? ''), 'cgst' => $fmt($data['sec_3_1_d']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_3_1_d']['sgst'] ?? ''), 'cess' => $fmt($data['sec_3_1_d']['cess'] ?? '')],
            ['label' => '(e) Non-GST outward supplies', 'taxable_amount' => $fmt($data['sec_3_1_e']['taxable_amount'] ?? ''), 'igst' => $fmt($data['sec_3_1_e']['igst'] ?? ''), 'cgst' => $fmt($data['sec_3_1_e']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_3_1_e']['sgst'] ?? ''), 'cess' => $fmt($data['sec_3_1_e']['cess'] ?? '')],
            ['label' => 'Total', 'taxable_amount' => $fmt($t31['taxable_amount']), 'igst' => $fmt($t31['igst']), 'cgst' => $fmt($t31['cgst']), 'sgst' => $fmt($t31['sgst']), 'cess' => $fmt($t31['cess']), '_isHighlight' => true],
        ];

        // ── Table 3.1.1 ──
        $tables['table_3_1_1'] = [
            ['label' => '(i) Taxable supplies on which E-Commerce operator pays tax u/s 9(5) [To be furnished by the E-Commerce operator]', 'taxable_amount' => '', 'igst' => '', 'cgst' => '', 'sgst' => '', 'cess' => ''],
            ['label' => '(ii) Taxable supplies made by the registered person through E-Commerce operator, on which E-Commerce operator is required to pay tax u/s 9(5) [To be furnished by registered person making supplies through E-Commerce operator]', 'taxable_amount' => '', 'igst' => '', 'cgst' => '', 'sgst' => '', 'cess' => ''],
        ];

        // ── Table 3.2 ──
        $t32_rows = [];
        $t32_rows[] = ['label' => 'Supplies made to UnReg. Persons', '_isLabel' => true];
        $t32 = ['taxable_amount' => 0, 'igst' => 0];
        if (!empty($data['sec_3_2'])) {
            foreach ($data['sec_3_2'] as $r) {
                $t32_rows[] = ['label' => '    ' . $r['place_of_supply'], 'taxable_amount' => $fmt($r['taxable_amount']), 'igst' => $fmt($r['igst_amount'])];
                $t32['taxable_amount'] += (float) $r['taxable_amount'];
                $t32['igst'] += (float) $r['igst_amount'];
            }
        }
        $t32_rows[] = ['label' => 'Total', 'taxable_amount' => $fmt($t32['taxable_amount']), 'igst' => $fmt($t32['igst']), '_isHighlight' => true];
        $t32_rows[] = ['label' => 'Supp. made to Composition Dealers', '_isLabel' => true];
        $t32_rows[] = ['label' => 'Total', '_isHighlight' => true];
        $t32_rows[] = ['label' => 'Supplies made to UIN holder', '_isLabel' => true];
        $t32_rows[] = ['label' => 'Total', '_isHighlight' => true];
        $tables['table_3_2'] = $t32_rows;

        // ── Table 4 ──
        $tables['table_4'] = [
            ['label' => '(A) ITC Available (whether in full or part)', '_isLabel' => true],
            ['label' => '    (1) Import of goods', 'igst' => $fmt($data['sec_4_a_1']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_a_1']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_a_1']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_a_1']['cess'] ?? '')],
            ['label' => '    (2) Import of services', 'igst' => $fmt($data['sec_4_a_2']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_a_2']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_a_2']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_a_2']['cess'] ?? '')],
            ['label' => '    (3) Inward supplies liable to reverse (other than 1 & 2 above)', 'igst' => $fmt($data['sec_4_a_3']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_a_3']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_a_3']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_a_3']['cess'] ?? '')],
            ['label' => '    (4) Inward supplies from ISD', 'igst' => $fmt($data['sec_4_a_4']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_a_4']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_a_4']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_a_4']['cess'] ?? '')],
            ['label' => '    (5) All other ITC', 'igst' => $fmt($data['sec_4_a_5']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_a_5']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_a_5']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_a_5']['cess'] ?? '')],
            ['label' => '(B) ITC Reversed', '_isLabel' => true],
            ['label' => '    (1) As per rules 38,42 & 43 of CGST Rules and section 17(5)', 'igst' => $fmt($data['sec_4_b_1']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_b_1']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_b_1']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_b_1']['cess'] ?? '')],
            ['label' => '    (2) Others', 'igst' => $fmt($data['sec_4_b_2']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_b_2']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_b_2']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_b_2']['cess'] ?? '')],
            ['label' => '(C) Net ITC Available(A) - (B)', 'igst' => $fmt($data['sec_4_c']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_c']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_c']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_c']['cess'] ?? ''), '_isHighlight' => true],
            ['label' => '(D) Other Details', '_isLabel' => true],
            ['label' => '    (1) ITC reclaimed which was reversed under Table 4(B)(2) in earlier tax period', 'igst' => $fmt($data['sec_4_d_1']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_d_1']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_d_1']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_d_1']['cess'] ?? '')],
            ['label' => '    (2) Ineligible ITC under section 16(4) & ITC restricted due to PoS rules', 'igst' => $fmt($data['sec_4_d_2']['igst'] ?? ''), 'cgst' => $fmt($data['sec_4_d_2']['cgst'] ?? ''), 'sgst' => $fmt($data['sec_4_d_2']['sgst'] ?? ''), 'cess' => $fmt($data['sec_4_d_2']['cess'] ?? '')],
        ];

        // ── Table 5 ──
        $tables['table_5'] = [
            ['label' => 'From a supplier under composition scheme, Exempt and Nil rated supply', 'inter' => $fmt($data['sec_5_nil']['inter_taxable_amount'] ?? ''), 'intra' => $fmt($data['sec_5_nil']['intra_taxable_amount'] ?? '')],
            ['label' => 'Non GST supply', 'inter' => $fmt($data['sec_5_nongst']['inter_taxable_amount'] ?? ''), 'intra' => $fmt($data['sec_5_nongst']['intra_taxable_amount'] ?? '')],
        ];

        // ── Table 6.1 ──
        $tables['table_6_1'] = [
            ['label' => 'Other than Reverse Charge', '_isLabel' => true],
            ['label' => 'Integrated Tax', 'payable' => $fmt($data['total_liability']['igst'] ?? ''), 'itc_igst' => $fmt($data['paid_through_itc']['igst'] ?? ''), 'cash' => $fmt($data['tax_payable_cash']['igst'] ?? ''), '_dash1' => false, '_dash2' => false],
            ['label' => 'Central Tax', 'payable' => $fmt($data['total_liability']['cgst'] ?? ''), 'itc_cgst' => $fmt($data['paid_through_itc']['cgst'] ?? ''), 'cash' => $fmt($data['tax_payable_cash']['cgst'] ?? ''), '_dash1' => false, '_dash2' => true],
            ['label' => 'State/UT Tax', 'payable' => $fmt($data['total_liability']['sgst'] ?? ''), 'itc_sgst' => $fmt($data['paid_through_itc']['sgst'] ?? ''), 'cash' => $fmt($data['tax_payable_cash']['sgst'] ?? ''), '_dash1' => true, '_dash2' => false],
            ['label' => 'Cess', 'payable' => $fmt($data['total_liability']['cess'] ?? ''), 'itc_cess' => $fmt($data['paid_through_itc']['cess'] ?? ''), 'cash' => $fmt($data['tax_payable_cash']['cess'] ?? ''), '_dash1' => false, '_dash2' => false],
            ['label' => 'Reverse Charge', '_isLabel' => true],
            ['label' => 'Integrated Tax'],
            ['label' => 'Central Tax'],
            ['label' => 'State/UT Tax'],
            ['label' => 'Cess'],
        ];

        // ── Table 6.2 ──
        $tables['table_6_2'] = [
            ['label' => 'TDS'],
            ['label' => 'TCS'],
        ];

        return view('company.pages.gst.gstr3b.export', [
            'companyName' => $this->company->print_name ?? $this->company->name ?? 'Company',
            'gstNumber'   => $this->company->gst_number ?? '',
            'datePeriod'  => $this->datePeriod,
            'data'        => $tables,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                
                // Professional column width mapping (10 columns A-J)
                $sheet->getColumnDimension('A')->setWidth(70);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(18);
                $sheet->getColumnDimension('J')->setWidth(18);

                // Prevent overlap by wrapping long text (like in Table 3.1.1) and top-aligning
                $sheet->getStyle("A1:J{$lastRow}")->getAlignment()
                      ->setWrapText(true)
                      ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

                $sheet->setShowGridLines(false);

                // Apply Cell-Wise Borders and Professional Colors
                for ($row = 6; $row <= $lastRow; $row++) {
                    $cellA = (string) $sheet->getCell("A{$row}")->getValue();
                    $cellB = (string) $sheet->getCell("B{$row}")->getValue();

                    if (empty($cellA) && empty($cellB)) {
                        continue; // Skip spacer rows completely
                    }

                    // Section Headers
                    if (preg_match('/^(3\.|4\.|5\.|6\.)/', $cellA) && empty($cellB)) {
                        $sheet->mergeCells("A{$row}:J{$row}");
                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF000000']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE8F0FE'], // Professional Light Blue
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']],
                            ]
                        ]);
                    } 
                    // Column Headers
                    elseif (in_array($cellA, ['Nature of Supplier', 'Description', 'Place of Supply(State/UT)', 'Details', 'Nature of supplies', 'IGST'])) {
                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => 'FF000000']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFF3F4F6'], // Professional Light Gray
                            ],
                            'borders' => [
                                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']],
                            ]
                        ]);
                    }
                    // Regular Data Rows
                    else {
                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                            'borders' => [
                                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']],
                            ]
                        ]);
                        
                        // Highlight Total rows
                        if ($cellA === 'Total' || str_contains($cellA, 'Net ITC Available')) {
                            $sheet->getStyle("A{$row}:J{$row}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFF9FAFB'],
                                ],
                            ]);
                        }
                    }
                }
            },
        ];
    }
}
