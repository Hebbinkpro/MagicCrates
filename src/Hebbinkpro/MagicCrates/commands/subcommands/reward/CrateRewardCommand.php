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
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;

class CrateRewardCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(MagicCrates::getPrefix() . " §eHelp:");
        $sender->sendMessage("- /mc reward set   => Set the rewards of a player");
        $sender->sendMessage("- /mc reward reset => Reset the rewards of a crate or player");
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.reward");

        /** @var PluginBase $plugin */
        $plugin = $this->getOwningPlugin();
        $this->registerSubCommand(new CrateRewardResetCommand($plugin, "reset", "Reset the rewards of a crate or player"));
        $this->registerSubCommand(new CrateRewardSetCommand($plugin, "set", "Set the rewards of a player"));
    }
}