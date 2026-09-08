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

class SalesInvoiceExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of AccountGroup models
     */
    protected \Illuminate\Support\Collection $salesInvoice;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var int Total number of columns in the sheet
     */
    protected int $totalColumn = 0;

    /**
     * Constructor
     *
     * @param object $company
     * @param \Illuminate\Support\Collection $salesInvoice
     * @param array $headings
     */
    public function __construct(object $company, \Illuminate\Support\Collection $salesInvoice, array $headings, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->salesInvoice = $salesInvoice;
        $this->headings = $headings;
        $this->datePeriod = $datePeriod;
    }

    /**
     * Return the view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        
        $rows = collect($this->salesInvoice)->flatMap(function ($salesInvoice) {
            return collect($salesInvoice['details'])->map(function ($detail) use ($salesInvoice) {                
                return [
                    $salesInvoice['reference_number'] ?? '',
                    $salesInvoice['salesOrder']['purchase_order_number'] ?? '',
                    format_date($salesInvoice['invoice_date'] ?? ''),
                    $salesInvoice['grn_number'] ?? '',
                    $salesInvoice['account']['name'] ?? '',  
                    $salesInvoice['account']['city'] ?? '',                                                                            
                    $detail['item']['name'] ?? '', 
                    $salesInvoice['vehicle_number'] ?? '',                                                                            
                    $salesInvoice['net_amount']?? '',                                                                                                                   
                ];
            });
        });

       $datePeriod = $this->datePeriod;
        if (!$datePeriod) {
            $fy = $this->company->currentFinancialYear;    
            $startDate = Carbon::parse($fy->start_date)->format('d-m-Y');
            $endDate   = Carbon::parse($fy->end_date)->format('d-m-Y');
            $datePeriod = "$startDate to $endDate";
        }
                
        return view('company.pages.sales-invoice.export', [
            'companyName' => $this->company->print_name,
            'gstNumber' => $this->company->gst_number,
            'reportTitle' => 'Sales Invoice Report',
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
                $totalColumn = $this->totalColumn;
                $lastColumnLetter = Coordinate::stringFromColumnIndex($totalColumn ?: 9);

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
                $sheet->getStyle("A1:{$lastColumnLetter}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
                    
                 // ====== Date Period (Row 4) ======
                $sheet->mergeCells("A4:{$lastColumnLetter}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Arial, Helvetica'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(15);
            

                // ====== Headings Row (Row 5) ======
                $sheet->getStyle("A5:{$lastColumnLetter}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Arial, Helvetica'],                    
                ]);                
                $sheet->getRowDimension(5)->setRowHeight(18);

                // ====== Dynamic Column Widths Based on Headings ======
                $widthMap = [
                    'Bill No.' => 15,
                    'Po No' => 15,
                    'Date' => 15,
                    'GRN No.' => 15,
                    'Customer Name' => 40,
                    'City' => 20,
                    'Item Name' => 40,
                    'Vehicle No' => 20,                    
                    'Net Total' => 20,                    
                ];


                foreach ($this->headings as $i => $heading) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i + 1);
                    $width = $widthMap[$heading] ?? 20;
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }
        

                // ====== Calculate rows ======
                $firstDataRow = 6;
                $rowsCount = count($this->salesInvoice->flatMap(fn($so) => $so['details']));
                $lastDataRow = $firstDataRow + $rowsCount - 1;
                $totalRow = $firstDataRow + $rowsCount;

                // ====== Alignment for Centered Columns (Bill No, Po No, Date) ======
                foreach (['A', 'B', 'D'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // date column center
                $sheet->getStyle("C5:C{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // ====== Alignment for Numeric/Right Columns (Date, Net Total) ======
                foreach (['I'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$totalRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // ====== Calculate totals from collection (Recommended) ======
               $totalAmount = $this->salesInvoice->sum('net_amount');
                
                // --- ADDED CODE: Format Rate and Inclusive Rate Columns to 2 Decimal Places ---

                // Column I is 'Incl_Rate' and Column J is 'Rate'
                // Apply the '0.00' number format to all data cells in these columns
                $rangeIRate = "I{$firstDataRow}:I{$lastDataRow}";
                $rangeJRate = "H{$firstDataRow}:H{$lastDataRow}";

                $sheet->getStyle($rangeIRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($rangeJRate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

                // ====== SET TOTAL ROW ======
                $sheet->setCellValue("H{$totalRow}", "Total:");
                $sheet->getStyle("H{$totalRow}")->getFont()->setBold(true);
                $sheet->getStyle("H{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Set calculated totals directly
                $sheet->setCellValueExplicit("I{$totalRow}", $totalAmount, DataType::TYPE_NUMERIC);              

                // Format total row
                $sheet->getStyle("I{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '0.00'],
                ]);
            },
        ];
    }
}
