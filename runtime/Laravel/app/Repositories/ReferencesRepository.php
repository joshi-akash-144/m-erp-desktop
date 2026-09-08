<?php

namespace App\Repositories;

use App\Models\Reference;


class ReferencesRepository extends BaseRepository
{
    public function __construct(Reference $reference)
    {
        parent::__construct($reference);
    }

    public function getRefBySourceTypeAndSourceId($voucherId)
    {
        return $this->model->where('voucher_id', $voucherId)->first();
    }

    public function getPendingReferenceByAccountId($accountId)
    {
        return $this->model->where('account_id', $accountId)->where('is_closed', false)->get();
    }
}
