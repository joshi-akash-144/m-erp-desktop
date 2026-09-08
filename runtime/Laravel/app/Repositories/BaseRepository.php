<?php

namespace App\Repositories;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get a new query builder for the model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->model->newQuery();
    }



    /**
     * Get all records with optional filters, relations, scopes, and ordering.
     *
     * @param array $columns Columns to select
     * @param array $with Relationships to eager load
     * @param array $filters Filters as field => value
     * @param array $scopes Array of closures that receive the query
     * @param string|null $orderBy Column to order by
     * @param string $direction Order direction
     * @return \Illuminate\Support\Collection
     */
    public function all(
        array $columns = ['*'],
        array $with = [],
        array $filters = [],
        array $scopes = [],
        ?string $orderBy = 'id',
        string $direction = 'asc'
    ): Collection {
        $query = $this->model->select($columns)->with($with);

        // Apply filters
        if ($filters) {
            foreach ($filters as $field => $value) {
                is_array($value)
                    ? $query->whereIn($field, $value)
                    : $query->where($field, $value);
            }
        }

        // Apply scopes
        foreach ($scopes as $scope) {
            if ($scope instanceof \Closure) {
                $query = $scope($query) ?? $query;
            }
        }

        // Apply ordering
        if ($orderBy) {
            $query->orderBy($orderBy, $direction);
        }

        return $query->get();
    }


    /**
     * Find by primary key.
     */
    public function find(int $id, array $with = [], array $filter = [], array $columns = ['*']): ?Model
    {
        $query = $this->model->with($with)->select($columns);

        // Apply filters dynamically
        foreach ($filter as $key => $value) {
            $query->where($key, $value);
        }

        // Find by ID with filters
        return $query->find($id);
    }


    /**
     * Find by UUID (including soft-deleted).
     */
    public function findByUuid(
        string $uuid,
        array $with = [],
        array $filter = [],
        array $columns = ['*']
    ): ?Model {
        $query = $this->model->with($with)->select($columns);

        // Apply filters dynamically
        foreach ($filter as $key => $value) {
            $query->where($key, $value);
        }

        // Filter by UUID
        return $query->where('uuid', $uuid)->first();
    }


    /**
     * Create new record.
     */
    public function create(array $data): Model
    {
        return DB::transaction(fn() => $this->model->create($data));
    }

    /**
     * Update record.
     */
    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $model->update($data);
            return $model->fresh();
        });
    }

    /**
     * Soft delete a model instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function delete(Model $model): bool
    {
        return DB::transaction(function () use ($model) {
            return $model->delete();
        });
    }
    /**
     * Force delete record.
     */
    public function forceDelete(Model $model): bool
    {
        return $model->forceDelete();
    }

    /**
     * Restore soft-deleted record by UUID.
     */
    public function restoreByUuid(string $uuid): void
    {
        $model = $this->findByUuid($uuid);

        if (!$model) {
            throw new \Exception(__('messages.not_found'));
        }

        if ($model->isFillable('deleted_by')) {
            $model->update(['deleted_by' => null]);
        }

        $model->restore();
    }
}
