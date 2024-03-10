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

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class RewardSetCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $crateType = CrateType::getById($args["crate_type"]);
        if ($crateType === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type.");
            return;
        }

        $player = Server::getInstance()->getPlayerExact($args["player"]);
        if ($player === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cThe given player is not online.");
            return;
        }

        $reward = $crateType->getRewardById($args["reward_id"]);
        if ($reward === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cThe reward {$args["reward_id"]} does not exist.");
            return;
        }

        $amount = $args["amount"];
        if ($amount < 0) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cThe amount of times the reward is received should be greater or equal then 0.");
            return;
        }

        MagicCrates::getDatabase()->setPlayerRewards($crateType, $player, $reward, $amount)->onCompletion(function () use ($sender, $crateType, $player, $reward, $amount) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §aThe amount of times {$player->getName()} has received reward {$reward->getId()} in crate {$crateType->getId()} is set to $amount.");
        }, function () use ($sender) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong.");
        });
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.reward.set");

        $this->registerArgument(0, new CrateTypeArgument("crate_type"));
        $this->registerArgument(1, new TargetPlayerArgument(false, "player"));
        $this->registerArgument(2, new RawStringArgument("reward_id"));
        $this->registerArgument(3, new IntegerArgument("amount"));
    }
}