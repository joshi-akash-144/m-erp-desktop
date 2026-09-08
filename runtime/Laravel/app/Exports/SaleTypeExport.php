<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SaleTypeExport implements FromView, WithEvents
{
    /**
     * @var object Company model
     */
    protected object $company;

    /**
     * @var \Illuminate\Support\Collection Collection of AccountGroup models
     */
    protected \Illuminate\Support\Collection $saleTypes;

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
     * @param \Illuminate\Support\Collection $saleTypes
     * @param array $headings
     */
    public function __construct(object $company, \Illuminate\Support\Collection $saleTypes, array $headings)
    {
        $this->company = $company;
        $this->saleTypes = $saleTypes;
        $this->headings = $headings;
    }

    /**
     * Return the view for Excel export
     *
     * @return View
     */
    public function view(): View
    {
        $rows = $this->saleTypes->map(function ($saleType) {
            return [
                $saleType->name,
                $saleType?->account->name,
                $saleType->taxation_type,
                $saleType->transaction_type,
                $saleType->region,
                $saleType->cgst,
                $saleType->sgst,
                $saleType->igst,
            ];
        });

        $this->totalColumn = $rows->isNotEmpty() ? count($rows->first()) : 0;

        return view('company.pages.masters.sale-type.export', [
            'companyName' => $this->company->print_name,
            'gstNumber'   => $this->company->gst_number,
            'reportTitle' => 'Sale Type Report',
            'headings'    => $this->headings,
            'rows'        => $rows,
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
                $lastColumnLetter = Coordinate::stringFromColumnIndex($this->totalColumn ?: 3);

                // ====== Company Name Row (Row 1) ======
                $sheet->mergeCells("A1:{$lastColumnLetter}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Times New Roman'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // ====== GSTIN Row (Row 2) ======
                $sheet->mergeCells("A2:{$lastColumnLetter}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 11, 'name' => 'Times New Roman'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(15);

                // ====== Report Title Row (Row 3) ======
                $sheet->mergeCells("A3:{$lastColumnLetter}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ====== Headings Row (Row 4) ======
                $sheet->getStyle("A4:{$lastColumnLetter}4")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'name' => 'Times New Roman'],
                ]);

                foreach (['F4', 'G4', 'H4'] as $cell) {
                    $sheet->getStyle($cell)->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }

                $sheet->getRowDimension(4)->setRowHeight(18);

                $widths = [];

                for ($i = 1; $i <= $totalColumn; $i++) {
                    $columnLetter = Coordinate::stringFromColumnIndex($i);

                    if (in_array($columnLetter, ['A', 'B'])) {
                        $widths[$i] = 35;
                    } elseif (in_array($columnLetter, ['C', 'D','E'])) {
                        $widths[$i] = 25;
                    } else {
                        $widths[$i] = 20; // default width for others
                    }
                }

                foreach ($widths as $index => $width) {
                    $columnLetter = Coordinate::stringFromColumnIndex($index);
                    $sheet->getColumnDimension($columnLetter)->setWidth($width);
                }

            },
        ];
    }
}
