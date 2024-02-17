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

namespace Hebbinkpro\MagicCrates\tasks;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateReward;
use Hebbinkpro\MagicCrates\entity\CrateItemEntity;
use Hebbinkpro\MagicCrates\entity\CrateRewardItemEntity;
use Hebbinkpro\MagicCrates\utils\EntityUtils;
use pocketmine\entity\EntityDataHelper;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class StartCrateAnimationTask extends Task
{
    private Crate $crate;
    private CrateReward $reward;
    private Player $player;

    public function __construct(Crate $crate, CrateReward $reward, Player $player)
    {
        $this->crate = $crate;
        $this->reward = $reward;
        $this->player = $player;
    }

    public function onRun(): void
    {
        // we cannot spawn an item in an unloaded world
        if (($world = $this->crate->getWorld()) === null || !$world->isLoaded()) return;

        if (sizeof($this->reward->getItems()) == 0) {
            $this->spawnReward(null);
            return;
        }

        // spawn a crate item entity for all items
        foreach ($this->reward->getItems() as $i => $item) {
            if ($i == 0) $this->spawnReward($item);
            else $this->spawnItem($item, $i);
        }
    }

    /**
     * Spawn a Reward Crate Item entity which gives the player the reward
     * @param Item|null $displayItem
     * @return void
     */
    private function spawnReward(?Item $displayItem): void
    {
        // set some reward exclusive tags
        $nbt = $this->getNbt($displayItem);
        $nbt->setString("reward", $this->reward->getId());

        // create a new crate item
        $crateItem = new CrateRewardItemEntity(EntityDataHelper::parseLocation($nbt, $this->crate->getWorld()), $nbt);
        $crateItem->spawnToAll();
    }

    /**
     * Construct the NBT required for a CrateItemEntity
     * @param Item|null $displayItem
     * @param int $itemCount
     * @return CompoundTag
     */
    private function getNbt(?Item $displayItem, int $itemCount = 0): CompoundTag
    {
        $pos = $this->crate->getPos();
        $spawnPos = $pos->add(0.5, 0, 0.5);

        $nbt = EntityUtils::createBaseNBT($spawnPos);
        $nbt->setString("owner", $this->player->getName());
        $nbt->setFloat("spawn-y", $spawnPos->getY());
        $nbt->setString("crate-pos", serialize($pos->asVector3()));
        $nbt->setInt("item-count", $itemCount);

        // only set the display item if it exists
        if ($displayItem !== null) $nbt->setTag("display-item", $displayItem->nbtSerialize());

        return $nbt;
    }

    /**
     * Spawn a default Crate Item entity
     * @param Item $displayItem
     * @param int $itemCount
     * @return void
     */
    private function spawnItem(Item $displayItem, int $itemCount): void
    {

        $nbt = $this->getNbt($displayItem, $itemCount);

        $crateItem = new CrateItemEntity(EntityDataHelper::parseLocation($nbt, $this->crate->getWorld()), $nbt);
        $crateItem->spawnToAll();
    }
}