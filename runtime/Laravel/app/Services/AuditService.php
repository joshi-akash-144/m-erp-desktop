<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\AuditTrailDetail;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an audit trail.
     *
     * @param array $data
     * @return AuditTrail
     */
    public function log(array $data)
    {
        if (!\isAuditLog()) {
            return null;
        }

        $userId = $data['user_id'] ?? current_user_id();
        $userName = $data['user_name'] ?? (current_user()?->name ?? 'System');

        $latestVersion = AuditTrail::where('model_name', $data['model_name'])
                                   ->where('voucher_id', $data['voucher_id'])
                                   ->where('source_id', $data['source_id'])
                                   ->max('version') ?? 0;

        $methodMap = [
            'GET'    => AuditTrail::HTTP_METHOD_GET,
            'POST'   => AuditTrail::HTTP_METHOD_POST,
            'PUT'    => AuditTrail::HTTP_METHOD_PUT,
            'PATCH'  => AuditTrail::HTTP_METHOD_PUT,
            'DELETE' => AuditTrail::HTTP_METHOD_DELETE,
        ];
        $incomingMethod = strtoupper($data['http_method'] ?? Request::method());
        $httpMethod = $methodMap[$incomingMethod] ?? AuditTrail::HTTP_METHOD_GET;

        $auditId = AuditTrail::insertGetId([
            'company_id'        => $data['company_id'],
            'financial_year_id' => $data['financial_year_id'],
            'action'            => $data['action'] ?? 'create',
            'module'            => $data['module'],
            'record_type'       => $data['record_type'] ?? AuditTrail::RECORD_TYPE_VOUCHER,
            'model_name'        => $data['model_name'],
            'source_id'         => $data['source_id'],
            'voucher_id'        => $data['voucher_id'] ?? null,
            'reference_number'  => $data['reference_number'] ?? null,
            'version'           => $latestVersion + 1,
            'user_id'           => $userId ?: 0,
            'user_name'         => $userName,
            'ip_address'        => $data['ip_address'] ?? (request()->header('CF-Connecting-IP') ?? request()->header('True-Client-IP') ?? request()->header('X-Real-IP') ?? trim(explode(',', request()->header('X-Forwarded-For', ''))[0]) ?: request()->ip()),
            'user_agent'        => $data['user_agent'] ?? (Request::userAgent() ?? ''),
            'request_url'       => $data['request_url'] ?? (Request::fullUrl() ?? ''),
            'http_method'       => $httpMethod,
            'org_amount'        => $data['org_amount'] ?? null,
            'final_amount'      => $data['final_amount'] ?? null,
            // 'description'       => $data['description'] ?? null,
            'status'            => $data['status'] ?? AuditTrail::STATUS_SUCCESS,
            'performed_at'      => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $oldValues = $data['old_values'] ?? [];
        $newValues = $data['new_values'] ?? [];
        $action = $data['action'] ?? 'create';

        $oldDot = \Illuminate\Support\Arr::dot($oldValues);
        $newDot = \Illuminate\Support\Arr::dot($newValues);

        $allKeys = array_unique(array_merge(array_keys($oldDot), array_keys($newDot)));
        $detailsToInsert = [];

        foreach ($allKeys as $key) {
            $oldVal = $oldDot[$key] ?? null;
            $newVal = $newDot[$key] ?? null;

            // Handle numeric comparison gracefully
            $isDifferent = false;
            
            if (is_numeric($oldVal) && is_numeric($newVal)) {
                // If both are numeric, compare as floats to ignore formatting differences like '19500.00' vs '19500'
                if ((float)$oldVal !== (float)$newVal) {
                    $isDifferent = true;
                }
            } elseif ($oldVal != $newVal) {
                // Use loose comparison for other types, so null and '' are treated as equal
                $isDifferent = true;
            }

            if ($isDifferent) {
                // Determine section and label
                $section = AuditTrailDetail::SECTION_MASTER;
                if (str_starts_with($key, 'details.')) {
                    // Journal / receipt / payment account-level transaction lines
                    $section = AuditTrailDetail::SECTION_VOUCHER_DETAILS;
                } elseif (str_starts_with($key, 'items.')) {
                    // Purchase / sale invoice product line items
                    $section = AuditTrailDetail::SECTION_ITEM_ENTRIES;
                } elseif (str_starts_with($key, 'invoice_entries.')) {
                    // Voucher-level invoice entries
                    $section = AuditTrailDetail::SECTION_INVOICE_ENTRIES;
                } elseif (str_starts_with($key, 'bill_sundries.')) {
                    $section = AuditTrailDetail::SECTION_BILL_SUNDRIES;
                }

                $keyParts = explode('.', $key);
                $lastPart = end($keyParts);
                $fieldLabel = ucwords(str_replace('_', ' ', $lastPart));

                // Make specific internal ID fields invisible by default
                $visible = true;
                if (in_array($lastPart, [
                    'account_id', 'id', 'voucher_id', 'company_id', 'financial_year_id',
                    'source_id', 'vehicle_id', 'consignor_id', 'consignee_id',
                    'from_destination_id', 'to_destination_id', 'item_id', 'zone_id',
                    'purchase_type_id', 'sale_type_id'
                ])) {
                    $visible = false;
                }

                $detailsToInsert[] = [
                    'audit_trail_id' => $auditId,
                    'section'        => $section,
                    'field_name'     => $key,
                    'key_name'       => $fieldLabel,
                    'old_value'      => $oldVal,
                    'new_value'      => $newVal,
                    'visible'        => $visible,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }

        if (!empty($detailsToInsert)) {
            AuditTrailDetail::insert($detailsToInsert);
        }

        return $auditId;
    }
}
