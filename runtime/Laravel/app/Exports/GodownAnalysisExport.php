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

class GodownAnalysisExport implements FromView, WithEvents
{
    protected object $company;
    protected \Illuminate\Support\Collection $analyses;
    protected array $headings;
    protected int $totalColumn = 0;
    protected ?string $datePeriod;

    public function __construct(object $company, \Illuminate\Support\Collection $analyses, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->analyses = $analyses;
        $this->headings = $headings;
        $this->totalColumn = count($headings);
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $rows = collect($this->analyses)->map(function ($analysis) {
            return [
                'grn_serial'    => $analysis->grn->grn_serial ?? '',
                'grn_date'      => $analysis->grn ? format_date($analysis->grn->grn_date) : '',
                'supplier_name' => $analysis->grn->account->name ?? '',
                'city'          => $analysis->grn->account->city ?? '',
                'rebate_amount' => $analysis->rebate_total ?? 0,
                'created_by'    => $analysis->creator->name ?? '',
            ];
        });
    
        $datePeriod = $this->datePeriod;
        
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;    
            $startDate = format_date($fy->start_date);
            $endDate   = format_date($fy->end_date);
            $datePeriod = "$startDate to $endDate";
        }
            
        return view('company.pages.godown-analysis.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Godown Analysis Report',
            'headings'    => $this->headings,
            'rows'        => $rows,
            'datePeriod'  => $datePeriod,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 6);
                
                $sheet->getStyle("A1:{$lastColumnLetter}1000")->getFont()->setName('Arial, Helvetica');

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
                    'GRN Serial' => 15,
                    'Date' => 15,
                    'Supplier Name' => 35,
                    'City' => 20,
                    'Rebate Amount' => 18,
                ];

                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 20;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

                // ====== Calculate rows ======
                $firstDataRow = 6;
                $rowsCount = count($this->analyses);
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Calculate totals ======
                $totalRebate = collect($this->analyses)->sum('rebate_total');

                // ====== Alignment for Text Columns (A to D) ======
                $sheet->getStyle("A6:D{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // ====== Alignment for Numeric Columns (Rebate Amount is Column E) ======
                $sheet->getStyle("E4:E{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Apply the '0.00' number format to Rebate Amount column
                $rangeERate = "E{$firstDataRow}:E{$totalRow}";
                $sheet->getStyle($rangeERate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // ====== SET TOTAL ROW ======
                $sheet->setCellValue("D{$totalRow}", "Total:");
                $sheet->getStyle("D{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("D{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->setCellValueExplicit("E{$totalRow}", $totalRebate, DataType::TYPE_NUMERIC);

                // Format total row
                $sheet->getStyle("E{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.00'],
                ]);
            },
        ];
    }
}
