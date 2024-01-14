<?php
/*
 *   _____           _        _   __  __
 *  |  __ \         | |      | | |  \/  |
 *  | |__) |__   ___| | _____| |_| \  / | __ _ _ __
 *  |  ___/ _ \ / __| |/ / _ \ __| |\/| |/ _` | '_ \
 *  | |  | (_) | (__|   <  __/ |_| |  | | (_| | |_) |
 *  |_|   \___/ \___|_|\_\___|\__|_|  |_|\__,_| .__/
 *                                            | |
 *                                            |_|
 *
 * Copyright (C) 2023 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\MagicCrates\commands\subcommands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class CrateKeyAllCommand extends BaseSubCommand
{
    protected function prepare(): void
    {

        $this->setPermission("magiccrates.cmd.key.all");

        $this->registerArgument(0, new CrateTypeArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        // check if the crate type exists, you never know
        if (($type = CrateType::getById($args["type"])) === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type");
            return;
        }

        // get the amount of keys
        if (!isset($args["amount"])) $amount = 1;
        else {
            $amount = $args["amount"];

            // negative or zero amount is given
            if ($amount <= 0) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid amount, should be >= 1");
                return;
            }
        }

        $typeName = $type->getName();
        $s = ($amount > 1 ? "s" : "");

        // give all online players a crate key
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $type->giveCrateKey($player, $amount);
            $player->sendMessage(MagicCrates::getPrefix() . " §aYou received $amount $typeName §r§akey$s");
        }

        $sender->sendMessage(MagicCrates::getPrefix() . " §eAll online players received $amount $typeName §r§ekey$s");
    }
}