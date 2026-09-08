<?php

namespace App\Services;

use App\Models\CompanyGstCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

/**
 * EInvoiceService
 *
 * Handles all Whitebook GSP E-Invoice API calls for a given company.
 * Credentials are loaded dynamically from the company_gst_credentials table
 * using the current company context. Supports both sandbox and production modes.
 *
 * Whitebook API provider: BVM IT Consulting Services India Private Limited (www.bvmcs.com)
 */
class EInvoiceService
{
    /**
     * Resolved credential row from company_gst_credentials (type = e_invoice).
     */
    protected CompanyGstCredential $credential;

    /**
     * Active base URL (sandbox or production).
     */
    protected string $baseUrl;

    /**
     * Whether the current environment is sandbox.
     */
    protected bool $isSandbox;

    /**
     * Bootstrap the service by loading credentials for the current company.
     *
     * Throws \RuntimeException when credentials are missing.
     */
    public function __construct()
    {
        $this->loadCredentials();
    }

    /*==========================================================================
    | CREDENTIAL & AUTH HELPERS
    ==========================================================================*/

    /**
     * Load the active E-Invoice credentials for the current company from the
     * company_gst_credentials table. Selects sandbox vs production based on the
     * application environment.
     *
     * @throws \RuntimeException if no credentials exist for this company.
     */
    private function loadCredentials(): void
    {
        $credential = CompanyGstCredential::where('company_id', company_id())
            ->where('type', 'e_invoice')
            ->first();

        if (!$credential) {
            throw new \RuntimeException(
                'E-Invoice credentials not configured for this company. ' .
                'Please set them up under Setup → GST Credentials.'
            );
        }

        $this->credential = $credential;

        // APP_ENV=production → use production credentials and production base URL.
        // Anything else (local, staging, testing, etc.) → use sandbox credentials.
        $this->isSandbox = !app()->environment('production');

        $rawBase = $this->isSandbox
            ? rtrim($credential->sandbox_base_url ?? '', '/')
            : rtrim($credential->production_base_url ?? '', '/');

        if (empty($rawBase)) {
            throw new \RuntimeException(
                'Base URL is not configured for E-Invoice ' .
                ($this->isSandbox ? 'sandbox' : 'production') . ' credentials.'
            );
        }

        // All Whitebook e-invoice endpoints live under /einvoice — append it if not already present
        $this->baseUrl = str_ends_with($rawBase, '/einvoice')
            ? $rawBase
            : $rawBase . '/einvoice';
    }

    private function clientId(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_client_id ?? '')
            : ($this->credential->production_client_id ?? '');
    }

    private function clientSecret(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_secret_id ?? '')
            : ($this->credential->production_secret_id ?? '');
    }

    private function ipAddress(): string
    {
        $ip = request()->ip();
        if ($ip === '::1') {
            $ip = '117.219.85.50';
        }
        return $ip;
    }

    private function gstin(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_gstin ?? '')
            : ($this->credential->production_gstin ?? '');
    }

    private function username(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_username ?? '')
            : ($this->credential->production_username ?? '');
    }

    private function email(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_email ?? '')
            : ($this->credential->production_email ?? '');
    }

    private function password(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_password ?? '')
            : ($this->credential->production_password ?? '');
    }

    private function tokenCacheKey(): string
    {
        $env = $this->isSandbox ? 'sandbox' : 'production';
        return 'einv_auth_token_' . company_id() . '_' . $env;
    }

    /**
     * Authenticate with the Whitebook API and return a bearer/auth token.
     * Cached for 4 hours; evicted early on 401 responses.
     *
     * @return string The auth token.
     * @throws \RuntimeException on API failure.
     */
    public function authenticate(): string
    {
        if (Cache::has($this->tokenCacheKey())) {
            return Cache::get($this->tokenCacheKey());
        }

        $authUrl = "{$this->baseUrl}/authenticate";

        $response = Http::timeout(30)
            ->withHeaders([
                'accept'        => '*/*',
                'username'      => $this->username(),
                'password'      => $this->password(),
                'ip_address'    => $this->ipAddress(),
                'client_id'     => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'gstin'         => $this->gstin(),
            ])
            ->get($authUrl, ['email' => $this->email()]);
            // dd(json_decode($response));

        $this->assertSuccess($response, 'Authentication');

        $rawBody = $response->body();
        $body    = $response->json() ?? [];

        // Whitebook API returns AuthToken (PascalCase) under data{}; cover all observed variants
        $token = $body['data']['AuthToken']
            ?? $body['data']['authToken']
            ?? $body['data']['auth_token']
            ?? $body['data']['token']
            ?? $body['AuthToken']
            ?? $body['authToken']
            ?? $body['auth_token']
            ?? $body['token']
            ?? '';

        if (empty($token)) {
            Log::error('EInvoiceService [authenticate] No token in response', [
                'http_status' => $response->status(),
                'raw_body'    => $rawBody,
                'parsed_body' => $body,
            ]);
            throw new \RuntimeException(
                'Whitebook authentication succeeded but returned no token. ' .
                'HTTP ' . $response->status() . ' | Raw: ' . mb_substr($rawBody, 0, 300)
            );
        }

        Cache::put($this->tokenCacheKey(), $token, now()->addHours(4));

        return $token;
    }

    private function clearTokenCache(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    private function headers(string $token): array
    {
        return [
            'accept'        => '*/*',
            'auth-token'    => $token,
            'ip_address'    => $this->ipAddress(),
            'client_id'     => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'gstin'         => $this->gstin(),
            'username'      => $this->username(),
            'password'      => $this->password(),
            'Content-Type'  => 'application/json',
        ];
    }

    private function isTokenError(Response $response): bool
    {
        if ($response->status() === 401) {
            return true;
        }

        $statusCd = $response->json('status_cd') ?? $response->json('status') ?? $response->json('Status');
        if ($statusCd !== '0' && $statusCd !== 0 && $statusCd !== false) {
            return false;
        }

        $msg = strtolower($this->extractErrorMessage($response));
        return str_contains($msg, 'invalid token')
            || str_contains($msg, 'token expired')
            || str_contains($msg, 'auth token')
            || str_contains($msg, 'token invalid')
            || str_contains($msg, 'unauthorized');
    }

    private function post(string $endpoint, array $payload = []): array
    {
        $token = $this->authenticate();
        $url   = "{$this->baseUrl}{$endpoint}?email=" . urlencode($this->email());

        $response = Http::withHeaders($this->headers($token))
            ->timeout(60)
            ->asJson()
            ->post($url, $payload);

        // Whitebook returns token errors as HTTP 200 with status_cd=0, not as HTTP 401.
        // Evict the cache and retry once whenever we detect a token-related failure.
        if ($this->isTokenError($response)) {
            $this->clearTokenCache();
            $token    = $this->authenticate();
            $response = Http::withHeaders($this->headers($token))
                ->timeout(60)
                ->asJson()
                ->post($url, $payload);
        }

        Log::info("EInvoiceService [{$endpoint}]", [
            'status' => $response->status(),
            'body'   => mb_substr($response->body(), 0, 500),
        ]);

        $this->assertSuccess($response, $endpoint, $payload);
        return $response->json() ?? [];
    }

    private function get(string $endpoint, array $query = []): array
    {
        $token = $this->authenticate();
        $url   = "{$this->baseUrl}{$endpoint}";

        $response = Http::withHeaders($this->headers($token))
            ->timeout(30)
            ->get($url, array_merge(['email' => $this->email()], $query));

        if ($response->status() === 401) {
            $this->clearTokenCache();
            $token    = $this->authenticate();
            $response = Http::withHeaders($this->headers($token))
                ->timeout(30)
                ->get($url, array_merge(['email' => $this->email()], $query));
        }

        $this->assertSuccess($response, $endpoint);
        return $response->json() ?? [];
    }

    /**
     * @throws \RuntimeException
     */
    private function assertSuccess(Response $response, string $context, array $requestPayload = []): void
    {
        if ($response->failed()) {
            $message = $response->json('error.message')
                ?? $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            $this->saveApiLog($context, $requestPayload, $response, $message);
            throw new \RuntimeException(
                "E-Invoice API [{$context}]: {$message} (HTTP {$response->status()})"
            );
        }

        // Whitebook uses status_cd ("0" = fail) or status (0/false = fail)
        $statusCd = $response->json('status_cd') ?? $response->json('status') ?? $response->json('Status');
        $isFail   = ($statusCd === '0' || $statusCd === 0 || $statusCd === false);

        if ($isFail) {
            // status_desc may be a JSON-encoded array of {ErrorCode, ErrorMessage} objects
            $message = $this->extractErrorMessage($response);

            $this->saveApiLog($context, $requestPayload, $response, $message);
            throw new \RuntimeException("E-Invoice API [{$context}]: {$message}");
        }
    }

    private function extractErrorMessage(Response $response): string
    {
        // Try plain fields first
        $plain = $response->json('error.message')
            ?? $response->json('message')
            ?? $response->json('error');

        if ($plain && is_string($plain)) {
            return $plain;
        }

        // status_desc is often a JSON-encoded array of {ErrorCode, ErrorMessage}
        $desc = $response->json('status_desc') ?? '';
        if (is_string($desc) && str_starts_with(trim($desc), '[')) {
            $errors = json_decode($desc, true);
            if (is_array($errors)) {
                $messages = array_column($errors, 'ErrorMessage');
                return implode(' | ', array_filter($messages));
            }
        }

        return is_string($desc) && $desc !== '' ? $desc : 'Unknown API error';
    }

    private function saveApiLog(string $endpoint, array $payload, Response $response, string $errorMessage): void
    {
        try {
            \App\Models\GstApiLog::create([
                'company_id'       => company_id(),
                'service'          => 'e_invoice',
                'method'           => 'POST',
                'endpoint'         => $endpoint,
                'request_payload'  => $payload,
                'response_payload' => $response->json() ?? ['raw' => mb_substr($response->body(), 0, 2000)],
                'status_code'      => $response->status(),
                'is_success'       => false,
                'error_message'    => mb_substr($errorMessage, 0, 2000),
                'created_by'       => current_user_id(),
            ]);
        } catch (\Throwable) {
            // Never let logging failure break the main flow
        }
    }

    /*==========================================================================
    | GSTN MASTER DATA
    ==========================================================================*/

    /**
     * Get GSTN registration details for a given GST Number.
     *
     * @param  string $gstin  15-character GSTIN.
     * @return array          Legal name, trade name, status, address, etc.
     */
    public function getGstnDetails(string $gstin): array
    {
        return $this->get("/getgstndetails/{$gstin}");
    }

    /**
     * Sync GSTIN details from the GST Common Portal.
     *
     * @param  string $gstin  15-character GSTIN.
     * @return array          Synced GSTIN details.
     */
    public function syncGstinFromCp(string $gstin): array
    {
        return $this->get("/syncgstinfromcp/{$gstin}");
    }

    /*==========================================================================
    | IRN / E-INVOICE OPERATIONS
    ==========================================================================*/

    /**
     * Generate an Invoice Reference Number (IRN) for a given invoice.
     *
     * Required payload keys (Whitebook spec):
     *   Version, TranDtls { TaxSch, SupTyp, RegRev, EcmGstin, IgstOnIntra },
     *   DocDtls { Typ, No, Dt },
     *   SellerDtls { Gstin, LglNm, TrdNm, Addr1, Addr2, Loc, Pin, Stcd, Ph, Em },
     *   BuyerDtls { Gstin, LglNm, TrdNm, Pos, Addr1, Addr2, Loc, Pin, Stcd, Ph, Em },
     *   ItemList [ { SlNo, PrdDesc, IsServc, HsnCd, Barcde, Qty, FreeQty, Unit,
     *                UnitPrice, TotAmt, Discount, PreTaxVal, AssAmt, GstRt,
     *                IgstAmt, CgstAmt, SgstAmt, CesRt, CesAmt, CesNonAdvolAmt,
     *                StateCesRt, StateCesAmt, StateCesNonAdvolAmt, OthChrg,
     *                TotItemVal, OrdLineRef, OrgCntry, PrdSlNo, BchDtls, AttribDtls } ],
     *   ValDtls { AssVal, CgstVal, SgstVal, IgstVal, CesVal, StCesVal, Discount,
     *             OthChrg, RndOffAmt, TotInvVal, TotInvValFc },
     *   PayDtls, RefDtls, AddlDocDtls, ExpDtls, EwbDtls
     *
     * @param  array $data  Invoice payload per Whitebook E-Invoice spec.
     * @return array        API response containing irn, ackNo, ackDt, signedInvoice, etc.
     */
    public function generateIrn(array $data): array
    {
        // base URL already includes /einvoice — endpoint is /type/GENERATE/version/V1_03
        return $this->post('/type/GENERATE/version/V1_03', $data);
    }

    /**
     * Get e-Invoice details for a given IRN.
     *
     * @param  string $irn  64-character Invoice Reference Number.
     * @return array        Full e-Invoice details including signed QR code.
     */
    public function getEInvoiceDetails(string $irn): array
    {
        return $this->get("/geteinvoicedetails/{$irn}");
    }

    /**
     * Get IRN details by document type, number, and date.
     *
     * @param  string $docType  Invoice document type (INV, CRN, DBN).
     * @param  string $docNo    Document number.
     * @param  string $docDate  Document date (DD/MM/YYYY).
     * @return array            IRN details if found.
     */
    public function getIrnByDocDetails(string $docType, string $docNo, string $docDate): array
    {
        $token = $this->authenticate();
        $url   = 'https://api.mastergst.com/einvoice/type/GETIRNBYDOCDETAILS/version/V1_03';

        $query = [
            'email'  => $this->email(),
            'param1' => $docType,
        ];

        $customHeaders = [
            'docnum'  => $docNo,
            'docdate' => $docDate,
        ];

        $headers = array_merge($this->headers($token), $customHeaders);

        $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
            ->timeout(30)
            ->get($url, $query);

        if ($response->status() === 401) {
            $this->clearTokenCache();
            $token    = $this->authenticate();
            $headers  = array_merge($this->headers($token), $customHeaders);

            $response = \Illuminate\Support\Facades\Http::withHeaders($headers)
                ->timeout(30)
                ->get($url, $query);
        }

        $this->assertSuccess($response, '/type/GETIRNBYDOCDETAILS/version/V1_03', array_merge($query, $customHeaders));
        return $response->json() ?? [];
    }

    /**
     * Cancel an Invoice Reference Number (IRN).
     * Must be cancelled within 24 hours of generation.
     *
     * @param  string $irn           64-character IRN to cancel.
     * @param  string $cancelRsnCode Reason code (1=Duplicate, 2=Data Entry Mistake, 3=Order Cancelled, 4=Other).
     * @param  string $cancelRmrk    Cancellation remark (required when code = 4).
     * @return array                 API response with cancellation status.
     */
    public function cancelIrn(string $irn, string $cancelRsnCode, string $cancelRmrk = ''): array
    {
        return $this->post('/cancelirn', [
            'irn'           => $irn,
            'cancelRsnCode' => $cancelRsnCode,
            'cancelRmrk'    => $cancelRmrk,
        ]);
    }

    /**
     * Get IRNs that were rejected by the GST portal, filtered by date.
     *
     * @param  string $date  Date in DD/MM/YYYY format.
     * @return array         List of rejected IRN records.
     */
    public function getRejectedIrns(string $date): array
    {
        return $this->get("/getrejectedirns/{$date}");
    }

    /*==========================================================================
    | E-WAY BILL FROM IRN
    ==========================================================================*/

    /**
     * Generate an E-Way Bill using an existing Invoice Reference Number (IRN).
     *
     * @param  string $irn         64-character IRN.
     * @param  string $transMode   Mode of transport (1=Road, 2=Rail, 3=Air, 4=Ship).
     * @param  string $transId     Transporter GSTIN or TRANSIN (optional if vehicle details provided).
     * @param  string $transName   Transporter name (optional).
     * @param  string $transDocNo  Transporter document number.
     * @param  string $transDocDate Transporter document date (DD/MM/YYYY).
     * @param  string $vehicleNo   Vehicle registration number.
     * @param  string $vehicleType Vehicle type (R=Regular, O=ODC).
     * @return array               API response containing ewbNo, ewbDate, validUpto.
     */
    public function generateEwayBillFromIrn(
        string $irn,
        string $transMode,
        ?string $transId     = null,
        string $transName    = 'SELF',
        string $transDocNo   = '',
        string $transDocDate = '',
        string $vehicleNo    = '',
        string $vehicleType  = 'R',
        int    $distance     = 0
    ): array {
        return $this->post('/type/GENERATE_EWAYBILL/version/V1_03', [
            'Irn'         => $irn,
            'Distance'    => $distance,
            'TransMode'   => $transMode,
            'TransId'     => $transId,
            'TransName'   => $transName,
            'TransDocNo'  => $transDocNo,
            'TransDocDt'  => $transDocDate,
            'VehNo'       => $vehicleNo,
            'VehType'     => $vehicleType,
        ]);
    }

    /**
     * Get E-Way Bill details associated with a given IRN.
     *
     * @param  string $irn  64-character IRN.
     * @return array        E-Way Bill details for the IRN.
     */
    public function getEwayBillDetailsByIrn(string $irn): array
    {
        return $this->get("/getewaybilldetailsbyirn/{$irn}");
    }

    /*==========================================================================
    | B2C QR CODE
    ==========================================================================*/

    /**
     * Get B2C QR code details for a B2C invoice.
     *
     * @param  string $docType  Document type (B2C).
     * @param  string $docNo    Document number.
     * @param  string $docDate  Document date (DD/MM/YYYY).
     * @return array            B2C QR code data.
     */
    public function getB2cQrCodeDetails(string $docType, string $docNo, string $docDate): array
    {
        return $this->get('/getb2cqrcodedetails', [
            'doctype' => $docType,
            'docnum'  => $docNo,
            'docdate' => $docDate,
        ]);
    }

    /*==========================================================================
    | UTILITY
    ==========================================================================*/

    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
