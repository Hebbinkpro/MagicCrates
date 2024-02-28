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

use CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\reward\reset\ResetCrateRewardsCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\reward\reset\ResetPlayerRewardsCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\reward\reset\ResetRewardCommand;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class RewardResetCommand extends BaseSubCommand
{


    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(MagicCrates::getPrefix() . " §eHelp:");
        $sender->sendMessage("- /mc reward reset crate  => Reset the amount of times all rewards in the crate are received");
        $sender->sendMessage("- /mc reward reset reward => Reset the amount of times the reward is received in a crate type");
        $sender->sendMessage("- /mc reward reset player => Reset the amount of times a player has received rewards");
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.reward.reset");

        /** @var PluginBase $plugin */
        $plugin = $this->getOwningPlugin();
        $this->registerSubCommand(new ResetCrateRewardsCommand($plugin, "crate", "Reset the amount of times all rewards in the crate are received"));
        $this->registerSubCommand(new ResetRewardCommand($plugin, "reward", "Reset the amount of times the reward is received in a crate type"));
        $this->registerSubCommand(new ResetPlayerRewardsCommand($plugin, "player", "Reset the amount of times a player has received rewards"));

    }
}