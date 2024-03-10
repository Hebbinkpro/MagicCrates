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
use pocketmine\form\Form;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;

class CrateForm
{
    /** @var array<string, Form> */
    private static array $previousForm = [];

    /**
     * Send a form to the player to create a crate
     * @param Player $player
     * @param Position $pos
     * @return void
     */
    public static function sendCreateForm(Player $player, Position $pos): void
    {
        $form = new CustomForm(function (Player $player, mixed $data) use ($pos) {
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
        $form = new ModalForm(function (Player $player, mixed $data) use ($pos, $type) {
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

        $form->setTitle("Create crate");

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

        $form = new ModalForm(function (Player $player, mixed $data) use ($crate) {
            if ($data === true) {
                $crate->remove();
                $crate->hideFloatingText();

                $player->sendMessage(MagicCrates::getPrefix() . " §aThe §e{$crate->getType()->getId()}§r§a crate is removed");
                return;
            }

            $player->sendMessage(MagicCrates::getPrefix() . " §aThe crate is not removed");
        });

        $form->setTitle("Remove crate");

        $form->setContent("Do you want to remove this §e{$crate->getType()->getId()}§r crate?\n\nClick §cRemove§r to remove the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§cRemove");
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
                /**
                 * @var array<string, CrateReward> $rewards
                 * @var array<string, int> $playerRewarded
                 * @var int $rewardTotal
                 */

                $form = new SimpleForm(function (Player $player, mixed $data) use ($crate, $showRewardInfo, $playerRewarded, $totalRewards) {

                    if (is_string($data)) {
                        if ($data === "open") {
                            $crate->openWithKey($player);
                        } else if ($showRewardInfo) {
                            // remove "reward_" from the name
                            $reward = $crate->getType()->getRewardById($data);
                            if ($reward !== null) {
                                // send the reward preview
                                self::sendCrateRewardPreviewForm($player, $crate->getType(), $reward, $playerRewarded[$reward->getId()] ?? 0, $totalRewards);
                                return;
                            }

                            $player->sendMessage(MagicCrates::getPrefix() . "§cInvalid reward.");
                        }

                        // remove the previous form
                        unset (self::$previousForm[$player->getUniqueId()->getBytes()]);
                    }

                });

                // set the form title
                $form->setTitle("Crate Rewards: " . $crate->getType()->getName());

                // add the open button if the player has a valid key in their inventory
                if ($crate->getType()->getKeyFromPlayer($player) !== null) $form->addButton("§2Open Crate", -1, "", "open");

                $form->setContent("This crate is filled with $totalRewards items.");

                // add buttons for all rewards inside the crate
                foreach ($rewards as $id => $reward) {
                    $p = $reward->getAmount() / $totalRewards;

                    $buttonName = $reward->getAmount() . "x §l" . $reward->getName() . "§r\n";
                    $buttonName .= "Probability: " . round($p * 100, 1) . "%%";

                    $image = $reward->getIcon() ?? $reward->getDefaultIcon();

                    $imageType = SimpleForm::IMAGE_TYPE_PATH;
                    if (str_starts_with($image, "http")) $imageType = SimpleForm::IMAGE_TYPE_URL;

                    $form->addButton($buttonName, $imageType, $image, $id);
                }


                $player->sendForm($form);
                self::$previousForm[$player->getUniqueId()->getBytes()] = $form;
            });
    }

    /**
     * Sends a crate reward preview form with some extra details about the crate reward
     * @param Player $player
     * @param CrateType $type
     * @param CrateReward $reward
     * @param int $playerRewarded
     * @param int $totalRewards
     * @return void
     */
    private static function sendCrateRewardPreviewForm(Player $player, CrateType $type, CrateReward $reward, int $playerRewarded = -1, int $totalRewards = 0): void
    {
        $form = new SimpleForm(function (Player $player, mixed $data) {
            if ($data === "back") {
                if (($form = self::$previousForm[$player->getUniqueId()->getBytes()] ?? null) !== null) {
                    $player->sendForm($form);
                    return;
                }
            }
            unset (self::$previousForm[$player->getUniqueId()->getBytes()]);
        });

        $amount = $reward->getAmount();

        // set the form title
        $form->setTitle("Reward: " . $reward->getName());


        $content = "Reward: " . $reward->getName() . "§r\n";
        $content .= "Crate: " . $type->getName() . "§r\n";

        if ($totalRewards > 0) {
            $content .= "Amount: " . $amount . "\n";
            $content .= "Probability: " . (round(($amount / $totalRewards) * 100, 1)) . "%%\n";
        }

        $items = array_map(fn(Item $i) => $i->getCount() . "x " . $i->getName() . "§r", $reward->getItems());
        $content .= sizeof($items) == 0 ? "" : ("Items: \n - " . implode("\n - ", $items) . "\n");
        $content .= sizeof($reward->getCommands()) == 0 ? "" : ("Commands: " . sizeof($reward->getCommands()) . "\n");

        if ($playerRewarded > -1) {
            $realReward = $type->getRewardById($reward->getId());
            $content .= ($realReward instanceof DynamicCrateReward ? "Times received: $playerRewarded / {$realReward->getPlayerMaxAmount()} \n" : "");
        }

        $form->setContent($content);

        $form->addButton("§c<- Back", -1, "", "back");

        $player->sendForm($form);
    }

    /**
     * @param Player $player
     * @param bool $showRewardInfo
     * @return void
     */
    public static function sendUnreceivedRewardsForm(Player $player, bool $showRewardInfo): void
    {
        MagicCrates::getDatabase()->getUnreceivedRewards($player)->onCompletion(function ($unreceivedRewards) use ($player, $showRewardInfo) {
            /** @var array<int, array{type: CrateType, reward: CrateReward}> $unreceivedRewards */

            if (sizeof($unreceivedRewards) == 0) {
                $player->sendMessage(MagicCrates::getPrefix() . "§e You have received all your rewards.");
                return;
            }

            $form = new SimpleForm(function (Player $player, mixed $data) use ($unreceivedRewards, $showRewardInfo) {
                if (is_string($data) && $showRewardInfo) {
                    $unreceivedReward = $unreceivedRewards[intval($data)] ?? null;
                    if ($unreceivedReward !== null) {
                        $type = $unreceivedReward["type"];
                        $reward = $unreceivedReward["reward"];
                        self::sendCrateRewardPreviewForm($player, $type, $reward);
                        return;
                    }
                }

                unset(self::$previousForm[$player->getUniqueId()->getBytes()]);
            });

            $form->setTitle("Unreceived Rewards");
            $form->setContent("You have not yet received the rewards below.\nYou can receive all the rewards by executing '/mc receive'");

            foreach ($unreceivedRewards as $id => $unreceivedReward) {
                $type = $unreceivedReward["type"];
                $reward = $unreceivedReward["reward"];

                $buttonName = $reward->getAmount() . "x §l" . $reward->getName() . "§r\n";
                $buttonName .= "From Crate: " . $type->getName();

                $image = $reward->getIcon() ?? $reward->getDefaultIcon();

                $imageType = SimpleForm::IMAGE_TYPE_PATH;
                if (str_starts_with($image, "http")) $imageType = SimpleForm::IMAGE_TYPE_URL;

                $form->addButton($buttonName, $imageType, $image, "$id");
            }

            $player->sendForm($form);
            self::$previousForm[$player->getUniqueId()->getBytes()] = $form;

        }, function () use ($player) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cSomething went wrong");
        });
    }
}