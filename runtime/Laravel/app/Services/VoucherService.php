<?php

namespace App\Services;

use App\Models\VoucherType;
use App\Repositories\VoucherRepository;
use App\DTOs\VoucherNumberDTO;
use App\DTOs\GrnDTO;
use App\DTOs\DeliveryChallanDTO;
use App\Models\Voucher;
use App\Models\Grn;
use App\Models\DeliveryChallan;
use Illuminate\Support\Facades\DB;
use App\Repositories\GrnRepository;
use App\Repositories\DeliveryChallanRepository;

class VoucherService
{
    protected VoucherRepository $voucherRepo;
    protected GrnRepository $grnRepo;
    protected DeliveryChallanRepository $deliveryChallanRepo;
    public function __construct(VoucherRepository $voucherRepo, GrnRepository $grnRepo, DeliveryChallanRepository $deliveryChallanRepo)
    {
        $this->voucherRepo = $voucherRepo;
        $this->grnRepo = $grnRepo;
        $this->deliveryChallanRepo = $deliveryChallanRepo;
    }



    public function getNextVoucherNumber(int $voucherTypeId, int $companyId, int $financialYearId): VoucherNumberDTO
    {
        $lastSerial = $this->voucherRepo->getNextVoucherSerial($voucherTypeId, $companyId, $financialYearId);
        $nextSerial = $lastSerial + 1;

        // Prefix logic based on constants
        $prefix = match ($voucherTypeId) {
            VoucherType::PURCHASE_INVOICE      => 'PI',
            VoucherType::SALE_INVOICE          => 'SI',
            VoucherType::PAYMENT               => 'PAY',
            VoucherType::RECEIPT               => 'REC',
            VoucherType::JOURNAL               => 'JRN',
            VoucherType::PURCHASE_ORDER        => 'PO',
            VoucherType::SALE_ORDER            => 'SO',
            VoucherType::GOODS_RECEIPT_NOTE    => 'GRN',
            VoucherType::GOODS_ISSUE_NOTE      => 'GIN',
            VoucherType::DEBIT_NOTE            => 'DN',
            VoucherType::CREDIT_NOTE           => 'CN',
            VoucherType::CONTRA                => 'CTR',
            VoucherType::EXPENSE               => 'EXP',
            VoucherType::INCOME                => 'INC',
            VoucherType::ADVANCE_PAYMENT       => 'ADP',
            VoucherType::ADVANCE_RECEIPT       => 'ADR',
            VoucherType::BANK_RECONCILIATION   => 'BRS',
            VoucherType::OPENING_BALANCE       => 'OPB',
            VoucherType::INVENTORY_ADJUSTMENT  => 'STK',
            VoucherType::PAYROLL               => 'PAYR',
            VoucherType::TAX_PAYMENT           => 'TAXP',
            VoucherType::TAX_RECEIPT           => 'TAXR',
            VoucherType::PETTY_CASH            => 'PC',
            VoucherType::CONTRA_RECEIPT        => 'CRR',
            VoucherType::CONTRA_PAYMENT        => 'CRP',
            VoucherType::STOCK                 => 'STK',
            VoucherType::DELIVERY_CHALLAN      => 'DC',
            VoucherType::OPENING_STOCK         => 'OPS',
            VoucherType::PURCHASE_RETURN       => 'PRDN',
            VoucherType::SALES_RETURN          => 'SRCN',
            default                            => 'VCH'
        };

        $year = now()->format('Y');

        $nextSerial = $lastSerial + 1;


        $financialYearName = financial_year_name() ?? now()->format('Y');

        $year = str_replace(['-', ' ', 'FY'], '', $financialYearName);

        if (strlen($year) > 4) {
            $year = substr($year, -4);
        }

        return new VoucherNumberDTO(
            serial: $nextSerial,
            voucher_number: sprintf("%s-%s-%05d", $prefix, $year, $nextSerial)
        );
    }

    public function createVoucher(array $master, array $lines): Voucher
    {
        $master['created_by'] = current_user_id();
        $voucher = $this->voucherRepo->create($master);

        $voucher->details()->createMany($lines);

        return $voucher;
    }

    public function checkUUIDExists($uuid)
    {
        return $this->voucherRepo->checkUUIDExists($uuid);
    }

    public function updateVoucher(array $master, array $lines, int $voucherId): Voucher
    {
        $voucher = $this->voucherRepo->find($voucherId);

        if (!$voucher) {
            throw new \Exception("Voucher not found");
        }
        $master['updated_by'] = current_user_id();
        $voucher = $this->voucherRepo->update($voucher, $master);

        $voucher->details()->delete();
        $voucher->details()->createMany($lines);

        return $voucher;
    }

    public function getAccountsClosingBalance(int $companyId, int $financialYearId, ?array $accountIds)
    {
        return  DB::table('voucher_transactions as vt')
            ->join('vouchers as v', 'v.id', '=', 'vt.voucher_id')
            ->whereIn('vt.account_id', $accountIds)
            ->where('v.company_id', $companyId)
            ->where('v.financial_year_id', $financialYearId)
            ->whereNull('v.deleted_at')
            ->select(
                'vt.account_id',
                DB::raw('SUM(vt.debit) as dr'),
                DB::raw('SUM(vt.credit) as cr')
            )
            ->groupBy('vt.account_id')
            ->get();
    }

    /* Last Purchase GRN */
    public function getLastGrn(int $companyId, int $financialYearId)
    {
        $grnData = Grn::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderBy('grn_serial', 'desc')
            ->first();

        $grnSerial = $grnData ? $grnData->grn_serial + 1 : 1;
        return $grnSerial;
    }

    public function getNextGrnVoucherNumber(int $companyId, int $financialYearId): GrnDTO
    {
        $lastSerial = $this->grnRepo->getNextVoucherSerial($companyId, $financialYearId);

        $nextSerial = $lastSerial + 1;

        $prefix = 'GRN';

        $financialYearName = financial_year_name() ?? now()->format('Y');

        $year = str_replace(['-', ' ', 'FY'], '', $financialYearName);

        if (strlen($year) > 4) {
            $year = substr($year, -4);
        }

        return new GrnDTO(
            grn_serial: $nextSerial,
            grn_number: sprintf("%s-%s-%05d", $prefix, $year, $nextSerial)
        );
    }

    /* Last Sale Delivery Challan */
    public function getLastDeliveryChallan(int $companyId, int $financialYearId)
    {
        $deliveryChallanData = DeliveryChallan::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderBy('challan_serial', 'desc')
            ->first();

        $deliveryChallanSerial = $deliveryChallanData ? $deliveryChallanData->challan_serial + 1 : 1;
        return $deliveryChallanSerial;
    }

    public function getNextDeliveryChallanVoucherNumber(int $companyId, int $financialYearId): DeliveryChallanDTO
    {
        $lastSerial = $this->deliveryChallanRepo->getNextVoucherSerial($companyId, $financialYearId);

        $nextSerial = $lastSerial + 1;

        $prefix = 'DC';

        $financialYearName = financial_year_name() ?? now()->format('Y');

        $year = str_replace(['-', ' ', 'FY'], '', $financialYearName);

        if (strlen($year) > 4) {
            $year = substr($year, -4);
        }

        return new DeliveryChallanDTO(
            challan_serial: $nextSerial,
            challan_number: sprintf("%s-%s-%05d", $prefix, $year, $nextSerial)
        );
    }
}
