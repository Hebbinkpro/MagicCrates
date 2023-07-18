<?php

namespace Hebbinkpro\MagicCrates\crate;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;

class CrateReward
{
    private string $name;
    private Item $item;
    private int $amount;
    /** @var string[] */
    private array $commands;

    /**
     * @param string $name
     * @param Item $item
     * @param int $amount
     * @param string[] $commands
     */
    public function __construct(string $name, Item $item, int $amount, array $commands)
    {
        $this->name = $name;
        $this->item = $item;
        $this->amount = $amount;
        $this->commands = $commands;
    }

    /**
     * @param array{name: string, item: array, commands: string[], amount: int} $reward
     * @return CrateReward|null
     */
    public static function decode(array $reward): ?CrateReward
    {
        $name = $reward["name"];
        $item = self::decodeItem($reward["item"]);
        if ($item === null) return null;

        $commands = $reward["commands"] ?? [];
        $amount = intval($reward["amount"]);

        // we cannot have less than 1 of a reward
        if ($amount < 1) return null;

        return new CrateReward($name, $item, $amount, $commands);
    }

    /**
     * @param array{name: string, id: string, amount: int, lore: string, enchantments: array{name: string, level: int}[]} $itemData
     * @return Item|null
     */
    public static function decodeItem(array $itemData): ?Item
    {
        $item = StringToItemParser::getInstance()->parse($itemData["id"]);

        $name = $itemData["name"] ?? null;
        if ($name !== null) $item->setCustomName($itemData["name"]);

        $amount = intval($itemData["amount"] ?? 1);
        $item->setCount($amount);

        $lore = $itemData["lore"] ?? null;
        if ($lore !== null) $item->setLore([$lore]);

        $enchData = $itemData["enchantments"] ?? [];
        foreach ($enchData as $ench) {
            $name = $ench["name"] ?? null;
            if ($name === null) continue;

            $level = intval($ench["level"] ?? 1);
            $item->addEnchantment(new EnchantmentInstance(StringToEnchantmentParser::getInstance()->parse($name), $level));
        }

        return $item;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Item
     */
    public function getItem(): Item
    {
        // clone the item, otherwise bad things can happen
        return clone $this->item;
    }

    /**
     * The amount of this reward inside the crate
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

}