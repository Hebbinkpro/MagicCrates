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

use pocketmine\item\Item;

class DynamicCrateReward extends CrateReward
{

    private int $maxPlayerAmount;
    private int $maxGlobalAmount;
    private bool $canReplace;
    private int $replaceAmount;

    /**
     * @param string $name the name of the reward
     * @param Item[] $items the items that has to be given to the player
     * @param string[] $commands the commands to be executed after rewarding the reward
     * @param string|null $icon the icon to be displayed
     * @param int $amount the amount of the reward inside the crate
     * @param int $maxPlayerAmount the amount of times this reward can be rewarded to the same player
     * @param int $maxGlobalAmount the amount of times this reward can be rewarded to every player in the server
     */
    public function __construct(string $id, string $name, int $amount, array $items, array $commands, ?string $icon, int $maxPlayerAmount, int $maxGlobalAmount, bool $canReplace, int $replaceAmount)
    {
        parent::__construct($id, $name, $amount, $items, $commands, $icon);

        $this->maxPlayerAmount = $maxPlayerAmount;
        $this->maxGlobalAmount = $maxGlobalAmount;
        $this->canReplace = $canReplace;
        $this->replaceAmount = $replaceAmount;
    }

    /**
     * Parse a dynamic crate reward from a default crate reward and data
     * @param CrateReward $reward
     * @param array $data
     * @param string $errorMsg
     * @return DynamicCrateReward|null
     */
    public static function parseDynamic(CrateReward $reward, array $data, string &$errorMsg = ""): ?DynamicCrateReward
    {
        // it is not a dynamic reward
        if (!isset($data["player_max"]) && !isset($data["global_max"])) return null;

        $maxPlayerAmount = self::parseInteger($data["player_max"] ?? 0, 0);
        if ($maxPlayerAmount === null) {
            $errorMsg = "player_max is not a valid integer.";
            return null;
        }

        $maxGlobalAmount = self::parseInteger($data["global_max"] ?? 0, 0);
        if ($maxGlobalAmount === null) {
            $errorMsg = "global_max is not a valid integer.";
            return null;
        }

        // invalid dynamic crate reward
        if ($maxPlayerAmount <= 0 && $maxGlobalAmount <= 0) {
            $errorMsg = "player_max and global_max are equal to 0, but at least one should be greater than 0.";
            return null;
        }


        $canReplace = boolval($data["replace"] ?? true);
        $replaceAmount = self::parseInteger($data["replace_amount"] ?? 0, 0);
        if ($replaceAmount === null) {
            $errorMsg = "replace_amount is not a valid integer.";
            return null;
        }

        return self::fromCrateReward($reward, $maxPlayerAmount, $maxGlobalAmount, $canReplace, $replaceAmount);
    }

    /**
     * Construct a CrateReward from a BasicCrateReward
     * @param CrateReward $reward
     * @param int $maxPlayerAmount
     * @param int $maxGlobalAmount
     * @param bool $canReplace
     * @param int $replacementAmount
     * @return DynamicCrateReward
     */
    public static function fromCrateReward(CrateReward $reward, int $maxPlayerAmount, int $maxGlobalAmount, bool $canReplace, int $replacementAmount): DynamicCrateReward
    {
        return new DynamicCrateReward($reward->getId(), $reward->getName(), $reward->getAmount(), $reward->getItems(), $reward->getCommands(), $reward->getIcon(), $maxPlayerAmount, $maxGlobalAmount, $canReplace, $replacementAmount);
    }

    /**
     * The amount of times the player can get the reward
     * @param int $totalAmount
     * @param int $playerAmount
     * @return int
     */
    public function getPlayerAmount(int $totalAmount, int $playerAmount): int
    {
        // reward is not available for the player
        if (!$this->canHaveReward($totalAmount, $playerAmount)) return 0;
        // the default amount is set
        if ($this->amount > 0) return $this->amount;

        // calculate the amount of times the player can get the reward
        return max(0, $this->getPlayerMaxAmount() - $playerAmount);
    }

    /**
     * Check if this reward can be given to the player
     * @param int $totalAmount
     * @param int $playerAmount
     * @return bool if the player can receive this reward
     */
    public function canHaveReward(int $totalAmount, int $playerAmount): bool
    {
        if ($this->maxGlobalAmount > 0 && $totalAmount >= $this->maxGlobalAmount) return false;
        if ($this->maxPlayerAmount > 0 && $playerAmount >= $this->maxPlayerAmount) return false;
        return true;
    }

    /**
     * Returns the max amount of the reward available in the crate based upon default amount, player max amount and max global amount
     * @return int
     */
    public function getPlayerMaxAmount(): int
    {
        // max player amount is set
        if ($this->maxPlayerAmount > 0 && $this->maxPlayerAmount) {
            // if global amount is set and smaller than the player amount, return the global amount
            if ($this->maxGlobalAmount > 0 && $this->maxGlobalAmount < $this->maxPlayerAmount) return $this->maxGlobalAmount;
            return $this->maxPlayerAmount;
        }

        // this will be > 0 by definition of the dynamic crate reward
        return $this->maxGlobalAmount;
    }

    /**
     * The amount of the replacement reward the player is able to get
     * @param int $totalUses
     * @param int $playerAmount
     * @return int the amount of times the player is able to get this reward
     */
    public function getPlayerReplaceAmount(int $totalUses, int $playerAmount): int
    {
        if (!$this->canReplace) return 0;

        // player cannot have the reward, so maximum replacement
        if (!$this->canHaveReward($totalUses, $playerAmount)) {
            if ($this->replaceAmount > 0) return $this->replaceAmount;
            if ($this->amount > 0) return $this->amount;
            return $this->getPlayerMaxAmount();
        }

        // default amount is set, so ignore any differences
        if ($this->amount > 0) return 0;

        // replace amount is set, calculate the correct replacement amount
        if ($this->replaceAmount > 0) {
            $max = $this->getPlayerMaxAmount();
            return intdiv($playerAmount, max(1, $max)) * $this->replaceAmount;
        }

        // use the player rewarded amount
        return $playerAmount;
    }

    /**
     * Check if it is allowed to replace this reward when limits are reached
     * @return bool
     */
    public function isReplaceable(): bool
    {
        return $this->canReplace;
    }

}