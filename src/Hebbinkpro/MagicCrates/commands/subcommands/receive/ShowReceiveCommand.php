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

namespace Hebbinkpro\MagicCrates\commands\subcommands\receive;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\utils\CrateForm;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ShowReceiveCommand extends BaseSubCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {

        if (!$sender instanceof Player) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cUse this command in-game.");
            return;
        }

        CrateForm::sendUnreceivedRewardsForm($sender, $this->plugin->getConfig()->get("show-reward-info"));
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.receive");
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}