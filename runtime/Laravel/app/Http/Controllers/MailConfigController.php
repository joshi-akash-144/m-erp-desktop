<?php

namespace App\Http\Controllers;

use App\Models\CompanyMailConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MailConfigController extends Controller
{
    public function index()
    {
        abort_unless(has_permission('mail_config.list'), 403);

        $config = CompanyMailConfig::where('company_id', company_id())->first();

        return view('company.pages.setup.mail-config.index', compact('config'));
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless(has_permission('mail_config.update'), 403);

        $validated = $request->validate([
            'host'         => 'required|string|max:255',
            'port'         => 'required|integer|min:1|max:65535',
            'encryption'   => 'required|in:tls,ssl,none',
            'username'     => 'required|string|max:255',
            'password'     => 'nullable|string',
            'from_address' => 'required|email|max:255',
            'from_name'    => 'required|string|max:255',
            'is_active'    => 'boolean',
        ]);

        $companyId = company_id();

        $existing = CompanyMailConfig::where('company_id', $companyId)->first();

        $data = array_merge($validated, [
            'company_id' => $companyId,
            'is_active'  => $request->boolean('is_active'),
            'updated_by' => current_user_id(),
        ]);

        // Keep existing password if not provided
        if (empty($validated['password']) && $existing) {
            unset($data['password']);
        }

        CompanyMailConfig::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );

        return response()->json([
            'status'  => true,
            'message' => 'Mail configuration saved successfully.',
        ]);
    }
}
