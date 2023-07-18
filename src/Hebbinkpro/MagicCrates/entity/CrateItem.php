<?php


namespace Hebbinkpro\MagicCrates\entity;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateReward;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\event\CrateRewardEvent;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;

class CrateItem extends Entity
{
    public float $width = 0.25;
    public float $height = 0.25;
    protected int $age = 0;

    protected string $owner;
    protected float $spawnY;

    protected Crate $crate;
    protected CrateType $crateType;
    protected CrateReward $reward;

    public static function getNetworkTypeId(): string
    {
        return EntityIds::ITEM;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) return false;

        $hasUpdate = parent::entityBaseTick($tickDiff);

        if (!$this->isFlaggedForDespawn() and $this->isAlive()) {
            $y = $this->getLocation()->getY();

            if (($y - $this->spawnY) < 1.1) {
                $this->setNameTagAlwaysVisible(false);
                $this->getLocation()->pitch = rad2deg(-pi() / 2);
                $this->move(0, 0.05, 0);
            }
            if (($y - $this->spawnY) >= 1.1 and $this->age < 100) {
                $this->setNameTagAlwaysVisible();
                $this->move(0, 0, 0);
            }
            $this->age += $tickDiff;
            if ($this->age >= 100) {
                $this->flagForDespawn();
            }

        }

        if ($this->isFlaggedForDespawn()) {
            $owner = Server::getInstance()->getPlayerExact($this->owner);

            if ($owner instanceof Player) {

                $owner->getInventory()->addItem($this->reward->getItem());
                $owner->sendMessage(MagicCrates::PREFIX . " §aYou won §e" . $this->getNameTag());

                $this->crateType->executeCommands($owner, $this->reward);
            }

            (new CrateRewardEvent($this->crate, $owner, $this->reward))->call();
            $this->crate->setOpener(null);
            $this->crate->showFloatingText();

        }

        return $hasUpdate;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();

        $nbt->setShort("age", $this->age);
        $nbt->setString("owner", $this->owner);
        $nbt->setFloat("spawn-y", $this->spawnY);
        $nbt->setString("crate-pos", serialize($this->crate->getPos()->asVector3()));
        $nbt->setString("crate-type", $this->crateType->getId());
        $nbt->setString("reward", $this->reward->getName());

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
        $this->owner = $nbt->getString("owner");
        $this->spawnY = $nbt->getFloat("spawn-y");

        $this->crate = Crate::getByPosition(Position::fromObject(unserialize($nbt->getString("crate-pos")), $this->getWorld()));
        $this->crateType = CrateType::getById($nbt->getString("crate-type"));
        $this->reward = $this->crateType->getRewardByName($nbt->getString("reward"));
    }

    protected function tryChangeMovement(): void
    {
        $this->checkObstruction($this->getLocation()->x, $this->getLocation()->y, $this->getLocation()->z);
        parent::tryChangeMovement();
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $networkSession = $player->getNetworkSession();

        $networkSession->sendDataPacket(AddItemActorPacket::create(
            $this->getId(), //TODO: entity unique ID
            $this->getId(),
            ItemStackWrapper::legacy($networkSession->getTypeConverter()->coreItemStackToNet($this->reward->getItem())),
            $this->location->asVector3(),
            $this->getMotion(),
            $this->getAllNetworkData(),
            false //TODO: I have no idea what this is needed for, but right now we don't support fishing anyway
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
