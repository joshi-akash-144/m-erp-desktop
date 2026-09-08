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

class DeliveryChallanExport implements FromView, WithEvents
{
    protected object $company;
    protected Collection $deliveryChallans;
    protected array $headings;
    protected int $totalColumn = 0;
    protected ?string $datePeriod;

    public function __construct(object $company, Collection $deliveryChallans, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->deliveryChallans = $deliveryChallans;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $rows = collect($this->deliveryChallans)->flatMap(function ($challan) {
            return collect($challan['details'])->map(function ($detail, $detailIndex) use ($challan) {
                $isFirstDetail = ($detailIndex === 0);

                $date = $challan['challan_date'] ?? '';

                return [
                    $isFirstDetail ? ($challan['challan_serial'] ?? '') : '',
                    $isFirstDetail ? ($date ? format_date($date) : '') : '',
                    $isFirstDetail ? ($detail['sales_order_serial'] ?? '') : '',
                    $isFirstDetail ? ($challan['account']['name'] ?? '') : '',
                    $detail['item']['name'] ?? '',
                    $detail['destination']['name'] ?? '',
                    $detail['condition']['name'] ?? '',
                    $detail['quantity'] ?? 0,
                    $detail['party_quantity'] ?? 0,
                    $detail['rate'] ?? 0,
                    $detail['inclusive_rate'] ?? 0,
                ];
            });
        });

        return view('company.pages.delivery-challan.export', [
            'companyName' => $this->company->print_name ?? $this->company->name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Delivery Challan Report',
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
                $lastColumnLetter = Coordinate::stringFromColumnIndex($this->totalColumn);
                
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Dynamic Column Widths Based on Headings ======
                $widthMap = [
                    'DC No.' => 12,
                    'Date In' => 18,
                    'Date Out' => 18,
                    'In Date' => 18,
                    'Out Date' => 18,
                    'S.O.No.' => 15,
                    'Customer' => 40,
                    'Item' => 25,
                    'Destination' => 35,
                    'Condition' => 25,
                    'Quantity' => 15,
                    'P.Qty' => 15,
                    'Rate' => 15,
                    'Incl Rate' => 15,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 20;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                $firstDataRow = 7;
                $rowsCount = count($this->deliveryChallans->flatMap(fn($c) => $c['details']));
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // Alignment for Numeric Columns (H onwards: Quantity, Party Qty, Rate, Incl Rate) and their Headings
                foreach (['H', 'I', 'J', 'K'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    
                    $sheet->getStyle("{$col}{$firstDataRow}:{$col}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                }

                // Totals (Label at Column G, Totals at H and I)
                $sheet->setCellValue("G{$totalRow}", "Total:");
                $sheet->getStyle("G{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("G{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                $totalQty = $this->deliveryChallans->flatMap(fn($c) => $c['details'])->sum('quantity');
                $totalPartyQty = $this->deliveryChallans->flatMap(fn($c) => $c['details'])->sum('party_quantity');
                // dd($totalRate);
                $sheet->setCellValueExplicit("H{$totalRow}", $totalQty, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("I{$totalRow}", $totalPartyQty, DataType::TYPE_NUMERIC);

                $sheet->getStyle("H{$totalRow}:I{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '#,##0.00'],
                ]);
            },
        ];
    }
}
