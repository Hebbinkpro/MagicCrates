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
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
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

    /**
     * @param array{id: string, name?: string, amount?: int, lore?: string|string[], enchantments?: array{name: string, level: int}[]} $itemData
     * @param string $errorMsg
     * @return Item|null
     */
    public static function parseItem(array $itemData, string &$errorMsg = ""): ?Item
    {
        if (!isset($itemData["id"])) {
            $errorMsg = "No item id given.";
            return null;
        }

        $item = self::getItemFromId($itemData["id"]);
        if ($item === null) {
            $errorMsg = "Invalid item id given: " . $itemData["id"];
            return null;
        }

        if (isset($itemData["name"])) $item->setCustomName(strval($itemData["name"]));

        $amount = intval($itemData["amount"] ?? 1);
        $item->setCount($amount);

        if (isset($itemData["lore"])) {
            if (is_array($itemData["lore"])) $lore = $itemData["lore"];
            else $lore = [strval($itemData["lore"])];
            $item->setLore($lore);
        }

        if (isset($itemData["enchantments"]) && is_array($itemData["enchantments"])) {
            $enchantments = $itemData["enchantments"];

            if (isset($enchantments["name"])) {
                // there is only a single enchantment given
                $enchantment = self::parseEnchantment($enchantments);
                if ($enchantment === null) {
                    $errorMsg = "Invalid enchantment: " . $enchantments["name"];
                    return null;
                }

                $item->addEnchantment($enchantment);
            } else {
                // there are multiple enchantments given
                foreach ($enchantments as $i => $enchData) {
                    $enchantment = self::parseEnchantment($enchData);
                    if ($enchantment === null) {
                        $errorMsg = "Invalid enchantment: " . $enchData["name"] ?? $i;
                        return null;
                    }
                    $item->addEnchantment($enchantment);
                }
            }
        }

        return $item;
    }

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
     * Parse an enchantment instance from the given data
     * @param array $data
     * @return EnchantmentInstance|null
     */
    public static function parseEnchantment(array $data): ?EnchantmentInstance
    {
        if (!isset($data["name"])) return null;

        $enchantment = StringToEnchantmentParser::getInstance()->parse($data["name"]);
        if ($enchantment === null) return null;

        $level = intval($data["level"] ?? 1);
        return new EnchantmentInstance($enchantment, $level);

    }
}
