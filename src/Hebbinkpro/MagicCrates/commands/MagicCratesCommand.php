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

namespace Hebbinkpro\MagicCrates\commands;

use CortexPE\Commando\BaseCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateCreateCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateRemoveCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\key\CrateKeyCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\reward\CrateRewardCommand;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;

class MagicCratesCommand extends BaseCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(MagicCrates::getPrefix() . " §eHelp:");
        $sender->sendMessage("- /mc create => Toggle crate create mode");
        $sender->sendMessage("- /mc remove => Toggle crate remove mode");
        $sender->sendMessage("- /mc key    => Give a crate key to a player");
        $sender->sendMessage("- /mc reward => Manage crate rewards");
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd");
        $this->setAliases(["mc"]);

        /** @var MagicCrates $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new CrateCreateCommand($plugin, "create", "Toggle crate create mode"));
        $this->registerSubCommand(new CrateRemoveCommand($plugin, "remove", "Toggle crate remove mode"));
        $this->registerSubCommand(new CrateKeyCommand($plugin, "key", "Give a crate key to a player"));
        $this->registerSubCommand(new CrateRewardCommand($plugin, "reward", "Manage crate rewards"));
    }


}