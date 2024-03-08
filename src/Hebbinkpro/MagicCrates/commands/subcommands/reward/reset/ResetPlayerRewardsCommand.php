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

namespace Hebbinkpro\MagicCrates\commands\subcommands\reward\reset;

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

class ResetPlayerRewardsCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {

        $player = Server::getInstance()->getPlayerExact($args["player"] ?? "");
        if ($player === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cThe given player is not online.");
            return;
        }

        if (!isset($args["crate_type"])) {
            MagicCrates::getDatabase()->resetPlayerRewards($player)
                ->onCompletion(function () use ($sender, $player) {
                    $sender->sendMessage(MagicCrates::getPrefix() . " §aAll rewards received by {$player->getName()} have been reset!");
                }, function () use ($sender) {
                    $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong.");
                });
            return;
        }

        $crateType = CrateType::getById($args["crate_type"]);
        if ($crateType === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type.");
            return;
        }

        if (!isset($args["reward_id"])) {
            // reset all rewards of the player from the given crate type
            MagicCrates::getDatabase()->resetPlayerCrateRewards($crateType, $player)
                ->onCompletion(function () use ($sender, $crateType, $player) {
                    $sender->sendMessage(MagicCrates::getPrefix() . " §aAll rewards received by {$player->getName()} from crate type {$crateType->getId()} have been reset!");

                }, function () use ($sender) {
                    $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong.");
                });
            return;
        }

        $reward = $crateType->getRewardById($args["reward_id"]);
        if (!$reward instanceof DynamicCrateReward) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cYou cannot reset the received rewards for a non-dynamic reward.");
            return;
        }

        // reset all rewards of the player from the given reward inside the crate type
        MagicCrates::getDatabase()->resetPlayerCrateReward($crateType, $player, $reward)->onCompletion(function () use ($sender, $crateType, $player, $reward) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §aThe amount of times {$player->getName()} received reward {$reward->getId()} from crate type {$crateType->getId()} has been reset!");
        }, function () use ($sender) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong.");
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.reward.reset");

        $this->registerArgument(0, new TargetPlayerArgument(false, "player"));
        $this->registerArgument(1, new CrateTypeArgument("crate_type", true));
        $this->registerArgument(2, new RawStringArgument("reward_id", true));
    }
}