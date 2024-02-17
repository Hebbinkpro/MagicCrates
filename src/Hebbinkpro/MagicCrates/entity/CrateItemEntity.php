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
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
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
    public const Y_DIFF = 0.75;
    public const Y_REVEAL = 1;
    public const Y_DEST = 2;

    public float $width = 0.25;
    public float $height = 0.25;
    public bool $keepMovement = true;
    protected int $age = 0;
    protected ?Player $owner;
    protected float $spawnY;
    protected Crate $crate;
    protected ?Item $displayItem;
    protected int $itemCount;
    protected bool $reachedDest = false;
    protected float $destY;
    protected float $revealY;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ITEM;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick();
        if ($this->closed) return $hasUpdate;


        if (!$this->isFlaggedForDespawn() && $this->isAlive()) {
            // the relative distance the item has traveled
            $currentY = $this->getLocation()->getY() - $this->spawnY;

            if ($currentY < $this->destY) {
                if ($currentY < $this->revealY) {
                    $this->setInvisible();
                } else if ($this->isInvisible()) {
                    $this->setInvisible(false);
                    $this->setNameTagVisible();
                    $this->getWorld()->addSound($this->getPosition(), $this->getRevealSound());
                }

                $this->getLocation()->pitch = rad2deg(-pi() / 2);
                $this->move(0, 0.05, 0);
                $this->reachedDest = false;
            } else {
                $this->reachedDest = true;
                $this->move(0, 0, 0);
            }

            $this->age += $tickDiff;
            if ($this->age >= 100) {
                $this->flagForDespawn();
            }
        }

        return $hasUpdate;
    }

    /**
     * Get the sound that plays when the crate item despawns
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
        $nbt->setFloat("spawn-y", $this->spawnY);
        $nbt->setString("crate-pos", serialize($this->crate->getPos()->asVector3()));
        $nbt->setTag("display-item", $this->displayItem->nbtSerialize());
        $nbt->setInt("item-count", $this->itemCount);

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
        $this->spawnY = $nbt->getFloat("spawn-y");
        $this->crate = Crate::getByPosition(Position::fromObject(unserialize($nbt->getString("crate-pos")), $this->getWorld()));
        $this->itemCount = $nbt->getInt("item-count", 0);

        $itemTag = $nbt->getCompoundTag("display-item");
        $this->displayItem = $itemTag === null ? null : Item::nbtDeserialize($itemTag);

        // the distance the item has to travel before revealing
        $travelDist = $this->itemCount * self::Y_DIFF;
        // set the destination y
        $this->destY = $travelDist + self::Y_DEST;
        // set the y at which the item is revealed to the player
        $this->revealY = $travelDist;

        $this->setNameTagAlwaysVisible(false);
        $this->setInvisible();
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $networkSession = $player->getNetworkSession();

        $networkSession->sendDataPacket(AddItemActorPacket::create(
            $this->getId(),
            $this->getId(),
            ItemStackWrapper::legacy($networkSession->getTypeConverter()->coreItemStackToNet($this->displayItem ?? VanillaItems::AIR())),
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