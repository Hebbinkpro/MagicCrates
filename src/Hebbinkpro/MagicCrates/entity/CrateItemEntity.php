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

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\utils\ItemUtils;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\sound\PopSound;
use pocketmine\world\sound\Sound;

class CrateItemEntity extends Entity
{
    /** @var int The delay (in ages) for the item to start its animation */
    public const DELAY_AGE = 15;
    /** @var int The (relative) height above a crate the item should reach before the animation stops */
    public const DESTINATION_HEIGHT = 2;
    /** @var float The amount of blocks the item should move up per tick */
    public const MOVEMENT_SPEED = 0.05;

    public float $width = 0.25;
    public float $height = 0.25;
    public bool $keepMovement = true;
    protected int $age = 0;
    protected int $delay = 0;
    protected ?Player $owner;
    protected Crate $crate;
    protected ?Item $displayItem;
    protected int $commandCount;
    protected int $spawnDelay;
    protected bool $reachedDest = false;
    protected bool $revealed = false;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ITEM;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick();
        if ($this->closed) return $hasUpdate;


        if (!$this->isFlaggedForDespawn() && $this->isAlive()) {

            // calculate the distance we have moved based upon the age
            $ageDist = self::MOVEMENT_SPEED * max(0, ($this->age - $this->delay));

            if ($this->age < $this->delay) {
                // we cannot move
                $this->move(0, 0, 0);
            } else if ($ageDist < self::DESTINATION_HEIGHT) {
                // we have not yet reached the destination height

                if (!$this->revealed) {
                    // the item was not yet revealed (the age is now the delay age)
                    $this->getWorld()->addSound($this->getPosition(), $this->getRevealSound());
                    $this->revealed = true;
                }

                // set the movement
                $this->getLocation()->pitch = rad2deg(-pi() / 2);
                $this->move(0, self::MOVEMENT_SPEED, 0);
                $this->reachedDest = false;
            } else {
                // we have reached the destination height
                $this->reachedDest = true;
                $this->move(0, 0, 0);
            }

            // increase the age
            $this->age += $tickDiff;
            if ($this->age > 100) {
                $this->flagForDespawn();
            }
        }

        return $hasUpdate;
    }

    /**
     * Get the sound that plays when the crate item de-spawns
     * @return Sound
     */
    public function getRevealSound(): Sound
    {
        return new PopSound();
    }


    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setShort("age", $this->age);
        $nbt->setString("owner", $this->owner);
        $nbt->setString("crate-pos", serialize($this->crate->getPos()->asVector3()));
        $nbt->setInt("spawn-delay", $this->spawnDelay);
        $nbt->setTag("display-item", $this->displayItem->nbtSerialize());
        $nbt->setInt("command-count", $this->commandCount);

        return $nbt;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.25, 0.25);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->age = $nbt->getShort("age", 0);
        $this->owner = Server::getInstance()->getPlayerExact($nbt->getString("owner", ""));
        $this->crate = Crate::getByPosition(Position::fromObject(unserialize($nbt->getString("crate-pos")), $this->getWorld()));
        $this->spawnDelay = $nbt->getInt("spawn-delay", 0);
        $itemTag = $nbt->getCompoundTag("display-item");
        $this->displayItem = $itemTag === null ? null : Item::nbtDeserialize($itemTag);
        $this->commandCount = $nbt->getInt("command-count", 0);

        $this->delay = $this->spawnDelay * self::DELAY_AGE;

        $this->setNameTagAlwaysVisible(false);
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $networkSession = $player->getNetworkSession();

        // get the item stack based upon the display item
        if ($this->displayItem !== null) {
            $itemStack = $networkSession->getTypeConverter()->coreItemStackToNet($this->displayItem);
        } else {
            // no display item is given, so show a command block
            $itemStack = ItemUtils::getFakeItemStack(ItemUtils::COMMAND_BLOCK_NETWORK_ID, $this->commandCount);
        }

        $networkSession->sendDataPacket(AddItemActorPacket::create(
            $this->getId(),
            $this->getId(),
            ItemStackWrapper::legacy($itemStack),
            $this->location->asVector3(),
            $this->getMotion(),
            $this->getAllNetworkData(),
            false
        ));
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0;
    }

    protected function getInitialGravity(): float
    {
        return 0;
    }
}