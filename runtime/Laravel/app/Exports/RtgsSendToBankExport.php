<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RtgsSendToBankExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithCustomValueBinder
{
    protected array $rows;
    protected string $password;

    public function __construct(array $rows, string $password = '1234')
    {
        $this->rows = $rows;
        $this->password = $password;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $column = $cell->getColumn();
        // Columns D (Remitter A/c), G (Beneficiary A/c), K (Cheque Alpha), L (Cheque No) should be strings
        if (in_array($column, ['D', 'G', 'K', 'L'])) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return collect($this->rows)->map(fn($row) => [
            $row['debit_type']                   ?? 'SD',
            $row['unique_transaction_reference'] ?? '',
            $row['transaction_date']             ?? '',
            $row['remitter_account_number']      ?? '',
            $row['mode_of_transfer']             ?? '',
            $row['amount']                       ?? 0,
            $row['beneficiary_account_number']   ?? '',
            $row['beneficiary_bank_ifsc']        ?? '',
            $row['beneficiary_account_name']     ?? '',
            $row['cheque_date']                  ?? '',
            $row['cheque_alpha_number']          ?? '',
            $row['cheque_number']                ?? '',
            $row['cheque_amount']                ?? 0,
            $row['remitting_customer_lei']       ?? '',
            $row['beneficiary_lei']              ?? '',
        ]);
    }

    public function headings(): array
    {
        return [
            'Debit Type',
            'Unique Transaction Reference',
            'Transaction Date',
            'Remitter Account Number',
            'Mode of Transfer (NEFT, RTGS, IFT)',
            'Amount',
            'Beneficiary Account Number',
            'Beneficiary Bank IFSC',
            'Beneficiary Account Name',
            'Cheque Date',
            'Cheque Alpha Number',
            'Cheque Number',
            'Cheque Amount',
            'Remitting customer LEI (Mandatory if amount is >50cr)',
            'Beneficiary Customer LEI (Mandatory if amount is >50cr)',
            'Sender to Receiver Information',
            'Beneficiary mobile number',
            'Beneficiary Email ID'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 25,
            'C' => 18,
            'D' => 25,
            'E' => 35,
            'F' => 15,
            'G' => 25,
            'H' => 25,
            'I' => 35,
            'J' => 18,
            'K' => 20,
            'L' => 18,
            'M' => 18,
            'N' => 30,
            'O' => 30,
            'P' => 30,
            'Q' => 30,
            'R' => 30,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Format specific columns as text to prevent Excel from converting large numbers
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('K')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('L')->getNumberFormat()->setFormatCode('@');

        // Style the header row (yellow background, bold)
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            // 'fill' => [
            //     'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            //     'startColor' => [
            //         'argb' => 'FFFFFF00', // Yellow
            //     ],
            // ],
        ]);

        // Apply password protection to the sheet
        if (!empty($this->password)) {
            $sheet->getProtection()->setSheet(true);
            $sheet->getProtection()->setPassword($this->password);
        }

        return [];
    }
}