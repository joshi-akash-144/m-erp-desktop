<?php

namespace App\Http\Controllers;

use App\Helpers\AjaxResponse;
use App\Http\Requests\PendingPurchaseOrderRequest;
use App\Http\Requests\StoreGrnRequest;
use App\Http\Requests\UpdateGrnRequest;
use App\Models\Grn;
use App\Services\GrnService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Throwable;
use App\Services\MasterDataService;

class MobileGrnController extends Controller
{
    protected GrnService $service;
    protected MasterDataService $masterService;
    public function __construct(GrnService $service, MasterDataService $masterService)
    {
        $this->middleware('permission:mobile_grn.create')->only(['create', 'store']);
        $this->middleware('permission:mobile_grn.update')->only(['edit', 'update']);
        $this->middleware('permission:mobile_grn.delete')->only('destroy');
        $this->middleware('permission:mobile_grn.restore')->only('restore');

        $this->service = $service;
        $this->masterService = $masterService;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $grnInfo = $this->service->getNextVoucherNumber(
            companyId: company_id(),
            financialYearId: financial_year_id()
        );

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.mobile-grn.create',[  
            'grnSerial' => $grnInfo->grn_serial,
            'grnNumber' => $grnInfo->grn_number,
            'accounts' => $accounts,
            'brokers' => $brokers,
            'items' => $items,
            'destinations' => $destinations,
            'conditions' => $conditions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGrnRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $this->handleImageUpload($request, $validated, 'url_path');

            $grn = $this->service->createGrn(
                $validated,
                companyId: company_id(),
                financialYearId: financial_year_id()
            );

            return AjaxResponse::success(
                message: "Mobile GRN Number <b class='text-primary'>{$grn->grn_serial}</b> has been created successfully.",
                data: $grn
            );
        } catch (ValidationException $e) {
            // RETURN VALIDATION FORMAT DIRECTLY
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.grn.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, ?int $grnId = null): JsonResponse{
        
        if ($request->ajax() && $request->has('grn_id')) {
            $id = $request->grn_id;
            if ($id) {
                $grn = $this->service->getViewData(
                    grnId : $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$grn) {
                return AjaxResponse::error('Grn not found.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.grn.fetched'),
                data: $grn
            );
        }

        $grnSerials = $this->service->getGrnSerial(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            entryFrom: Grn::ENTRY_FROM_MOBILE
        );
        // dd($grnSerials->toArray(),$grnId,$grn);
        // return view('company.pages.grn.view', compact('grnSerials','grnId'));
        return AjaxResponse::success(
            message: __('messages.grn.fetched'),
            data: $grnSerials
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Grn $grn = null): View|JsonResponse
    {
        
        if ($request->ajax() && $request->has('grn_id')) {
            $id = $request->grn_id;
            if ($id) {
                $grn = $this->service->getEditData(
                    grnId : $id,
                    companyId: company_id(),
                    financialYearId: financial_year_id()
                );
            }
            if (!$grn || $grn->entry_from !== Grn::ENTRY_FROM_MOBILE) {
                return AjaxResponse::error('Grn not found or invalid entry type.', code: 404);
            }

            return AjaxResponse::success(
                message: __('messages.grn.fetched'),
                data: $grn
            );
        }

        $grnSerials = $this->service->getGrnSerial(
            companyId: company_id(),
            financialYearId: financial_year_id(),
            entryFrom: Grn::ENTRY_FROM_MOBILE
        );

        $masterData   = $this->masterService->purchaseOrderMasterData(company_id());
        extract($this->extractMasterData($masterData));

        return view('company.pages.mobile-grn.edit', [
            'grnSerials' => $grnSerials,
            'accounts' => $accounts,
            'brokers' => $brokers,
            'items' => $items,
            'destinations' => $destinations,
            'conditions' => $conditions,
            'grnId' => $grn?->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGrnRequest $request, Grn $grn)
    {
        if ($grn->entry_from !== Grn::ENTRY_FROM_MOBILE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid entry type for Mobile GRN update.'
            ], 403);
        }

        $data            = $request->validated();
        $this->handleImageUpload($request, $data, 'url_path');
        $financialYearId = financial_year_id();
        $companyId       = company_id();
    
        try {
    
            $data = $this->service->updateGrn($data, $companyId, $financialYearId, $grn->id);
    
            return AjaxResponse::success(
                message: __('messages.grn.updated'),
                data: $data
            );
    
        } catch (ValidationException $e) {
            // RETURN VALIDATION FORMAT DIRECTLY
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return AjaxResponse::error(
                message: __('messages.grn.throwable_error'),
                code: 500,
                errors: $e->getMessage()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grn $grn)
    {
        //
    }

    /**
     * Extracts commonly used master data arrays.
     */
    private function extractMasterData(array $masterData): array
    {
        return [
            'accounts'     => $masterData['accounts'] ?? [],
            'brokers'      => $masterData['brokers'] ?? [],
            'conditions'   => $masterData['conditions'] ?? [],
            'items'        => $masterData['items'] ?? [],
            'destinations' => $masterData['destinations'] ?? [],
        ];
    }

    private function handleImageUpload(Request $request, array &$data, string $fieldName = 'url_path'): void
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $filename = time() . '_' . uniqid() . '.jpg';
            $destinationPath = public_path('storage/grn_attachments');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $imageInfo = @getimagesize($file->getPathname());
            if ($imageInfo) {
                $mimeType = $imageInfo['mime'];
                $image = null;

                switch ($mimeType) {
                    case 'image/jpeg':
                        $image = @imagecreatefromjpeg($file->getPathname());
                        break;
                    case 'image/png':
                        $image = @imagecreatefrompng($file->getPathname());
                        break;
                    case 'image/webp':
                        $image = @imagecreatefromwebp($file->getPathname());
                        break;
                    default:
                        $content = @file_get_contents($file->getPathname());
                        if ($content) {
                            $image = @imagecreatefromstring($content);
                        }
                }

                if ($image !== false && $image !== null) {
                    // Compress and save as JPEG with 40% quality to reduce size to KB
                    imagejpeg($image, $destinationPath . '/' . $filename, 40);
                    imagedestroy($image);
                    
                    $data[$fieldName] = 'storage/grn_attachments/' . $filename;
                } else {
                    $file->move($destinationPath, $filename);
                    $data[$fieldName] = 'storage/grn_attachments/' . $filename;
                }
            } else {
                $file->move($destinationPath, $filename);
                $data[$fieldName] = 'storage/grn_attachments/' . $filename;
            }
        }
    }

}
