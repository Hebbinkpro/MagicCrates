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

use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class CrateResetCommand extends BaseSubCommand
{


    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $crateType = CrateType::getById($args["crate_type"]);

        if ($crateType === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type");
            return;
        }

        if (!isset($args["player"])) {
            $crateType->resetRewards();
            $sender->sendMessage(MagicCrates::getPrefix() . " §aAll rewards in crate type {$crateType->getId()} have been reset!");
            return;
        }

        $player = Server::getInstance()->getOfflinePlayer($args["player"]);
        if ($player === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cThe given player does not exist");
            return;
        }


        $crateType->resetPlayerRewards($player);
        $sender->sendMessage(MagicCrates::getPrefix() . " §aThe rewards for {$player->getName()} in crate type {$crateType->getId()} have been reset!");
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.reset");
        $this->registerArgument(0, new CrateTypeArgument("crate_type"));
        $this->registerArgument(1, new TargetPlayerArgument(true, "player"));
    }
}