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
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class KeyCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $plugin = $this->getOwningPlugin();

        // console sender has to give the player
        if (!$sender instanceof Player && !isset($args["player"])) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cUsage: /mc makekey <type> <amount> <player>");
            return;
        }

        // check if the crate type exists, you never know
        if (($type = CrateType::getById($args["type"])) === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type");
            return;
        }

        // get the amount of keys
        $amount = $args["amount"] ?? 1;

        // negative or zero amount is given
        if ($amount <= 0) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid amount, should be >= 1");
            return;
        }

        // get the type name, used in the messages
        $typeName = $type->getName();
        $s = ($amount > 1 ? "s" : "");

        if (isset($args["player"])) {
            // get the online player
            $player = $plugin->getServer()->getPlayerExact($args["player"]);

            // the player is not  online
            if ($player === null) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cThe given player is not online");
                return;
            }

            $type->giveCrateKey($player, $amount);

            $name = $player->getName();
            $player->sendMessage(MagicCrates::getPrefix() . " §aYou received $amount $typeName §r§akey$s");
            $sender->sendMessage(MagicCrates::getPrefix() . " §e$name received $amount $typeName §r§ekey$s");
            return;
        }

        $type->giveCrateKey($sender, $amount);
        $sender->sendMessage(MagicCrates::getPrefix() . " §aYou received $amount §e$typeName §r§akey$s");
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {

        $this->setPermission("magiccrates.cmd.key");

        /** @var PluginBase $plugin */
        $plugin = $this->getOwningPlugin();
        $this->registerSubCommand(new KeyAllCommand($plugin, "all", "Give a crate key to all online players", ["everyone"]));

        $this->registerArgument(0, new CrateTypeArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
        $this->registerArgument(2, new TargetPlayerArgument(true, "player"));
    }
}