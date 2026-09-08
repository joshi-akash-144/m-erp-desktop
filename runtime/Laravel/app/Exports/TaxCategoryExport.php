<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class TaxCategoryExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of TaxCategory models
     */
    protected \Illuminate\Support\Collection $taxCategories;

    /**
     * @var array Headings for the Excel sheet
     */
    protected array $headings;

    /**
     * @var int Total number of columns
     */
    protected int $totalColumn = 0;

    /**
     * Constructor
     *
     * @param object $company
     * @param \Illuminate\Support\Collection $taxCategories
     * @param array $headings
     */
    public function __construct(object $company, Collection $taxCategories, array $headings)
    {
        $this->company = $company;
        $this->taxCategories = $taxCategories;
        $this->headings = $headings;
    }

    /**
     * Return view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        $rows = $this->taxCategories->map(function ($taxCategory) {
            return [
                $taxCategory->name,
                ucfirst($taxCategory->type ?? '-'),
                $taxCategory->zero_tax_type ?? '',
                $taxCategory->cgst ?? '',
                $taxCategory->sgst ?? '',
                $taxCategory->igst ?? '',
            ];
        });

        $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.tax-category.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Tax Category Report',
            'headings'    => $this->headings,
            'rows'        => $rows,
        ]);
    }

    /**
     * Register events for Excel formatting
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumnLetter = Coordinate::stringFromColumnIndex($this->totalColumn);

                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ====== GSTIN Row (Row 2) ======
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // ====== Report Title Row (Row 3) ======
                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Headings Row (Row 4) ======
                $sheet->getStyle("A4:{$lastColumnLetter}4")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
                ]);
                foreach (['D4', 'E4','F4'] as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }

                // ====== Column Widths ======
                for ($i = 1; $i <= $this->totalColumn; $i++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($columnLetter)->setWidth(match ($columnLetter) {
                        'A' => 50,
                        // 'B', 'C' => 20,  // Correct way to match multiple values
                        'B' => 20,
                        default => 25,
                    });
                }

                // ====== Number Formatting for Tax Columns ======
                $lastRow = 4 + count($this->taxCategories);
                foreach (['C', 'D', 'E', 'F'] as $col) {
                    $sheet->getStyle("{$col}5:{$col}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('0.00');
                }
            },
        ];
    }
}
