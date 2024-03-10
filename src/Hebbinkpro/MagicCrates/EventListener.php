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

namespace Hebbinkpro\MagicCrates;

use Hebbinkpro\MagicCrates\action\CrateAction;
use Hebbinkpro\MagicCrates\action\PlayerCrateActions;
use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\utils\CrateForm;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\ChestPairEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

class EventListener implements Listener
{

    public function __construct(private MagicCrates $plugin)
    {
    }

    public function onInteractChest(PlayerInteractEvent $e): void
    {
        $block = $e->getBlock();

        // when a block isn't a chest or when, it's not a right click interaction return
        if (!$block instanceof Chest || $e->getAction() != PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;

        $pos = $block->getPosition();

        // get the chest tile
        $tile = $pos->getWorld()->getTile($pos);
        // the crate isn't a TileChest
        if (!$tile instanceof TileChest) return;

        $player = $e->getPlayer();
        $playerAction = PlayerCrateActions::getInstance()->getAction($player);
        $crate = Crate::getByPosition($block->getPosition());

        // no player action
        if ($playerAction === CrateAction::NONE) {
            // it's a default chest
            if ($crate === null) return;

            // open the crate
            $e->cancel();
            $item = $e->getItem();
            $this->openCrate($player, $crate, $item);

            return;
        }

        $this->handleCrateAction($player, $block);
        $e->cancel();
    }

    private function openCrate(Player $player, Crate $crate, Item $item): void
    {
        if ($crate->isOpen()) {
            $playerName = $crate->getOpener()->getName();
            $player->sendMessage(MagicCrates::getPrefix() . " §cYou have to wait, §e$playerName §r§cis now opening the crate");
            return;
        }

        $type = $crate->getType();

        if (!$type->isValidKey($item)) {
            if (($typeId = $item->getNamedTag()->getString(CrateType::KEY_NBT_TAG, "")) !== "") {
                // the player interacted with an item with the key nbt tag
                if (($expectedCrate = CrateType::getById($typeId)) !== null) {
                    // there exists a crate type with the typeId
                    $player->sendMessage(MagicCrates::getPrefix() . " §cYou can only open a {$expectedCrate->getName()}§r§c crate with this key.");
                    return;
                }
            }

            // check if crate info is enabled
            if ($this->plugin->getConfig()->get("show-crate-info")) {
                // send the crate preview form
                $showRewardInfo = $this->plugin->getConfig()->get("show-reward-info");
                CrateForm::sendPreviewForm($player, $crate, $showRewardInfo);
            }
            return;
        }

        $crate->openWithKey($player, $item);
    }

    private function handleCrateAction(Player $player, Block $block): void
    {
        $action = PlayerCrateActions::getInstance()->getAction($player);
        $crate = Crate::getByPosition($block->getPosition());

        switch ($action) {
            case CrateAction::CREATE:
                $tile = $player->getWorld()->getTile($block->getPosition());
                // the block is not a chest
                if (!$tile instanceof TileChest) {
                    $player->sendMessage(MagicCrates::getPrefix() . "§cInteract with a chest to create a new crate.");
                    return;
                }

                // the chest is not empty
                if (sizeof($tile->getInventory()->getContents()) > 0) {
                    $player->sendMessage(MagicCrates::getPrefix() . " §cYou can only interact with an empty chest.");
                    return;
                }

                // we interacted with a paired chest
                if ($tile->isPaired()) {
                    $player->sendMessage(MagicCrates::getPrefix() . "§cYou cannot interact with a paired chest.");
                    return;
                }

                // check if a player is creating a crate
                if ($crate !== null) {
                    $player->sendMessage(MagicCrates::getPrefix() . " §cThere is already a crate at this position.");
                    return;
                }

                CrateForm::sendCreateForm($player, $block->getPosition());
                break;

            case CrateAction::REMOVE:
                if ($crate === null) {
                    $player->sendMessage(MagicCrates::getPrefix() . " §cThere is no crate at this position.");
                    return;
                }

                // send the remove form
                CrateForm::sendRemoveForm($player, $crate);
                break;

            case CrateAction::NONE:
                break;
        }

        // reset the player action
        PlayerCrateActions::getInstance()->setAction($player, CrateAction::NONE);

    }

    public function onBlockPlace(BlockPlaceEvent $e): void
    {
        $player = $e->getPlayer();
        $playerAction = PlayerCrateActions::getInstance()->getAction($player);

        if ($playerAction !== CrateAction::NONE) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cYou cannot place blocks while in crate create or remove mode.");
            $e->cancel();
        }
    }

    public function onBlockBreak(BlockBreakEvent $e): void
    {
        $player = $e->getPlayer();
        $playerAction = PlayerCrateActions::getInstance()->getAction($player);

        $block = $e->getBlock();
        $crate = Crate::getByPosition($block->getPosition());

        if ($crate === null) {
            // crate create mode is enabled
            if ($playerAction === CrateAction::CREATE) {
                $this->handleCrateAction($player, $block);
                $e->cancel();
                return;
            }

            // crate create or remove mode is enabled
            if ($playerAction !== CrateAction::NONE) {
                $player->sendMessage(MagicCrates::getPrefix() . " §cYou cannot break blocks while in crate creation or remove mode.");
                $e->cancel();
                return;
            }

            return;
        }


        // cancel the event
        $e->cancel();

        if (!$player->hasPermission("magiccrates.break.remove")) {
            // the player is not allowed to break a crate
            $player->sendMessage(MagicCrates::getPrefix() . "§cYou cannot break crates.");
            return;
        }

        // try and handle the players crate action
        $this->handleCrateAction($player, $block);
    }

    public function onChestPair(ChestPairEvent $e): void
    {
        // if the left or the right chest is a crate, cancel the event
        if (Crate::getByPosition($e->getLeft()->getPosition()) !== null ||
            Crate::getByPosition($e->getRight()->getPosition()) !== null) {
            $e->cancel();
        }
    }

    public function onJoin(PlayerJoinEvent $e): void
    {
        $player = $e->getPlayer();

        // show the particles
        Crate::showAllFloatingText($player);

        MagicCrates::getDatabase()->getUnreceivedRewards($player)
            ->onCompletion(function (array $rewards) use ($player) {
                if (sizeof($rewards) > 0) {
                    // notify the player that they have unreceived rewards
                    $player->sendToastNotification("You have unreceived rewards", "Execute '/mc receive' to receive all your rewards.");
                }
            }, function () use ($player) {
                $this->plugin->getLogger()->warning("Could not load the received rewards of {$player->getName()}");
            });
    }

    public function onWorldChange(EntityTeleportEvent $e): void
    {
        $player = $e->getEntity();
        if (!$player instanceof Player || $e->isCancelled()) return;

        $to = $e->getTo();
        $from = $e->getFrom();
        if ($to->getWorld()->getFolderName() === $from->getWorld()->getFolderName()) return;

        Crate::showAllFloatingText($player, $to->getWorld());
    }
}
