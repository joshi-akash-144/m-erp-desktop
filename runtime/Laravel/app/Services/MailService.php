<?php

namespace App\Services;

use App\Exports\RtgsSendToBankExport;
use App\Mail\SendEmailTemplateMail;
use App\Models\Account;
use App\Models\GodownAnalysis;
use App\Models\CompanyMailConfig;
use App\Models\CompanyBankMailConfig;
use App\Models\DairyAnalysis;
use App\Models\EmailTemplate;
use App\Models\PaymentVoucher;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MailService
{
    protected CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /*--------------------------------------------------------------
    | Configure the SMTP mailer from company's mail config record
    --------------------------------------------------------------*/
    private function configureMailer(int $companyId): void
    {
        $config = CompanyMailConfig::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            throw new \Exception(
                'No active mail configuration found for this company. ' .
                    'Please set it up in Setup → Mail Config.'
            );
        }
        Config::set('mail.mailers.smtp', [
            'transport'  => 'smtp',
            'host'       => $config->host,
            'port'       => $config->port,
            'encryption' => $config->encryption === 'none' ? null : $config->encryption,
            'username'   => $config->username,
            'password'   => $config->password,
            'timeout'    => null,
        ]);

        Config::set('mail.from', [
            'address' => $config->from_address,
            'name'    => $config->from_name,
        ]);

        Mail::purge('smtp');
    }

    /*--------------------------------------------------------------
    | Configure SMTP from bank mail config; fall back to company
    | mail config if no active bank config is found.
    --------------------------------------------------------------*/
    private function configureBankMailer(int $companyId): void
    {
        $bankConfig = CompanyBankMailConfig::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if ($bankConfig) {
            Config::set('mail.mailers.smtp', [
                'transport'  => 'smtp',
                'host'       => $bankConfig->host,
                'port'       => $bankConfig->port,
                'encryption' => $bankConfig->encryption === 'none' ? null : $bankConfig->encryption,
                'username'   => $bankConfig->username,
                'password'   => $bankConfig->password,
                'timeout'    => null,
            ]);

            Config::set('mail.from', [
                'address' => $bankConfig->from_address,
                'name'    => $bankConfig->from_name,
            ]);

            Mail::purge('smtp');
        } else {
            // No bank-specific config — fall back to company mail config
            $this->configureMailer($companyId);
        }
    }

    /*--------------------------------------------------------------
    | RTGS — Preview (prepare data + generate Excel, do not send)
    | Returns all data needed to populate the compose modal.
    --------------------------------------------------------------*/
    public function previewRtgsToBankEmail(
        array  $paymentVoucherIds,
        int    $bankId,
        string $chequeNo,
        int    $companyId,
        int    $financialYearId
    ): array {
        $pvRecords = $this->fetchVoucherRecords($paymentVoucherIds, $companyId, $financialYearId);

        if ($pvRecords->isEmpty()) {
            throw new \Exception('No payment records found for the selected vouchers.');
        }

        $bankAccount = Account::find($bankId);
        $bankEmail   = $bankAccount?->email ?? '';

        if (empty($bankEmail)) {
            throw new \Exception(
                'Bank email address not found. Please update the bank account email in master data.'
            );
        }

        $defaultPass = 'rtgs@bank';

        $company     = $this->companyService->current($companyId);
        $companyName = $company?->name ?? '';
        $companyCode = $company?->code ?? 'CMP';

        $password = $companyCode . $defaultPass;

        ['subject' => $subject, 'body' => $body] = $this->resolveTemplate(
            'send-to-bank-rtgs-register-print',
            ['COMPANY-NAME' => $companyName, 'CHEQUE-NO' => $chequeNo]
        );



        $exportRows    = $this->buildRtgsExportRows($pvRecords, $chequeNo, $bankAccount);
        $filePath      = $this->saveRtgsExcel($exportRows, $chequeNo, $password, $companyCode, $bankAccount);
        // $filePath      = $this->saveRtgsExcel($exportRows, $chequeNo);
        $excelFilename = basename($filePath);

        // Suppliers and customers with email — available for CC selection
        $ccAccounts = Account::where('company_id', $companyId)
            ->whereIn('party_type', [Account::CUSTOMER_TYPE, Account::SUPPLIER_TYPE])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'party_type'])
            ->toArray();

        return [
            'to_email'       => $bankEmail,
            'bank_name'      => $bankAccount->name,
            'subject'        => $subject,
            'body'           => $body,
            'excel_filename' => $excelFilename,
            'records_count'  => count($exportRows),
            'cheque_no'      => $chequeNo,
            'cc_accounts'    => $ccAccounts,
        ];
    }

    /*--------------------------------------------------------------
    | RTGS — Send to Bank Email (uses pre-generated Excel from preview)
    --------------------------------------------------------------*/
    public function sendRtgsToBankEmail(
        array  $paymentVoucherIds,
        int    $bankId,
        string $chequeNo,
        int    $companyId,
        int    $financialYearId,
        string $toEmail,
        array  $ccEmails = [],
        string $subject = '',
        string $body = '',
        string $excelFilename = ''
    ): array {
        $this->configureBankMailer($companyId);
        $bankAccount = Account::find($bankId);

        // Extract password from body in case we need to rebuild the Excel file
        preg_match('/The password is: <b>(.*?)<\/b>/', $body, $matches);
        $fallbackPassword = $matches[1] ?? '';

        // Use the Excel generated during preview; fall back to a fresh one if missing
        if ($excelFilename) {
            $filePath = storage_path('app/public/rtgs_register/' . $excelFilename);
            if (!file_exists($filePath)) {
                $filePath = $this->rebuildExcel($paymentVoucherIds, $companyId, $financialYearId, $chequeNo, $bankAccount, $fallbackPassword);
            }
        } else {
            $filePath = $this->rebuildExcel($paymentVoucherIds, $companyId, $financialYearId, $chequeNo, $bankAccount, $fallbackPassword);
        }
        $fileMimeType = mime_content_type($filePath);

        $mailer = Mail::mailer('smtp')->to($toEmail);

        if (!empty($ccEmails)) {
            $mailer->cc($ccEmails);
        }

        $this->safelySendMail($mailer, $toEmail, $subject, new SendEmailTemplateMail(
            $subject,
            $body,
            null,
            null,
            $filePath,
            basename($filePath),
            $fileMimeType,
        ), $companyId, $financialYearId);

        $bankAccount = Account::find($bankId);

        return [
            'bank_email'    => $toEmail,
            'bank_name'     => $bankAccount?->name ?? '',
            'subject'       => $subject,
            'records_count' => count($paymentVoucherIds),
            'cheque_no'     => $chequeNo,
        ];
    }

    /*--------------------------------------------------------------
    | Template resolver
    --------------------------------------------------------------*/
    public function resolveTemplate(string $templateName, array $replacements = []): array
    {
        $template = EmailTemplate::where('template_name', $templateName)
            ->where('status', 0)
            ->first();

        if (!$template) {
            throw new \Exception(
                "Email template '{$templateName}' not found. " .
                    "Please create it in the Email Templates module."
            );
        }

        $search  = array_map(fn($k) => '{' . $k . '}', array_keys($replacements));
        $replace = array_values($replacements);

        return [
            'subject' => str_replace($search, $replace, $template->subject ?? ''),
            'body'    => str_replace($search, $replace, $template->message ?? ''),
        ];
    }

    /*--------------------------------------------------------------
    | PRIVATE HELPERS
    --------------------------------------------------------------*/
    private function fetchVoucherRecords(array $ids, int $companyId, int $financialYearId)
    {
        return PaymentVoucher::with([
            'voucher:id,voucher_date,voucher_serial',
            'account:id,name,city',
            'account.bankDetail:id,account_id,bank_name,bank_branch_name,bank_account_number,bank_ifsc',
            'payment:id,cheque_number,cheque_alpha_number',
        ])
            ->whereIn('id', $ids)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();
    }

    private function rebuildExcel(array $ids, int $companyId, int $financialYearId, string $chequeNo, $bankAccount = null, string $password = ''): string
    {
        $pvRecords  = $this->fetchVoucherRecords($ids, $companyId, $financialYearId);
        $exportRows = $this->buildRtgsExportRows($pvRecords, $chequeNo, $bankAccount);

        $company = $this->companyService->current($companyId);
        $companyCode = $company?->code ?? 'CMP';

        if (empty($password)) {
            $password = $companyCode . '@' . \Illuminate\Support\Str::password(8);
        }

        return $this->saveRtgsExcel($exportRows, $chequeNo, $password, $companyCode, $bankAccount);
    }

    private function buildRtgsExportRows($pvRecords, string $chequeNo, $bankAccount = null): array
    {
        $remitterAccountNo = $bankAccount?->bankDetail?->bank_account_number ?? '';
        $remitterIfsc      = $bankAccount?->bankDetail?->bank_ifsc ?? '';
        $totalAmount       = $pvRecords->sum('paid_amount');

        $firstPv = $pvRecords->first();
        $financialYearId = $firstPv->financial_year_id ?? null;
        $fyNameStr = '';

        if ($financialYearId) {
            $fy = \App\Models\FinancialYear::find($financialYearId);
            if ($fy && $fy->name) {
                $parts = explode('-', $fy->name);
                if (count($parts) === 2) {
                    $fyNameStr = substr(trim($parts[0]), -2) . substr(trim($parts[1]), -2);
                } else {
                    $fyNameStr = substr(preg_replace('/[^0-9]/', '', $fy->name), -4);
                }
            }
        }

        if (empty($fyNameStr)) {
            $fyNameStr = date('y') . date('y', strtotime('+1 year')); // e.g. 2425
        }

        $paymentId = $firstPv->payment->id ?? '0000';
        $paymentIdStr = str_pad($paymentId, 4, '0', STR_PAD_LEFT);

        $base = 'PAY' . $fyNameStr . $paymentIdStr;
        if (strlen($base) > 11) {
            if (strlen('PAY' . $paymentIdStr) > 11) {
                // Extreme case: Payment ID alone is massive, just take the last 11 characters
                $uniqueTransactionRef = substr('PAY' . $paymentIdStr, -11);
            } else {
                // Compress the Financial Year string to make room for the Payment ID
                $fyAllowedLen = 11 - strlen('PAY') - strlen($paymentIdStr);
                $uniqueTransactionRef = 'PAY' . substr($fyNameStr, -$fyAllowedLen) . $paymentIdStr;
            }
        } else {
            $uniqueTransactionRef = $base;
        }

        // Determine a unified transaction execution date that is never less than today
        $maxDate = $pvRecords->max('voucher.voucher_date');

        $today = date('Y-m-d');
        $executionDate = ($maxDate > $today) ? date('d-M-Y', strtotime($maxDate)) : date('d-M-Y');

        return $pvRecords->sortBy(fn($pv) => $pv->account?->name ?? '')->values()->map(function ($pv, $i) use ($chequeNo, $remitterAccountNo, $remitterIfsc, $totalAmount, $uniqueTransactionRef, $executionDate) {
            $beneficiaryAccountNo = $pv->account?->bankDetail?->bank_account_number ?? '';
            $beneficiaryIfsc      = $pv->account?->bankDetail?->bank_ifsc ?? '';
            $amount               = $pv->paid_amount ?? 0;

            // Determine Mode of Transfer
            $mode = 'NEFT';
            if ($beneficiaryIfsc && $remitterIfsc && substr($beneficiaryIfsc, 0, 4) === substr($remitterIfsc, 0, 4)) {
                $mode = 'IFT';
            } elseif ($amount >= 200000) {
                $mode = 'NEFT';
            }

            return [
                'debit_type'                   => 'SD',
                'unique_transaction_reference' => $uniqueTransactionRef,
                'transaction_date'             => $executionDate,
                'remitter_account_number'      => $remitterAccountNo,
                'mode_of_transfer'             => $mode,
                'amount'                       => $amount,
                'beneficiary_account_number'   => $beneficiaryAccountNo,
                'beneficiary_bank_ifsc'        => $beneficiaryIfsc,
                'beneficiary_account_name'     => $pv->account?->name ?? '',
                'cheque_date'                  => !empty($pv->voucher->voucher_date) ? date('d-M-Y', strtotime($pv->voucher->voucher_date)) : date('d-M-Y'),
                'cheque_alpha_number'          => $pv->payment?->cheque_alpha_number ?? '',
                'cheque_number'                => $pv->payment?->cheque_number ?? $chequeNo,
                'cheque_amount'                => $totalAmount,
                'remitting_customer_lei'       => '',
                'beneficiary_lei'              => '',
            ];
        })->toArray();
    }

    private function saveRtgsExcel(array $rows, string $chequeNo, string $password = '', string $companyCode = 'CMP', $bankAccount = null): string
    {
        $directory = 'rtgs_register';
        Storage::disk('public')->makeDirectory($directory);

        $safeChq  = preg_replace('/[^A-Za-z0-9_\-]/', '-', $chequeNo);
        // Ensure uniqueness even on the same day by appending the time (His = 6 chars)
        $uniqueId = date('His');
        $fileNameCore = substr($safeChq, 0, 13) . '_' . $uniqueId; // Limit to 20 chars max

        $date = date('Y.m.d'); // YYYY.MM.DD

        $ifsc = $bankAccount?->bankDetail?->bank_ifsc ?? '';
        $solId = strlen($ifsc) >= 4 ? substr($ifsc, -4) : '0000';

        // if (strtoupper($companyCode) === 'SDMC') {
        $filename = $solId . '_CSD_' . $date . '_' . $fileNameCore . '.xlsx';
        // } elseif (strtoupper($companyCode) === 'MDMC') {
        //     $filename = $solId . '_CMD_' . $date . '_' . $fileNameCore . '.xlsx';
        // } else {
        //     $filename = $solId . '_CSD_' . $date . '_' . $fileNameCore . '.xlsx'; // Default fallback matching bank format
        // }

        Excel::store(
            new RtgsSendToBankExport($rows, $password),
            $directory . '/' . $filename,
            'public'
        );

        return storage_path('app/public/' . $directory . '/' . $filename);
    }

    /*--------------------------------------------------------------
    | Payment Advice — Preview
    | Groups selected vouchers by party, validates emails & template.
    | Single party  → returns subject/body/cc for compose modal.
    | Multiple      → returns parties list for bulk-confirm modal.
    --------------------------------------------------------------*/
    public function previewPaymentAdviceEmail(
        array $paymentVoucherIds,
        int   $companyId,
        int   $financialYearId
    ): array {
        $pvRecords = PaymentVoucher::with(['account:id,name,email'])
            ->whereIn('id', $paymentVoucherIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        if ($pvRecords->isEmpty()) {
            throw new \Exception('No payment records found for the selected vouchers.');
        }

        // Group vouchers by account
        $partiesMap = [];
        foreach ($pvRecords as $pv) {
            $accountId = $pv->account_id;
            if (!isset($partiesMap[$accountId])) {
                $partiesMap[$accountId] = [
                    'account_name' => $pv->account->name  ?? '',
                    'email'        => $pv->account->email ?? '',
                    'voucher_ids'  => [],
                ];
            }
            $partiesMap[$accountId]['voucher_ids'][] = $pv->id;
        }

        $partiesWithEmail    = [];
        $partiesWithoutEmail = [];
        foreach ($partiesMap as $party) {
            if (!empty($party['email'])) {
                $partiesWithEmail[] = $party;
            } else {
                $partiesWithoutEmail[] = ['account_name' => $party['account_name']];
            }
        }

        // For a single party with no email, fail fast with a clear message
        $totalParties = count($partiesMap);
        if ($totalParties === 1 && empty($partiesWithEmail)) {
            $name = $partiesWithoutEmail[0]['account_name'];
            throw new \Exception(
                "No email address found for \"{$name}\". Please update the account before sending."
            );
        }

        $company     = $this->companyService->current($companyId);
        $companyName = $company?->name ?? '';
        $paymentDate = Carbon::parse($pvRecords->first()->voucher->voucher_date)->format('d-m-Y');

        // Resolve template (throws if missing — catches both single & bulk)
        $firstParty = $partiesWithEmail[0] ?? ['account_name' => ''];
        ['subject' => $subject, 'body' => $body] = $this->resolveTemplate(
            'send-to-bank-payment-advise',
            [
                'COMPANY-NAME' => $companyName,
                'PARTY-NAME'   => $firstParty['account_name'],
                'DATE'         => $paymentDate,
            ]
        );

        // CC accounts list (for compose modal)
        $ccAccounts = Account::where('company_id', $companyId)
            ->whereIn('party_type', [Account::CUSTOMER_TYPE, Account::SUPPLIER_TYPE])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'party_type'])
            ->toArray();

        return [
            'parties'          => $partiesWithEmail,
            'parties_no_email' => $partiesWithoutEmail,
            'subject'          => $subject,
            'body'             => $body,
            'cc_accounts'      => $ccAccounts,
        ];
    }

    /*--------------------------------------------------------------
    | Payment Advice — Send to single party (from compose modal)
    --------------------------------------------------------------*/
    public function sendPaymentAdviceEmail(
        array  $paymentVoucherIds,
        int    $companyId,
        int    $financialYearId,
        string $toEmail,
        array  $ccEmails = [],
        string $subject  = '',
        string $body     = ''
    ): array {
        $this->configureMailer($companyId);

        $pdfPath = $this->generatePaymentAdvicePdf($paymentVoucherIds, $companyId, $financialYearId);

        try {
            $mailer = Mail::mailer('smtp')->to($toEmail);
            if (!empty($ccEmails)) {
                $mailer->cc($ccEmails);
            }
            $this->safelySendMail($mailer, $toEmail, $subject, new SendEmailTemplateMail(
                $subject,
                $body,
                null,
                null,
                $pdfPath,
                basename($pdfPath),
                'application/pdf'
            ), $companyId, $financialYearId);
        } finally {
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
        }

        $pv          = PaymentVoucher::with('account:id,name')->find($paymentVoucherIds[0]);
        $accountName = $pv?->account?->name ?? '';

        return [
            'account_name' => $accountName,
            'to_email'     => $toEmail,
        ];
    }

    /*--------------------------------------------------------------
    | Payment Advice — Bulk send (one email per party, auto-template)
    | Continues on individual failures; returns sent/failed arrays.
    --------------------------------------------------------------*/
    public function sendPaymentAdviceBulkEmail(
        array $paymentVoucherIds,
        int   $companyId,
        int   $financialYearId
    ): array {
        $this->configureMailer($companyId);

        $pvRecords = PaymentVoucher::with(['account:id,name,email'])
            ->whereIn('id', $paymentVoucherIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        $company     = $this->companyService->current($companyId);
        $companyName = $company?->name ?? '';

        // Group by account
        $partiesMap = [];
        foreach ($pvRecords as $pv) {
            $accountId = $pv->account_id;
            if (!isset($partiesMap[$accountId])) {
                $partiesMap[$accountId] = [
                    'account_name' => $pv->account->name  ?? '',
                    'email'        => $pv->account->email ?? '',
                    'voucher_ids'  => [],
                ];
            }
            $partiesMap[$accountId]['voucher_ids'][] = $pv->id;
        }

        $sent   = [];
        $failed = [];

        foreach ($partiesMap as $party) {
            if (empty($party['email'])) {
                $failed[] = [
                    'account_name' => $party['account_name'],
                    'email'        => '',
                    'error'        => 'No email address configured',
                ];
                continue;
            }

            $pdfPath = null;
            try {
                ['subject' => $subject, 'body' => $body] = $this->resolveTemplate(
                    'send-to-bank-payment-advise',
                    [
                        'COMPANY-NAME' => $companyName,
                        'PARTY-NAME'   => $party['account_name'],
                        'DATE'         => now()->format('d-m-Y'),
                    ]
                );

                $pdfPath = $this->generatePaymentAdvicePdf(
                    $party['voucher_ids'],
                    $companyId,
                    $financialYearId
                );

                $bulkMailer = Mail::mailer('smtp')->to($party['email']);
                $this->safelySendMail($bulkMailer, $party['email'], $subject, new SendEmailTemplateMail(
                    $subject,
                    $body,
                    null,
                    null,
                    $pdfPath,
                    basename($pdfPath),
                    'application/pdf'
                ), $companyId, $financialYearId);

                $sent[] = [
                    'account_name' => $party['account_name'],
                    'email'        => $party['email'],
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'account_name' => $party['account_name'],
                    'email'        => $party['email'],
                    'error'        => $e->getMessage(),
                ];
            } finally {
                if ($pdfPath && file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
            }

            // Throttle to avoid tripping the mail host's outgoing rate limit
            usleep(1000000);
        }

        return compact('sent', 'failed');
    }

    /*--------------------------------------------------------------
    | Generate a PDF from the payment-advice-print blade view
    | Requires: composer require barryvdh/laravel-dompdf
    --------------------------------------------------------------*/
    private function generatePaymentAdvicePdf(
        array $paymentVoucherIds,
        int   $companyId,
        int   $financialYearId
    ): string {
        $data = app(PaymentVoucherService::class)->printPaymentAdvice(
            $paymentVoucherIds,
            $companyId,
            $financialYearId
        );

        $html = view('company.pages.payment-online-rtgs.payment-advice-print', compact('data'))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');

        $directory = storage_path('app/public/payment_advice_pdfs');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'payment_advice_' . date('dmY') . '_' . uniqid() . '.pdf';
        $filePath = $directory . '/' . $filename;

        file_put_contents($filePath, $pdf->output());

        return $filePath;
    }

    public function previewDairyAnalysisEmail(
        array  $dairyAnalysisIds,
        int    $companyId,
        int    $financialYearId
    ): array {
        $company     = $this->companyService->current($companyId);
        $companyName = $company?->name ?? '';

        $records = DairyAnalysis::with([
            'details.element',
            'purchaseInvoice.details.item',
            'purchaseInvoice.details.destination',
            'purchaseInvoice.details.condition',
            'purchaseInvoice.account',
            'salesInvoice.details.item',
            'salesInvoice.details.destination',
            'salesInvoice.details.condition',
            'salesInvoice.account'
        ])
            ->whereIn('id', $dairyAnalysisIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        $template = EmailTemplate::where('template_name', 'dairy-analysis')->first();
        $subject = $template ? $template->subject : 'Price Difference Statement (PDS)';
        $partyName = $records->first()?->purchaseInvoice?->account?->name ?? '';

        $tableHtml = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd; font-size: 13px; text-align: center;">';
        $tableHtml .= '<thead style="background: #f8f9fa;"><tr>
            <th style="border: 1px solid #ddd; padding: 5px;">Party Bill No</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Date</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Element</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Actual</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Rebate Percentage</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Rebate</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Premium</th>
            <th style="border: 1px solid #ddd; padding: 5px;">G.R.Qty</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Actual Amt</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Diff Amt.</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Bill Amt</th>
        </tr></thead><tbody>';

        foreach ($records as $record) {
            $detailsCount = $record->details->count() ?: 1;
            $isFirstDetail = true;

            $refNo = $record->purchaseInvoice->reference_number ?? '';
            $date = $record->purchaseInvoice->invoice_date ? Carbon::parse($record->purchaseInvoice->invoice_date)->format('d/m/Y') : '';

            if ($record->details->isEmpty()) {
                continue;
            }

            foreach ($record->details->sortBy('element.name') as $detail) {
                $elementName = $detail->element->name ?? '';
                $rangeVal = $detail->element->range ?? null;
                $operator = '';
                if ($rangeVal == 1) {
                    $operator = '&gt;=';
                } elseif ($rangeVal == 2) {
                    $operator = '&lt;=';
                }

                $guarantee = $detail->guarantee ?? '';
                $element = $elementName;
                if ($operator && $guarantee !== '') {
                    $element .= "<br>{$operator} " . number_format((float)$guarantee, 2);
                }

                $actual = $detail->actual ?? '';
                $amountVal = (float)($record->purchaseInvoice->taxable_amount ?? 0);
                $purchaseRebate = (float)($detail->purchase_rebate ?? 0);
                $calculatedPct = $amountVal > 0 ? ($purchaseRebate / $amountVal) * 100 : 0;

                $rebatePct = $calculatedPct > 0 ? number_format($calculatedPct, 2) : '0.00';
                $rebate = $detail->purchase_rebate ?? '';
                $premium = $detail->purchase_premium ?? '';
                $grQty = $isFirstDetail ? (number_format($record->purchaseInvoice->total_quantity ?? 0, 3)) : 0;

                $diffAmtVal = (float)($record->purchase_premium_total ?? 0) - (float)($record->purchase_rebate_total ?? 0);

                // netAmt = amount + diffAmt
                $netAmtVal = $amountVal + $diffAmtVal;

                $amount = $isFirstDetail ? number_format($amountVal, 1, '.', '') : 0;
                $diffAmt = $isFirstDetail ? number_format($diffAmtVal, 1, '.', '') : 0;
                $netAmt = $isFirstDetail ? number_format($netAmtVal, 1, '.', '') : 0;
                $tableHtml .= "<tr>";

                if ($isFirstDetail) {
                    $tableHtml .= "<td rowspan=\"{$detailsCount}\" style=\"border: 1px solid #ddd; padding: 5px; vertical-align: top;\">{$refNo}</td>";
                    $tableHtml .= "<td rowspan=\"{$detailsCount}\" style=\"border: 1px solid #ddd; padding: 5px; vertical-align: top;\">{$date}</td>";
                }

                $tableHtml .= "<td style=\"border: 1px solid #ddd; padding: 5px;\">{$element}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$actual}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$rebatePct}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$rebate}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$premium}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$grQty}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$amount}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$diffAmt}</td>
                    <td style=\"border: 1px solid #ddd; padding: 5px;\">{$netAmt}</td>
                </tr>";
                $isFirstDetail = false;
            }
        }
        $tableHtml .= "</tbody></table>";

        if ($template && $template->message) {
            $body = str_replace(
                ['{COMPANY-NAME}', '{PARTY-NAME}', '{SUPPLIER-NAME}'],
                [$companyName, $partyName, $partyName],
                $template->message
            );
            if (str_contains($body, '{TABLE}')) {
                $body = str_replace('{TABLE}', $tableHtml, $body);
            } elseif (str_contains($body, '{PRODUCT-TABLE}')) {
                $body = str_replace('{PRODUCT-TABLE}', $tableHtml, $body);
            } else {
                $body .= "<br>" . $tableHtml;
            }
        } else {
            $body = "<p>Dear Sir/Madam,</p><p>Please find the Price Difference Statement (PDS) details below:</p><br>";
            $body .= $tableHtml;
            $body .= "<br><br><p>Thanks &amp; Regards<br>{$companyName}</p>";
        }

        $toEmail = $records->first()?->purchaseInvoice?->account?->email ?? '';

        $ccAccounts = Account::where('company_id', $companyId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'name', 'email')
            ->get();

        return [
            'to_email'    => $toEmail,
            'subject'     => $subject,
            'body'        => $body,
            'cc_accounts' => $ccAccounts->toArray(),
            'records'     => $records->toArray()
        ];
    }

    public function sendDairyAnalysisEmail(
        array  $dairyAnalysisIds,
        int    $companyId,
        int    $financialYearId,
        string $toEmail,
        array  $ccEmails = [],
        string $subject = '',
        string $body = ''
    ): array {
        $this->configureMailer($companyId);

        if (empty($toEmail)) {
            throw new \Exception('No email address provided for the recipient.');
        }

        $mailer = Mail::mailer('smtp')->to($toEmail);

        if (!empty($ccEmails)) {
            $mailer->cc($ccEmails);
        }

        $this->safelySendMail($mailer, $toEmail, $subject, new SendEmailTemplateMail(
            $subject,
            $body,
            null,
            null,
            null,
            null,
            null
        ), $companyId, $financialYearId);

        return [
            'to_email' => $toEmail,
            'subject'  => $subject,
        ];
    }

    public function previewGodownAnalysisEmail(
        array  $godownAnalysisIds,
        int    $companyId,
        int    $financialYearId
    ): array {
        $company     = $this->companyService->current($companyId);
        $companyName = $company?->name ?? '';

        $records = GodownAnalysis::with([
            'grn.account',
            'grn.details.item',
            'details.element'
        ])
            ->whereIn('id', $godownAnalysisIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        $template = EmailTemplate::where('template_name', 'rebate-due-to-low-quality-of-material')->first();
        $subject = $template ? $template->subject : 'Rebate Due To Low Quality Of Material';
        $partyName = $records->first()?->grn?->account?->name ?? '';
        $partyCity = $records->first()?->grn?->account?->city ?? '';
        $toEmail = $records->first()?->grn?->account?->email ?? '';
        $productName = $records->first()?->grn?->details?->first()?->item?->name ?? '';
        $referenceNumber = $records->first()?->grn?->reference_number ?? '';
        $challanDate = $records->first()?->grn?->grn_date ? \Carbon\Carbon::parse($records->first()?->grn?->grn_date)->format('d/m/Y') : '';
        $vehicleNumber = $records->first()?->grn?->vehicle_number ?? '';

        $tableHtml = '<table style="border-collapse: collapse; width: 100%; border: 1px dashed #ddd; font-size: 13px;">';
        $tableHtml .= '<thead style="background: #f8f9fa;"><tr>
            <th style="border: 1px dashed #ddd; padding: 5px; text-align: left;">Element</th>
            <th style="border: 1px dashed #ddd; padding: 5px; text-align: right;">Guarantee</th>
            <th style="border: 1px dashed #ddd; padding: 5px; text-align: right;">Actual</th>
            <th style="border: 1px dashed #ddd; padding: 5px; text-align: right;">Diff%</th>
            <th style="border: 1px dashed #ddd; padding: 5px; text-align: right;">Rebate</th>
        </tr></thead><tbody>';
        $totalRebate = 0;
        foreach ($records as $record) {
            if ($record->details->isEmpty()) {
                continue;
            }

            foreach ($record->details->sortBy('element.name') as $detail) {
                $elementName = $detail->element->name ?? '';
                $guarantee = $detail->guarantee ?? '';
                $actual = $detail->actual ?? '';
                $diff = $detail->difference ?? '';
                $rebateVal = is_numeric($detail->rebate) ? (float)$detail->rebate : 0;
                $totalRebate += $rebateVal;
                $rebate = is_numeric($detail->rebate) ? number_format($rebateVal, 2, '.', '') : ($detail->rebate ?? '');

                $tableHtml .= "<tr>
                    <td style=\"border: 1px dashed #ddd; text-align: left; padding: 5px;\">{$elementName}</td>
                    <td style=\"border: 1px dashed #ddd; text-align: right; padding: 5px;\">{$guarantee}</td>
                    <td style=\"border: 1px dashed #ddd; text-align: right; padding: 5px;\">{$actual}</td>
                    <td style=\"border: 1px dashed #ddd; text-align: right; padding: 5px;\">{$diff}</td>
                    <td style=\"border: 1px dashed #ddd; text-align: right; padding: 5px;\">{$rebate}</td>
                </tr>";
            }
        }

        $roundedTotalRebate = round($totalRebate);
        $tableHtml .= "<tr>
            <td colspan=\"3\" style=\"border: 1px dashed #ddd; text-align: left; padding: 5px;\"></td>
            <td style=\"border: 1px dashed #ddd; text-align: right; padding: 5px;\">Total Rebate:</td>
            <td style=\"border: 1px dashed #ddd; text-align: right; padding: 5px; font-weight: bold;\">{$roundedTotalRebate}</td>
        </tr>";
        $tableHtml .= "</tbody></table>";

        if ($template && $template->message) {
            $body = str_replace(
                [
                    '{COMPANY-NAME}',
                    '{PARTY-NAME}',
                    '{SUPPLIER-NAME}',
                    '{SUPPLIER-CITY}',
                    '{SUPPLIER-ADDRESS}',
                    '{ORDER-ID}',
                    '{ORDER-DATE}',
                    '{VEHICLE-NUMBER}',
                    '{PRODUCT-NAME}'
                ],
                [
                    $companyName,
                    $partyName,
                    $partyName,
                    $partyCity,
                    $partyCity,
                    $referenceNumber,
                    $challanDate,
                    $vehicleNumber,
                    $productName
                ],
                $template->message
            );
            if (str_contains($body, '{TABLE}')) {
                $body = str_replace('{TABLE}', $tableHtml, $body);
            } elseif (str_contains($body, '{PRODUCT-TABLE}')) {
                $body = str_replace('{PRODUCT-TABLE}', $tableHtml, $body);
            } else {
                $body .= "<br>" . $tableHtml;
            }
        } else {
            $body = "<p>Dear Sir/Madam,</p><p>Please find the Rebate Due To Low Quality Of Material details below:</p><br>";
            $body .= $tableHtml;
            $body .= "<br><br><p>Thanks &amp; Regards<br>{$companyName}</p>";
        }

        $ccAccounts = Account::where('company_id', $companyId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'name', 'email')
            ->get();

        return [
            'to_email'    => $toEmail,
            'subject'     => $subject,
            'body'        => $body,
            'cc_accounts' => $ccAccounts->toArray(),
            'records'     => $records->toArray()
        ];
    }

    public function sendGodownAnalysisEmail(
        array  $godownAnalysisIds,
        int    $companyId,
        int    $financialYearId,
        string $toEmail,
        array  $ccEmails = [],
        string $subject = '',
        string $body = ''
    ): array {
        $this->configureMailer($companyId);

        if (empty($toEmail)) {
            throw new \Exception('No email address provided for the recipient.');
        }

        $mailer = Mail::mailer('smtp')->to($toEmail);

        if (!empty($ccEmails)) {
            $mailer->cc($ccEmails);
        }

        $this->safelySendMail($mailer, $toEmail, $subject, new SendEmailTemplateMail(
            $subject,
            $body,
            null,
            null,
            null,
            null,
            null
        ), $companyId, $financialYearId);

        return [
            'to_email' => $toEmail,
            'subject'  => $subject,
        ];
    }

    public function previewUrgentPurchaseOrderEmail(
        array $purchaseOrderIds,
        int $companyId,
        int $financialYearId
    ): array {
        $company        = $this->companyService->current($companyId);
        $companyName    = $company?->name ?? '';
        $addressParts = array_filter([
            $company?->address_one,
            $company?->address_two,
            ($company?->postal_code ?? ''),
            $company?->state?->name,
            $company?->country?->name,
        ], fn($val) => $val !== null && trim((string) $val) !== '');
        $companyAddress = implode(', ', $addressParts);

        $purchaseOrders = PurchaseOrder::with(['details.item', 'account', 'broker', 'destination'])
            ->whereIn('id', $purchaseOrderIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        $template = EmailTemplate::where('template_name', 'urgent-fast-delivery-of-pending-material')->first();
        $subject  = $template ? $template->subject : 'Urgent Fast Delivery Of Pending Material';
        $partyName = $purchaseOrders->first()?->account?->name ?? '';
        $toEmail  = $purchaseOrders->first()?->account?->email ?? '';

        $tableHtml = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd; font-size: 13px; text-align: center;">';
        $tableHtml .= '<thead style="background: #f8f9fa;"><tr>
            <th style="border: 1px solid #ddd; padding: 5px;">Date</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Ord.No.</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Contract</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Broker</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Destination</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Last Date</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Product</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Rate</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Ord.Qty</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Rec.Qty</th>
            <th style="border: 1px solid #ddd; padding: 5px;">Balance</th>
        </tr></thead><tbody>';

        foreach ($purchaseOrders as $po) {
            foreach ($po->details as $item) {
                if ($item->remaining_qty <= 0 && $item->received_qty > 0) {
                    continue;
                }

                $tableHtml .= '<tr>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->order_date ? Carbon::parse($po->order_date)->format('d / m / Y') : 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->order_serial ?? 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->contract_number ?? 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->broker->name ?? 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->destination->name ?? 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->due_date ? Carbon::parse($po->due_date)->format('d / m / Y') : 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . ($item->item->name ?? 'N/A') . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->rate, 2) . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->ordered_qty, 3) . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->received_qty, 3) . '</td>
                    <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->remaining_qty, 3) . '</td>
                </tr>';
            }
        }

        $tableHtml .= '</tbody></table>';

        $body = $template ? $template->message : '';
        $body = str_replace('{SUPPLIER-NAME}', $partyName, $body);
        $body = str_replace('{PRODUCT-TABLE}', $tableHtml, $body);
        $body = str_replace('{COMPANY-NAME}', $companyName, $body);
        $body = str_replace('{COMPANY-ADDRESS}', $companyAddress, $body);

        $ccAccounts = Account::where('company_id', $companyId)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'name', 'email')
            ->get();

        return [
            'to'      => $toEmail,
            'subject' => $subject,
            'body'    => $body,
            'cc_accounts' => $ccAccounts->toArray()
        ];
    }

    public function sendUrgentPurchaseOrderCustomEmail(
        array  $purchaseOrderIds,
        int    $companyId,
        int    $financialYearId,
        string $toEmail,
        array  $ccEmails = [],
        string $subject = '',
        string $body = ''
    ): array {
        $this->configureMailer($companyId);

        if (empty($toEmail)) {
            throw new \Exception('No email address provided for the recipient.');
        }

        $mailer = Mail::mailer('smtp')->to($toEmail);

        if (!empty($ccEmails)) {
            $mailer->cc($ccEmails);
        }

        $this->safelySendMail($mailer, $toEmail, $subject, new SendEmailTemplateMail(
            $subject,
            $body,
            null,
            null,
            null,
            null,
            null
        ), $companyId, $financialYearId);

        return [
            'to_email' => $toEmail,
            'subject'  => $subject,
        ];
    }

    public function sendUrgentPurchaseOrderBulkEmail(
        array $purchaseOrderIds,
        int $companyId,
        int $financialYearId
    ): array {
        $this->configureMailer($companyId);

        $company        = $this->companyService->current($companyId);
        $companyName    = $company?->name ?? '';
        $addressParts = array_filter([
            $company?->address_one,
            $company?->address_two,
            ($company?->postal_code ?? ''),
            $company?->state?->name,
            $company?->country?->name,
        ], fn($val) => $val !== null && trim((string) $val) !== '');
        $companyAddress = implode(', ', $addressParts);

        $purchaseOrders = PurchaseOrder::with(['details.item', 'account', 'broker', 'destination'])
            ->whereIn('id', $purchaseOrderIds)
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->get();

        $template = EmailTemplate::where('template_name', 'urgent-fast-delivery-of-pending-material')->first();
        $subject  = $template ? $template->subject : 'Urgent Fast Delivery Of Pending Material';

        $groupedPOs = $purchaseOrders->groupBy('account_id');
        $sent   = [];
        $failed = [];

        foreach ($groupedPOs as $accountId => $pos) {
            $partyName = $pos->first()?->account?->name ?? '';
            $toEmail  = $pos->first()?->account?->email ?? '';

            if (empty($toEmail)) {
                $failed[] = [
                    'account_name' => $partyName,
                    'email'        => '',
                    'error'        => 'No email address configured',
                ];
                continue;
            }

            $tableHtml = '<table style="border-collapse: collapse; width: 100%; border: 1px solid #ddd; font-size: 13px; text-align: center;">';
            $tableHtml .= '<thead style="background: #f8f9fa;"><tr>
                <th style="border: 1px solid #ddd; padding: 5px;">Date</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Ord.No.</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Contract</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Broker</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Destination</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Last Date</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Product</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Rate</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Ord.Qty</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Rec.Qty</th>
                <th style="border: 1px solid #ddd; padding: 5px;">Balance</th>
            </tr></thead><tbody>';

            $hasItems = false;
            foreach ($pos as $po) {
                foreach ($po->details as $item) {
                    if ($item->remaining_qty <= 0 && $item->received_qty > 0) {
                        continue;
                    }

                    $hasItems = true;
                    $tableHtml .= '<tr>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->order_date ? Carbon::parse($po->order_date)->format('d / m / Y') : 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->order_serial ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->contract_number ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->broker->name ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->destination->name ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($po->due_date ? Carbon::parse($po->due_date)->format('d / m / Y') : 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . ($item->item->name ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->rate, 2) . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->ordered_qty, 3) . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->received_qty, 3) . '</td>
                        <td style="border: 1px solid #ddd; padding: 5px;">' . number_format($item->remaining_qty, 3) . '</td>
                    </tr>';
                }
            }

            $tableHtml .= '</tbody></table>';

            if (!$hasItems) {
                $failed[] = [
                    'account_name' => $partyName,
                    'email'        => $toEmail,
                    'error'        => 'No pending items found for this supplier.',
                ];
                continue;
            }

            $body = $template ? $template->message : '';
            $body = str_replace('{SUPPLIER-NAME}', $partyName, $body);
            $body = str_replace('{PRODUCT-TABLE}', $tableHtml, $body);
            $body = str_replace('{COMPANY-NAME}', $companyName, $body);
            $body = str_replace('{COMPANY-ADDRESS}', $companyAddress, $body);

            try {
                $bulkPoMailer = Mail::mailer('smtp')->to($toEmail);
                $this->safelySendMail($bulkPoMailer, $toEmail, $subject, new SendEmailTemplateMail(
                    $subject,
                    $body,
                    null,
                    null,
                    null,
                    null,
                    null
                ), $companyId, $financialYearId);

                $sent[] = [
                    'account_name' => $partyName,
                    'email'        => $toEmail,
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'account_name' => $partyName,
                    'email'        => $toEmail,
                    'error'        => $e->getMessage(),
                ];
            }

            // Throttle to avoid tripping the mail host's outgoing rate limit
            usleep(1000000);
        }

        return compact('sent', 'failed');
    }

    private function safelySendMail($mailer, string $toEmail, string $subject, $mailable, $companyId = null, $financialYearId = null)
    {
        $cacheKey = 'email_lock_' . md5($toEmail . '_' . $subject);

        if (Cache::has($cacheKey)) {
            throw new \Exception("Email already sent recently. Please wait 1 minutes before trying again.");
        }

        // Lock for 1 minute before sending to prevent concurrent identical requests
        Cache::put($cacheKey, true, now()->addMinutes(1));

        $logData = [
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'user_id' => auth()->id(),
            'recipient' => $toEmail,
            'subject' => $subject,
            'status' => 'sent',
            'error_message' => null,
            'sent_at' => now(),
        ];

        try {
            $mailer->send($mailable);
            \App\Models\MailLog::create($logData);
        } catch (\Throwable $e) {
            $logData['status'] = 'failed';
            $logData['error_message'] = $e->getMessage();
            \App\Models\MailLog::create($logData);
            // Keep the lock so we don't spam a failing address automatically
            throw $e;
        }
    }
}
