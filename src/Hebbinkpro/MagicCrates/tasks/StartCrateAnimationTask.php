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

        // there are only commands in this the reward
        if (sizeof($this->reward->getItems()) == 0) {
            $this->spawnReward(null);
            return;
        }

        // spawn a crate item entity for all items
        foreach ($this->reward->getItems() as $i => $item) {
            if ($i == 0) $this->spawnReward($item);
            else $this->spawnItem($item, $i);
        }

        // if there are items and commands, add the commands at the end
        if (sizeof($this->reward->getCommands()) > 0) {
            // set the spawn delay to the number of items as size-1 will be used for the last item
            $this->spawnItem(null, sizeof($this->reward->getItems()));
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
     * @param int $spawnDelay
     * @return CompoundTag
     */
    private function getNbt(?Item $displayItem, int $spawnDelay = 0): CompoundTag
    {
        $pos = $this->crate->getPos();
        $spawnPos = $pos->add(0.5, 0, 0.5);

        $nbt = EntityUtils::createBaseNBT($spawnPos);
        $nbt->setString("owner", $this->player->getName());
        $nbt->setString("crate-pos", serialize($pos->asVector3()));
        $nbt->setInt("spawn-delay", $spawnDelay);

        // only set the display item if it exists, otherwise set the command count
        if ($displayItem !== null) $nbt->setTag("display-item", $displayItem->nbtSerialize());
        else $nbt->setInt("command-count", sizeof($this->reward->getCommands()));

        return $nbt;
    }

    /**
     * Spawn a default Crate Item entity
     * @param Item|null $displayItem
     * @param int $spawnDelay
     * @return void
     */
    private function spawnItem(?Item $displayItem, int $spawnDelay): void
    {
        $nbt = $this->getNbt($displayItem, $spawnDelay);

        $crateItem = new CrateItemEntity(EntityDataHelper::parseLocation($nbt, $this->crate->getWorld()), $nbt);
        $crateItem->spawnToAll();
    }
}