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

namespace Hebbinkpro\MagicCrates\entity;

use Hebbinkpro\MagicCrates\crate\CrateReward;
use Hebbinkpro\MagicCrates\event\CrateRewardEvent;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\sound\Sound;
use pocketmine\world\sound\XpLevelUpSound;

class CrateRewardItemEntity extends CrateItemEntity
{
    protected CrateReward $reward;
    protected bool $rewarded = false;

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        // if the destination is reached, make the name tag visible
        if ($this->reachedDest) {
            if (!$this->isNameTagVisible()) $this->setNameTagVisible();
        }


        if ($this->isFlaggedForDespawn() && !$this->rewarded) $this->reward();

        return $hasUpdate;
    }

    /**
     * Rewards the owner with the given reward
     * @return void
     */
    protected function reward(): void
    {

        if ($this->owner !== null && $this->owner->isOnline()) {
            foreach ($this->reward->getItems() as $item) {
                $this->owner->getInventory()->addItem($item);
            }
            $this->owner->sendMessage(MagicCrates::getPrefix() . " §aYou won §e" . $this->reward->getName());

            $this->crate->getType()->executeCommands($this->owner, $this->reward);
            (new CrateRewardEvent($this->crate, $this->owner, $this->reward))->call();
        }

        // close the crate
        $this->crate->close();

        $this->rewarded = true;
    }

    public function getRevealSound(): Sound
    {
        // play level up sound
        return new XpLevelUpSound(5);
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setString("reward", $this->reward->getId());
        return $nbt;
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->reward = $this->crate->getType()->getRewardById($nbt->getString("reward"));
        $this->setNameTag($this->reward->getName());
        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible(false);
    }
}
