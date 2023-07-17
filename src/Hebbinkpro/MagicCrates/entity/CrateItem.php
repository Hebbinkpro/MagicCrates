<?php


namespace Hebbinkpro\MagicCrates\entity;

use Hebbinkpro\MagicCrates\Main;
use Hebbinkpro\MagicCrates\utils\CrateUtils;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;

class CrateItem extends Entity
{
    public float $width = 0.25;
    public float $height = 0.25;
    public bool $canCollide = false;
    protected string $owner;
    protected bool $pickup = false;
    protected Item $item;
    protected float $baseOffset = 0.125;
    protected float $spawnY;
    protected int $count;
    protected int $crateKey;
    protected int $age = 0;
    protected array $rewardCommands;

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

        if ($this->isFlaggedForDespawn() and !$this->pickup and $this->owner !== "") {
            $owner = Main::getInstance()->getServer()->getPlayerByPrefix($this->owner);

            if ($owner instanceof Player) {
                $this->pickup = true;

                if ($owner->getInventory()->canAddItem($this->item)) {
                    $lore = $this->item->getLore();
                    $key = array_search("§7Pickup: §cfalse", $lore);
                    unset($lore[$key]);
                    $this->item->setLore($lore);
                    $give = 0;
                    while ($give < $this->count) {
                        $owner->getInventory()->addItem($this->item);
                        $give++;
                    }

                    $owner->sendMessage(Main::prefix() . " §aYou won §e" . $this->getNameTag());
                } else $owner->sendMessage(Main::prefix() . " §cYour inventory is full");

                $crates = Main::getInstance()->crates->get("crates");
                $crateType = $crates[$this->crateKey]["type"];
                $crateData = Main::getInstance()->getConfig()->get("types")[$crateType];

                if (isset($crateData["commands"])) CrateUtils::sendCommands($crateData["commands"], $crateType, $owner, $this->item, $this->count);
                CrateUtils::sendCommands($this->rewardCommands, $crateType, $owner, $this->item, $this->count);
            }

            unset(Main::getInstance()->openCrates[$this->crateKey]);
        }

        return $hasUpdate;
    }

    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setTag("Item", $this->item->nbtSerialize());
        $nbt->setShort("Health", (int)$this->getHealth());
        $nbt->setShort("Age", $this->age);
        $nbt->setString("Owner", $this->owner);
        $nbt->setShort("SpawnY", $this->spawnY);
        $nbt->setShort("ItemCount", $this->count);
        $nbt->setShort("CrateKey", $this->crateKey);
        $nbt->setString("RewardCommands", json_encode($this->rewardCommands));

        return $nbt;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.25, 0.25);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);

        $this->age = $nbt->getShort("Age", 0);
        $this->owner = $nbt->getString("Owner");
        $this->spawnY = $nbt->getShort("SpawnY");
        $this->count = $nbt->getShort("ItemCount");
        $this->crateKey = $nbt->getShort("CrateKey");
        $this->rewardCommands = json_decode($nbt->getString("RewardCommands"));
        if ($this->count < 1) $this->count = 1;

        $itemTag = $nbt->getCompoundTag("Item");
        $this->item = Item::nbtDeserialize($itemTag);
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
            ItemStackWrapper::legacy($networkSession->getTypeConverter()->coreItemStackToNet($this->getItem())),
            $this->location->asVector3(),
            $this->getMotion(),
            $this->getAllNetworkData(),
            false //TODO: I have no idea what this is needed for, but right now we don't support fishing anyway
        ));
    }

    public function getItem(): Item
    {
        return $this->item;
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
