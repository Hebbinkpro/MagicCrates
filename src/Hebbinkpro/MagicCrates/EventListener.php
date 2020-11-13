<?php


namespace Hebbinkpro\MagicCrates;

use Hebbinkpro\MagicCrates\entity\CrateItem;
use Hebbinkpro\MagicCrates\forms\CrateForm;
use Hebbinkpro\MagicCrates\tasks\CreateEntityTask;
use Hebbinkpro\MagicCrates\tasks\OpenCrateTask;
use pocketmine\Player;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemIds;
use pocketmine\block\BlockIds;
use pocketmine\block\Chest;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\level\particle\FloatingTextParticle;

class EventListener implements Listener
{

	private $main;
	private $config;
	private $crates;

	public function __construct()
	{
		$this->main = Main::getInstance();
		$this->config = $this->main->getConfig();
		$this->main->reloadCrates();
		$this->crates = $this->main->crates;

	}

	public function onInteractChest(PlayerInteractEvent $e){
		$player = $e->getPlayer();
		$block = $e->getBlock();
		$item = $e->getItem();

		//check if block is a chest
		if($block instanceof Chest){

			$bX = $block->getFloorX();
			$bY = $block->getFloorY();
			$bZ = $block->getFloorZ();
			$bLevel = $block->getLevel();

			$crateType = $this->getCrateType($block);

			if(isset($this->main->createCrates[$player->getName()])){
				if($this->main->createCrates[$player->getName()] === true){
					if(!$crateType){
						$form = new CrateForm($bX, $bY, $bZ, $bLevel->getName());
						$form->sendCreateForm($player);
						$e->setCancelled();
						return;
					}else{
						$player->sendMessage("[§6Magic§cCrates§r] §cThis crate is already registerd");
						$e->setCancelled();
						return;

					}
				}
			}

			if(isset($this->main->removeCrates[$player->getName()])){
				if($this->main->removeCrates[$player->getName()] === true){
					if($crateType != false){
						$form = new CrateForm($bX, $bY, $bZ, $bLevel->getName());
						$form->sendRemoveForm($player);
						$e->setCancelled();
						return;
					}else{
						$player->sendMessage("[§6Magic§cCrates§r] §cThis chest isn't a crate");
						$e->setCancelled();
						return;
					}
				}
			}

			if(!$crateType) {
				return;
			}

			$crateKey = $this->getCrateKey($block);
			if(isset($this->main->openCrates[$crateKey])){
				if($this->main->openCrates[$crateKey] != false){
					$who = $this->main->openCrates[$crateKey];
					$player->sendMessage("[§6Magic§cCrates§r] §cYou have to wait. §e$who §r§cis opening a crate");
					$e->setCancelled();
					return;
				}
			}
			
			if($item->getId() === ItemIds::PAPER ){
				if(!in_array("§6Magic§cCrates §7Key - " . $crateType, $item->getLore()) or $item->getCustomName() != "§e" . $crateType . " §r§dCrate Key"){
					$player->sendMessage("[§6Magic§cCrates§r] §cUse a crate key to open this §e$crateType §r§ccrate");
					$e->setCancelled();
					return;
				}
				//create the key
				$key = new Item(ItemIds::PAPER);
				$key->setCustomName("§e" . $crateType . " §r§dCrate Key");
				$key->setLore(["§6Magic§cCrates §7Key - " . $crateType]);
				$key->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 1));
				//remove the key from the inventory
				$player->getInventory()->removeItem($key);

			}else{
				$player->sendMessage("[§6Magic§cCrates§r] §cUse a crate key to open this §e$crateType §r§ccrate");
				$e->setCancelled();
				return;
			}

			$crate = $this->getCrateContent($crateType);
			if(!$crate) {
				$player->sendMessage("[§6Magic§cCrates§r] §cSomething went wrong");
				return;
			}

			$reward = $this->getReward($crate["rewards"]);
			if(!$reward) {
				$player->sendMessage("[§6Magic§cCrates§r] §cSomething went wrong");
				return;
			}

			//get reward data
			$name = $reward["name"];
			$id = $reward["id"];
			$meta = 0;
			if(isset($reward["meta"])){
			    $meta = $reward["meta"];
            }
			$count = 1;
			if(isset($reward["amount"])){
                $count = $reward["amount"];
            }
			$lore = null;
			if(isset($reward["lore"])){
                $lore = $reward["lore"];
            }
			$enchantments = [];
			if(isset($reward["enchantments"])){
                $enchantments = $reward["enchantments"];
            }


			//create item
			$item = new Item($id, $meta, $name);
			$item->setCustomName($name);
			$item->setLore([$lore, "\n§a$crateType §r§6Crate", "§7Pickup: §cfalse"]);
			foreach ($enchantments as $ench){
				$ename = $ench["name"];
				$lvl = intval($ench["level"]);
				$enchantment = Enchantment::getEnchantmentByName($ename);
				if($enchantment instanceof Enchantment){
                    $item->addEnchantment(new EnchantmentInstance($enchantment, $lvl));
                }
			}

			//create spawn position
			$spawnX = $bX + 0.5;
			$spawnY = $bY + 1;
			$spawnZ = $bZ + 0.5;
			$spawnPos = new Vector3($spawnX, $spawnY, $spawnZ);

			//set crate in opening state
			$this->main->openCrates[$crateKey] = $player->getName();

			//create nbt
			$itemTag = $item->nbtSerialize();
			$itemTag->setName("Item");
			$nbt= Entity::createBaseNBT($spawnPos);
			$nbt->setShort("Health", 5);
			$nbt->setString("Owner", $player->getName());
			$nbt->setShort("SpawnX", $spawnX);
			$nbt->setShort("SpawnY", $spawnY);
			$nbt->setShort("SpawnZ", $spawnZ);
			$nbt->setShort("ItemCount", $count);
			$nbt->setShort("CrateKey", $crateKey);
			$nbt->setTag($itemTag);

			//create entity
			$delay = $this->config->get("delay") * 20;
			if(!is_int($delay)){
				$delay = 0;
			}
			$this->main->getScheduler()->scheduleDelayedTask(new CreateEntityTask($name, $bLevel, $nbt, $count), $delay);

			$e->setCancelled();
		}

	}

	public function onPickup(InventoryPickupItemEvent $e){
		$item = $e->getItem();
		$lore = $item->getItem()->getLore();
		if(in_array("§7Pickup: §cfalse", $lore)){
			$e->setCancelled();
		}
	}

	public function onBlockBreak(BlockBreakEvent $e){
		$player = $e->getPlayer();
		$block = $e->getBlock();
		if($player->hasPermission("mc.remove")){
			if($this->getCrateType($block) != false){
				$e->setCancelled();

				$bX = $block->getFloorX();
				$bY = $block->getFloorY();
				$bZ = $block->getFloorZ();
				$bLevel = $block->getLevel();

				$form = new CrateForm($bX, $bY, $bZ, $bLevel->getName(), $e);
				$form->sendRemoveForm($player);
				//$e->setCancelled();
				return;
			}
		}
	}

	public function onJoin(PlayerJoinEvent $e){
		$player = $e->getPlayer();
		if($player instanceof Player){
			$this->main->loadAllParticles($player);
		}
	}

	public function onLevelChange(EntityLevelChangeEvent $e){
		$player = $e->getEntity();
		$dest = $e->getTarget();
		if($player instanceof Player){
			$this->main->loadAllParticles($player, $dest);
		}
	}






	/**
	 * @param Block $block
	 * @return false|array
	 */
	public function getCrateType(Block $block){
		if($block instanceof Chest){
			$cX = $block->getFloorX();
			$cY = $block->getFloorY();
			$cZ = $block->getFloorZ();
			$cLevel = $block->getLevel()->getName();

			foreach($this->crates->get("crates") as $crate){
				if($crate["x"] === $cX and $crate["y"] === $cY and $crate["z"] === $cZ and $crate["level"] === $cLevel){
					return $crate["type"];
				}
			}
		}

		return false;
	}

	/**
	 * @param Block $block
	 * @return false|int
	 */
	public function getCrateKey(Block $block){
		if($block instanceof Chest){
			$cX = $block->getFloorX();
			$cY = $block->getFloorY();
			$cZ = $block->getFloorZ();
			$cLevel = $block->getLevel()->getName();

			foreach($this->crates->get("crates") as $key=>$crate){
				if($crate["x"] === $cX and $crate["y"] === $cY and $crate["z"] === $cZ and $crate["level"] === $cLevel){
					return $key;
				}
			}
		}

		return false;
	}

	/**
	 * @param $type
	 * @return false|array
	 */
	public function getCrateContent($type){
		foreach($this->config->get("types") as $key=>$crateType){
			if($key === $type){
				return $crateType;
			}
		}
		return false;
	}

	public function getReward(array $items){

		$rewards = [];
		foreach($items as $item){
			$change = $item["change"];
			$i = 0;
			while($i < $change){
				$i++;
				$rewards[] = $item;
			}
		}
		if($rewards === []){
			return false;
		}
		$reward = array_rand($rewards, 1);
		return $rewards[$reward];

	}

}