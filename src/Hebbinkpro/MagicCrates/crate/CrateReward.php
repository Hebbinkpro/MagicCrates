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

namespace Hebbinkpro\MagicCrates\crate;

use Hebbinkpro\MagicCrates\utils\InventoryUtils;
use Hebbinkpro\MagicCrates\utils\ItemUtils;
use pocketmine\block\Air;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

class CrateReward
{
    protected string $id;
    protected string $name;
    protected int $amount;
    /** @var Item[] */
    protected array $items;
    /** @var string[] */
    protected array $commands;
    protected ?string $icon;

    /**
     * @param string $id the id of the reward
     * @param string $name the name of the reward
     * @param Item[] $items the items that have to be given to the player
     * @param string[] $commands the commands to be executed after rewarding the reward
     * @param string|null $icon the icon to be displayed
     */
    public function __construct(string $id, string $name, int $amount, array $items, array $commands, ?string $icon)
    {
        $this->id = $id;
        $this->name = $name;
        $this->items = $items;
        $this->amount = $amount;
        $this->commands = $commands;
        $this->icon = $icon;
    }

    /**
     * @param mixed $id
     * @param array{name: string, amount: int, item?: array, items?: array<array>, commands?: string[], icon?: string, player_max?: int, global_max?: int, replace?: boolean} $data
     * @param string $errorMsg message for when something went wrong while decoding
     * @param bool $parseDynamic
     * @return CrateReward|null
     */
    public static function parse(mixed $id, array $data, string &$errorMsg = "", bool $parseDynamic = true): ?CrateReward
    {
        if (!isset($data["name"])) {
            $errorMsg = "No name given.";
            return null;
        }

        $name = $data["name"];

        // create an id from the name if there is no ID given
        // TODO: Force servers to use dynamic ID's (>= v4.0.0)
        if (!is_string($id)) {
            $id = str_replace(" ", "_", strtolower($name));
        }

        $amount = self::parseInteger($data["amount"] ?? 0, 0);
        if ($amount === null) {
            $errorMsg = "amount is not a valid integer.";
            return null;
        }

        $encodedItems = $data["items"] ?? $data["item"] ?? [];

        if (!is_array($encodedItems)) {
            $errorMsg = "Could not decode 'item' or 'items': invalid value $encodedItems";
            return null;
        }

        if (isset($encodedItems["id"])) {
            $encodedItems = [$encodedItems];
        }

        // there is a list with items given
        $items = self::parseItems($encodedItems, $errorMsg);
        if ($items === null) {
            $errorMsg = "Could not decode 'items': $errorMsg";
            return null;
        }

        $commands = $data["commands"] ?? [];
        $icon = $data["icon"] ?? null;

        // construct the reward
        $reward = new CrateReward($id, $name, $amount, $items, $commands, $icon);

        // check if it is a dynamic reward
        if ($parseDynamic) {
            // check if we can construct a dynamic crate reward
            $dynamic = DynamicCrateReward::parseDynamic($reward, $data, $errorMsg);
            if ($dynamic !== null) return $dynamic;
            else if (strlen($errorMsg) > 0) return null; // something went wrong while parsing the dynamic reward
        }


        // amount = 0 is only valid for a DynamicReward
        if ($amount == 0) {
            $errorMsg = "Amount should be at least 1.";
            return null;
        }

        return $reward;
    }

    /**
     * Parse the given value to an integer
     * @param mixed $value the value to parse
     * @param int|null $minValue minimum allowed value
     * @param int|null $maxValue maximum allowed value
     * @return int|null the bounded value or null when the given value was not a valid integer
     */
    public static function parseInteger(mixed $value, int $minValue = null, int $maxValue = null): ?int
    {
        // check if it is not already an integer
        if (!is_int($value)) {
            // we cannot make an integer of this value
            if (!ctype_digit($value)) return null;
            $value = intval($value);
        }

        // no min or max value given
        if ($minValue === null && $maxValue === null) return $value;

        // if the min or max value is null, set them to the min/max allowed integers
        $minValue ??= PHP_INT_MIN;
        $maxValue ??= PHP_INT_MAX;

        // minmax the value
        return min(max($value, $minValue), $maxValue);
    }

    /**
     * @param array<array{id: string, name?: string, amount?: int, lore?: string|string[], enchantments?: array{name: string, level: int}[]}> $itemsData
     * @param string $errorMsg
     * @return ?array the list of all items, or null when something went wrong
     */
    public static function parseItems(array $itemsData, string &$errorMsg = ""): ?array
    {
        $items = [];
        foreach ($itemsData as $i => $itemData) {
            $item = ItemUtils::parseItem($itemData, $errorMsg);

            // we do not allow the user to continue with their actions before the user fixes their JSON
            if ($item === null) {
                $errorMsg = "Could not decode item $i: $errorMsg";
                return null;
            }

            $items[] = $item;

        }

        return $items;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get the icon of the reward
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon ?? $this->getDefaultIcon();
    }

    /**
     * Get the default icon of the reward based upon the first item or a command block when no items are given
     * @return string
     */
    public function getDefaultIcon(): string
    {
        // when there is no item defined, only commands will be executed, so ue command block texture
        $item = $this->items[0] ?? null;
        if ($item === null) return "textures/blocks/command_block";

        $block = $item->getBlock();
        if ($block instanceof Air) {
            $name = StringToItemParser::getInstance()->lookupAliases($item)[0];
            $icon = "textures/items/$name";
        } else {
            $name = StringToItemParser::getInstance()->lookupBlockAliases($block)[0];
            $icon = "textures/blocks/$name";
        }

        return $icon;
    }

    /**
     * The (maximum) amount of this reward inside the crate
     * @return int
     */
    public function getAmount(): int
    {

        return $this->amount;
    }

    /**
     * Construct a crate reward instance with the given amount
     * @param int $amount
     * @return CrateReward
     */
    public function setAmount(int $amount): CrateReward
    {
        return new CrateReward($this->id, $this->name, $amount, $this->items, $this->commands, $this->icon);
    }

    /**
     * Check if the player can receive the items from the reward
     * @param Player $player
     * @return bool
     */
    public function canPlayerReceive(Player $player): bool
    {
        if (sizeof($this->items) == 0) return true;
        // return if every item can be added to the player's inventory
        return InventoryUtils::canAddItems($player->getInventory(), $this->items);
    }
}