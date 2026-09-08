<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class PurchaseInvoiceExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of PurchaseInvoice models
     */
    protected \Illuminate\Support\Collection $purchaseInvoice;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var \Illuminate\Support\Collection Collection of GST bill sundries
     */
    protected \Illuminate\Support\Collection $gstSundries;

    /**
     * @var \Illuminate\Support\Collection Collection of deduction bill sundries
     */
    protected \Illuminate\Support\Collection $deductionSundries;

    /**
     * @var int Total number of columns in the sheet
     */
    protected int $totalColumn = 0;

    /**
     * @var string|null Date period string
     */
    protected ?string $datePeriod;

    /**
     * Constructor
     *
     * @param object $company
     * @param \Illuminate\Support\Collection $purchaseInvoice
     * @param array $headings
     * @param string|null $datePeriod
     */
    public function __construct(object $company, \Illuminate\Support\Collection $purchaseInvoice, array $headings = [], ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->purchaseInvoice = $purchaseInvoice->sortBy(function ($invoice) {
            return (int)(is_array($invoice) ? ($invoice['invoice_serial'] ?? 0) : ($invoice->invoice_serial ?? 0));
        })->values();
        $this->datePeriod = $datePeriod;
        $this->gstSundries = $this->resolveGstSundries();
        $this->deductionSundries = $this->resolveDeductionSundries();

        if (!empty($headings)) {
            $this->headings = $headings;
        } else {
            $baseHeadings = [
                'Bill Date',
                'Bill No.',
                'Voucher Date',
                'Voucher No.',
                'Grn No.',
                'File No.',
                'Item Name',
                'Supplier Name',
                'Sales Inv. No.',
            ];

            $gstHeadings = $this->gstSundries->pluck('name')->toArray();
            $deductionHeadings = $this->deductionSundries->pluck('name')->toArray();

            $trailingHeadings = [
                'Net Total',
            ];

            $this->headings = array_merge($baseHeadings, $gstHeadings, $deductionHeadings, $trailingHeadings);
        }

        $this->totalColumn = count($this->headings);
    }

    /**
     * Resolve all unique GST bill sundries from company master and invoice records
     *
     * @return \Illuminate\Support\Collection
     */
    protected function resolveGstSundries(): \Illuminate\Support\Collection
    {
        $gstSundries = collect();

        // 1. Fetch from company bill sundries master with nature = gst
        $companyId = $this->company->id ?? company_id();
        $masterSundries = \App\Models\BillSundry::where('company_id', $companyId)
            ->where('bill_sundry_nature', 'gst')
            ->orderBy('purchases_preload_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        foreach ($masterSundries as $ms) {
            $gstSundries->put($ms->name, [
                'code' => $ms->code,
                'name' => $ms->name,
            ]);
        }

        // Default fallback if master doesn't have GST records
        if ($gstSundries->isEmpty()) {
            $gstSundries->put('CGST', ['code' => 1002, 'name' => 'CGST']);
            $gstSundries->put('SGST', ['code' => 1003, 'name' => 'SGST']);
            $gstSundries->put('IGST', ['code' => 1004, 'name' => 'IGST']);
        }

        // 2. Also check if any invoice has additional GST sundries
        foreach ($this->purchaseInvoice as $invoice) {
            $sundries = $invoice->billSundries ?? $invoice['billSundries'] ?? [];
            foreach ($sundries as $sundry) {
                $nature = is_array($sundry) ? ($sundry['bill_sundry_nature'] ?? null) : ($sundry->bill_sundry_nature ?? null);
                $name = is_array($sundry) ? ($sundry['name'] ?? null) : ($sundry->name ?? null);
                $code = is_array($sundry) ? ($sundry['code'] ?? null) : ($sundry->code ?? null);

                $isGst = ($nature === 'gst' || in_array((string)$code, ['1002', '1003', '1004']) || in_array($name, ['CGST', 'SGST', 'IGST']));
                if ($isGst && $name && !$gstSundries->has($name)) {
                    $gstSundries->put($name, [
                        'code' => $code,
                        'name' => $name,
                    ]);
                }
            }
        }

        return $gstSundries;
    }

    /**
     * Resolve all unique subtractive (deduction) bill sundries from company master and invoice records
     *
     * @return \Illuminate\Support\Collection
     */
    protected function resolveDeductionSundries(): \Illuminate\Support\Collection
    {
        $deductions = collect();

        // 1. Fetch from company bill sundries master ordered by preload order
        $companyId = $this->company->id ?? company_id();
        $masterSundries = \App\Models\BillSundry::where('company_id', $companyId)
            ->where('bill_sundry_type', 'subtractive')
            ->where('bill_sundry_nature', '!=', 'gst')
            ->orderBy('purchases_preload_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        foreach ($masterSundries as $ms) {
            $deductions->put($ms->name, [
                'code' => $ms->code,
                'name' => $ms->name,
            ]);
        }

        // 2. Also check if any invoice has additional subtractive sundries
        foreach ($this->purchaseInvoice as $invoice) {
            $sundries = $invoice->billSundries ?? $invoice['billSundries'] ?? [];
            foreach ($sundries as $sundry) {
                $nature = is_array($sundry) ? ($sundry['bill_sundry_nature'] ?? null) : ($sundry->bill_sundry_nature ?? null);
                if ($nature === 'gst') {
                    continue;
                }

                $isSubtractive = is_array($sundry)
                    ? (($sundry['bill_sundry_type'] ?? '') === 'subtractive' || (float)($sundry['amount'] ?? 0) < 0)
                    : (($sundry->bill_sundry_type ?? '') === 'subtractive' || (float)($sundry->amount ?? 0) < 0);

                $name = is_array($sundry) ? ($sundry['name'] ?? null) : ($sundry->name ?? null);
                $code = is_array($sundry) ? ($sundry['code'] ?? null) : ($sundry->code ?? null);

                if ($isSubtractive && $name && !$deductions->has($name)) {
                    $deductions->put($name, [
                        'code' => $code,
                        'name' => $name,
                    ]);
                }
            }
        }

        return $deductions;
    }

    /**
     * @var array Totals for each GST sundry column
     */
    protected array $gstTotals = [];

    /**
     * @var array Totals for each deduction sundry column
     */
    protected array $deductionTotals = [];

    /**
     * @var float Total net amount across all invoices
     */
    protected float $grandNetAmount = 0.0;

    /**
     * @var int Number of data rows
     */
    protected int $rowsCount = 0;

    /**
     * Return the view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        $gstSundriesList = $this->gstSundries->values()->toArray();
        $deductionSundriesList = $this->deductionSundries->values()->toArray();

        // Initialize totals
        $this->gstTotals = array_fill(0, count($gstSundriesList), 0.0);
        $this->deductionTotals = array_fill(0, count($deductionSundriesList), 0.0);
        $this->grandNetAmount = 0.0;
        $this->rowsCount = 0;

        $rows = [];

        foreach ($this->purchaseInvoice as $invoice) {
            $details = $invoice['details'] ?? $invoice->details ?? [];
            if (empty($details) || (is_countable($details) && count($details) === 0)) {
                $details = [null];
            }

            $partyBillDate = is_array($invoice) ? ($invoice['party_bill_date'] ?? null) : ($invoice->party_bill_date ?? null);
            $voucherDate = is_array($invoice) ? ($invoice['invoice_date'] ?? null) : ($invoice->invoice_date ?? null);
            $refNumber = is_array($invoice) ? ($invoice['reference_number'] ?? '') : ($invoice->reference_number ?? '');
            $voucherSerial = is_array($invoice) ? ($invoice['invoice_serial'] ?? '') : ($invoice->invoice_serial ?? '');
            $grnSerial = is_array($invoice) ? ($invoice['grn_serial'] ?? '') : ($invoice->grn_serial ?? '');
            $fileNumber = is_array($invoice) ? ($invoice['file_number'] ?? '') : ($invoice->file_number ?? '');
            $salesInvSerial = is_array($invoice) ? ($invoice['sales_invoice_serial'] ?? '') : ($invoice->sales_invoice_serial ?? '');
            $accountName = is_array($invoice) ? ($invoice['account']['name'] ?? '') : ($invoice->account->name ?? '');

            $netAmount = (float)(is_array($invoice) ? ($invoice['net_amount'] ?? 0) : ($invoice->net_amount ?? 0));
            $this->grandNetAmount += $netAmount;

            $sundries = is_array($invoice) ? ($invoice['billSundries'] ?? $invoice['bill_sundries'] ?? []) : ($invoice->billSundries ?? $invoice->bill_sundries ?? []);

            // Pre-calculate invoice GST values
            $invGstValues = [];
            foreach ($gstSundriesList as $gIdx => $gst) {
                $val = 0.0;
                foreach ($sundries as $s) {
                    $code = is_array($s) ? ($s['code'] ?? null) : ($s->code ?? null);
                    $name = is_array($s) ? ($s['name'] ?? null) : ($s->name ?? null);

                    $matches = (!empty($gst['code']) && !empty($code))
                        ? ((string)$code === (string)$gst['code'])
                        : (strtolower(trim((string)$name)) === strtolower(trim((string)$gst['name'])));

                    if ($matches) {
                        $amt = (float)(is_array($s) ? ($s['amount'] ?? 0) : ($s->amount ?? 0));
                        $value = (float)(is_array($s) ? ($s['value'] ?? 0) : ($s->value ?? 0));

                        if ($amt != 0) {
                            $val = abs($amt);
                        } elseif ($value != 0) {
                            $val = abs($value);
                        }
                        break;
                    }
                }
                $invGstValues[$gIdx] = $val;
                $this->gstTotals[$gIdx] += $val;
            }

            // Pre-calculate invoice deduction values
            $invDeductionValues = [];
            foreach ($deductionSundriesList as $dIdx => $deduction) {
                $val = 0.0;
                foreach ($sundries as $s) {
                    $code = is_array($s) ? ($s['code'] ?? null) : ($s->code ?? null);
                    $name = is_array($s) ? ($s['name'] ?? null) : ($s->name ?? null);

                    $matches = (!empty($deduction['code']) && !empty($code))
                        ? ((string)$code === (string)$deduction['code'])
                        : (strtolower(trim((string)$name)) === strtolower(trim((string)$deduction['name'])));

                    if ($matches) {
                        $amt = (float)(is_array($s) ? ($s['amount'] ?? 0) : ($s->amount ?? 0));
                        $value = (float)(is_array($s) ? ($s['value'] ?? 0) : ($s->value ?? 0));
                        $type = is_array($s) ? ($s['bill_sundry_type'] ?? '') : ($s->bill_sundry_type ?? '');

                        if ($amt != 0) {
                            $val = -abs($amt);
                        } elseif ($value != 0 && $type === 'subtractive') {
                            $val = -abs($value);
                        }
                        break;
                    }
                }
                $invDeductionValues[$dIdx] = $val;
                $this->deductionTotals[$dIdx] += $val;
            }

            $detailIndex = 0;
            foreach ($details as $detail) {
                $isFirstDetail = ($detailIndex === 0);
                $this->rowsCount++;

                $itemName = '';
                if ($detail) {
                    $itemName = is_array($detail) ? ($detail['item']['name'] ?? '') : ($detail->item->name ?? '');
                }

                $row = [
                    $isFirstDetail && !empty($partyBillDate) ? format_date($partyBillDate) : '',
                    $isFirstDetail ? $refNumber : '',
                    $isFirstDetail && !empty($voucherDate) ? format_date($voucherDate) : '',
                    $isFirstDetail ? $voucherSerial : '',
                    $isFirstDetail ? $grnSerial : '',
                    $isFirstDetail ? $fileNumber : '',
                    strtoupper($itemName),
                    $isFirstDetail ? strtoupper($accountName) : '',
                    $isFirstDetail ? $salesInvSerial : '',
                ];

                // Append GST sundries
                foreach ($gstSundriesList as $gIdx => $gst) {
                    $row[] = $isFirstDetail ? $invGstValues[$gIdx] : '';
                }

                // Append deduction sundries
                foreach ($deductionSundriesList as $dIdx => $deduction) {
                    $row[] = $isFirstDetail ? $invDeductionValues[$dIdx] : '';
                }

                // Append Net Total
                $row[] = $isFirstDetail ? $netAmount : '';

                $rows[] = $row;
                $detailIndex++;
            }
        }

        $datePeriod = $this->datePeriod;
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;
            $startDate = Carbon::parse($fy->start_date)->format('d-m-Y');
            $endDate   = Carbon::parse($fy->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
        }

        return view('company.pages.purchase-invoice.export', [
            'companyName' => $this->company->print_name ?? $this->company->name ?? '',
            'gstNumber' => $this->company->gst_number ?? '',
            'reportTitle' => 'Purchase Invoice Report',
            'headings' => $this->headings,
            'rows' => $rows,
            'datePeriod' => $datePeriod,
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
                $totalColumn = count($this->headings);
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 11);

                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ====== GSTIN Row (Row 2) ======
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // ====== Report Title Row (Row 3) ======
                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Date Period Title Row (Row 4) ======
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Dynamic Column Widths Based on Headings ======
                $widthMap = [
                    'Bill Date'              => 15,
                    'Bill No.'               => 15,
                    'Voucher Date'           => 15,
                    'Voucher No.'            => 15,
                    'Grn No.'                => 15,
                    'File No.'               => 15,
                    'Item Name'              => 30,
                    'Supplier Name'          => 35,
                    'Sales Inv. No.'         => 15,
                    'CGST'                   => 15,
                    'SGST'                   => 15,
                    'IGST'                   => 15,
                    'Net Total'              => 18,
                    'CD'                     => 15,
                    'Penalty'                => 15,
                    'Rebate'                 => 15,
                    'TDS(Purchase of Goods)' => 24,
                    'Round Off(-)'           => 15,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 18;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                // ====== Calculate Rows ======
                $firstDataRow = 6;
                $rowsCount = $this->rowsCount;
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Alignment for Text and ID Columns ======
                if ($rowsCount > 0) {
                    foreach (['A', 'C'] as $col) {
                        $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    foreach (['B', 'D', 'E', 'F', 'I'] as $col) {
                        $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    foreach (['G', 'H'] as $col) {
                        $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                }

                // ====== GST & Deduction Sundries Columns & Net Total Column Setup ======
                $gstCount = count($this->gstSundries);
                $deductionCount = count($this->deductionSundries);
                $netTotalColIndex = 9 + $gstCount + $deductionCount + 1; // Col 9 is Sales Inv. No.
                $netTotalColLetter = Coordinate::stringFromColumnIndex($netTotalColIndex);

                // Alignment and number formatting for GST columns
                $gIdx = 0;
                foreach ($this->gstSundries as $gst) {
                    $colIndex = 10 + $gIdx;
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                    $sheet->getStyle("{$colLetter}{$firstDataRow}:{$colLetter}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    if ($rowsCount > 0) {
                        $sheet->getStyle("{$colLetter}{$firstDataRow}:{$colLetter}{$lastDataRow}")
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    }

                    $sheet->setCellValueExplicit("{$colLetter}{$totalRow}", $this->gstTotals[$gIdx] ?? 0.0, DataType::TYPE_NUMERIC);
                    $gIdx++;
                }

                // Alignment and number formatting for all deduction columns
                $dIdx = 0;
                foreach ($this->deductionSundries as $deduction) {
                    $colIndex = 10 + $gstCount + $dIdx;
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);

                    $sheet->getStyle("{$colLetter}{$firstDataRow}:{$colLetter}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    if ($rowsCount > 0) {
                        $sheet->getStyle("{$colLetter}{$firstDataRow}:{$colLetter}{$lastDataRow}")
                            ->getNumberFormat()
                            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                    }

                    $sheet->setCellValueExplicit("{$colLetter}{$totalRow}", $this->deductionTotals[$dIdx] ?? 0.0, DataType::TYPE_NUMERIC);
                    $dIdx++;
                }

                // Format Net Total Column
                $sheet->getStyle("{$netTotalColLetter}{$firstDataRow}:{$netTotalColLetter}{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                if ($rowsCount > 0) {
                    $sheet->getStyle("{$netTotalColLetter}{$firstDataRow}:{$netTotalColLetter}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                }

                $sheet->setCellValueExplicit("{$netTotalColLetter}{$totalRow}", $this->grandNetAmount, DataType::TYPE_NUMERIC);

                // ====== SET TOTAL ROW LABEL AND STYLES ======
                $sheet->setCellValue("I{$totalRow}", "Total:");
                $sheet->getStyle("I{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("I{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $startTotalColLetter = Coordinate::stringFromColumnIndex(10);
                if (($gstCount + $deductionCount) > 0) {
                    $sheet->getStyle("{$startTotalColLetter}{$totalRow}:{$netTotalColLetter}{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_00],
                    ]);
                } else {
                    $sheet->getStyle("{$netTotalColLetter}{$totalRow}")->applyFromArray([
                        'font' => ['bold' => true],
                        'numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_00],
                    ]);
                }
            },
        ];
    }
}
