<?php

namespace App\Services;

use App\Models\CompanyGstCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EwayBillService
 *
 * Handles all Whitebook GSP E-Way Bill API calls for a given company.
 * Credentials are loaded dynamically from the company_gst_credentials table
 * using the current company context. Supports both sandbox and production modes.
 *
 * Whitebook API provider: BVM IT Consulting Services India Private Limited (www.bvmcs.com)
 */
class EwayBillService
{
    /**
     * Resolved credential row from company_gst_credentials (type = eway_bill).
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
     * Load the active E-Way Bill credentials for the current company from the
     * company_gst_credentials table. Selects sandbox vs production based on the
     * application environment.
     *
     * @throws \RuntimeException if no credentials exist for this company.
     */
    private function loadCredentials(): void
    {
        $credential = CompanyGstCredential::where('company_id', company_id())
            ->where('type', 'eway_bill')
            ->first();

        if (!$credential) {
            throw new \RuntimeException(
                'E-Way Bill credentials not configured for this company. ' .
                'Please set them up under Setup → GST Credentials.'
            );
        }

        $this->credential = $credential;

        // APP_ENV=production  → use production credentials and production base URL.
        // Anything else (local, staging, testing, etc.) → use sandbox credentials.
        $this->isSandbox = !app()->environment('production');

        $this->baseUrl = $this->isSandbox
            ? rtrim($credential->sandbox_base_url ?? '', '/')
            : rtrim($credential->production_base_url ?? '', '/');

        if (empty($this->baseUrl)) {
            throw new \RuntimeException(
                'Base URL is not configured for E-Way Bill ' .
                ($this->isSandbox ? 'sandbox' : 'production') . ' credentials.'
            );
        }
    }

    /**
     * Return the active client ID (sandbox or production).
     */
    private function clientId(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_client_id ?? '')
            : ($this->credential->production_client_id ?? '');
    }

    /**
     * Return the active client secret / secret ID (sandbox or production).
     */
    private function clientSecret(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_secret_id ?? '')
            : ($this->credential->production_secret_id ?? '');
    }

    /**
     * Return the active GSTIN (sandbox or production).
     */
    private function gstin(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_gstin ?? '')
            : ($this->credential->production_gstin ?? '');
    }

    /**
     * Return the active username (sandbox or production).
     */
    private function username(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_username ?? '')
            : ($this->credential->production_username ?? '');
    }

    /**
     * Return the active password (sandbox or production).
     * The model auto-decrypts the value via its $casts definition.
     */
    private function password(): string
    {
        return $this->isSandbox
            ? ($this->credential->sandbox_password ?? '')
            : ($this->credential->production_password ?? '');
    }

    /**
     * Unique cache key for this company's auth token.
     * Scoped per company and environment so tokens never bleed across contexts.
     */
    private function tokenCacheKey(): string
    {
        $env = $this->isSandbox ? 'sandbox' : 'production';
        return 'ewb_auth_token_' . company_id() . '_' . $env;
    }

    /**
     * Authenticate with the Whitebook API and return a bearer/auth token.
     * The token is cached for 4 hours to avoid unnecessary round-trips; it is
     * evicted early only when a request fails with a 401 response.
     *
     * @return string The auth token.
     * @throws \RuntimeException on API failure.
     */
    public function authenticate(): string
    {
        // Return cached token when available.
        if (Cache::has($this->tokenCacheKey())) {
            return Cache::get($this->tokenCacheKey());
        }

        $response = Http::timeout(30)
            ->post("{$this->baseUrl}/authenticate", [
                'client_id'     => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'gstin'         => $this->gstin(),
                'username'      => $this->username(),
                'password'      => $this->password(),
            ]);

        $this->assertSuccess($response, 'Authentication');

        $token = $response->json('data.authToken')
            ?? $response->json('authToken')
            ?? $response->json('data.auth_token')
            ?? '';

        if (empty($token)) {
            throw new \RuntimeException('Whitebook authentication succeeded but returned no token.');
        }

        // Tokens are cached for 4 hours (Whitebook tokens are typically valid for 6 hours).
        Cache::put($this->tokenCacheKey(), $token, now()->addHours(4));

        return $token;
    }

    /**
     * Clear the cached auth token. Called automatically on 401 responses so
     * the next request will re-authenticate.
     */
    private function clearTokenCache(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    /**
     * Build the common HTTP headers required by every Whitebook API request.
     *
     * @param  string $token  Auth token from authenticate().
     * @return array<string, string>
     */
    private function headers(string $token): array
    {
        return [
            'client-id'     => $this->clientId(),
            'client-secret' => $this->clientSecret(),
            'authtoken'     => $token,
            'Gstin'         => $this->gstin(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Send an authenticated POST request to the Whitebook API.
     * Re-authenticates once if the token has expired (HTTP 401).
     *
     * @param  string $endpoint  Path after the base URL (e.g. '/generateEwayBill').
     * @param  array  $payload   JSON body.
     * @return array             Decoded response body.
     * @throws \RuntimeException on API or HTTP failure.
     */
    private function post(string $endpoint, array $payload = []): array
    {
        $token    = $this->authenticate();
        $response = Http::withHeaders($this->headers($token))
            ->timeout(30)
            ->post("{$this->baseUrl}{$endpoint}", $payload);

        // Re-authenticate once on token expiry.
        if ($response->status() === 401) {
            $this->clearTokenCache();
            $token    = $this->authenticate();
            $response = Http::withHeaders($this->headers($token))
                ->timeout(30)
                ->post("{$this->baseUrl}{$endpoint}", $payload);
        }

        $this->assertSuccess($response, $endpoint);
        return $response->json() ?? [];
    }

    /**
     * Send an authenticated GET request to the Whitebook API.
     * Re-authenticates once if the token has expired (HTTP 401).
     *
     * @param  string $endpoint  Path after the base URL.
     * @param  array  $query     Query string parameters.
     * @return array             Decoded response body.
     * @throws \RuntimeException on API or HTTP failure.
     */
    private function get(string $endpoint, array $query = []): array
    {
        $token    = $this->authenticate();
        $response = Http::withHeaders($this->headers($token))
            ->timeout(30)
            ->get("{$this->baseUrl}{$endpoint}", $query);

        // Re-authenticate once on token expiry.
        if ($response->status() === 401) {
            $this->clearTokenCache();
            $token    = $this->authenticate();
            $response = Http::withHeaders($this->headers($token))
                ->timeout(30)
                ->get("{$this->baseUrl}{$endpoint}", $query);
        }

        $this->assertSuccess($response, $endpoint);
        return $response->json() ?? [];
    }

    /**
     * Throw a descriptive RuntimeException when the API returns a non-2xx
     * response or when the response body carries a logical error.
     *
     * @throws \RuntimeException
     */
    private function assertSuccess(\Illuminate\Http\Client\Response $response, string $context): void
    {
        if ($response->failed()) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? $response->body();

            Log::error("EwayBillService [{$context}] HTTP error", [
                'status'  => $response->status(),
                'message' => $message,
            ]);

            throw new \RuntimeException(
                "Whitebook API error [{$context}]: {$message} (HTTP {$response->status()})"
            );
        }

        // Whitebook wraps errors in 200 responses with status = 0 / false.
        $status = $response->json('status') ?? $response->json('Status');
        if ($status === 0 || $status === '0' || $status === false) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? 'Unknown API error';
            throw new \RuntimeException("Whitebook API [{$context}]: {$message}");
        }
    }

    /*==========================================================================
    | E-WAY BILL OPERATIONS
    ==========================================================================*/

    /**
     * Generate a new E-Way Bill.
     *
     * Required payload keys (Whitebook spec):
     *   supplyType, subSupplyType, subSupplyDesc, docType, docNo, docDate,
     *   fromGstin, fromTrdName, fromAddr1, fromAddr2, fromPlace, fromPincode,
     *   fromStateCode, toGstin, toTrdName, toAddr1, toAddr2, toPlace,
     *   toPincode, toStateCode, transactionType, dispatchFromGSTIN,
     *   dispatchFromTradeName, shipToGSTIN, shipToTradeName, totalValue,
     *   cgstValue, sgstValue, igstValue, cessValue, cessNonAdvolValue,
     *   otherValue, totInvValue, transMode, transDistance, transporterName,
     *   transporterId, transDocNo, transDocDate, vehicleNo, vehicleType,
     *   itemList [ { itemNo, productName, productDesc, hsnCode, quantity,
     *                qtyUnit, cgstRate, sgstRate, igstRate, cessRate,
     *                cessAdvol, taxableAmount } ]
     *
     * @param  array $data  Invoice and item details per Whitebook spec.
     * @return array        API response containing ewbNo, ewbDate, etc.
     */
    public function generateEwayBill(array $data): array
    {
        return $this->post('/generateEwayBill', $data);
    }

    /**
     * Update Part-B (vehicle / transporter details) of an existing E-Way Bill.
     *
     * @param  string      $ewbNo        E-Way Bill number.
     * @param  string      $vehicleNo    New vehicle registration number.
     * @param  string      $fromPlace    Place from where the movement starts.
     * @param  int         $fromState    State code for the start location.
     * @param  string      $reasonCode   Reason code for the update (e.g. "1" = Due to Break Down).
     * @param  string      $reasonRemark Freeform remark for the reason.
     * @param  string      $transDocNo   Transporter document number (LR/RR/etc.).
     * @param  string      $transDocDate Transporter document date (DD/MM/YYYY).
     * @param  string      $transMode    Mode of transport (1=Road, 2=Rail, 3=Air, 4=Ship).
     * @param  string      $vehicleType  Vehicle type (R=Regular, O=ODC).
     * @return array                     API response.
     */
    public function updatePartBVehicle(
        string $ewbNo,
        string $vehicleNo,
        string $fromPlace,
        int    $fromState,
        string $reasonCode,
        string $reasonRemark,
        string $transDocNo,
        string $transDocDate,
        string $transMode,
        string $vehicleType
    ): array {
        return $this->post('/updatePartB', [
            'ewbNo'       => $ewbNo,
            'vehicleNo'   => $vehicleNo,
            'fromPlace'   => $fromPlace,
            'fromState'   => $fromState,
            'reasonCode'  => $reasonCode,
            'reasonRmrk'  => $reasonRemark,
            'transDocNo'  => $transDocNo,
            'transDocDate'=> $transDocDate,
            'transMode'   => $transMode,
            'vehicleType' => $vehicleType,
        ]);
    }

    /**
     * Generate a Consolidated E-Way Bill (CEWB) for multiple E-Way Bills
     * that share the same vehicle and route.
     *
     * @param  string   $vehicleNo    Vehicle registration number.
     * @param  string   $fromPlace    Starting place of movement.
     * @param  int      $fromState    State code for the start location.
     * @param  string   $transMode    Mode of transport (1=Road, 2=Rail, 3=Air, 4=Ship).
     * @param  string   $vehicleType  Vehicle type (R=Regular, O=ODC).
     * @param  array    $ewbNos       Array of E-Way Bill numbers to consolidate.
     * @param  string   $transDocNo   Optional transporter document number.
     * @param  string   $transDocDate Optional transporter document date (DD/MM/YYYY).
     * @return array                  API response containing cewbNo, cewbDate.
     */
    public function generateConsolidatedEwayBill(
        string $vehicleNo,
        string $fromPlace,
        int    $fromState,
        string $transMode,
        string $vehicleType,
        array  $ewbNos,
        string $transDocNo   = '',
        string $transDocDate = ''
    ): array {
        return $this->post('/generateConsolidatedEwb', [
            'vehicleNo'   => $vehicleNo,
            'fromPlace'   => $fromPlace,
            'fromState'   => $fromState,
            'transMode'   => $transMode,
            'vehicleType' => $vehicleType,
            'ewbNos'      => $ewbNos,
            'transDocNo'  => $transDocNo,
            'transDocDate'=> $transDocDate,
        ]);
    }

    /**
     * Cancel an existing E-Way Bill before its validity expires.
     *
     * @param  string $ewbNo      E-Way Bill number to cancel.
     * @param  string $cancelCode Cancellation reason code (1=Duplicate, 2=Order Cancelled, 3=Data Entry Mistake, 4=Other).
     * @param  string $cancelRmrk Cancellation remark (required when code = 4).
     * @return array              API response.
     */
    public function cancelEwayBill(string $ewbNo, string $cancelCode, string $cancelRmrk = ''): array
    {
        return $this->post('/cancelEwayBill', [
            'ewbNo'         => $ewbNo,
            'cancelRsnCode' => $cancelCode,
            'cancelRmrk'    => $cancelRmrk,
        ]);
    }

    /**
     * Reject an E-Way Bill generated by another party for this GSTIN.
     * Must be rejected within 72 hours of generation.
     *
     * @param  string $ewbNo  E-Way Bill number to reject.
     * @return array          API response.
     */
    public function rejectEwayBill(string $ewbNo): array
    {
        return $this->post('/rejectEwayBill', [
            'ewbNo' => $ewbNo,
        ]);
    }

    /**
     * Update the transporter details on an existing E-Way Bill.
     *
     * @param  string $ewbNo          E-Way Bill number.
     * @param  string $transporterId  GSTIN or transporter ID of the new transporter.
     * @return array                  API response.
     */
    public function updateTransporter(string $ewbNo, string $transporterId): array
    {
        return $this->post('/updateTransporter', [
            'ewbNo'         => $ewbNo,
            'transporterId' => $transporterId,
        ]);
    }

    /**
     * Extend the validity of an E-Way Bill when goods have not been delivered
     * within the original validity period.
     *
     * @param  string $ewbNo          E-Way Bill number.
     * @param  string $vehicleNo      Current vehicle registration number.
     * @param  string $fromPlace      Place from where extension starts.
     * @param  int    $fromState      State code for the extension start location.
     * @param  string $remainingDist  Remaining distance in KM.
     * @param  string $transDocNo     Transporter document number.
     * @param  string $transDocDate   Transporter document date (DD/MM/YYYY).
     * @param  string $transMode      Mode of transport.
     * @param  string $extnRsnCode    Extension reason code.
     * @param  string $extnRemarks    Extension remarks.
     * @param  string $consignmentStatus Delivery status of consignment.
     * @param  string $addressLine1   Address line 1.
     * @param  string $addressLine2   Address line 2.
     * @param  string $addressLine3   Address line 3 (city/district).
     * @return array                  API response with new validity date/time.
     */
    public function extendValidity(
        string $ewbNo,
        string $vehicleNo,
        string $fromPlace,
        int    $fromState,
        string $remainingDist,
        string $transDocNo,
        string $transDocDate,
        string $transMode,
        string $extnRsnCode,
        string $extnRemarks,
        string $consignmentStatus,
        string $addressLine1 = '',
        string $addressLine2 = '',
        string $addressLine3 = ''
    ): array {
        return $this->post('/extendValidityEwayBill', [
            'ewbNo'             => $ewbNo,
            'vehicleNo'         => $vehicleNo,
            'fromPlace'         => $fromPlace,
            'fromState'         => $fromState,
            'remainingDistance' => $remainingDist,
            'transDocNo'        => $transDocNo,
            'transDocDate'      => $transDocDate,
            'transMode'         => $transMode,
            'extnRsnCode'       => $extnRsnCode,
            'extnRemarks'       => $extnRemarks,
            'consignmentStatus' => $consignmentStatus,
            'addressLine1'      => $addressLine1,
            'addressLine2'      => $addressLine2,
            'addressLine3'      => $addressLine3,
        ]);
    }

    /**
     * Regenerate a Consolidated E-Way Bill after updating vehicle or transporter details.
     *
     * @param  string $cewbNo  Consolidated E-Way Bill number to regenerate.
     * @return array           API response with new CEWB number.
     */
    public function regenerateConsolidatedEwayBill(string $cewbNo): array
    {
        return $this->post('/regenerateConsolidatedEwb', [
            'cewbNo' => $cewbNo,
        ]);
    }

    /**
     * Initiate multi-vehicle movement for a single E-Way Bill (Part-B multi-stop).
     *
     * @param  string $ewbNo        E-Way Bill number.
     * @param  string $fromPlace    Starting place.
     * @param  int    $fromState    State code.
     * @param  string $multiVehNo  Vehicle number.
     * @param  string $transDocNo  Transporter document number.
     * @param  string $transDocDate Transporter document date (DD/MM/YYYY).
     * @param  string $transMode   Mode of transport.
     * @param  string $vehicleType Vehicle type.
     * @return array               API response.
     */
    public function initiateMultiVehicleMovement(
        string $ewbNo,
        string $fromPlace,
        int    $fromState,
        string $multiVehNo,
        string $transDocNo,
        string $transDocDate,
        string $transMode,
        string $vehicleType
    ): array {
        return $this->post('/initiateMultiVehicleMovement', [
            'ewbNo'       => $ewbNo,
            'fromPlace'   => $fromPlace,
            'fromState'   => $fromState,
            'multiVehNo'  => $multiVehNo,
            'transDocNo'  => $transDocNo,
            'transDocDate'=> $transDocDate,
            'transMode'   => $transMode,
            'vehicleType' => $vehicleType,
        ]);
    }

    /**
     * Add additional vehicles to an existing multi-vehicle movement.
     *
     * @param  string $groupId     Group ID returned by initiateMultiVehicleMovement.
     * @param  array  $vehicleList Array of vehicle detail objects.
     * @return array               API response.
     */
    public function addMultiVehicles(string $groupId, array $vehicleList): array
    {
        return $this->post('/addMultiVehicles', [
            'groupId'     => $groupId,
            'vehicleList' => $vehicleList,
        ]);
    }

    /**
     * Change vehicle details for an existing multi-vehicle movement.
     *
     * @param  string $groupId     Group ID of the multi-vehicle movement.
     * @param  array  $vehicleList Updated array of vehicle detail objects.
     * @return array               API response.
     */
    public function changeMultiVehicles(string $groupId, array $vehicleList): array
    {
        return $this->post('/changeMultiVehicles', [
            'groupId'     => $groupId,
            'vehicleList' => $vehicleList,
        ]);
    }

    /*==========================================================================
    | E-WAY BILL RETRIEVAL / QUERY
    ==========================================================================*/

    /**
     * Fetch the full details of an E-Way Bill by its number.
     *
     * @param  string $ewbNo  E-Way Bill number.
     * @return array          Full EWB details from the GST portal.
     */
    public function getEwayBillDetails(string $ewbNo): array
    {
        return $this->get("/getEwayBillDetails/{$ewbNo}");
    }

    /**
     * Retrieve all E-Way Bills assigned to this GSTIN as transporter, filtered by date.
     *
     * @param  string $date  Date in DD/MM/YYYY format.
     * @return array         List of E-Way Bill records.
     */
    public function getEwayBillForTransporterByDate(string $date): array
    {
        return $this->get('/getEwayBillForTransporter', ['date' => $date]);
    }

    /**
     * Retrieve E-Way Bills for this transporter GSTIN filtered by state.
     *
     * @param  int    $stateCode  State code (e.g. 27 for Maharashtra).
     * @param  string $date       Date in DD/MM/YYYY format.
     * @return array              List of E-Way Bill records.
     */
    public function getEwayBillsForTransporterByState(int $stateCode, string $date): array
    {
        return $this->get('/getEwayBillsForTransporterByState', [
            'stateCode' => $stateCode,
            'date'      => $date,
        ]);
    }

    /**
     * Retrieve E-Way Bills for a specific transporter GSTIN.
     *
     * @param  string $transporterGstin  GSTIN of the transporter.
     * @param  string $date              Date in DD/MM/YYYY format.
     * @return array                     List of E-Way Bill records.
     */
    public function getEwayBillsForTransporterByGstin(string $transporterGstin, string $date): array
    {
        return $this->get('/getEwayBillsForTransporterByGstin', [
            'transporterGstin' => $transporterGstin,
            'date'             => $date,
        ]);
    }

    /**
     * Retrieve an E-Way Bill report for a transporter based on the date
     * the E-Way Bill was assigned to them.
     *
     * @param  string $assignedDate  Date in DD/MM/YYYY format.
     * @return array                 List of E-Way Bill records.
     */
    public function getEwayBillReportByTransporterDate(string $assignedDate): array
    {
        return $this->get('/getEwayBillReportByTransporterDate', [
            'assignedDate' => $assignedDate,
        ]);
    }

    /**
     * Retrieve all E-Way Bills generated by this GSTIN on a given date.
     *
     * @param  string $date  Date in DD/MM/YYYY format.
     * @return array         List of E-Way Bill records.
     */
    public function getEwayBillsByDate(string $date): array
    {
        return $this->get('/getEwayBillsByDate', ['date' => $date]);
    }

    /**
     * Retrieve E-Way Bills that were rejected by other parties for this GSTIN.
     *
     * @param  string $date  Date in DD/MM/YYYY format.
     * @return array         List of rejected E-Way Bill records.
     */
    public function getEwayBillsRejectedByOthers(string $date): array
    {
        return $this->get('/getEwayBillsRejectedByOthers', ['date' => $date]);
    }

    /**
     * Retrieve E-Way Bills associated with specific supplier / recipient parties.
     *
     * @param  string $fromGstin  GSTIN of the supplier (consigner).
     * @param  string $toGstin    GSTIN of the buyer (consignee).
     * @param  string $date       Date in DD/MM/YYYY format.
     * @return array              List of matching E-Way Bill records.
     */
    public function getEwayBillsByParties(string $fromGstin, string $toGstin, string $date): array
    {
        return $this->get('/getEwayBillsByParties', [
            'fromGstin' => $fromGstin,
            'toGstin'   => $toGstin,
            'date'      => $date,
        ]);
    }

    /**
     * Retrieve the details of a Consolidated E-Way Bill by its CEWB number.
     *
     * @param  string $cewbNo  Consolidated E-Way Bill number.
     * @return array           Full CEWB details.
     */
    public function getConsolidatedEwayBill(string $cewbNo): array
    {
        return $this->get('/getConsolidatedEwayBill', ['cewbNo' => $cewbNo]);
    }

    /**
     * Retrieve E-Way Bills generated by a specific consigner (supplier) GSTIN.
     *
     * @param  string $consignerGstin  GSTIN of the consigner.
     * @param  string $date            Date in DD/MM/YYYY format.
     * @return array                   List of E-Way Bill records.
     */
    public function getEwayBillByConsigner(string $consignerGstin, string $date): array
    {
        return $this->get('/getEwayBillByConsigner', [
            'consignerGstin' => $consignerGstin,
            'date'           => $date,
        ]);
    }

    /*==========================================================================
    | MASTER DATA & LOOKUP
    ==========================================================================*/

    /**
     * Retrieve the error code list from the GST portal via Whitebook.
     * Useful for translating API error codes into readable messages.
     *
     * @return array  List of error codes and their descriptions.
     */
    public function getErrorList(): array
    {
        return $this->get('/getErrorList');
    }

    /**
     * Fetch GSTIN registration details (legal name, trade name, address, status).
     *
     * @param  string $gstin  15-character GSTIN to look up.
     * @return array          GSTIN master details.
     */
    public function getGstinDetails(string $gstin): array
    {
        return $this->get("/getGstinDetails/{$gstin}");
    }

    /**
     * Fetch Transporter ID (TRANSIN) details.
     * TRANSIN is a 15-digit ID for unregistered transporters.
     *
     * @param  string $transId  15-digit TRANSIN number.
     * @return array            Transporter details.
     */
    public function getTransinDetails(string $transId): array
    {
        return $this->get("/getTransinDetails/{$transId}");
    }

    /**
     * Fetch HSN code details including description and applicable tax rates.
     *
     * @param  string $hsnCode  4–8 digit HSN/SAC code.
     * @return array            HSN details including description and tax rates.
     */
    public function getHsnDetails(string $hsnCode): array
    {
        return $this->get("/getHsnDetails/{$hsnCode}");
    }

    /*==========================================================================
    | UTILITY
    ==========================================================================*/

    /**
     * Expose whether the service is currently operating in sandbox mode.
     * Useful for display / logging purposes.
     */
    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    /**
     * Return the resolved base URL (sandbox or production) being used.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
