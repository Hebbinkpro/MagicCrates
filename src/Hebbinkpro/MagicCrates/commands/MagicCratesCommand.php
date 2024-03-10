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
use Hebbinkpro\MagicCrates\commands\subcommands\CreateCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\key\KeyCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\receive\ReceiveCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\RemoveCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\reward\RewardCommand;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;

class MagicCratesCommand extends BaseCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(MagicCrates::getPrefix() . " §eHelp:");
        $sender->sendMessage("- /mc receive => Receive your unreceived rewards");
        if ($sender->hasPermission("magiccrates.cmd.create"))
            $sender->sendMessage("- /mc create => Toggle crate create mode");
        if ($sender->hasPermission("magiccrates.cmd.remove"))
            $sender->sendMessage("- /mc remove => Toggle crate remove mode");
        if ($sender->hasPermission("magiccrates.cmd.key"))
            $sender->sendMessage("- /mc key    => Give a crate key to a player");
        if ($sender->hasPermission("magiccrates.cmd.reward"))
            $sender->sendMessage("- /mc reward => Manage the amount of times crate rewards are received");
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd");
        $this->setAliases(["mc"]);

        /** @var MagicCrates $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new CreateCommand($plugin, "create", "Toggle the create mode"));
        $this->registerSubCommand(new RemoveCommand($plugin, "remove", "Toggle the remove mode"));
        $this->registerSubCommand(new KeyCommand($plugin, "key", "Give a crate key to a player"));
        $this->registerSubCommand(new RewardCommand($plugin, "reward", "Manage the amount of times crate rewards are received"));
        $this->registerSubCommand(new ReceiveCommand($plugin, "receive", "Receive your unreceived rewards"));
    }


}