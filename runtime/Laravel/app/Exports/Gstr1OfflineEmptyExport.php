<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Gstr1OfflineEmptyExport implements FromView, WithTitle, WithEvents
{
    protected string $sheetName;
    protected string $summaryTitle;
    protected array $headers;

    public function __construct(string $sheetName, string $summaryTitle, array $headers)
    {
        $this->sheetName    = $sheetName;
        $this->summaryTitle = $summaryTitle;
        $this->headers      = $headers;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function view(): View
    {
        return view('company.pages.gst.gstr1.offline_sheet', [
            'summaryTitle'   => $this->summaryTitle,
            'summaryHeaders' => array_fill(0, count($this->headers), ''),
            'summaryValues'  => array_fill(0, count($this->headers), ''),
            'headers'        => $this->headers,
            'rows'           => [],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A2:Z2')->getFont()->setBold(true);
                $sheet->getStyle('A4:Z4')->getFont()->setBold(true);
                $sheet->getStyle('A4:Z4')->getFill()
                      ->setFillType(Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFD9D9D9');

                // Set column widths based on header text length
                foreach ($this->headers as $index => $header) {
                    $col   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $width = max(14, mb_strlen($header) * 1.15);
                    $sheet->getColumnDimension($col)->setWidth($width);
                }
            }
        ];
    }
}
