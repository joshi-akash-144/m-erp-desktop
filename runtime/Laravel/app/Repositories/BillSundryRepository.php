<?php

namespace App\Repositories;

use App\Models\BillSundry;
use Illuminate\Support\Facades\DB;

class BillSundryRepository extends BaseRepository
{
    public function __construct(BillSundry $billSundry)
    {
        parent::__construct($billSundry);
    }

    public function create(array $data): BillSundry
    {
        return DB::transaction(function () use ($data) {

            // -----------------------------
            // Normalize inputs (SAFE)
            // -----------------------------
            $purchaseAccountType       = $data['purchase_account_type'] ?? null;
            $purchasePartyAccountType  = $data['purchase_party_account_type'] ?? null;
            $saleAccountType           = $data['sale_account_type'] ?? null;
            $salePartyAccountType      = $data['sale_party_account_type'] ?? null;

            $purchaseAdjustAmount      = $data['purchase_adjust_in_amount'] ?? false;
            $purchaseAdjustPartyAmount = $data['purchase_adjust_in_party_amount'] ?? false;
            $saleAdjustAmount          = $data['sale_adjust_in_amount'] ?? false;
            $saleAdjustPartyAmount     = $data['sale_adjust_in_party_amount'] ?? false;

            // -----------------------------
            // Purchase logic
            // -----------------------------
            $hasSeparateAccountForPurchase = $this->hasSeparateAccount(
                $purchaseAccountType,
                $purchasePartyAccountType
            );

            if (
                !$purchaseAdjustAmount &&
                !$purchaseAdjustPartyAmount &&
                $hasSeparateAccountForPurchase
            ) {
                $data['purchase_post_over_and_above'] = true;
            }

            // -----------------------------
            // Sale logic
            // -----------------------------
            $hasSeparateAccountForSale = $this->hasSeparateAccount(
                $saleAccountType,
                $salePartyAccountType
            );

            if (
                !$saleAdjustAmount &&
                !$saleAdjustPartyAmount &&
                $hasSeparateAccountForSale
            ) {
                $data['sale_post_over_and_above'] = true;
            }

            // -----------------------------
            // Code generation (company-wise)
            // -----------------------------
            $companyId = $data['company_id'];

            $lastCode = $this->model
                ->where('company_id', $companyId)
                ->orderByDesc('id')
                ->value('code');

            if (!$lastCode) {
                throw new \Exception("Bill Sundry code sequence not found for company [$companyId]");
            }

            return $this->model->create(array_merge($data, [
                'code' => $lastCode + 1,
            ]));
        });
    }

    public function updateBillSundry(BillSundry $billSundry, array $data): BillSundry
    {
        return DB::transaction(function () use ($billSundry, $data) {

            // -----------------------------
            // Normalize inputs (SAFE)
            // -----------------------------
            $purchaseAccountType       = $data['purchase_account_type'] ?? null;
            $purchasePartyAccountType  = $data['purchase_party_account_type'] ?? null;
            $saleAccountType           = $data['sale_account_type'] ?? null;
            $salePartyAccountType      = $data['sale_party_account_type'] ?? null;

            $purchaseAdjustAmount      = $data['purchase_adjust_in_amount'] ?? false;
            $purchaseAdjustPartyAmount = $data['purchase_adjust_in_party_amount'] ?? false;
            $saleAdjustAmount          = $data['sale_adjust_in_amount'] ?? false;
            $saleAdjustPartyAmount     = $data['sale_adjust_in_party_amount'] ?? false;

            // -----------------------------
            // Purchase logic
            // -----------------------------
            $hasSeparateAccountForPurchase = $this->hasSeparateAccount(
                $purchaseAccountType,
                $purchasePartyAccountType
            );

            if (
                !$purchaseAdjustAmount &&
                !$purchaseAdjustPartyAmount &&
                $hasSeparateAccountForPurchase
            ) {
                $data['purchase_post_over_and_above'] = true;
            }

            // -----------------------------
            // Sale logic
            // -----------------------------
            $hasSeparateAccountForSale = $this->hasSeparateAccount(
                $saleAccountType,
                $salePartyAccountType
            );

            if (
                !$saleAdjustAmount &&
                !$saleAdjustPartyAmount &&
                $hasSeparateAccountForSale
            ) {
                $data['sale_post_over_and_above'] = true;
            }

            $billSundry->update($data);

            return $billSundry;
        });
    }

    /**
     * Check if separate account is selected
     */
    private function hasSeparateAccount(?string $type1, ?string $type2): bool
    {
        return in_array($type1, [
            'specify_account',
            'specify_account_in_voucher',
        ], true)
            || in_array($type2, [
                'specify_account',
                'specify_account_in_voucher',
            ], true);
    }
}
