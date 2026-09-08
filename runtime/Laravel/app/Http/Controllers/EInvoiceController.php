<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\EInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * EInvoiceController
 *
 * Exposes all Whitebook GSP E-Invoice API operations over HTTP.
 * Each action delegates to EInvoiceService, which loads credentials
 * dynamically from the company_gst_credentials table.
 *
 * All responses follow the AjaxResponse helper format:
 *   { success, error, code, message, data }
 */
class EInvoiceController extends Controller
{
    protected EInvoiceService $service;

    public function __construct(EInvoiceService $service)
    {
        $this->middleware('permission:e_invoice.manage');
        $this->service = $service;
    }

    /*==========================================================================
    | AUTHENTICATION
    ==========================================================================*/

    /**
     * Authenticate with the Whitebook API and retrieve (or return cached) token.
     *
     * POST /e-invoice/authenticate
     *
     * @return JsonResponse  { data: { token, environment, base_url } }
     */
    public function authenticate(): JsonResponse
    {
        try {
            $token = $this->service->authenticate();

            return AjaxResponse::success('Authentication successful.', [
                'token'       => $token,
                'environment' => $this->service->isSandbox() ? 'sandbox' : 'production',
                'base_url'    => $this->service->getBaseUrl(),
            ]);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /*==========================================================================
    | GSTN MASTER DATA
    ==========================================================================*/

    /**
     * Get GSTN Details for a given GST Number.
     *
     * GET /e-invoice/gstn-details/{gstin}
     *
     * @param  string  $gstin  15-character GSTIN.
     * @return JsonResponse
     */
    public function getGstnDetails(string $gstin): JsonResponse
    {
        if (strlen($gstin) !== 15) {
            return AjaxResponse::error('GSTIN must be exactly 15 characters.', [], 422);
        }

        try {
            $result = $this->service->getGstnDetails($gstin);
            return AjaxResponse::success('GSTN details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Sync GSTIN details from the GST Common Portal.
     *
     * GET /e-invoice/sync-gstin/{gstin}
     *
     * @param  string  $gstin  15-character GSTIN.
     * @return JsonResponse
     */
    public function syncGstinFromCp(string $gstin): JsonResponse
    {
        if (strlen($gstin) !== 15) {
            return AjaxResponse::error('GSTIN must be exactly 15 characters.', [], 422);
        }

        try {
            $result = $this->service->syncGstinFromCp($gstin);
            return AjaxResponse::success('GSTIN synced from Common Portal.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /*==========================================================================
    | IRN / E-INVOICE OPERATIONS
    ==========================================================================*/

    /**
     * Generate an Invoice Reference Number (IRN).
     *
     * POST /e-invoice/generate-irn
     *
     * Request body: Full Whitebook generateirn payload (JSON).
     * See EInvoiceService::generateIrn() docblock for required keys.
     *
     * @return JsonResponse  { data: { irn, ackNo, ackDt, signedInvoice, ... } }
     */
    public function generateIrn(Request $request): JsonResponse
    {
        $request->validate([
            'Version'           => 'required|string',
            'TranDtls'          => 'required|array',
            'DocDtls'           => 'required|array',
            'DocDtls.Typ'       => 'required|string|in:INV,CRN,DBN',
            'DocDtls.No'        => 'required|string|max:16',
            'DocDtls.Dt'        => 'required|string',
            'SellerDtls'        => 'required|array',
            'SellerDtls.Gstin'  => 'required|string|size:15',
            'BuyerDtls'         => 'required|array',
            'BuyerDtls.Gstin'   => 'required|string',
            'ItemList'          => 'required|array|min:1',
            'ValDtls'           => 'required|array',
        ]);

        try {
            $result = $this->service->generateIrn($request->all());
            return AjaxResponse::success('IRN generated successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get e-Invoice details for a given IRN.
     *
     * GET /e-invoice/details/{irn}
     *
     * @param  string  $irn  64-character Invoice Reference Number.
     * @return JsonResponse
     */
    public function getEInvoiceDetails(string $irn): JsonResponse
    {
        if (strlen($irn) !== 64) {
            return AjaxResponse::error('IRN must be exactly 64 characters.', [], 422);
        }

        try {
            $result = $this->service->getEInvoiceDetails($irn);
            return AjaxResponse::success('E-Invoice details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get IRN details by document type, number, and date.
     *
     * GET /e-invoice/irn-by-doc-details?doctype=INV&docnum=INV001&docdate=01/01/2025
     *
     * @return JsonResponse
     */
    public function getIrnByDocDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctype' => 'required|string|in:INV,CRN,DBN',
            'docnum'  => 'required|string|max:16',
            'docdate' => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getIrnByDocDetails(
                $validated['doctype'],
                $validated['docnum'],
                $validated['docdate']
            );
            return AjaxResponse::success('IRN details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Cancel an Invoice Reference Number (IRN).
     * Must be done within 24 hours of IRN generation.
     *
     * POST /e-invoice/cancel-irn
     *
     * @param  string  irn            64-character IRN.
     * @param  string  cancelRsnCode  Reason code (1=Duplicate, 2=Data Entry Mistake, 3=Order Cancelled, 4=Other).
     * @param  string  cancelRmrk     Remark (required when cancelRsnCode = 4).
     *
     * @return JsonResponse
     */
    public function cancelIrn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'irn'           => 'required|string|size:64',
            'cancelRsnCode' => 'required|string|in:1,2,3,4',
            'cancelRmrk'    => 'nullable|string|max:300',
        ]);

        try {
            $result = $this->service->cancelIrn(
                $validated['irn'],
                $validated['cancelRsnCode'],
                $validated['cancelRmrk'] ?? ''
            );
            return AjaxResponse::success('IRN cancelled successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get IRNs rejected by the GST portal for a given date.
     *
     * GET /e-invoice/rejected-irns?date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getRejectedIrns(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getRejectedIrns($validated['date']);
            return AjaxResponse::success('Rejected IRNs fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /*==========================================================================
    | E-WAY BILL FROM IRN
    ==========================================================================*/

    /**
     * Generate an E-Way Bill using an existing IRN.
     *
     * POST /e-invoice/generate-ewaybill
     *
     * @param  string  irn          64-character IRN.
     * @param  string  transMode    1=Road, 2=Rail, 3=Air, 4=Ship.
     * @param  string  transId      Transporter GSTIN or TRANSIN (optional).
     * @param  string  transName    Transporter name (optional).
     * @param  string  transDocNo   Transporter document number (optional).
     * @param  string  transDocDate Transporter document date DD/MM/YYYY (optional).
     * @param  string  vehicleNo    Vehicle registration number (optional).
     * @param  string  vehicleType  R=Regular, O=ODC (default: R).
     *
     * @return JsonResponse  { data: { ewbNo, ewbDate, validUpto } }
     */
    public function generateEwayBillFromIrn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'irn'          => 'required|string|size:64',
            'transMode'    => 'required|string|in:1,2,3,4',
            'transId'      => 'nullable|string|max:15',
            'transName'    => 'nullable|string|max:100',
            'transDocNo'   => 'nullable|string|max:15',
            'transDocDate' => 'nullable|string',
            'vehicleNo'    => 'nullable|string|max:15',
            'vehicleType'  => 'nullable|string|in:R,O',
        ]);

        try {
            $result = $this->service->generateEwayBillFromIrn(
                irn:         $validated['irn'],
                transMode:   $validated['transMode'],
                transId:     $validated['transId'] ?? '',
                transName:   $validated['transName'] ?? '',
                transDocNo:  $validated['transDocNo'] ?? '',
                transDocDate:$validated['transDocDate'] ?? '',
                vehicleNo:   $validated['vehicleNo'] ?? '',
                vehicleType: $validated['vehicleType'] ?? 'R',
            );
            return AjaxResponse::success('E-Way Bill generated from IRN successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bill details associated with a given IRN.
     *
     * GET /e-invoice/ewaybill-details/{irn}
     *
     * @param  string  $irn  64-character IRN.
     * @return JsonResponse  { data: { ewbNo, ewbDate, validUpto, ... } }
     */
    public function getEwayBillDetailsByIrn(string $irn): JsonResponse
    {
        if (strlen($irn) !== 64) {
            return AjaxResponse::error('IRN must be exactly 64 characters.', [], 422);
        }

        try {
            $result = $this->service->getEwayBillDetailsByIrn($irn);
            return AjaxResponse::success('E-Way Bill details by IRN fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /*==========================================================================
    | B2C QR CODE
    ==========================================================================*/

    /**
     * Get B2C QR code details for a B2C invoice.
     *
     * GET /e-invoice/b2c-qr-code?doctype=B2C&docnum=INV001&docdate=01/01/2025
     *
     * @return JsonResponse  { data: { qrCode, ... } }
     */
    public function getB2cQrCodeDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctype' => 'required|string',
            'docnum'  => 'required|string|max:16',
            'docdate' => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getB2cQrCodeDetails(
                $validated['doctype'],
                $validated['docnum'],
                $validated['docdate']
            );
            return AjaxResponse::success('B2C QR code details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }
}
