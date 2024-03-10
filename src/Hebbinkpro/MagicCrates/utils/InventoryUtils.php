<?php
/*
 *   __  __             _       _____           _
 *  |  \/  |           (_)     / ____|         | |
 *  | \  / | __ _  __ _ _  ___| |     _ __ __ _| |_ ___  ___
 *  | |\/| |/ _` |/ _` | |/ __| |    | '__/ _` | __/ _ \/ __|
 *  | |  | | (_| | (_| | | (__| |____| | | (_| | ||  __/\__ \
 *  |_|  |_|\__,_|\__, |_|\___|\_____|_|  \__,_|\__\___||___/
 *                 __/ |
 *                |___/
 *
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\MagicCrates\utils;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class InventoryUtils
{
    /**
     * Get if all the given items can be added to the inventory
     * @param Inventory $inventory
     * @param Item[] $items
     * @return bool
     */
    public static function canAddItems(Inventory $inventory, array $items): bool
    {
        // clone the inventory
        $testInventory = clone $inventory;

        // add the items to the cloned inventory
        $leftOver = $testInventory->addItem(...$items);

        return sizeof($leftOver) == 0;
    }

    /**
     * Get the index of all the empty slots in the inventory
     * @param Inventory $inventory
     * @return int[]
     */
    public static function getEmptySlots(Inventory $inventory): array
    {
        return array_diff_key($inventory->getContents(true), $inventory->getContents());
    }
}