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

namespace Hebbinkpro\MagicCrates\utils;


use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateReward;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\crate\DynamicCrateReward;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;

class CrateForm
{

    /**
     * Send a form to the player to create a crate
     * @param Player $player
     * @param Position $pos
     * @return void
     */
    public static function sendCreateForm(Player $player, Position $pos): void
    {
        $form = new CustomForm(function (Player $player, $data) use ($pos) {
            // data[1] is the value of the selected crate type
            if (isset($data[1])) {
                // get the selected type
                $typeId = CrateType::getAllTypeIds()[$data[1]] ?? "";
                $type = CrateType::getById($typeId);

                if ($type === null) {
                    // this should not be possible, but you never know
                    $player->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type selected");
                    return;
                }

                self::sendSubmitCreate($player, $pos, $type);
            }

        });

        $form->setTitle(MagicCrates::getPrefix() . " - Create crate");

        $form->addLabel("Select the crate type for the new crate");
        $form->addDropdown("Crate type", CrateType::getAllTypeIds(), 0);

        $player->sendForm($form);

    }

    /**
     * Send a form to the player to confirm the creation of a crate
     * @param Player $player
     * @param Position $pos
     * @param CrateType $type
     * @return void
     */
    public static function sendSubmitCreate(Player $player, Position $pos, CrateType $type): void
    {
        $form = new ModalForm(function (Player $player, $data) use ($pos, $type) {
            if (!is_bool($data)) return;

            if ($data) {
                $crate = new Crate($pos->asVector3(), $pos->getWorld(), $type);
                if (!Crate::registerCrate($crate)) {
                    $player->sendMessage(MagicCrates::getPrefix() . " §cThere already exists a crate at this position");
                    return;
                }

                // add the crate to the db
                MagicCrates::getDatabase()->addCrate($crate);

                $crate->showFloatingText();
                $player->sendMessage(MagicCrates::getPrefix() . " §aThe {$crate->getType()->getName()}§r§a crate is created");
                return;
            }

            $player->sendMessage(MagicCrates::getPrefix() . " §aCrate creation is cancelled");
        });

        $form->setTitle(MagicCrates::getPrefix() . " - Create crate");

        $form->setContent("Are you sure you want to create a §e{$type->getId()}§r crate?\n\nClick§a save§r to save the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§aSave");
        $form->setButton2("§eCancel");

        $player->sendForm($form);
    }

    /**
     * Send a form to a player to remove a crate
     * @param Player $player
     * @param Crate $crate
     * @return void
     */
    public static function sendRemoveForm(Player $player, Crate $crate): void
    {

        $form = new ModalForm(function (Player $player, $data) use ($crate) {
            if ($data === true) {
                $crate->remove();
                $crate->hideFloatingText();

                $player->sendMessage(MagicCrates::getPrefix() . " §aThe §e{$crate->getType()->getId()}§r§a crate is removed");
                return;
            }

            $player->sendMessage(MagicCrates::getPrefix() . " §aThe crate isn't removed");
        });

        $form->setTitle(MagicCrates::getPrefix() . " - Remove crate");

        $form->setContent("Do you want to delete this §e{$crate->getType()->getId()}§r crate?\n\nClick §cDelete§r to delete the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§cDelete");
        $form->setButton2("§eCancel");

        $player->sendForm($form);
    }

    /**
     * Send a form to the player containing the contents of the crate
     * @param Player $player
     * @param Crate $crate
     * @param bool $showRewardInfo if the player is able to see the reward details
     * @return void
     */
    public static function sendPreviewForm(Player $player, Crate $crate, bool $showRewardInfo): void
    {
        $crate->getType()->getPlayerRewards($player,
            function (array $rewards, array $playerRewarded, int $totalRewards) use ($player, $crate, $showRewardInfo) {
                $form = new SimpleForm(function (Player $player, $data) use ($crate, $showRewardInfo, $playerRewarded, $totalRewards) {

                    if (is_string($data)) {
                        if ($data === "open") {
                            $crate->openWithKey($player);
                        } else if (str_starts_with($data, "reward_")) {
                            // remove "reward_" from the name
                            $rewardId = substr($data, 7);
                            $reward = $crate->getType()->getRewardById($rewardId);
                            if ($reward !== null && $showRewardInfo) {
                                // send the reward preview
                                self::sendCrateRewardPreviewForm($player, $crate, $reward, $playerRewarded[$rewardId] ?? 0, $totalRewards);
                            }
                        }
                    }

                });

                // set the form title
                $form->setTitle(MagicCrates::getPrefix() . " - " . $crate->getType()->getName());

                // add the open button if the player has a valid key in their inventory
                if ($crate->getType()->getKeyFromPlayer($player) !== null) $form->addButton("§aOpen Crate", -1, "", "open");

                $form->setContent("This crate is filled with $totalRewards items.");

                // add buttons for all rewards inside the crate
                foreach ($rewards as $reward) {
                    $p = $reward->getAmount() / $totalRewards;

                    $buttonName = $reward->getAmount() . "x §l" . $reward->getName() . "§r\n";
                    $buttonName .= "Probability: " . round($p * 100, 1) . "%%";

                    $image = $reward->getIcon() ?? $reward->getDefaultIcon();

                    $imageType = SimpleForm::IMAGE_TYPE_PATH;
                    if (str_starts_with($image, "http")) $imageType = SimpleForm::IMAGE_TYPE_URL;

                    $form->addButton($buttonName, $imageType, $image, "reward_" . $reward->getId());
                }


                $player->sendForm($form);
            });
    }

    /**
     * Sends a crate reward preview form with some extra details about the crate reward
     * @param Player $player
     * @param Crate $crate
     * @param CrateReward $reward
     * @param int $playerRewarded
     * @param int $totalRewards
     * @return void
     */
    public static function sendCrateRewardPreviewForm(Player $player, Crate $crate, CrateReward $reward, int $playerRewarded, int $totalRewards): void
    {
        $form = new SimpleForm(function (Player $player, $data) use ($crate) {
            if ($data === "back") self::sendPreviewForm($player, $crate, true);
        });

        $amount = $reward->getAmount();

        // set the form title
        $form->setTitle(MagicCrates::getPrefix() . " - " . $crate->getType()->getName());

        $items = array_map(fn(Item $i) => $i->getCount() . "x " . $i->getName(), $reward->getItems());

        $realReward = $crate->getType()->getRewardById($reward->getId());

        $form->setContent(
            "Reward Name: " . $reward->getName() . "\n" .
            "Amount: " . $amount . "\n" .
            "Probability: " . (round(($amount / $totalRewards) * 100, 1)) . "%%\n" .
            (sizeof($items) == 0 ? "" : "Items: \n - " . implode("\n - ", $items) . "\n") .
            (sizeof($reward->getCommands()) == 0 ? "" : "Commands: " . sizeof($reward->getCommands()) . "\n") .
            ($realReward instanceof DynamicCrateReward ? "Times received: $playerRewarded / {$realReward->getPlayerMaxAmount()} \n" : "")
        );


        $form->addButton("§c<- Back", -1, "", "back");

        $player->sendForm($form);
    }
}