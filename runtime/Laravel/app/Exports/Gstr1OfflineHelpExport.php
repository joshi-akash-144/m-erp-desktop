<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class Gstr1OfflineHelpExport implements FromView, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Help Instruction';
    }

    public function view(): View
    {
        return view('company.pages.gst.gstr1.offline_help');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:A20')->getFont()->setBold(true);
                $sheet->getColumnDimension('A')->setWidth(100);
            },
        ];
    }
}
