<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProfitLossExport implements FromView, WithEvents
{
    protected object $company;
    protected array  $report;
    protected array  $filters;
    protected string $datePeriod;

    /**
     * Row metadata indexed by Excel row number.
     * Structural rows  → string  ('header','section_header','col_group','col_names','total','blank','net_result')
     * Data rows        → array   ['dr' => type, 'cr' => type]
     * Storing both sides independently so AfterSheet can bold/indent each column group correctly
     * regardless of what the opposite side's type is at the same row.
     */
    protected array $rowMeta = [];

    public function __construct(object $company, array $report, array $filters, string $datePeriod)
    {
        $this->company    = $company;
        $this->report     = $report;
        $this->filters    = $filters;
        $this->datePeriod = $datePeriod;
    }

    public function view(): View
    {
        $trading  = $this->report['trading'] ?? [];
        $pl       = $this->report['pl']      ?? [];
        $isAcct   = ($this->filters['view_type'] ?? 'group_wise') === 'account_wise';

        // ── Build flat side rows ─────────────────────────────────────────────
        $buildSide = function(
            array  $items,
            array  $sections,
            bool   $isAcct,
            float  $stockTotal,
            string $stockLabel,
            string $stockSection
        ): array {
            $rows = [];

            if ($stockTotal > 0) {
                $rows[] = ['type' => 'stock', 'name' => $stockLabel, 'amount' => null, 'total' => $stockTotal];
                if ($isAcct) {
                    foreach ($items as $grp) {
                        if (($grp['section'] ?? '') !== $stockSection) continue;
                        foreach ($grp['children'] ?? [] as $child) {
                            if ($child['is_item'] ?? false) {
                                $qty  = round(abs((float)($child['quantity'] ?? 0)), 3);
                                $rate = (float)($child['rate'] ?? 0);
                                $unit = $child['unit_name'] ?? '';
                                $name = $child['name'] . ' (' . number_format($qty, 3)
                                      . ($unit ? ' ' . $unit : '') . ' @ ' . number_format($rate, 2) . ')';
                                $rows[] = ['type' => 'item', 'name' => $name, 'amount' => (float)($child['amount'] ?? 0), 'total' => null];
                            } else {
                                $rows[] = ['type' => 'account', 'name' => $child['name'], 'amount' => (float)($child['amount'] ?? 0), 'total' => null];
                            }
                        }
                    }
                }
            }

            $bySection = [];
            foreach ($items as $item) {
                $sec = $item['section'] ?? '';
                if (!in_array($sec, ['opening_stock', 'closing_stock'])) {
                    $bySection[$sec][] = $item;
                }
            }
            foreach ($sections as $section) {
                foreach ($bySection[$section] ?? [] as $group) {
                    $rows[] = ['type' => 'group', 'name' => $group['name'], 'amount' => null, 'total' => (float)($group['amount'] ?? 0)];
                    if ($isAcct) {
                        foreach ($group['children'] ?? [] as $child) {
                            $rows[] = ['type' => 'account', 'name' => $child['name'], 'amount' => (float)($child['amount'] ?? 0), 'total' => null];
                        }
                    }
                }
            }
            return $rows;
        };

        // ── Trading ──────────────────────────────────────────────────────────
        $isGpProfit = (bool)($trading['is_gross_profit'] ?? true);
        $gpProfit   = (float)($trading['gross_profit']   ?? 0);
        $gpLoss     = (float)($trading['gross_loss']     ?? 0);

        $drRows = $buildSide($trading['debit']  ?? [], ['trading_dr_purchase', 'trading_dr_direct_expense'], $isAcct, (float)($trading['opening_stock_total'] ?? 0), 'Opening Stock', 'opening_stock');
        $crRows = $buildSide($trading['credit'] ?? [], ['trading_cr_sales', 'trading_cr_direct_income'],     $isAcct, (float)($trading['closing_stock_total'] ?? 0), 'Closing Stock', 'closing_stock');

        if ($isGpProfit && $gpProfit > 0) $drRows[] = ['type' => 'profit', 'name' => 'Gross Profit c/d', 'amount' => null, 'total' => $gpProfit];
        if (!$isGpProfit && $gpLoss > 0)  $crRows[] = ['type' => 'loss',   'name' => 'Gross Loss c/d',   'amount' => null, 'total' => $gpLoss];

        // ── P&L ──────────────────────────────────────────────────────────────
        $isNpProfit = (bool)($pl['is_net_profit'] ?? true);
        $netProfit  = (float)($pl['net_profit']   ?? 0);
        $netLoss    = (float)($pl['net_loss']     ?? 0);

        $plDrRows = $buildSide($pl['debit']  ?? [], ['indirect_expense'], $isAcct, 0, '', '');
        $plCrRows = $buildSide($pl['credit'] ?? [], ['indirect_income'],  $isAcct, 0, '', '');

        if ($isGpProfit  && $gpProfit  > 0) array_unshift($plCrRows, ['type' => 'transfer', 'name' => 'Gross Profit b/d', 'amount' => null, 'total' => $gpProfit]);
        if (!$isGpProfit && $gpLoss    > 0) array_unshift($plDrRows, ['type' => 'transfer', 'name' => 'Gross Loss b/d',   'amount' => null, 'total' => $gpLoss]);
        if ($isNpProfit  && $netProfit > 0) $plDrRows[] = ['type' => 'profit', 'name' => 'Net Profit', 'amount' => null, 'total' => $netProfit];
        if (!$isNpProfit && $netLoss   > 0) $plCrRows[] = ['type' => 'loss',   'name' => 'Net Loss',   'amount' => null, 'total' => $netLoss];

        // ── Zip ──────────────────────────────────────────────────────────────
        $blank   = ['type' => 'blank', 'name' => '', 'amount' => null, 'total' => null];
        $zipRows = function(array $left, array $right) use ($blank): array {
            $maxLen = max(count($left), count($right), 1);
            $out    = [];
            for ($i = 0; $i < $maxLen; $i++) {
                $out[] = ['dr' => $left[$i] ?? $blank, 'cr' => $right[$i] ?? $blank];
            }
            return $out;
        };

        $tradingZipped = $zipRows($drRows, $crRows);
        $plZipped      = $zipRows($plDrRows, $plCrRows);
        $tradingTotal  = (float)($trading['trading_total'] ?? 0);
        $plTotal       = (float)($pl['pl_total'] ?? 0);
        $netAmt        = $isNpProfit ? $netProfit : $netLoss;
        $netLabel      = $isNpProfit ? 'Net Profit' : 'Net Loss';

        // ── Row metadata: structural = string, data = ['dr'=>type,'cr'=>type] ─
        foreach (range(1, 4) as $r) { $this->rowMeta[$r] = 'header'; }
        $this->rowMeta[5] = 'section_header';
        $this->rowMeta[6] = 'col_group';
        $this->rowMeta[7] = 'col_names';

        $excelRow = 8;
        foreach ($tradingZipped as $row) {
            $this->rowMeta[$excelRow++] = ['dr' => $row['dr']['type'], 'cr' => $row['cr']['type']];
        }
        $this->rowMeta[$excelRow++] = 'total';
        $this->rowMeta[$excelRow++] = 'blank';

        $this->rowMeta[$excelRow++] = 'section_header';
        $this->rowMeta[$excelRow++] = 'col_group';
        $this->rowMeta[$excelRow++] = 'col_names';

        foreach ($plZipped as $row) {
            $this->rowMeta[$excelRow++] = ['dr' => $row['dr']['type'], 'cr' => $row['cr']['type']];
        }
        $this->rowMeta[$excelRow++] = 'total';
        $this->rowMeta[$excelRow++] = 'blank';
        $this->rowMeta[$excelRow]   = 'net_result';

        return view('company.pages.profit-loss.export', [
            'company'       => $this->company,
            'datePeriod'    => $this->datePeriod,
            'tradingZipped' => $tradingZipped,
            'plZipped'      => $plZipped,
            'tradingTotal'  => $tradingTotal,
            'plTotal'       => $plTotal,
            'netLabel'      => $netLabel,
            'netAmt'        => $netAmt,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = 'G';

                // ── Column widths ─────────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(50);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(3);
                $sheet->getColumnDimension('E')->setWidth(50);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);

                // ── Per-side styler (applies to ONE side: name+amt+tot cols) ──
                $styleSide = function(
                    string $nameCol, string $amtCol, string $totCol,
                    string $type, int $rowNum
                ) use ($sheet) {
                    // Number format + right-align always
                    foreach ([$amtCol, $totCol] as $col) {
                        $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        $sheet->getStyle("{$col}{$rowNum}")->getNumberFormat()->setFormatCode('0.00');
                    }

                    switch ($type) {
                        case 'stock':
                        case 'profit':
                        case 'loss':
                            $sheet->getStyle("{$nameCol}{$rowNum}")->getFont()->setBold(true);
                            $sheet->getStyle("{$totCol}{$rowNum}")->getFont()->setBold(true);
                            foreach ([$nameCol, $amtCol, $totCol] as $col) {
                                $sheet->getStyle("{$col}{$rowNum}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                            }
                            break;

                        case 'group':
                            $sheet->getStyle("{$nameCol}{$rowNum}")->getFont()->setBold(true);
                            $sheet->getStyle("{$totCol}{$rowNum}")->getFont()->setBold(true);
                            break;

                        case 'transfer':
                            $sheet->getStyle("{$nameCol}{$rowNum}")->applyFromArray(['font' => ['bold' => true, 'italic' => true]]);
                            $sheet->getStyle("{$totCol}{$rowNum}")->applyFromArray(['font' => ['bold' => true, 'italic' => true]]);
                            foreach ([$nameCol, $amtCol, $totCol] as $col) {
                                $sheet->getStyle("{$col}{$rowNum}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_HAIR);
                            }
                            break;

                        case 'account':
                        case 'item':
                            $sheet->getStyle("{$nameCol}{$rowNum}")->getAlignment()->setIndent(2);
                            break;
                        // 'blank': nothing extra
                    }
                };

                // ── Main styling loop ─────────────────────────────────────────
                foreach ($this->rowMeta as $rowNum => $meta) {

                    if (is_array($meta)) {
                        // Data row — style each side independently
                        $styleSide('A', 'B', 'C', $meta['dr'], $rowNum);
                        $sheet->getStyle("D{$rowNum}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                        $styleSide('E', 'F', 'G', $meta['cr'], $rowNum);
                        // Thin bottom on data cells
                        $sheet->getStyle("A{$rowNum}:C{$rowNum}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);
                        $sheet->getStyle("E{$rowNum}:{$lastCol}{$rowNum}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);
                        continue;
                    }

                    switch ($meta) {

                        case 'header':
                            $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");
                            $styleMap = [
                                1 => ['font' => ['bold' => true, 'size' => 18], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
                                2 => ['font' => ['size' => 11],                  'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
                                3 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
                                4 => ['font' => ['bold' => true, 'size' => 11], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
                            ];
                            if (isset($styleMap[$rowNum])) {
                                $sheet->getStyle("A{$rowNum}")->applyFromArray($styleMap[$rowNum]);
                            }
                            if ($rowNum === 1) $sheet->getRowDimension(1)->setRowHeight(26);
                            break;

                        case 'section_header':
                            $sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");
                            $sheet->getStyle("A{$rowNum}")->applyFromArray([
                                'font'      => ['bold' => true, 'size' => 12],
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                                'borders'   => ['top' => ['borderStyle' => Border::BORDER_THIN], 'bottom' => ['borderStyle' => Border::BORDER_THIN]],
                            ]);
                            $sheet->getRowDimension($rowNum)->setRowHeight(18);
                            break;

                        case 'col_group':
                            $sheet->mergeCells("A{$rowNum}:C{$rowNum}");
                            $sheet->mergeCells("E{$rowNum}:{$lastCol}{$rowNum}");
                            foreach (['A', 'E'] as $col) {
                                $sheet->getStyle("{$col}{$rowNum}")->applyFromArray([
                                    'font'      => ['bold' => true, 'size' => 11],
                                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                                ]);
                            }
                            $sheet->getStyle("D{$rowNum}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                            break;

                        case 'col_names':
                            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                                'font'    => ['bold' => true, 'size' => 10],
                                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
                            ]);
                            foreach (['B', 'C', 'F', $lastCol] as $col) {
                                $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            }
                            $sheet->getStyle("D{$rowNum}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                            break;

                        case 'total':
                            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                                'font'    => ['bold' => true, 'size' => 11],
                                'borders' => [
                                    'top'    => ['borderStyle' => Border::BORDER_MEDIUM],
                                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                                ],
                            ]);
                            foreach (['B', 'C', 'F', $lastCol] as $col) {
                                $sheet->getStyle("{$col}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                                $sheet->getStyle("{$col}{$rowNum}")->getNumberFormat()->setFormatCode('0.00');
                            }
                            $sheet->getStyle("D{$rowNum}")->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
                            break;

                        case 'net_result':
                            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                                'font'    => ['bold' => true, 'size' => 12],
                                'borders' => [
                                    'top'    => ['borderStyle' => Border::BORDER_MEDIUM],
                                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM],
                                ],
                            ]);
                            $sheet->getStyle("{$lastCol}{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                            $sheet->getStyle("{$lastCol}{$rowNum}")->getNumberFormat()->setFormatCode('0.00');
                            break;
                        // 'blank': no styling needed
                    }
                }
            },
        ];
    }
}
