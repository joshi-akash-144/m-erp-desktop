<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ZoneWiseStatementExport implements WithMultipleSheets
{
    protected $zonewise_data;

    public function __construct(array $zonewise_data)
    {
        $this->zonewise_data = $zonewise_data;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->zonewise_data as $zone => $products) {
            $sheets[] = new ZoneSheet($zone, $products);
        }
        return $sheets;
    }
}

class ZoneSheet implements FromArray, WithTitle, WithStyles
{
    protected $zone;
    protected $products;

    public function __construct($zone, array $products)
    {
        $this->zone = $zone;
        $this->products = $products;
    }

    public function array(): array
    {
        $data = [
            [$this->zone],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
        ];
        foreach ($this->products as $product_name => $items) {
            $product_total = 0;
            $data[] = [$product_name];
            $data[] = ['Billing Date', 'Customer PO No', 'Name of Sold To Party', 'Material Quantity', 'Vehicle Number'];

            foreach ($items as $item) {
                $data[] = array_values($item);
                $product_total += $item['Material Quantity'];
            }
            $data[] = ['', '', 'Total', $product_total, ''];

            for ($i = 0; $i < 5; $i++) {
                $data[] = ['', '', '', '', ''];
            }
        }
        return $data;
    }

    public function title(): string
    {
        return $this->zone;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells("A1:E1");
        $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle("A1")->getFill()->getStartColor()->setARGB('baedd5');

        $sheet->getRowDimension(1)->setRowHeight(height: 30);

        $rowCount = 5;

        foreach ($this->products as $product_name => $items) {
            $sheet->mergeCells("A{$rowCount}:E{$rowCount}");
            $sheet->getStyle("A{$rowCount}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A{$rowCount}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$rowCount}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("A{$rowCount}")->getFill()->getStartColor()->setARGB('FFFF00');
            $sheet->getRowDimension($rowCount)->setRowHeight(20);

            $rowCount++;

            $sheet->getStyle("A{$rowCount}:E{$rowCount}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowCount}:E{$rowCount}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("A{$rowCount}:E{$rowCount}")->getFill()->getStartColor()->setARGB('D3D3D3');
            $sheet->getRowDimension($rowCount)->setRowHeight(20);

            $rowCount++;

            foreach ($items as $item) {
                $sheet->fromArray(array_values($item), null, "A{$rowCount}");
                $sheet->getRowDimension($rowCount)->setRowHeight(18);
                $rowCount++;
            }

            $sheet->getStyle("D{$rowCount}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowCount}:E{$rowCount}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle("A{$rowCount}:E{$rowCount}")->getFill()->getStartColor()->setARGB('ADD8E6');
            $sheet->getRowDimension($rowCount)->setRowHeight(22);
            $rowCount++;

            $rowCount += 5;
        }

        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $sheet->getStyle("A1:E{$rowCount}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }
}

