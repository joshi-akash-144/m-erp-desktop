<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class SalesOrderWithBillExport implements FromView, WithEvents
{
    protected object $company;
    protected \Illuminate\Support\Collection $salesOrders;
    protected array $headings;
    protected array $footerData;
    protected ?string $datePeriod;

    public function __construct(object $company, \Illuminate\Support\Collection $salesOrders, array $headings, array $footerData = [], ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->salesOrders = $salesOrders;
        $this->headings = $headings;
        $this->footerData = $footerData;
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $rows = $this->salesOrders->map(function ($row) {
            return [
                $row['so_no'] ?? '',
                $row['so_date'] ?? '',
                $row['customer_name'] ?? '',
                $row['purchase_order_number'] ?? '',
                $row['product_name'] ?? '',
                $row['destination_name'] ?? '',
                $row['rate'] ?? '',
                $row['qty'] ?? '',
                $row['invoice_date'] ?? '',
                $row['grn_no'] ?? '',
                $row['invoice_no'] ?? '',
                $row['vehicle_no'] ?? '',
                $row['bags'] ?? '',
                $row['p_qty'] ?? '',
                $row['rec_qty'] ?? '',
                $row['remaining_qty'] ?? '',
            ];
        });

        if (!empty($this->footerData)) {
            $rows->push([
                '',
                '',
                '',
                '',
                '',
                '',
                'Grand Total:',
                $this->footerData['order_qty_total'] ?? '',
                '',
                '',
                '',
                '',
                '',
                '',
                $this->footerData['rec_qty_total'] ?? '',
                $this->footerData['rem_qty_total'] ?? '',
            ]);
        }

        $datePeriod = $this->datePeriod;
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;
            $startDate = Carbon::parse($fy->start_date)->format('d-m-Y');
            $endDate   = Carbon::parse($fy->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
        }

        return view('company.pages.sales-order-with-bill.export', [
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Sales Order Detail With Bill Report',
            'headings' => $this->headings,
            'rows' => $rows,
            'datePeriod' => $datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $totalColumn = count($this->headings);
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 16);

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
                    'So.No' => 12,
                    'Date' => 12,
                    'Customer Name' => 40,
                    'P.O. No.' => 20,
                    'Product Name' => 25,
                    'Destination' => 25,
                    'Rate' => 15,
                    'Order Qty' => 15,
                    'Invoice Date' => 14,
                    'GRN Number' => 15,
                    'Invoice No' => 15,
                    'Vehicle No' => 15,
                    'Bags' => 10,
                    'P.Qty' => 12,
                    'Bill.Qty' => 15,
                    'Rem.Qty' => 15,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 15;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                $firstDataRow = 6;
                $rowsCount = $this->salesOrders->count() + (empty($this->footerData) ? 0 : 1);
                $lastDataRow = $firstDataRow + $rowsCount - 1;

                // ====== Alignment for Numeric Columns ======
                foreach (['G', 'H', 'M', 'N', 'O', 'P'] as $col) {
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Format Rate, Qty columns to 2 and 3 decimal places
                $sheet->getStyle("G{$firstDataRow}:G{$lastDataRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle("H{$firstDataRow}:H{$lastDataRow}")->getNumberFormat()->setFormatCode("0.000");
                $sheet->getStyle("N{$firstDataRow}:P{$lastDataRow}")->getNumberFormat()->setFormatCode("0.000");

                if (!empty($this->footerData)) {
                    $sheet->getStyle("G{$lastDataRow}:P{$lastDataRow}")->applyFromArray([
                        'font' => ['bold' => true],
                    ]);
                }
            },
        ];
    }
}
