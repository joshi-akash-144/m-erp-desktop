<?php

namespace App\Repositories;

use App\Models\DairyParameter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\DairyParameterDetail;

class DairyParameterRepository extends BaseRepository
{
    public function __construct(DairyParameter $dairyParameter)
    {
        parent::__construct($dairyParameter);
    }

    /**
     * Create a new dairy parameter with details.
     */
    public function create(array $data): DairyParameter
    {
        return DB::transaction(function () use ($data) {
            // Generate UUID if not provided
            if (!isset($data['uuid'])) {
                $data['uuid'] = Str::uuid()->toString();
            }

            // Create the main dairy parameter
            $dairyParameter = $this->model->create(
                Arr::only($data, [
                    'uuid',
                    'company_id',
                    'condition_id',
                    'element_id',
                    'guarantee',
                    'is_system',
                    'status',
                    'created_by'
                ])
            );

            // Create parameter details if provided
            if (isset($data['parameter_details']) && is_array($data['parameter_details'])) {
                foreach ($data['parameter_details'] as $detail) {
                    // Ensure we have the parameter_id
                    $detailData = array_merge(
                        ['parameter_id' => $dairyParameter->id],
                        Arr::only($detail, [
                            'from',
                            'to',
                            'difference',
                            'rebate',
                            'premium'
                        ])
                    );
                    
                    // Direct insert using the ParameterDetail model or DB
                    DairyParameterDetail::insert($detailData);
                }
            }

            return $dairyParameter->fresh()->load('parameterDetails');
        });
    }

    /**
     * Update a dairy parameter and its details.
     */
    public function update(Model $model, array $data): Model
    {
        /** @var DairyParameter $dairyParameter */
        $dairyParameter = $model;

        return DB::transaction(function () use ($dairyParameter, $data) {
            // Update main dairy parameter
            $dairyParameter->update(
                Arr::only($data, [
                    'condition_id',
                    'element_id',
                    'guarantee',
                    'is_system',
                    'status',
                    'updated_by'
                ])
            );

            // Handle parameter details update
            if (isset($data['parameter_details']) && is_array($data['parameter_details'])) {
                // Get existing detail IDs
                $existingDetailIds = DairyParameterDetail::where('parameter_id', $dairyParameter->id)
                    ->whereNull('deleted_at')
                    ->pluck('id')
                    ->toArray();
                    
                $processedDetailIds = [];

                foreach ($data['parameter_details'] as $detail) {
                    if (isset($detail['id']) && in_array($detail['id'], $existingDetailIds)) {
                        // Update existing detail
                        DairyParameterDetail::where('id', $detail['id'])
                            ->where('parameter_id', $dairyParameter->id)
                            ->update(array_merge(
                                Arr::only($detail, [
                                    'from',
                                    'to',
                                    'difference',
                                    'rebate',
                                    'premium'
                                ]),
                                ['updated_at' => now()]
                            ));
                        $processedDetailIds[] = $detail['id'];
                    } else {
                        // Create new detail
                        $detailData = array_merge(
                            ['parameter_id' => $dairyParameter->id],
                            Arr::only($detail, [
                                'from',
                                'to',
                                'difference',
                                'rebate',
                                'premium'
                            ]),
                            [
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                        
                        DairyParameterDetail::insert($detailData);
                    }
                }

                // Soft delete details that were not in the update data
                $detailsToDelete = array_diff($existingDetailIds, $processedDetailIds);
                if (!empty($detailsToDelete)) {
                    DairyParameterDetail::whereIn('id', $detailsToDelete)
                        ->where('parameter_id', $dairyParameter->id)
                        ->update([
                            'deleted_at' => now()
                        ]);
                }
            }

            return $dairyParameter->fresh(['details']);
        });
    }

    /**
     * Soft delete a dairy parameter and all its related data.
     */
    public function delete(Model $model): bool
    {
        /** @var DairyParameter $dairyParameter */
        $dairyParameter = $model;

        return DB::transaction(function () use ($dairyParameter) {
            // First delete all parameter details
            $dairyParameter->parameterDetails()->delete();

            // Then delete the main dairy parameter record
            return $dairyParameter->delete();
        });
    }

    /**
     * Check if dairy parameter has any parameter details.
     */
    public function hasParameterDetails(DairyParameter $dairyParameter): bool
    {
        return $dairyParameter->parameterDetails()->exists();
    }

    /**
     * Check if a specific parameter detail can be deleted.
     */
    public function canDeleteParameterDetail(DairyParameter $dairyParameter, int $detailId): bool
    {
        // Check if this is the last detail
        $detailCount = $dairyParameter->parameterDetails()->count();
        
        // You might want to enforce at least one detail remains
        // Adjust this logic based on your business requirements
        if ($detailCount <= 1) {
            return false;
        }

        // Check if the detail exists and belongs to this parameter
        return $dairyParameter->parameterDetails()
            ->where('id', $detailId)
            ->exists();
    }

    /**
     * Check if dairy parameter exists for specific condition and element.
     */
    public function existsForConditionAndElement(int $companyId, int $conditionId, int $elementId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('company_id', $companyId)
            ->where('condition_id', $conditionId)
            ->where('element_id', $elementId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get the count of parameter details for a dairy parameter.
     */
    public function getParameterDetailsCount(DairyParameter $dairyParameter): int
    {
        return $dairyParameter->parameterDetails()->count();
    }
}