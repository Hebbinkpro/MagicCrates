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

namespace Hebbinkpro\MagicCrates\commands\subcommands\key;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class KeyAllCommand extends BaseSubCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        // check if the crate type exists, you never know
        if (($type = CrateType::getById($args["type"])) === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type.");
            return;
        }

        // get the number of keys
        $amount = $args["amount"] ?? 1;

        // negative or zero amount is given
        if ($amount <= 0) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid amount, should be greater or equal then 1");
            return;
        }

        $typeName = $type->getName();
        $s = ($amount > 1 ? "s" : "");

        // give all online players a crate key
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $type->giveCrateKey($player, $amount);
            $player->sendMessage(MagicCrates::getPrefix() . " §aYou have received {$amount}x $typeName §r§akey$s.");
        }

        $sender->sendMessage(MagicCrates::getPrefix() . " §eAll online players have received {$amount}x $typeName §r§ekey$s.");
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {

        $this->setPermission("magiccrates.cmd.key.all");

        $this->registerArgument(0, new CrateTypeArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
    }
}