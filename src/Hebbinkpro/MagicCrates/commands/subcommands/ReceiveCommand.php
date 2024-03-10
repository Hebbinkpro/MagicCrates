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

        MagicCrates::getDatabase()->getReceivedRewards($sender)
            ->onCompletion(function (array $rewards) use ($sender) {
                if (sizeof($rewards) == 0) {
                    // the player has received all their rewards
                    $sender->sendMessage(MagicCrates::getPrefix() . "§e You have received all your rewards.");
                    return;
                }

                if (InventoryUtils::getEmptySlots($sender->getInventory()) < sizeof($rewards)) {
                    $sender->sendMessage(MagicCrates::getPrefix() . " §cYour inventory is full, try again when you have cleared your inventory!");
                    return;
                }

                // loop through all the rewards the player has to receive
                foreach ($rewards as $reward) {
                    $id = $reward["id"];
                    $typeId = $reward["type"];
                    $rewardId = $reward["reward"];

                    $type = CrateType::getById($typeId);
                    if ($type === null) continue;

                    $reward = $type->getRewardById($rewardId);
                    if ($reward === null) continue;

                    // check if the player has enough inventory space
                    if (!$reward->canPlayerReceive($sender)) {
                        $sender->sendMessage(MagicCrates::getPrefix() . " §cYour inventory is full, please clear your inventory to receive the reward $rewardId from crate " . $type->getName());
                        return;
                    }

                    // make sure the reward is removed from the db before giving the player the reward
                    MagicCrates::getDatabase()->removeReceivedReward($id)
                        ->onCompletion(function () use ($sender, $type, $typeId, $reward, $rewardId) {
                            //  check if the player managed to log out before we got the responded
                            if ($sender->isConnected()) {
                                // recheck if the player has enough inventory space,
                                // as it is possible (but unlikely) that the inventory is filled during the time we had to wait
                                if ($reward->canPlayerReceive($sender)) {
                                    // reward the player
                                    $type->rewardPlayer($sender, $reward);
                                    return;
                                }

                                $sender->sendMessage(MagicCrates::getPrefix() . " §cYour inventory is full, please clear your inventory to receive the reward $rewardId from crate " . $type->getName());
                            }

                            // the player could not receive the reward
                            // reinsert the reward in the database
                            MagicCrates::getDatabase()->addReceivedReward($sender, $type, $reward);
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
        $this->setPermission("magiccrates.cmd.receive");
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}