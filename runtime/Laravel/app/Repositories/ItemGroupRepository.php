<?php

namespace App\Repositories;

use App\Models\ItemGroup;

class ItemGroupRepository extends BaseRepository
{
    public function __construct(ItemGroup $itemGroup)
    {
        parent::__construct($itemGroup);
    }
    /**
     * Determine if the given item group is linked to any items.
     *
     * This is used to prevent deletion of item group that are
     * already in use by one or more accounts.
     *
     * @param  ItemGroup  $itemGroup
     * @return bool  True if at least one account exists, false otherwise.
     */
    public function hasItems(ItemGroup $itemGroup): bool
    {
        return $itemGroup->items()->exists();
    }
}
