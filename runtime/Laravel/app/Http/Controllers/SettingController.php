<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function store(Request $request, string $module, string $key): JsonResponse
    {
        $companyId = company_id();
        $setting = Setting::updateOrCreate(
            [
                'company_id' => $companyId,
                'module' => $module,
                'key' => $key,
            ],
            [
                'value' => $request->value,
            ]
        );

        return response()->json([
            'status'  => true,
            'message' => 'Setting saved successfully',
            'data' => $setting,
        ]);
    }

    public function show(string $module, string $key): JsonResponse
    {
        $companyId = company_id();
        $setting = Setting::where([
            'company_id' => $companyId,
            'module' => $module,
            'key' => $key,
        ])->first();

        return response()->json([
            'status' => true,
            'data'   => $setting,
        ]);
    }
}
