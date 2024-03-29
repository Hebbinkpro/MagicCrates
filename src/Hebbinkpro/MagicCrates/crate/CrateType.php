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

use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\utils\CrateCommandSender;
use Hebbinkpro\MagicCrates\utils\StringUtils;
use pocketmine\item\Item;
use pocketmine\player\Player;

class CrateType
{
    public const KEY_NBT_TAG = "magic-crates-key";

    /** @var CrateType[] */
    private static array $crateTypes = [];

    private string $id;
    private string $name;
    /** @var array<string, CrateReward> */
    private array $rewards;
    /** @var array<string, CrateReward> List of replacement rewards mapped by their corresponding reward name */
    private array $replacementRewards;

    /** @var string[] */
    private array $commands;

    /**
     * @param string $id
     * @param string $name
     * @param array<string, CrateReward> $rewards
     * @param string[] $commands
     * @param array $replacementRewards
     */
    public function __construct(string $id, string $name, array $rewards, array $commands, array $replacementRewards)
    {
        $this->id = $id;
        $this->name = $name;
        $this->rewards = $rewards;
        $this->commands = $commands;
        $this->replacementRewards = $replacementRewards;

        self::$crateTypes[$id] = $this;
    }


    /**
     * @param string $id
     * @param array{name: string, rewards: array, commands: string[], replacement?: array} $data
     * @param string $errorMsg
     * @return CrateType|null
     */
    public static function parse(string $id, array $data, string &$errorMsg = ""): ?CrateType
    {
        $typeName = $data["name"] ?? "§6$id §cCrate§r";

        $rewardReplacements = [];
        $rewards = [];
        foreach ($data["rewards"] ?? [] as $i => $rewardData) {

            $reward = CrateReward::parse($i, $rewardData, $errorMsg);

            // reward is not valid, that is not valid
            if ($reward === null) {
                $errorMsg = "Could not parse reward $i: $errorMsg";
                return null;
            }

            $rewards[$reward->getId()] = $reward;

            if ($reward instanceof DynamicCrateReward) {
                if (isset($rewardData["replacement"])) {
                    if (is_string($rewardData["replacement"])) $rewardReplacements[$reward->getId()] = $rewardData["replacement"];
                    else {
                        $rewardReplacement = CrateReward::parse($i, $rewardData["replacement"], $errorMsg, false);
                        if ($rewardReplacement === null) {
                            $errorMsg = "Could not parse the replacement reward for reward {$reward->getId()} in type $id: $errorMsg";
                            return null;
                        }
                        $rewardReplacement[$reward->getId()] = $rewardReplacement;
                    }
                }
            }
        }

        $commands = $data["commands"] ?? [];

        // we cannot have a type without rewards
        if (count($rewards) == 0 && count($commands) == 0) {
            $errorMsg = "No rewards or commands given.";
            return null;
        }

        // get the replacement to use for all rewards in this type
        $typeReplacement = null;
        if (isset($data["replacement"])) {
            if (is_string($data["replacement"])) {
                $typeReplacement = $rewards[$data["replacement"]] ?? null;

                if ($typeReplacement === null) {
                    $errorMsg = "Given replacement reward does not exist.";
                    return null;
                }
            } else if (is_array($data["replacement"])) {
                $typeReplacement = CrateReward::parse($id, $data["replacement"], $errorMsg, false);

            } else {
                $errorMsg = "Could not parse the replacement reward: $errorMsg";
                return null;
            }
        }

        // determine the replacements for dynamic rewards
        $replacements = [];
        foreach ($rewards as $rewardId => $reward) {
            // not dynamic
            if (!$reward instanceof DynamicCrateReward || !$reward->isReplaceable()) continue;

            $replacement = $rewardReplacements[$rewardId] ?? $typeReplacement;

            // replace this reward by an existing reward
            if (is_string($replacement)) {
                $replacement = $rewards[$replacement] ?? null;
                // invalid reward name
                if ($replacement === null) {
                    $errorMsg = "Replacement reward for reward $rewardId not found.";
                    return null;
                }
            }

            $realReward = $rewards[$replacement->getId()] ?? null;
            if ($realReward instanceof DynamicCrateReward) {
                $errorMsg = $replacement->getId() . " is not a valid replacement reward. (Dynamic rewards cannot be used as replacement)";
                return null;
            }

            // create a new instance for the replacement with the same amount as the original reward
            $replacements[$rewardId] = $replacement->setAmount($reward->getAmount());
        }

        return new CrateType($id, $typeName, $rewards, $commands, $replacements);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get a crate type by its id
     * @param string $id
     * @return CrateType|null
     */
    public static function getById(string $id): ?CrateType
    {
        return self::$crateTypes[$id] ?? null;
    }

    /**
     * @return string[]
     */
    public static function getAllTypeIds(): array
    {
        return array_keys(self::$crateTypes);
    }

    public function getRewardById(string $id): ?CrateReward
    {
        return $this->rewards[$id] ?? null;
    }

    /**
     * Get the key from a players inventory
     * @param Player $player
     * @return Item|null null if the player does not have a valid key
     */
    public function getKeyFromPlayer(Player $player): ?Item
    {
        $item = null;
        foreach ($player->getInventory()->getContents() as $i) {
            if ($this->isValidKey($i)) {
                $item = $i;
                break;
            }
        }

        return $item;
    }

    /**
     * Check if the given item is a valid key for this crate type
     * @param Item $item the item to check
     * @return bool true if it is valid
     */
    public function isValidKey(Item $item): bool
    {
        return $item->getNamedTag()->getString(CrateType::KEY_NBT_TAG, "") === $this->getId();
    }

    /**
     * Give crate keys of this crate type to the specified player
     * @param Player $player
     * @param int $amount
     * @return void
     */
    public function giveCrateKey(Player $player, int $amount): void
    {
        $key = $this->getCrateKey();
        $key->setCount($amount);
        $player->getInventory()->addItem($key);
    }

    public function getCrateKey(): Item
    {
        $key = MagicCrates::getKeyItem();

        // changes all parameters inside the custom name
        $key->setCustomName(StringUtils::prepare($key->getCustomName(), [
            "prefix" => MagicCrates::getPrefix(),
            "crate_type" => $this->getId(),
            "crate_id" => $this->getId(),
            "crate" => $this->getName(),
        ]));

        $key->getNamedTag()->setString(CrateType::KEY_NBT_TAG, $this->id);

        return $key;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Reward a player with the given reward
     * @param Player $player the player that will receive the reward
     * @param CrateReward $reward the reward given to the player
     * @param int $playerReceived amount of times the reward is received by the player
     * @return void
     */
    public function updatePlayerRewards(Player $player, CrateReward $reward, int $playerReceived): void
    {
        // get the reward with the same id from the type
        $realReward = $this->rewards[$reward->getId()] ?? null;
        // set the new amount
        MagicCrates::getDatabase()->setPlayerRewards($this, $player, $realReward, $playerReceived + 1);
    }

    /**
     * Give the items and execute the commands of a reward for the given player
     * @param Player $player
     * @param CrateReward $reward
     * @return void
     */
    public function rewardPlayer(Player $player, CrateReward $reward): void
    {
        foreach ($reward->getItems() as $item) {
            $player->getInventory()->addItem($item);
        }

        $commands = array_merge($this->commands, $reward->getCommands());
        foreach ($commands as $cmd) {
            CrateCommandSender::getInstance()->executeCommand(StringUtils::prepare($cmd, [
                "prefix" => MagicCrates::getPrefix(),
                "player" => $player->getName(),
                "crate_type" => $this->getId(),
                "crate_id" => $this->getId(),
                "crate" => $this->getName(),
                "reward_id" => $reward->getId(),
                "reward" => $reward->getName()
            ]));
        }
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Get a random reward from the rewards list based upon the given player
     * @param Player $player
     * @param callable(CrateReward $reward, int $playerRewarded): void $callback
     * @return void
     */
    public function getRandomReward(Player $player, callable $callback): void
    {
        // get all the player rewards
        $this->getPlayerRewards($player, function (array $rewards, array $playerReceived) use ($callback) {
            $dist = [];
            foreach ($rewards as $i => $reward) {
                // add amount times the index of the reward to the distribution
                $dist = array_merge($dist, array_fill(0, $reward->getAmount(), $i));
            }

            // get a random reward index from the distribution
            $rewardId = $dist[array_rand($dist)];

            $reward = $rewards[$rewardId];
            $rewarded = $playerReceived[$rewardId] ?? 0;


            // run the callback with the random reward
            call_user_func($callback, $reward, $rewarded);
        });


    }

    /**
     * Get all the rewards the player can receive
     * @param Player $player
     * @param callable(array<string, CrateReward> $rewards, array<string, int> $playerRewarded, int $rewardTotal): void $callback
     * @return void
     */
    public function getPlayerRewards(Player $player, callable $callback): void
    {
        MagicCrates::getDatabase()->getRewardTotal($this)
            ->onCompletion(function (array $totalRewards) use ($player, $callback) {
                MagicCrates::getDatabase()->getPlayerRewards($this, $player)
                    ->onCompletion(function (array $playerReceived) use ($player, $callback, $totalRewards) {
                        [$rewards, $totalAmount] = $this->determinePlayerRewards($totalRewards, $playerReceived);

                        call_user_func($callback, $rewards, $playerReceived, $totalAmount);
                    }, function () use ($callback) {
                        call_user_func($callback, [], [], 0);
                    });

            }, function () use ($callback) {
                call_user_func($callback, [], [], 0);
            });
    }

    /**
     * Get the rewards the player can get
     * @param array $rewardTotals the rewards the player can get
     * @param array $playerReceived the amount of times the player has received the rewards
     * @return array{array<string, CrateReward>, array<string, int>}
     */
    protected function determinePlayerRewards(array $rewardTotals, array $playerReceived): array
    {
        /** @var array<string, CrateReward> $rewards */
        $rewards = [];
        $totalAmount = 0;

        foreach ($this->rewards as $reward) {
            $replacement = null;
            // dynamic reward
            if ($reward instanceof DynamicCrateReward) {
                // get the player and reward totals
                $playerTotal = $playerReceived[$reward->getId()] ?? 0;
                $rewardTotal = $rewardTotals[$reward->getId()] ?? 0;

                // get the amounts for the reward and replacement reward
                $rewardAmount = $reward->getPlayerAmount($rewardTotal, $playerTotal);
                $replacementAmount = $reward->getPlayerReplaceAmount($rewardTotal, $playerTotal);

                // set the replacement reward
                if ($replacementAmount > 0) {
                    $replacement = $this->replacementRewards[$reward->getId()]?->setAmount($replacementAmount) ?? null;
                }


                // update the player reward
                if ($rewardAmount > 0) $reward = $reward->setAmount($rewardAmount);
                else $reward = null;
            }

            foreach ([$reward, $replacement] as $r) {
                /** @var CrateReward|null $r */
                if ($r === null) continue;

                // increment the total amount
                $totalAmount += $r->getAmount();

                // there already is a reward with the same name
                if (isset($rewards[$r->getId()])) {
                    $oldReward = $rewards[$r->getId()];
                    $newAmount = $oldReward->getAmount() + $r->getAmount();
                    $rewards[$r->getId()] = $oldReward->setAmount($newAmount);
                    continue;
                }
                // append the new reward to the array
                $rewards[$r->getId()] = $r;
            }
        }

        return [$rewards, $totalAmount];
    }
}
