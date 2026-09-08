<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Gstr1OfflineExport implements WithMultipleSheets
{
    use Exportable;

    protected object $company;
    protected array $summaryData;
    protected array $detailDataMap;
    protected ?string $datePeriod;

    public function __construct(object $company, array $summaryData, array $detailDataMap, ?string $datePeriod = null)
    {
        $this->company = $company;
        $this->summaryData = $summaryData;
        $this->detailDataMap = $detailDataMap;
        $this->datePeriod = $datePeriod;
    }

    public function sheets(): array
    {
        $sheets = [];

        // 1. Help Instruction
        $sheets[] = new Gstr1OfflineHelpExport();

        // 2. Data Sheets
        $sections = [
            'b2b'         => ['sheet' => 'b2b,sez,de', 'summaryTitle' => 'Summary For B2B(4)', 'headers' => ['GSTIN/UIN of Recipient', 'Receiver Name', 'Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Invoice Type', 'E-Commerce GSTIN', 'Rate', 'Taxable Value', 'Cess Amount']],
            'b2ba'        => ['sheet' => 'b2ba', 'summaryTitle' => 'Summary For B2BA(4A)', 'headers' => ['Original GSTIN', 'Original Invoice Number', 'Original Invoice date', 'Revised GSTIN', 'Revised Receiver Name', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Invoice Type', 'E-Commerce GSTIN', 'Rate', 'Taxable Value', 'Cess Amount']],
            'b2cl'        => ['sheet' => 'b2cl', 'summaryTitle' => 'Summary For B2CL(5)', 'headers' => ['Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN']],
            'b2cla'       => ['sheet' => 'b2cla', 'summaryTitle' => 'Summary For B2CLA(5A)', 'headers' => ['Original Invoice Number', 'Original Invoice date', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN']],
            'b2cs'        => ['sheet' => 'b2cs', 'summaryTitle' => 'Summary For B2CS(7)', 'headers' => ['Type', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN']],
            'b2csa'       => ['sheet' => 'b2csa', 'summaryTitle' => 'Summary For B2CSA(7A)', 'headers' => ['Financial Year', 'Original Month', 'Type', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount', 'E-Commerce GSTIN']],
            'cdnr'        => ['sheet' => 'cdnr', 'summaryTitle' => 'Summary For CDNR(9B)', 'headers' => ['GSTIN/UIN of Recipient', 'Receiver Name', 'Note/Refund Voucher Number', 'Note/Refund Voucher date', 'Document Type', 'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount']],
            'cdnra'       => ['sheet' => 'cdnra', 'summaryTitle' => 'Summary For CDNRA(9B)', 'headers' => ['Original Note/Refund Voucher Number', 'Original Note/Refund Voucher date', 'Revised Note/Refund Voucher Number', 'Revised Note/Refund Voucher date', 'Document Type', 'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount']],
            'cdnu'        => ['sheet' => 'cdnur', 'summaryTitle' => 'Summary For CDNUR(9B)', 'headers' => ['UR Type', 'Note/Refund Voucher Number', 'Note/Refund Voucher date', 'Document Type', 'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount']],
            'cdnura'      => ['sheet' => 'cdnura', 'summaryTitle' => 'Summary For CDNURA(9B)', 'headers' => ['Original Note/Refund Voucher Number', 'Original Note/Refund Voucher date', 'Revised Note/Refund Voucher Number', 'Revised Note/Refund Voucher date', 'Document Type', 'Place Of Supply', 'Note/Refund Voucher Value', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount']],
            'exports'     => ['sheet' => 'exp', 'summaryTitle' => 'Summary For EXP(6)', 'headers' => ['Export Type', 'Invoice Number', 'Invoice date', 'Invoice Value', 'Port Code', 'Shipping Bill Number', 'Shipping Bill Date', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value']],
            'expa'        => ['sheet' => 'expa', 'summaryTitle' => 'Summary For EXPA(6A)', 'headers' => ['Original Invoice Number', 'Original Invoice date', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Port Code', 'Shipping Bill Number', 'Shipping Bill Date', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value']],
            'at'          => ['sheet' => 'at', 'summaryTitle' => 'Summary For AT(11A)', 'headers' => ['Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Received', 'Cess Amount']],
            'ata'         => ['sheet' => 'ata', 'summaryTitle' => 'Summary For ATA(11A)', 'headers' => ['Financial Year', 'Original Month', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Received', 'Cess Amount']],
            'atadj'       => ['sheet' => 'atadj', 'summaryTitle' => 'Summary For ATADJ(11B)', 'headers' => ['Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Adjusted', 'Cess Amount']],
            'atadja'      => ['sheet' => 'atadja', 'summaryTitle' => 'Summary For ATADJA(11B)', 'headers' => ['Financial Year', 'Original Month', 'Place Of Supply', 'Applicable % of Tax Rate', 'Rate', 'Gross Advance Adjusted', 'Cess Amount']],
            'nil_rated'   => ['sheet' => 'exemp', 'summaryTitle' => 'Summary For Nil rated, exempted and non GST outward supplies (8)', 'headers' => ['Description', 'Nil Rated Supplies', 'Exempted (Other than Nil rated/non-GST supply)', 'Non-GST supplies']],
            'hsn_summary' => ['sheet' => 'hsn(b2b)', 'summaryTitle' => 'Summary For HSN(12)', 'headers' => ['HSN', 'Description', 'UQC', 'Total Quantity', 'Total Value', 'Rate', 'Taxable Value', 'Integrated Tax Amount', 'Central Tax Amount', 'State/UT Tax Amount', 'Cess Amount']],
            'hsn_b2c'     => ['sheet' => 'hsn(b2c)', 'summaryTitle' => 'Summary For HSN(12)', 'headers' => ['HSN', 'Description', 'UQC', 'Total Quantity', 'Total Value', 'Rate', 'Taxable Value', 'Integrated Tax Amount', 'Central Tax Amount', 'State/UT Tax Amount', 'Cess Amount']],
            'docs'        => ['sheet' => 'docs', 'summaryTitle' => 'Summary For DOCS(13)', 'headers' => ['Nature of Document', 'Sr. No. From', 'Sr. No. To', 'Total Number', 'Cancelled']],
            'eco'         => ['sheet' => 'eco', 'summaryTitle' => 'Summary For ECO(14A, 14B)', 'headers' => ['GSTIN/UIN of E-Commerce Operator', 'GSTIN of Supplier', 'Merchant Name', 'Invoice Number', 'Invoice date', 'Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount']],
            'ecoa'        => ['sheet' => 'ecoa', 'summaryTitle' => 'Summary For ECOA(14A, 14B)', 'headers' => ['Original GSTIN/UIN of E-Commerce Operator', 'Original GSTIN of Supplier', 'Original Invoice Number', 'Original Invoice date', 'Revised GSTIN/UIN of E-Commerce Operator', 'Revised GSTIN of Supplier', 'Revised Merchant Name', 'Revised Invoice Number', 'Revised Invoice date', 'Revised Invoice Value', 'Place Of Supply', 'Reverse Charge', 'Applicable % of Tax Rate', 'Rate', 'Taxable Value', 'Cess Amount']],
        ];

        foreach ($sections as $sectionKey => $config) {
            $data = $this->detailDataMap[$sectionKey] ?? [];
            if (!empty($data)) {
                $sheets[] = new Gstr1OfflineSheetExport($sectionKey, $data);
            } else {
                $sheets[] = new Gstr1OfflineEmptyExport($config['sheet'], $config['summaryTitle'], $config['headers']);
            }
        }

        return $sheets;
    }
}
