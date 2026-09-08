<?php

namespace App\Repositories;

use App\Models\Country;
use Illuminate\Support\Collection;

class CountryRepository
{
    protected Country $model;

    public function __construct(Country $country)
    {
        $this->model = $country;
    }

     /**
     * Get all Country.
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->select($columns)->get();
    }

}