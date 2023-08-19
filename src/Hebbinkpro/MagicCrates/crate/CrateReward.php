<?php

namespace Hebbinkpro\MagicCrates\crate;

use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\block\Air;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\Server;

class CrateReward
{
    private string $name;
    private ?Item $item;
    private int $amount;
    /** @var string[] */
    private array $commands;
    private ?string $icon;

    /**
     * @param string $name
     * @param Item|null $item
     * @param int $amount
     * @param string[] $commands
     * @param string|null $icon
     */
    public function __construct(string $name, ?Item $item, int $amount, array $commands, ?string $icon)
    {
        $this->name = $name;
        $this->item = $item;
        $this->amount = $amount;
        $this->commands = $commands;
        $this->icon = $icon;
    }

    /**
     * @param array{name: string, item?: array, commands?: string[], amount?: int} $reward
     * @param string $errorMsg message for when something went wrong while decoding
     * @return CrateReward|null
     */
    public static function decode(array $reward, string &$errorMsg = ""): ?CrateReward
    {
        if (!isset($reward["name"])) {
            $errorMsg = "No name given.";
            return null;
        }

        $name = $reward["name"];

        $item = null;
        if (isset($reward["item"])) {
            $item = self::decodeItem($reward["item"] ?? [], $errorMsg);
            if ($item === null) {
                $errorMsg = "Could not decode item: $errorMsg";
                return null;
            }
        }

        $commands = $reward["commands"] ?? [];
        $amount = intval($reward["amount"] ?? 1);

        $icon = $reward["icon"] ?? null;

        // we cannot have less than 1 of a reward
        if ($amount < 1) {
            $errorMsg = "Amount is less then 1.";
            return null;
        }

        return new CrateReward($name, $item, $amount, $commands, $icon);
    }

    /**
     * @param array{id: string, name?: string, amount?: int, lore?: string|string[], enchantments?: array{name: string, level: int}[]} $itemData
     * @return Item|null
     */
    public static function decodeItem(array $itemData, string &$errorMsg = ""): ?Item
    {
        if (!isset($itemData["id"])) {
            $errorMsg = "No item id given.";
            return null;
        }

        $item = StringToItemParser::getInstance()->parse($itemData["id"]);

        // its not a vanilla item
        if ($item === null) {
            // check if Customies is enabled
            $plManager = Server::getInstance()->getPluginManager();
            $customies = $plManager->getPlugin("Customies");
            if ($customies !== null && $plManager->isPluginEnabled($customies)) {
                // get the Customies item, and catch the error...
                try {
                    $item = CustomiesItemFactory::getInstance()->get($itemData["id"]);
                } catch (\Exception $e) {
                    $item = null;
                }
            }

            //  Customies does not have the item or customies is not available
            if ($item === null) {
                $errorMsg = "Invalid item id given: " . $itemData["id"];
                return null;
            }
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
            $enchData = $itemData["enchantments"];
            foreach ($enchData as $ench) {
                if (!isset($ench["name"])) continue;

                $level = intval($ench["level"] ?? 1);
                $item->addEnchantment(new EnchantmentInstance(StringToEnchantmentParser::getInstance()->parse($ench["name"]), $level));
            }
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
     * @return Item|null
     */
    public function getItem(): ?Item
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

    /**
     * Get the icon of the reward
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * Get the icon of the item
     * @return string
     */
    public function getItemIcon(): string
    {
        // only commands will be executed, set the icon to a command block
        if ($this->item === null && count($this->commands) > 0) return "textures/blocks/command_block";

        $block = $this->item->getBlock();
        if ($block instanceof Air) {
            $name = StringToItemParser::getInstance()->lookupAliases($this->item)[0];
            $icon = "textures/items/$name";
        } else {
            $name = StringToItemParser::getInstance()->lookupBlockAliases($block)[0];
            $icon = "textures/blocks/$name";
        }

        return $icon;
    }
}