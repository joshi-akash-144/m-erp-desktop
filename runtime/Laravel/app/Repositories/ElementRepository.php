<?php

namespace App\Repositories;

use App\Models\Element;
use App\Models\DairyAnalysisItem;

class ElementRepository extends BaseRepository
{
    public function __construct(Element $element)
    {
        parent::__construct($element);
    }

    private function hasElementId(Element $element):bool
    {
        return $element->elementParameter()->exists();
    }
    private function isUsedInDairyAnalysis(Element $element): bool
    {           
        return DairyAnalysisItem::where('element_id', $element->id)->exists();
    }

    public function canDelete(Element $element): bool
    {        
        return $this->hasElementId($element) || $this->isUsedInDairyAnalysis($element);
    }
}
