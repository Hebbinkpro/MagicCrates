<?php


namespace Hebbinkpro\MagicCrates\entity;

use Hebbinkpro\MagicCrates\Main;
use pocketmine\block\Water;
use pocketmine\entity\Entity;
use pocketmine\event\entity\ItemDespawnEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;
use pocketmine\Player;

use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\Vector3;

use function get_class;

class CrateItem extends Entity
{
	public const NETWORK_ID = self::ITEM;

	/** @var string */
	protected $owner = "";

	/** @var bool */
	protected $pickup = false;

	/** @var Item */
	protected $item;

	public $width = 0.25;
	public $height = 0.25;
	protected $baseOffset = 0.125;

	public $canCollide = false;

	protected $spawnX = 0.5;
	protected $spawnY = 1;
	protected $spawnZ = 0.5;

	protected $count = 1;
	protected $crateKey;
	protected $delay = 0;

	/** @var int */
	protected $age = 0;

	private $main;

	protected function initEntity() : void{
		$this->main = Main::getInstance();

		parent::initEntity();

		$this->setMaxHealth(5);
		$this->setImmobile(true);

		$this->setHealth($this->namedtag->getShort("Health", (int) $this->getHealth()));
		$this->age = $this->namedtag->getShort("Age", $this->age);
		$this->owner = $this->namedtag->getString("Owner", $this->owner);
		$this->spawnX = $this->namedtag->getShort("SpawnX", $this->spawnX);
		$this->spawnY = $this->namedtag->getShort("SpawnY", $this->spawnY);
		$this->spawnZ = $this->namedtag->getShort("SpawnZ", $this->spawnZ);
		$this->count = $this->namedtag->getShort("ItemCount", $this->count);
		$this->crateKey = $this->namedtag->getShort("CrateKey", $this->crateKey);
		if($this->count < 1) $this->count = 1;

		$itemTag = $this->namedtag->getCompoundTag("Item");
		if($itemTag === null){
			throw new \UnexpectedValueException("Invalid " . get_class($this) . " entity: expected \"CrateItem\" NBT tag not found");
		}

		$this->item = Item::nbtDeserialize($itemTag);
		if($this->item->isNull()){
			throw new \UnexpectedValueException("CrateItem for " . get_class($this) . " is invalid");
		}


		//(new ItemSpawnEvent($this))->call();
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->closed){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isFlaggedForDespawn() and $this->isAlive()){

			$x = $this->getX();
			$y = $this->getY();
			$z = $this->getZ();

			if(($y - $this->spawnY) < 1.1){
				$this->setNameTagAlwaysVisible(false);
				$this->pitch = rad2deg(-pi() / 2);
				$this->move(0,0.05,0);
			}
			if(($y - $this->spawnY) >= 1.1 and $this->age < 100){
				$this->setNameTagAlwaysVisible(true);
				$this->move(0,0,0);
			}
			$this->age += $tickDiff;
			if($this->age >= 100){
				$this->flagForDespawn();
			}

		}

		if($this->isFlaggedForDespawn() and !$this->pickup and $this->owner != ""){
			$owner = $this->main->getServer()->getPlayer($this->owner);

			if($owner instanceof Player and $owner->getInventory()->canAddItem($this->item)){
				$this->pickup = true;
				$lore = $this->item->getLore();
				$key = array_search("§7Pickup: §cfalse", $lore);
				unset($lore[$key]);
				$this->item->setLore($lore);
				$give = 0;
				while($give < $this->count){
					$owner->getInventory()->addItem($this->item);
					$give ++;
				}

				$owner->sendMessage("[§6Magic§cCrates§r] §aYou won §e".$this->getNameTag());
			}
			
			//set crate to closed
			$this->main->openCrates[$this->crateKey] = false;
		}

		return $hasUpdate;
	}

	protected function tryChangeMovement() : void{
		$this->checkObstruction($this->x, $this->y, $this->z);
		parent::tryChangeMovement();
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->setTag($this->item->nbtSerialize(-1, "CrateItem"));
		$this->namedtag->setShort("Health", (int) $this->getHealth());
	}

	public function getItem() : Item{
		return $this->item;
	}

	protected function sendSpawnPacket(Player $player) : void{
		$pk = new AddItemActorPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->item = $this->getItem();
		$pk->metadata = $this->propertyManager->getAll();

		$player->dataPacket($pk);
	}

}
