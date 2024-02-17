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

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\utils\CrateForm;
use Hebbinkpro\MagicCrates\utils\PlayerData;
use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\ChestPairEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;

class EventListener implements Listener
{
    private MagicCrates $plugin;

    public function __construct(MagicCrates $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onInteractChest(PlayerInteractEvent $e): void
    {
        $block = $e->getBlock();

        // when block isn't a chest or when it's a left click interaction return
        if (!$block instanceof Chest || $e->getAction() == PlayerInteractEvent::LEFT_CLICK_BLOCK) return;

        $pos = $block->getPosition();

        // get the chest tile
        $tile = $pos->getWorld()->getTile($pos);
        // the crate isn't a TileChest, or it's a double chest
        if (!$tile instanceof TileChest) return;

        $player = $e->getPlayer();
        $playerAction = PlayerData::getInstance()->getInt($player, PlayerData::ACTION_TAG, PlayerData::ACTION_NONE);

        if ($tile->isPaired() && $playerAction > PlayerData::ACTION_NONE) {
            $player->sendMessage(MagicCrates::getPrefix() . "§c You cannot interact with a paired chest!");
            $e->cancel();
            return;
        }

        $crate = Crate::getByPosition($block->getPosition());

        // check if player is creating a crate
        if ($playerAction == PlayerData::ACTION_CRATE_CREATE) {
            $this->createCrate($player, $crate, $pos);
            $e->cancel();
            return;
        }

        // check if player is removing a crate
        if ($playerAction == PlayerData::ACTION_CRATE_REMOVE) {
            $this->removeCrate($player, $crate);
            $e->cancel();
            return;
        }

        if ($crate !== null) {
            $item = $e->getItem();
            $this->openCrate($player, $crate, $item);
            $e->cancel();
        }
    }


    private function createCrate(Player $player, ?Crate $crate, Position $pos): void
    {

        if ($crate !== null) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cThere is already a crate on this position.");
            return;
        }

        PlayerData::getInstance()->setInt($player, PlayerData::ACTION_TAG, PlayerData::ACTION_NONE);

        $form = new CrateForm($pos);
        $form->sendCreateForm($player);
    }

    private function removeCrate(Player $player, ?Crate $crate): void
    {

        if ($crate === null) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cThere is no crate on this position.");
            return;
        }

        PlayerData::getInstance()->setInt($player, PlayerData::ACTION_TAG, PlayerData::ACTION_NONE);

        $form = new CrateForm($crate->getPos());
        $form->sendRemoveForm($player);
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
            $form = new CrateForm($crate->getPos());
            $form->sendPreviewForm($player);
            return;
        }

        $crate->openWithKey($player, $item);
    }

    public function onBlockBreak(BlockBreakEvent $e): void
    {
        $player = $e->getPlayer();
        $playerAction = PlayerData::getInstance()->getInt($player, PlayerData::ACTION_TAG, PlayerData::ACTION_NONE);

        if ($playerAction > PlayerData::ACTION_NONE) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cYou can't break blocks while in crate create or remove mode");
            $e->cancel();
            return;
        }

        $block = $e->getBlock();
        $crate = Crate::getByPosition($block->getPosition());

        if ($player->hasPermission("magiccrates.break.remove") && $crate !== null) {
            $form = new CrateForm($crate->getPos());
            $form->sendRemoveForm($player);
            $e->cancel();
        }
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
        Crate::showAllFloatingText($player);
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
