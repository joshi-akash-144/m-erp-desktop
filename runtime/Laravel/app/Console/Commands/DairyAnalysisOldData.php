<?php

namespace App\Console\Commands;

use App\Models\DairyAnalysis;
use App\Models\DairyAnalysisItem;
use App\Models\ElementMapping;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Str;

class DairyAnalysisOldData extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dairy {company_id} {financial_year_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dairy Analysis Old Data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            $this->setOldData();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function setOldData()
    {
        $companyId =  $this->argument('company_id');
        $financialYearId = $this->argument('financial_year_id');

        $ccId = [
            1 => '39',
            2 => '40',
            3 => '41',
            4 => '42',
            5 => '43',
            6 => '44',
            7 => '45',
            8 => '46',
            9 => '47',
        ];


        $purchaseSerial = PurchaseInvoice::where('company_id', $companyId)->orderBy('id', 'desc')->pluck('id', 'invoice_serial');
        $salesSerial = SalesInvoice::where('company_id', $companyId)->orderBy('id', 'desc')->pluck('id', 'invoice_serial');

        $elementMaster = ElementMapping::where('company_id', $companyId)->pluck('new_element_id', 'old_element_id');

        $dairyAnDetail = DB::connection('old_db')
            ->table('dairy_analysis_result_details')
            ->get()
            ->groupBy('dar_id');

        DB::connection('old_db')
            ->table('dairy_analysis_results')
            ->where('cc_id', $ccId[$companyId])
            ->orderBy('id')
            ->chunk(200, function ($grns) use (
                $companyId,
                $financialYearId,
                $elementMaster,
                $purchaseSerial,
                $dairyAnDetail,
                $salesSerial
            ) {
                foreach ($grns as $od) {

                    $dairyAnalysis = DairyAnalysis::create([
                        'uuid' => Str::uuid(),
                        'company_id' => $companyId,
                        'financial_year_id' => $financialYearId,
                        'sales_inv_number'  => $od->salesbill_no, 
                        'sales_invoice_id' => $od->salesbill_no ? ($salesSerial[$od->salesbill_no] ?? null) : null,
                        'purchase_invoice_id' => $od->purchasevoucher_no ? ($purchaseSerial[$od->purchasevoucher_no] ?? null) : null,
                        'sales_rebate_total' => $od->dar_srebate_total,
                        'sales_premium_total' => $od->dar_spremium_total,
                        'purchase_rebate_total' => $od->dar_prebate_total,
                        'purchase_premium_total' => $od->dar_ppremium_total,
                    ]);

                    foreach ($dairyAnDetail[$od->dar_id] ?? [] as $oAd) {
                        DairyAnalysisItem::create([
                            'dairy_analysis_id' => $dairyAnalysis->id,
                            'element_id' => $elementMaster[$oAd->dar_element_id] ?? null,
                            'guarantee' => $oAd->dar_guarantee,
                            'actual' => $oAd->dar_actual,
                            'diff' => $oAd->dar_difference,
                            'sales_rebate' => $oAd->dar_sales_rebate,
                            'sales_premium' => $oAd->dar_sales_premium,
                            'purchase_rebate' => $oAd->dar_purchase_rebate,
                            'purchase_premium' => $oAd->dar_purchase_premium,
                            'rebate_percentage' => 0
                        ]);
                    }
                }
            });
    }
}
