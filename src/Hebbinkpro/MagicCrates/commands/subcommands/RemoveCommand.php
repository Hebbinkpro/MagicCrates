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

namespace Hebbinkpro\MagicCrates\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Hebbinkpro\MagicCrates\action\CrateAction;
use Hebbinkpro\MagicCrates\action\PlayerCrateActions;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class RemoveCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cUse this command in-game!");
            return;
        }

        $action = PlayerCrateActions::getInstance()->getAction($sender);

        switch ($action) {
            case CrateAction::CREATE:
                $sender->sendMessage(MagicCrates::getPrefix() . " §cCrate creation mode is currently§a enabled§c.");
                $sender->sendMessage(MagicCrates::getPrefix() . " §eDisable the crate creation mode with '/mc create' and try again.");
                break;
            case CrateAction::REMOVE:
                PlayerCrateActions::getInstance()->setAction($sender, CrateAction::NONE);
                $sender->sendMessage(MagicCrates::getPrefix() . " §eCrate remove mode has been§c disabled.");
                break;
            default:
                PlayerCrateActions::getInstance()->setAction($sender, CrateAction::REMOVE);
                $sender->sendMessage(MagicCrates::getPrefix() . " §eCrate remove mode has been§a enabled§e. Click on a crate to remove it.");
        }
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.remove");
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}