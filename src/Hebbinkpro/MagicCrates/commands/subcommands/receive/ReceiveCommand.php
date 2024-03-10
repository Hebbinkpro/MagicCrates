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
use Hebbinkpro\MagicCrates\crate\CrateReward;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\utils\InventoryUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ReceiveCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cUse this command in-game.");
            return;
        }

        MagicCrates::getDatabase()->getUnreceivedRewards($sender)
            ->onCompletion(function (array $unreceivedRewards) use ($sender) {
                /** @var array<int, array{type: CrateType, reward: CrateReward}> $unreceivedRewards */

                if (sizeof($unreceivedRewards) == 0) {
                    // the player has received all their rewards
                    $sender->sendMessage(MagicCrates::getPrefix() . "§e You have received all your rewards.");
                    return;
                }

                if (sizeof(InventoryUtils::getEmptySlots($sender->getInventory())) < sizeof($unreceivedRewards)) {
                    $sender->sendMessage(MagicCrates::getPrefix() . " §cYour inventory is full. Please clear your inventory and try again.");
                    return;
                }

                // loop through all the rewards the player has to receive
                foreach ($unreceivedRewards as $id => $unreceivedReward) {
                    $type = $unreceivedReward["type"];
                    $reward = $unreceivedReward["reward"];

                    // make sure the reward is removed from the db before giving the player the reward
                    MagicCrates::getDatabase()->removeUnreceivedReward($id)
                        ->onCompletion(function () use ($sender, $type, $reward) {
                            //  check if the player managed to log out before we got the responded
                            if ($sender->isConnected()) {
                                // recheck if the player has enough inventory space,
                                // as it is possible (but unlikely) that the inventory is filled during the time we had to wait
                                if ($reward->canPlayerReceive($sender)) {
                                    // reward the player
                                    $sender->sendMessage(MagicCrates::getPrefix() . " §aYou received reward §e" . $reward->getName());
                                    $type->rewardPlayer($sender, $reward);
                                    return;
                                }

                                $sender->sendMessage(MagicCrates::getPrefix() . " §cCould not receive reward {$reward->getName()}, your inventory is full. Please clear your inventory and try again.");
                            }

                            // the player could not receive the reward
                            // reinsert the reward in the database
                            MagicCrates::getDatabase()->addUnreceivedReward($sender, $type, $reward);
                        }, function () use ($sender) {
                            $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong.");
                        });
                }

            }, function () use ($sender) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong.");
            });
    }

    /**
     * @inheritDoc
     */
    protected function prepare(): void
    {
        /** @var MagicCrates $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new ShowReceiveCommand($plugin, "show", "Show all the rewards you have not yet received."));

        $this->setPermission("magiccrates.cmd.receive");
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}