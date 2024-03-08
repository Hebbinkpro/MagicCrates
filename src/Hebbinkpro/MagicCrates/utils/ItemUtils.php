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

use customiesdevs\customies\item\CustomiesItemFactory;
use Exception;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

class ItemUtils
{
    /**
     * The network ID of the Command Block
     */
    public const COMMAND_BLOCK_NETWORK_ID = 137;

    /**
     * Get an item from the given identifier.
     *
     * If the item is registered by Customies, this item will be used.
     * @param string $identifier
     * @return Item|null
     */
    public static function getItemFromId(string $identifier): ?Item
    {
        $item = StringToItemParser::getInstance()->parse($identifier);

        // the item is not registered by pmmp, but customies is enabled
        if ($item === null && class_exists(CustomiesItemFactory::class)) {
            // try to get the item from customies
            try {
                $item = CustomiesItemFactory::getInstance()->get($identifier);
            } catch (Exception) {
                // if the item cannot be found by customies, it throws an error...
                $item = null;
            }
        }

        return $item;
    }

    /**
     * Create a fake item stack with a given network ID which the client can recognize.
     * @param int $networkId
     * @param int $count
     * @return ItemStack
     */
    public static function getFakeItemStack(int $networkId, int $count = 1): ItemStack
    {
        return new ItemStack(
            $networkId,
            0,
            $count,
            ItemTranslator::NO_BLOCK_RUNTIME_ID,
            new CompoundTag()
        );
    }
}
