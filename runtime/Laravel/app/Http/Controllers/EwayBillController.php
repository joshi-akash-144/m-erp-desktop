<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Services\EwayBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

/**
 * EwayBillController
 *
 * Exposes all Whitebook GSP E-Way Bill API operations over HTTP.
 * Each action delegates to EwayBillService, which loads credentials
 * dynamically from the company_gst_credentials table.
 *
 * All responses follow the AjaxResponse helper format:
 *   { success, error, code, message, data }
 */
class EwayBillController extends Controller
{
    protected EwayBillService $service;

    public function __construct(EwayBillService $service)
    {
        $this->middleware('permission:eway_bill.manage');
        $this->service = $service;
    }

    /*==========================================================================
    | AUTHENTICATION
    ==========================================================================*/

    /**
     * Authenticate with the Whitebook API and retrieve (or return cached) token.
     *
     * POST /eway-bill/authenticate
     *
     * @return JsonResponse  { data: { token, environment } }
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
    | E-WAY BILL OPERATIONS
    ==========================================================================*/

    /**
     * Generate a new E-Way Bill from invoice data.
     *
     * POST /eway-bill/generate
     *
     * Request body: Full Whitebook generateEwayBill payload (JSON).
     * See EwayBillService::generateEwayBill() docblock for required keys.
     *
     * @return JsonResponse  { data: { ewbNo, ewbDate, validUpto, ... } }
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'supplyType'      => 'required|string',
            'subSupplyType'   => 'required|string',
            'docType'         => 'required|string',
            'docNo'           => 'required|string|max:16',
            'docDate'         => 'required|string',
            'fromGstin'       => 'required|string|size:15',
            'toGstin'         => 'required|string|size:15',
            'transMode'       => 'required|string|in:1,2,3,4',
            'transDistance'   => 'required|integer|min:1',
            'itemList'        => 'required|array|min:1',
            'itemList.*.hsnCode'       => 'required|string',
            'itemList.*.productName'   => 'required|string',
            'itemList.*.quantity'      => 'required|numeric|min:0',
            'itemList.*.qtyUnit'       => 'required|string',
            'itemList.*.taxableAmount' => 'required|numeric|min:0',
        ]);

        try {
            $result = $this->service->generateEwayBill($request->all());
            return AjaxResponse::success('E-Way Bill generated successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Update Part-B (vehicle / transporter) details of an E-Way Bill.
     *
     * POST /eway-bill/update-part-b
     *
     * @param  string  ewbNo         E-Way Bill number.
     * @param  string  vehicleNo     New vehicle number.
     * @param  string  fromPlace     Place of departure.
     * @param  int     fromState     State code.
     * @param  string  reasonCode    Reason code for the update.
     * @param  string  reasonRemark  Free-text remark.
     * @param  string  transDocNo    Transporter LR/RR number.
     * @param  string  transDocDate  Document date (DD/MM/YYYY).
     * @param  string  transMode     1=Road, 2=Rail, 3=Air, 4=Ship.
     * @param  string  vehicleType   R=Regular, O=ODC.
     *
     * @return JsonResponse
     */
    public function updatePartB(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ewbNo'        => 'required|string',
            'vehicleNo'    => 'required|string|max:15',
            'fromPlace'    => 'required|string',
            'fromState'    => 'required|integer',
            'reasonCode'   => 'required|string',
            'reasonRemark' => 'nullable|string|max:300',
            'transDocNo'   => 'nullable|string|max:15',
            'transDocDate' => 'nullable|string',
            'transMode'    => 'required|string|in:1,2,3,4',
            'vehicleType'  => 'required|string|in:R,O',
        ]);

        try {
            $result = $this->service->updatePartBVehicle(
                ewbNo:         $validated['ewbNo'],
                vehicleNo:     $validated['vehicleNo'],
                fromPlace:     $validated['fromPlace'],
                fromState:     (int) $validated['fromState'],
                reasonCode:    $validated['reasonCode'],
                reasonRemark:  $validated['reasonRemark'] ?? '',
                transDocNo:    $validated['transDocNo'] ?? '',
                transDocDate:  $validated['transDocDate'] ?? '',
                transMode:     $validated['transMode'],
                vehicleType:   $validated['vehicleType'],
            );

            return AjaxResponse::success('Part-B updated successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Generate a Consolidated E-Way Bill (CEWB) for multiple EWBs.
     *
     * POST /eway-bill/generate-consolidated
     *
     * @param  string   vehicleNo    Vehicle registration number.
     * @param  string   fromPlace    Place of departure.
     * @param  int      fromState    State code.
     * @param  string   transMode    Mode of transport.
     * @param  string   vehicleType  R=Regular, O=ODC.
     * @param  array    ewbNos       List of E-Way Bill numbers.
     * @param  string   transDocNo   Optional transporter document number.
     * @param  string   transDocDate Optional transporter document date.
     *
     * @return JsonResponse  { data: { cewbNo, cewbDate } }
     */
    public function generateConsolidated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicleNo'    => 'required|string|max:15',
            'fromPlace'    => 'required|string',
            'fromState'    => 'required|integer',
            'transMode'    => 'required|string|in:1,2,3,4',
            'vehicleType'  => 'required|string|in:R,O',
            'ewbNos'       => 'required|array|min:1',
            'ewbNos.*'     => 'required|string',
            'transDocNo'   => 'nullable|string|max:15',
            'transDocDate' => 'nullable|string',
        ]);

        try {
            $result = $this->service->generateConsolidatedEwayBill(
                vehicleNo:    $validated['vehicleNo'],
                fromPlace:    $validated['fromPlace'],
                fromState:    (int) $validated['fromState'],
                transMode:    $validated['transMode'],
                vehicleType:  $validated['vehicleType'],
                ewbNos:       $validated['ewbNos'],
                transDocNo:   $validated['transDocNo'] ?? '',
                transDocDate: $validated['transDocDate'] ?? '',
            );

            return AjaxResponse::success('Consolidated E-Way Bill generated successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Cancel an E-Way Bill.
     *
     * POST /eway-bill/cancel
     *
     * @param  string  ewbNo       E-Way Bill number to cancel.
     * @param  string  cancelCode  Reason code (1=Duplicate, 2=Order Cancelled, 3=Data Entry Mistake, 4=Other).
     * @param  string  cancelRmrk  Required remark when cancelCode = 4.
     *
     * @return JsonResponse
     */
    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ewbNo'      => 'required|string',
            'cancelCode' => 'required|string|in:1,2,3,4',
            'cancelRmrk' => 'nullable|string|max:300',
        ]);

        try {
            $result = $this->service->cancelEwayBill(
                ewbNo:      $validated['ewbNo'],
                cancelCode: $validated['cancelCode'],
                cancelRmrk: $validated['cancelRmrk'] ?? '',
            );

            return AjaxResponse::success('E-Way Bill cancelled successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Reject an E-Way Bill generated by another party.
     * Must be done within 72 hours of generation.
     *
     * POST /eway-bill/reject
     *
     * @param  string  ewbNo  E-Way Bill number to reject.
     *
     * @return JsonResponse
     */
    public function reject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ewbNo' => 'required|string',
        ]);

        try {
            $result = $this->service->rejectEwayBill($validated['ewbNo']);
            return AjaxResponse::success('E-Way Bill rejected successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Update the transporter on an existing E-Way Bill.
     *
     * POST /eway-bill/update-transporter
     *
     * @param  string  ewbNo          E-Way Bill number.
     * @param  string  transporterId  New transporter GSTIN or TRANSIN.
     *
     * @return JsonResponse
     */
    public function updateTransporter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ewbNo'         => 'required|string',
            'transporterId' => 'required|string|max:15',
        ]);

        try {
            $result = $this->service->updateTransporter(
                ewbNo:         $validated['ewbNo'],
                transporterId: $validated['transporterId'],
            );

            return AjaxResponse::success('Transporter updated successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Extend the validity of an E-Way Bill.
     *
     * POST /eway-bill/extend-validity
     *
     * @return JsonResponse  { data: { validUpto } }
     */
    public function extendValidity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ewbNo'             => 'required|string',
            'vehicleNo'         => 'required|string|max:15',
            'fromPlace'         => 'required|string',
            'fromState'         => 'required|integer',
            'remainingDist'     => 'required|integer|min:1',
            'transDocNo'        => 'nullable|string|max:15',
            'transDocDate'      => 'nullable|string',
            'transMode'         => 'required|string|in:1,2,3,4',
            'extnRsnCode'       => 'required|string',
            'extnRemarks'       => 'nullable|string|max:300',
            'consignmentStatus' => 'required|string',
            'addressLine1'      => 'nullable|string|max:100',
            'addressLine2'      => 'nullable|string|max:100',
            'addressLine3'      => 'nullable|string|max:100',
        ]);

        try {
            $result = $this->service->extendValidity(
                ewbNo:             $validated['ewbNo'],
                vehicleNo:         $validated['vehicleNo'],
                fromPlace:         $validated['fromPlace'],
                fromState:         (int) $validated['fromState'],
                remainingDist:     (string) $validated['remainingDist'],
                transDocNo:        $validated['transDocNo'] ?? '',
                transDocDate:      $validated['transDocDate'] ?? '',
                transMode:         $validated['transMode'],
                extnRsnCode:       $validated['extnRsnCode'],
                extnRemarks:       $validated['extnRemarks'] ?? '',
                consignmentStatus: $validated['consignmentStatus'],
                addressLine1:      $validated['addressLine1'] ?? '',
                addressLine2:      $validated['addressLine2'] ?? '',
                addressLine3:      $validated['addressLine3'] ?? '',
            );

            return AjaxResponse::success('E-Way Bill validity extended successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Regenerate a Consolidated E-Way Bill.
     *
     * POST /eway-bill/regenerate-consolidated
     *
     * @param  string  cewbNo  Consolidated E-Way Bill number to regenerate.
     *
     * @return JsonResponse  { data: { cewbNo, cewbDate } }
     */
    public function regenerateConsolidated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cewbNo' => 'required|string',
        ]);

        try {
            $result = $this->service->regenerateConsolidatedEwayBill($validated['cewbNo']);
            return AjaxResponse::success('Consolidated E-Way Bill regenerated successfully.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Initiate multi-vehicle movement for an E-Way Bill.
     *
     * POST /eway-bill/multi-vehicle/initiate
     *
     * @return JsonResponse  { data: { groupId } }
     */
    public function initiateMultiVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ewbNo'       => 'required|string',
            'fromPlace'   => 'required|string',
            'fromState'   => 'required|integer',
            'multiVehNo'  => 'required|string|max:15',
            'transDocNo'  => 'nullable|string|max:15',
            'transDocDate'=> 'nullable|string',
            'transMode'   => 'required|string|in:1,2,3,4',
            'vehicleType' => 'required|string|in:R,O',
        ]);

        try {
            $result = $this->service->initiateMultiVehicleMovement(
                ewbNo:        $validated['ewbNo'],
                fromPlace:    $validated['fromPlace'],
                fromState:    (int) $validated['fromState'],
                multiVehNo:   $validated['multiVehNo'],
                transDocNo:   $validated['transDocNo'] ?? '',
                transDocDate: $validated['transDocDate'] ?? '',
                transMode:    $validated['transMode'],
                vehicleType:  $validated['vehicleType'],
            );

            return AjaxResponse::success('Multi-vehicle movement initiated.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Add vehicles to an existing multi-vehicle movement.
     *
     * POST /eway-bill/multi-vehicle/add
     *
     * @param  string  groupId      Group ID from initiateMultiVehicle.
     * @param  array   vehicleList  Array of vehicle detail objects.
     *
     * @return JsonResponse
     */
    public function addMultiVehicles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'groupId'       => 'required|string',
            'vehicleList'   => 'required|array|min:1',
            'vehicleList.*' => 'required|array',
        ]);

        try {
            $result = $this->service->addMultiVehicles(
                groupId:     $validated['groupId'],
                vehicleList: $validated['vehicleList'],
            );

            return AjaxResponse::success('Vehicles added to multi-vehicle movement.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Change vehicle details in an existing multi-vehicle movement.
     *
     * POST /eway-bill/multi-vehicle/change
     *
     * @param  string  groupId      Group ID of the multi-vehicle movement.
     * @param  array   vehicleList  Updated vehicle detail objects.
     *
     * @return JsonResponse
     */
    public function changeMultiVehicles(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'groupId'       => 'required|string',
            'vehicleList'   => 'required|array|min:1',
            'vehicleList.*' => 'required|array',
        ]);

        try {
            $result = $this->service->changeMultiVehicles(
                groupId:     $validated['groupId'],
                vehicleList: $validated['vehicleList'],
            );

            return AjaxResponse::success('Multi-vehicle movement updated.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /*==========================================================================
    | E-WAY BILL RETRIEVAL / QUERY
    ==========================================================================*/

    /**
     * Get full details of a single E-Way Bill.
     *
     * GET /eway-bill/details/{ewbNo}
     *
     * @param  string  $ewbNo  E-Way Bill number.
     *
     * @return JsonResponse  { data: { ewbNo, ewbDate, validUpto, itemList, ... } }
     */
    public function details(string $ewbNo): JsonResponse
    {
        try {
            $result = $this->service->getEwayBillDetails($ewbNo);
            return AjaxResponse::success('E-Way Bill details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bills assigned to this GSTIN as transporter, filtered by date.
     *
     * GET /eway-bill/transporter/by-date?date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getForTransporterByDate(Request $request): JsonResponse
    {
        $request->validate(['date' => 'required|date_format:d/m/Y']);

        try {
            $result = $this->service->getEwayBillForTransporterByDate($request->input('date'));
            return AjaxResponse::success('E-Way Bills fetched by date.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bills for this transporter GSTIN filtered by state.
     *
     * GET /eway-bill/transporter/by-state?stateCode=27&date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getForTransporterByState(Request $request): JsonResponse
    {
        $request->validate([
            'stateCode' => 'required|integer',
            'date'      => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getEwayBillsForTransporterByState(
                (int) $request->input('stateCode'),
                $request->input('date')
            );
            return AjaxResponse::success('E-Way Bills fetched by state.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bills for a specific transporter GSTIN.
     *
     * GET /eway-bill/transporter/by-gstin?transporterGstin=...&date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getForTransporterByGstin(Request $request): JsonResponse
    {
        $request->validate([
            'transporterGstin' => 'required|string|size:15',
            'date'             => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getEwayBillsForTransporterByGstin(
                $request->input('transporterGstin'),
                $request->input('date')
            );
            return AjaxResponse::success('E-Way Bills fetched by transporter GSTIN.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get an E-Way Bill report for a transporter by the date it was assigned.
     *
     * GET /eway-bill/transporter/report-by-assigned-date?assignedDate=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getTransporterReportByAssignedDate(Request $request): JsonResponse
    {
        $request->validate(['assignedDate' => 'required|date_format:d/m/Y']);

        try {
            $result = $this->service->getEwayBillReportByTransporterDate($request->input('assignedDate'));
            return AjaxResponse::success('Transporter report fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get all E-Way Bills generated by this GSTIN on a given date.
     *
     * GET /eway-bill/by-date?date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getByDate(Request $request): JsonResponse
    {
        $request->validate(['date' => 'required|date_format:d/m/Y']);

        try {
            $result = $this->service->getEwayBillsByDate($request->input('date'));
            return AjaxResponse::success('E-Way Bills fetched by date.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bills that were rejected by other parties for this GSTIN.
     *
     * GET /eway-bill/rejected-by-others?date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getRejectedByOthers(Request $request): JsonResponse
    {
        $request->validate(['date' => 'required|date_format:d/m/Y']);

        try {
            $result = $this->service->getEwayBillsRejectedByOthers($request->input('date'));
            return AjaxResponse::success('Rejected E-Way Bills fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bills for specific supplier and buyer parties.
     *
     * GET /eway-bill/by-parties?fromGstin=...&toGstin=...&date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getByParties(Request $request): JsonResponse
    {
        $request->validate([
            'fromGstin' => 'required|string|size:15',
            'toGstin'   => 'required|string|size:15',
            'date'      => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getEwayBillsByParties(
                $request->input('fromGstin'),
                $request->input('toGstin'),
                $request->input('date')
            );
            return AjaxResponse::success('E-Way Bills by parties fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get details of a Consolidated E-Way Bill.
     *
     * GET /eway-bill/consolidated?cewbNo=...
     *
     * @return JsonResponse  { data: { cewbNo, cewbDate, ewbList } }
     */
    public function getConsolidated(Request $request): JsonResponse
    {
        $request->validate(['cewbNo' => 'required|string']);

        try {
            $result = $this->service->getConsolidatedEwayBill($request->input('cewbNo'));
            return AjaxResponse::success('Consolidated E-Way Bill details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Get E-Way Bills generated by a specific consigner GSTIN.
     *
     * GET /eway-bill/by-consigner?consignerGstin=...&date=DD/MM/YYYY
     *
     * @return JsonResponse  { data: [ ... ] }
     */
    public function getByConsigner(Request $request): JsonResponse
    {
        $request->validate([
            'consignerGstin' => 'required|string|size:15',
            'date'           => 'required|date_format:d/m/Y',
        ]);

        try {
            $result = $this->service->getEwayBillByConsigner(
                $request->input('consignerGstin'),
                $request->input('date')
            );
            return AjaxResponse::success('E-Way Bills by consigner fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /*==========================================================================
    | MASTER DATA & LOOKUP
    ==========================================================================*/

    /**
     * Retrieve the full error code list from the GST portal via Whitebook.
     *
     * GET /eway-bill/master/error-list
     *
     * @return JsonResponse  { data: [ { errorCode, errorMessage } ] }
     */
    public function errorList(): JsonResponse
    {
        try {
            $result = $this->service->getErrorList();
            return AjaxResponse::success('Error list fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Fetch GSTIN registration details (legal name, trade name, status, address).
     *
     * GET /eway-bill/master/gstin/{gstin}
     *
     * @param  string  $gstin  15-character GSTIN to look up.
     *
     * @return JsonResponse  { data: { legalName, tradeName, gstinStatus, ... } }
     */
    public function gstinDetails(string $gstin): JsonResponse
    {
        if (strlen($gstin) !== 15) {
            return AjaxResponse::error('GSTIN must be exactly 15 characters.', [], 422);
        }

        try {
            $result = $this->service->getGstinDetails($gstin);
            return AjaxResponse::success('GSTIN details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Fetch TRANSIN (Transporter ID) details.
     *
     * GET /eway-bill/master/transin/{transId}
     *
     * @param  string  $transId  15-digit TRANSIN number.
     *
     * @return JsonResponse  { data: { transName, address, ... } }
     */
    public function transinDetails(string $transId): JsonResponse
    {
        try {
            $result = $this->service->getTransinDetails($transId);
            return AjaxResponse::success('TRANSIN details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }

    /**
     * Fetch HSN code details including description and applicable tax rates.
     *
     * GET /eway-bill/master/hsn/{hsnCode}
     *
     * @param  string  $hsnCode  4–8 digit HSN/SAC code.
     *
     * @return JsonResponse  { data: { hsnCode, description, cgstRate, sgstRate, igstRate } }
     */
    public function hsnDetails(string $hsnCode): JsonResponse
    {
        if (!preg_match('/^\d{4,8}$/', $hsnCode)) {
            return AjaxResponse::error('HSN code must be 4–8 digits.', [], 422);
        }

        try {
            $result = $this->service->getHsnDetails($hsnCode);
            return AjaxResponse::success('HSN details fetched.', $result);
        } catch (Throwable $e) {
            return AjaxResponse::error($e->getMessage(), [], 422);
        }
    }
}
