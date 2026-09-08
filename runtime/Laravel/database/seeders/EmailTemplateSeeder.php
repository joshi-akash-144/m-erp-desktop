<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('email_templates')->insert([
            [
                'name' => 'Rebate Due To Low Quality Of Material',
                'slug' => 'rebate-due-to-low-quality-of-material',
                'template_name' => 'rebate-due-to-low-quality-of-material',
                'subject' => 'Rebate Due To Low Quality Of Material',
                'message' => '<p>{SUPPLIER-NAME}</p>
                <p>{SUPPLIER-ADDRESS}</p>
                <p>SUB: Rebate Due To Low Quality Of Material</p>
                <p>Dear Sir,</p>
                <p>Please Note That Your Below-Stated Consignment Of {PRODUCT-NAME} Has Been Observed Low Quality Of Material &amp; Not As Per Quality Condition By Our Quality Control Dept With Total Bag Sampling Of Material. So Rebate Of The Material Will Be Deducted From Your Bill Payment.</p>
                <p>We Will Unload The Same After Your Confirmation Regarding The Rebate Accepted By You. Please Reply by Return Mail as Early As Possible.</p>
                <p>Invoice Number: {ORDER-ID}</p>
                <p>Date: {ORDER-DATE}</p>
                <p>Vehicle Number: {VEHICLE-NUMBER}</p>
                <p>{PRODUCT-TABLE}</p>
                <p>We Hereby Inform You That You Have To Send Good Quality Material To Avoid Rebate Deduction Or Rejection Of The Material.</p>
                <p>For,<br>{COMPANY-NAME}</p>',
                'status' => 0,
                'created_by' => 1,
                'created_at' => now(),
            ],
            [
                'name' => 'Urgent Fast Delivery Of Pending Material',
                'slug' => 'urgent-fast-delivery-of-pending-material',
                'template_name' => 'urgent-fast-delivery-of-pending-material',
                'subject' => 'Urgent Fast Delivery Of Pending Material',
                'message' => '<p>To</p>
                <p>M/s</p>
                <p>{SUPPLIER-NAME}</p>
                <p>SUB: Urgent Fast Delivery Of Pending Material</p>
                <p>Dear Sir,</p>
                <p>Please Note That Your Below-stated Souda / Contract Material Is Still Now Pending delivery &amp; the date of delivery has been expired or likely to expire. we hereby strictly Inform you That You Have To Deliver The Said Pending/balance quantity of material as fast &amp; urgent basic.</p>
                <p>{PRODUCT-TABLE}</p>
                <p>Thanks &amp; Regards</p>
                <p>{COMPANY-NAME}{COMPANY-ADDRESS}</p>',
                'status' => 0,
                'created_by' => 1,
                'created_at' => now(),
            ],
            [
                'name' => 'Send to bank Payment Advise',
                'slug' => 'send-to-bank-payment-advise',
                'template_name' => 'send-to-bank-payment-advise',
                'subject' => 'Payment Advise - {COMPANY-NAME} - {DATE}',
                'message' => '<p>Hello Sir / Madam,<br>Please Find The Payment Advice in The Attachment.</p>
                <p>Thanks &amp; Regards<br>{COMPANY-NAME}</p>',
                'status' => 0,
                'created_by' => 1,
                'created_at' => now(),
            ],
            [
                'name' => 'Send to bank RTGS Register Print',
                'slug' => 'send-to-bank-rtgs-register-print',
                'template_name' => 'send-to-bank-rtgs-register-print',
                'subject' => '{COMPANY-NAME} CHQ. NO. : {CHEQUE-NO}',
                'message' => '<p>Hello Sir / Madam,</p>
                <p>Please Find The RTGS Register in The Attachment.</p>
                <p>{PRODUCT-TABLE}</p>
                <p>Thanks &amp; Regards<br>{COMPANY-NAME}{COMPANY-ADDRESS}</p>',
                'status' => 0,
                'created_by' => 1,
                'created_at' => now(),

            ],
        ]);
    }
}