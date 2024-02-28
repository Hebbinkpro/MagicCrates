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
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\crate\DynamicCrateReward;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;

class ResetRewardCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $crateType = CrateType::getById($args["crate_type"]);

        if ($crateType === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type.");
            return;
        }

        $reward = $crateType->getRewardById($args["reward_id"] ?? "");
        if (!$reward instanceof DynamicCrateReward) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cYou cannot reset the received amount for a non dynamic reward.");
            return;
        }

        // reset the reward from the crate type
        MagicCrates::getDatabase()->resetReward($crateType, $reward)->onCompletion(function () use ($sender, $crateType, $reward) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §aThe received amount of all players that received the reward {$reward->getId()} in crate type {$crateType->getId()} has been reset.");
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

        $this->registerArgument(0, new CrateTypeArgument("crate_type"));
        $this->registerArgument(1, new RawStringArgument("reward_id"));
    }
}