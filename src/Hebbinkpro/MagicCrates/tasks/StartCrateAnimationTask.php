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
use Hebbinkpro\MagicCrates\entity\CrateItem;
use pocketmine\entity\EntityDataHelper;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\Task;

class StartCrateAnimationTask extends Task
{
    private Crate $crate;
    private CrateReward $reward;
    private CompoundTag $nbt;

    public function __construct(Crate $crate, CrateReward $reward, CompoundTag $nbt)
    {
        $this->crate = $crate;
        $this->reward = $reward;
        $this->nbt = $nbt;
    }

    public function onRun(): void
    {
        // we cannot spawn an item in an unloaded world
        if (($world = $this->crate->getWorld()) === null) return;

        // create a new crate item
        $crateItem = new CrateItem(EntityDataHelper::parseLocation($this->nbt, $world), $this->nbt);
        $crateItem->setNameTag($this->reward->getName());
        $crateItem->spawnToAll();
    }
}