<?php

namespace App\Http\Controllers;

use App\Models\CompanyGstCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstCredentialController extends Controller
{
    public function index()
    {
        abort_unless(has_permission('gst_credential.list'), 403);

        $companyId = company_id();

        $ewayBill = CompanyGstCredential::where('company_id', $companyId)
            ->where('type', 'eway_bill')
            ->first();

        $eInvoice = CompanyGstCredential::where('company_id', $companyId)
            ->where('type', 'e_invoice')
            ->first();

        return view('company.pages.setup.gst-credentials.index', compact('ewayBill', 'eInvoice'));
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless(has_permission('gst_credential.update'), 403);

        $validated = $request->validate([
            'type'                  => 'required|in:eway_bill,e_invoice',
            'sandbox_client_id'     => 'nullable|string|max:255',
            'sandbox_base_url'      => 'nullable|url|max:500',
            'sandbox_secret_id'     => 'nullable|string|max:255',
            'sandbox_gstin'         => 'nullable|string|max:20',
            'sandbox_email'         => 'nullable|email|max:255',
            'sandbox_username'      => 'nullable|string|max:255',
            'sandbox_password'      => 'nullable|string',
            'production_client_id'  => 'nullable|string|max:255',
            'production_base_url'   => 'nullable|url|max:500',
            'production_secret_id'  => 'nullable|string|max:255',
            'production_gstin'      => 'nullable|string|max:20',
            'production_email'      => 'nullable|email|max:255',
            'production_username'   => 'nullable|string|max:255',
            'production_password'   => 'nullable|string',
        ]);

        $companyId = company_id();

        CompanyGstCredential::updateOrCreate(
            ['company_id' => $companyId, 'type' => $validated['type']],
            array_merge($validated, [
                'company_id' => $companyId,
                'updated_by' => current_user_id(),
            ])
        );

        return response()->json([
            'status'  => true,
            'message' => 'Credentials updated successfully.',
        ]);
    }
}
