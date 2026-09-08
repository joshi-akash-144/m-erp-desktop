<?php

namespace App\Repositories;

use App\Models\Condition;

class ConditionRepository extends BaseRepository
{
    public function __construct(Condition $condition)
    {
        parent::__construct($condition);
    }

    private function hasConditionId(Condition $condition):bool
    {
        return $condition->conditionParameter()->exists();
    }

    public function canDelete(Condition $condition): bool
    {
        return $this->hasConditionId($condition);
    }
}
