<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Gstr1OfflineSheetExport implements FromView, WithTitle, WithEvents, WithColumnFormatting
{
    protected string $section;
    protected array $data;

    public function __construct(string $section, array $data)
    {
        $this->section = $section;
        $this->data    = $data;
    }

    public function title(): string
    {
        $map = [
            'b2b'         => 'b2b,sez,de',
            'b2ba'        => 'b2ba',
            'b2cl'        => 'b2cl',
            'b2cla'       => 'b2cla',
            'b2cs'        => 'b2cs',
            'b2csa'       => 'b2csa',
            'cdnr'        => 'cdnr',
            'cdnra'       => 'cdnra',
            'cdnu'        => 'cdnur',
            'cdnura'      => 'cdnura',
            'exports'     => 'exp',
            'expa'        => 'expa',
            'at'          => 'at',
            'ata'         => 'ata',
            'atadj'       => 'atadj',
            'atadja'      => 'atadja',
            'nil_rated'   => 'exemp',
            'hsn_summary' => 'hsn(b2b)',
            'hsn_b2c'     => 'hsn(b2c)',
            'docs'        => 'docs',
            'eco'         => 'eco',
            'ecoa'        => 'ecoa',
        ];

        return $map[$this->section] ?? $this->section;
    }

    public function view(): View
    {
        $config = $this->getSectionConfig();

        // If the config provides a pre-built rows generator (e.g. nil_rated aggregation), use it directly
        if (isset($config['rowsGenerator'])) {
            $rows = ($config['rowsGenerator'])($this->data);
        } else {
            $rows = [];
            foreach ($this->data as $item) {
                $rows[] = ($config['rowFormatter'])($item);
            }
        }

        // Calculate summary values
        $summaryValues = [];
        if (isset($config['summaryCalculator'])) {
            $summaryValues = ($config['summaryCalculator'])($this->data, $rows);
        }

        return view('company.pages.gst.gstr1.offline_sheet', [
            'summaryTitle'   => $config['summaryTitle'],
            'summaryHeaders' => $config['summaryHeaders'],
            'summaryValues'  => $summaryValues,
            'headers'        => $config['headers'],
            'rows'           => $rows,
        ]);
    }

    public function columnFormats(): array
    {
        $config = $this->getSectionConfig();
        return $config['columnFormats'] ?? [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style for the Summary Header (Row 1)
                $sheet->getStyle('A1')->getFont()->setBold(true);

                // Style for Summary Headers (Row 2) and Data Headers (Row 4)
                $sheet->getStyle('A2:Z2')->getFont()->setBold(true);
                $sheet->getStyle('A4:Z4')->getFont()->setBold(true);

                // Set background color for Data Headers
                $sheet->getStyle('A4:Z4')->getFill()
                      ->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFD9D9D9');

                // Set column widths: use max of summary header and data header text length
                $config         = $this->getSectionConfig();
                $headers        = $config['headers']        ?? [];
                $summaryHeaders = $config['summaryHeaders'] ?? [];
                foreach ($headers as $index => $header) {
                    $summaryHeader = $summaryHeaders[$index] ?? '';
                    $col   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $width = max(14, mb_strlen($header) * 1.15, mb_strlen($summaryHeader) * 1.15);
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            }
        ];
    }

    private function getSectionConfig(): array
    {
        $fmtDate = fn($d) => !empty($d) ? format_date($d, 'd-M-y') : '';
        $fmtNum  = fn($v) => is_numeric($v) ? (float)round($v, 2) : 0.00;
        $fmtRate = fn($v) => is_numeric($v) ? (float)round($v, 2) : 0.00;

        switch ($this->section) {
            case 'b2b':
                return [
                    'summaryTitle'   => 'Summary For B2B(4)',
                    'summaryHeaders' => ['No. of Recipients', 'No. of Invoices', '', '', '', '', '', '', '', '', '', 'Total Taxable Value', 'Total Cess'],
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) {
                        $recipients = count(array_unique(array_column($data, 'gstin')));
                        $invoices   = count(array_unique(array_column($data, 'invoice_no')));
                        $taxable    = array_sum(array_column($data, 'taxable_amount'));
                        $cess       = array_sum(array_column($data, 'cess_amount'));
                        return [
                            // 0 => $recipients, 
                            // 1 => $invoices, 
                            // 11 => $fmtNum($taxable), 
                            // 12 => $fmtNum($cess)
                        ];
                    },
                    'headers' => [
                        'GSTIN/UIN of Recipient', 'Receiver Name', 'Invoice Number', 'Invoice date', 
                        'Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 
                        'Invoice Type', 'E-Commerce GSTIN', 'Rate', 'Taxable Value', 'Cess Amount'
                    ],
                    'rowFormatter' => fn($r) => [
                        $r['gstin'] ?? '',
                        $r['account_name'] ?? '',
                        $r['invoice_no'] ?? '',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $fmtNum($r['invoice_value'] ?? 0),
                        $r['place_of_supply'] ?? '',
                        !empty($r['reverse_charge']) ? 'Y' : 'N',
                        '', // Applicable % of Tax Rate
                        'Regular B2B', // Invoice Type
                        '', // E-Commerce GSTIN
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount'] ?? 0),
                        $fmtNum($r['cess_amount'] ?? 0),
                    ],
                    'columnFormats' => [
                        'E' => NumberFormat::FORMAT_NUMBER_00,
                        'K' => NumberFormat::FORMAT_NUMBER_00,
                        'L' => NumberFormat::FORMAT_NUMBER_00,
                        'M' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'b2cl':
                return [
                    'summaryTitle'   => 'Summary For B2CL(5)',
                    'summaryHeaders' => ['No. of Invoices', '', '', '', '', '', 'Total Taxable Value', 'Total Cess', ''],
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) {
                        $invoices = count(array_unique(array_column($data, 'invoice_no')));
                        $taxable  = array_sum(array_column($data, 'taxable_amount'));
                        $cess     = array_sum(array_column($data, 'cess_amount'));
                        return [
                            // 0 => $invoices, 
                            // 6 => $fmtNum($taxable), 
                            // 7 => $fmtNum($cess)
                        ];
                    },
                    'headers' => [
                        'Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 
                        'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN'
                    ],
                    'rowFormatter' => fn($r) => [
                        $r['invoice_no'] ?? '',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $fmtNum($r['invoice_value'] ?? 0),
                        $r['place_of_supply'] ?? '',
                        '', // Applicable % of Tax Rate
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount'] ?? 0),
                        $fmtNum($r['cess_amount'] ?? 0),
                        '', // E-Commerce GSTIN
                    ],
                    'columnFormats' => [
                        'C' => NumberFormat::FORMAT_NUMBER_00,
                        'F' => NumberFormat::FORMAT_NUMBER_00,
                        'G' => NumberFormat::FORMAT_NUMBER_00,
                        'H' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'b2cs':
                return [
                    'summaryTitle'   => 'Summary For B2CS(7)',
                    'summaryHeaders' => ['', '', '', '', 'Total Taxable Value', 'Total Cess', ''],
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) {
                        $taxable = array_sum(array_column($data, 'taxable_amount'));
                        $cess    = array_sum(array_column($data, 'cess_amount'));
                        return [
                            // 4 => $fmtNum($taxable), 
                            // 5 => $fmtNum($cess)
                        ];
                    },
                    'headers' => [
                        'Type', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 
                        'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN'
                    ],
                    'rowFormatter' => fn($r) => [
                        'OE', // Type
                        $r['place_of_supply'] ?? '',
                        '', // Applicable % of Tax Rate
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount'] ?? 0),
                        $fmtNum($r['cess_amount'] ?? 0),
                        '', // E-Commerce GSTIN
                    ],
                    'columnFormats' => [
                        'D' => NumberFormat::FORMAT_NUMBER_00,
                        'E' => NumberFormat::FORMAT_NUMBER_00,
                        'F' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'cdnr':
                return [
                    'summaryTitle'   => 'Summary For CDNR(9B)',
                    'summaryHeaders' => ['No. of Recipients', 'No. of Notes/Vouchers', '', '', '', '', '', '', 'Total Note/Refund Voucher Value', '', '', 'Total Taxable Value', 'Total Cess'],
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) {
                        $recipients = count(array_unique(array_column($data, 'gstin')));
                        $notes      = count(array_unique(array_column($data, 'invoice_no')));
                        $taxable    = array_sum(array_column($data, 'taxable_amount'));
                        $cess       = array_sum(array_column($data, 'cess_amount'));
                        return [
                            // 0 => $recipients, 
                            // 1 => $notes, 
                            // 11 => $fmtNum($taxable), 
                            // 12 => $fmtNum($cess)
                        ];
                    },
                    'headers' => [
                        'GSTIN/UIN of Recipient', 'Receiver Name', 'Note/Refund Voucher Number', 'Note/Refund Voucher date', 
                        'Document Type', 'Place Of Supply', 'Reverse Charge', 'Note Supply Type', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 
                        'Rate', 'Taxable Value', 'Cess Amount'
                    ],
                    'rowFormatter' => fn($r) => [
                        $r['gstin'] ?? '',
                        $r['account_name'] ?? '',
                        $r['invoice_no'] ?? '',
                        $fmtDate($r['invoice_date'] ?? ''),
                        ($r['voucher_type_id'] ?? 0) == (\App\Models\VoucherType::CREDIT_NOTE || \App\Models\VoucherType::SalesReturn) ? 'C' : 'D', // Document Type
                        $r['place_of_supply'] ?? '',
                        !empty($r['reverse_charge']) ? 'Y' : 'N', // Reverse Charge
                        'Regular B2B', // Note Supply Type
                        $fmtNum($r['invoice_value'] ?? 0),
                        '', // Applicable % of Tax Rate
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount'] ?? 0),
                        $fmtNum($r['cess_amount'] ?? 0),
                    ],
                    'columnFormats' => [
                        'I' => NumberFormat::FORMAT_NUMBER_00,
                        'K' => NumberFormat::FORMAT_NUMBER_00,
                        'L' => NumberFormat::FORMAT_NUMBER_00,
                        'M' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'cdnu':
                return [
                    'summaryTitle'   => 'Summary For CDNUR(9B)',
                    'summaryHeaders' => ['', 'No. of Notes/Vouchers', '', '', '', '', '', '', 'Total Taxable Value', 'Total Cess'],
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) {
                        $notes   = count(array_unique(array_column($data, 'note_no')));
                        $taxable = array_sum(array_column($data, 'taxable_amount'));
                        $cess    = array_sum(array_column($data, 'cess_amount'));
                        return [
                            // 1 => $notes, 
                            // 8 => $fmtNum($taxable), 
                            // 9 => $fmtNum($cess)
                        ];
                    },
                    'headers' => [
                        'UR Type', 'Note/Refund Voucher Number', 'Note/Refund Voucher date', 'Document Type', 
                        'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 
                        'Rate', 'Taxable Value', 'Cess Amount'
                    ],
                    'rowFormatter' => fn($r) => [
                        'B2CL', // UR Type
                        $r['invoice_no'] ?? '',
                        $fmtDate($r['invoice_date'] ?? ''),
                        ($r['voucher_type_id'] ?? 0) == (\App\Models\VoucherType::CREDIT_NOTE || \App\Models\VoucherType::SalesReturn) ? 'C' : 'D', // Document Type
                        $r['place_of_supply'] ?? '',
                        $fmtNum($r['invoice_value'] ?? 0),
                        '', // Applicable % of Tax Rate
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount'] ?? 0),
                        $fmtNum($r['cess_amount'] ?? 0),
                    ],
                    'columnFormats' => [
                        'F' => NumberFormat::FORMAT_NUMBER_00,
                        'H' => NumberFormat::FORMAT_NUMBER_00,
                        'I' => NumberFormat::FORMAT_NUMBER_00,
                        'J' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'exports':
                return [
                    'summaryTitle'   => 'Summary For EXP(6)',
                    'summaryHeaders' => ['', 'No. of Invoices', '', '', '', '', '', '', '', 'Total Taxable Value'],
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) {
                        $invoices = count(array_unique(array_column($data, 'invoice_no')));
                        $taxable  = array_sum(array_column($data, 'taxable_amount'));
                        return [
                            // 1 => $invoices, 
                            // 9 => $fmtNum($taxable)
                        ];
                    },
                    'headers' => [
                        'Export Type', 'Invoice Number', 'Invoice date', 'Invoice Value', 
                        'Port Code', 'Shipping Bill Number', 'Shipping Bill Date', 
                        'Applicable % of Tax Rate', 'Rate', 'Taxable Value'
                    ],
                    'rowFormatter' => fn($r) => [
                        'WOPAY', // Export Type (Without Payment / WPAY = With Payment)
                        $r['invoice_no'] ?? '',
                        $fmtDate($r['invoice_date'] ?? ''),
                        $fmtNum($r['invoice_value'] ?? 0),
                        $r['port_code'] ?? '',
                        $r['shipping_bill_no'] ?? '',
                        $fmtDate($r['shipping_bill_date'] ?? ''),
                        '', // Applicable % of Tax Rate
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount'] ?? 0),
                    ],
                    'columnFormats' => [
                        'D' => NumberFormat::FORMAT_NUMBER_00,
                        'I' => NumberFormat::FORMAT_NUMBER_00,
                        'J' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'nil_rated':
                return [
                    'summaryTitle'   => 'Summary For Nil rated, exempted and non GST outward supplies (8)',
                    'summaryHeaders' => ['', 'Total Nil Rated Supplies', 'Total Exempted Supplies', 'Total Non-GST Supplies'],
                    'summaryCalculator' => function($data, $rows) { return []; },
                    'headers' => [
                        'Description', 'Nil Rated Supplies', 'Exempted (other than nil rated/non GST supply )', 'Non-GST supplies'
                    ],
                    // Pre-aggregate raw invoice rows into exactly 4 fixed rows
                    'rowsGenerator' => function($data) use ($fmtNum) {
                        // Buckets: [inter-state registered, intra-state registered, inter-state unregistered, intra-state unregistered]
                        $buckets = [
                            'Total Inter-State supplies to registered persons'   => 0,
                            'Total Intra-State supplies to registered persons'   => 0,
                            'Total Inter-State supplies to unregistered persons' => 0,
                            'Total Intra-State supplies to unregistered persons' => 0,
                        ];

                        foreach ($data as $r) {
                            $gstin    = $r['gstin'] ?? '';
                            $isB2B    = !empty($gstin) && $gstin !== '-';
                            // Inter-state = IGST > 0, else Intra-state
                            $isInter  = (float)($r['igst_amount'] ?? 0) > 0;
                            $taxable  = (float)($r['taxable_amount'] ?? 0);

                            if ($isB2B) {
                                $key = $isInter
                                    ? 'Total Inter-State supplies to registered persons'
                                    : 'Total Intra-State supplies to registered persons';
                            } else {
                                $key = $isInter
                                    ? 'Total Inter-State supplies to unregistered persons'
                                    : 'Total Intra-State supplies to unregistered persons';
                            }
                            $buckets[$key] += $taxable;
                        }

                        $rows = [];
                        foreach ($buckets as $label => $amount) {
                            $rows[] = [
                                $label,
                                $fmtNum($amount), // Nil Rated Supplies
                                $fmtNum(0),       // Exempted
                                $fmtNum(0),       // Non-GST
                            ];
                        }
                        return $rows;
                    },
                    'rowFormatter' => fn($r) => [], // unused — rowsGenerator takes over
                    'columnFormats' => [
                        'B' => NumberFormat::FORMAT_NUMBER_00,
                        'C' => NumberFormat::FORMAT_NUMBER_00,
                        'D' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'hsn_summary':
                return [
                    'summaryTitle'   => 'Summary For HSN(12)',
                    'summaryHeaders' => ['No. of HSN', '', '', 'Total Quantity', 'Total Value', '', 'Total Taxable Value', 'Total Integrated Tax Amount', 'Total Central Tax Amount', 'Total State/UT Tax Amount', 'Total Cess Amount'],
                    'summaryCalculator' => function($data, $rows) {
                        return [];
                    },
                    'headers' => [
                        'HSN', 'Description', 'UQC', 'Total Quantity', 'Total Value', 'Rate',
                        'Taxable Value', 'Integrated Tax Amount', 'Central Tax Amount', 'State/UT Tax Amount', 'Cess Amount'
                    ],
                    'rowFormatter' => fn($r) => [
                        $r['hsn_code']       ?? '',
                        $r['description']    ?? '',
                        $r['uqc']            ?? 'OTH',
                        number_format((float)($r['total_qty']      ?? 0), 2, '.', ''),
                        $fmtNum($r['invoice_value']   ?? 0),
                        $fmtRate(max((float)($r['igst_rate'] ?? 0), (float)($r['cgst_rate'] ?? 0) + (float)($r['sgst_rate'] ?? 0))),
                        $fmtNum($r['taxable_amount']  ?? 0),
                        $fmtNum($r['igst']            ?? 0),
                        $fmtNum($r['cgst']            ?? 0),
                        $fmtNum($r['sgst']            ?? 0),
                        $fmtNum($r['cess']            ?? 0),
                    ],
                    'columnFormats' => [
                        'D' => NumberFormat::FORMAT_NUMBER_00,
                        'E' => NumberFormat::FORMAT_NUMBER_00,
                        'F' => NumberFormat::FORMAT_NUMBER_00,
                        'G' => NumberFormat::FORMAT_NUMBER_00,
                        'H' => NumberFormat::FORMAT_NUMBER_00,
                        'I' => NumberFormat::FORMAT_NUMBER_00,
                        'J' => NumberFormat::FORMAT_NUMBER_00,
                        'K' => NumberFormat::FORMAT_NUMBER_00,
                    ]
                ];
            case 'b2ba':
                return [
                    'summaryTitle'   => 'Summary For B2BA(4A)',
                    'summaryHeaders' => array_fill(0, 16, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Original GSTIN', 'Original Invoice Number', 'Original Invoice date', 'Revised GSTIN', 'Revised Receiver Name', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Invoice Type', 'E-Commerce GSTIN', 'Rate', 'Taxable Value', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'b2cla':
                return [
                    'summaryTitle'   => 'Summary For B2CLA(5A)',
                    'summaryHeaders' => array_fill(0, 11, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Original Invoice Number', 'Original Invoice date', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'b2csa':
                return [
                    'summaryTitle'   => 'Summary For B2CSA(7A)',
                    'summaryHeaders' => array_fill(0, 9, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Financial Year', 'Original Month', 'Type', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'cdnra':
                return [
                    'summaryTitle'   => 'Summary For CDNRA(9B)',
                    'summaryHeaders' => array_fill(0, 11, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Original Note/Refund Voucher Number', 'Original Note/Refund Voucher date', 'Revised Note/Refund Voucher Number', 'Revised Note/Refund Voucher date', 'Document Type', 'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'cdnura':
                return [
                    'summaryTitle'   => 'Summary For CDNURA(9B)',
                    'summaryHeaders' => array_fill(0, 11, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Original Note/Refund Voucher Number', 'Original Note/Refund Voucher date', 'Revised Note/Refund Voucher Number', 'Revised Note/Refund Voucher date', 'Document Type', 'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'expa':
                return [
                    'summaryTitle'   => 'Summary For EXPA(6A)',
                    'summaryHeaders' => array_fill(0, 11, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Original Invoice Number', 'Original Invoice date', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Port Code', 'Shipping Bill Number', 'Shipping Bill Date', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'at':
                return [
                    'summaryTitle'   => 'Summary For AT(11A)',
                    'summaryHeaders' => array_fill(0, 5, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Received', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'ata':
                return [
                    'summaryTitle'   => 'Summary For ATA(11A)',
                    'summaryHeaders' => array_fill(0, 7, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Financial Year', 'Original Month', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Received', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'atadj':
                return [
                    'summaryTitle'   => 'Summary For ATADJ(11B)',
                    'summaryHeaders' => array_fill(0, 5, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Adjusted', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'atadja':
                return [
                    'summaryTitle'   => 'Summary For ATADJA(11B)',
                    'summaryHeaders' => array_fill(0, 7, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Financial Year', 'Original Month', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Adjusted', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'hsn_b2c':
                return [
                    'summaryTitle'   => 'Summary For HSN(12)',
                    'summaryHeaders' => array_fill(0, 11, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['HSN', 'Description', 'UQC', 'Total Quantity', 'Total Value', 'Rate', 'Taxable Value', 'Integrated Tax Amount', 'Central Tax Amount', 'State/UT Tax Amount', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'docs':
                return [
                    'summaryTitle'   => 'Summary For DOCS(13)',
                    'summaryHeaders' => array_fill(0, 5, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Nature of Document', 'Sr. No. From', 'Sr. No. To', 'Total Number', 'Cancelled'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'eco':
                return [
                    'summaryTitle'   => 'Summary For ECO(14A, 14B)',
                    'summaryHeaders' => array_fill(0, 12, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['GSTIN/UIN of E-Commerce Operator', 'GSTIN of Supplier', 'Merchant Name', 'Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            case 'ecoa':
                return [
                    'summaryTitle'   => 'Summary For ECOA(14A, 14B)',
                    'summaryHeaders' => array_fill(0, 16, ''),
                    'summaryCalculator' => function($data, $rows) use ($fmtNum) { return []; },
                    'headers' => ['Original GSTIN/UIN of E-Commerce Operator', 'Original GSTIN of Supplier', 'Original Invoice Number', 'Original Invoice date', 'Revised GSTIN/UIN of E-Commerce Operator', 'Revised GSTIN of Supplier', 'Revised Merchant Name', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount'],
                    'rowFormatter' => fn($r) => [],
                ];
            default:
                return [
                    'summaryTitle' => 'Summary',
                    'summaryHeaders' => [],
                    'headers' => [],
                    'rowFormatter' => fn($r) => []
                ];
        }
    }
}
