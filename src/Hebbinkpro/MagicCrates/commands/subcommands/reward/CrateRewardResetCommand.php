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

namespace Hebbinkpro\MagicCrates\commands\subcommands\reward;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\crate\DynamicCrateReward;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class CrateRewardResetCommand extends BaseSubCommand
{


    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $crateType = CrateType::getById($args["crate_type"]);

        if ($crateType === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type");
            return;
        }

        if (!isset($args["player"])) {
            // reset all rewards of the given crate type
            MagicCrates::getDatabase()->resetRewards($crateType)->onCompletion(function () use ($sender, $crateType) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §aAll rewards in crate type {$crateType->getId()} have been reset!");
            }, function () use ($sender) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong");
            });
            return;
        }

        $player = Server::getInstance()->getPlayerExact($args["player"]);
        if ($player === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cThe given player is not online.");
            return;
        }

        if (!isset($args["reward_id"])) {
            // reset all rewards of th player from the given crate type
            MagicCrates::getDatabase()->resetPlayerRewards($crateType, $player)->onCompletion(function () use ($sender, $crateType, $player) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §aThe rewards for {$player->getName()} in crate type {$crateType->getId()} have been reset!");

            }, function () use ($sender) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong");
            });
            return;
        }


        $reward = $crateType->getRewardById($args["reward_id"]);
        if (!$reward instanceof DynamicCrateReward) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cYou cannot reset the reward amount for a non dynamic reward.");
            return;
        }

        // reset all rewards of the player from the given reward inside the crate type
        MagicCrates::getDatabase()->resetPlayerReward($crateType, $player, $reward)->onCompletion(function () use ($sender, $crateType, $player, $reward) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §aThe rewards for {$player->getName()} of reward {$reward->getId()} in crate type {$crateType->getId()} is reset!");
        }, function () use ($sender) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong");
        });

    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.reward.reset");
        $this->registerArgument(0, new CrateTypeArgument("crate_type"));
        $this->registerArgument(1, new TargetPlayerArgument(true, "player"));
        $this->registerArgument(2, new RawStringArgument("reward_id", true));
    }
}