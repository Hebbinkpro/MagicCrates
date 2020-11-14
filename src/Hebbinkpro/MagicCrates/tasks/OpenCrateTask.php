<?php


namespace Hebbinkpro\MagicCrates\tasks;

use Hebbinkpro\MagicCrates\entity\CrateItem;

use pocketmine\entity\Entity;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class OpenCrateTask
{

	public static function spawnItem(int $itemId, int $itemMeta, string $name, string $lore, array $enchantments, Vector3 $chestPos, Level $level){

		$spawnX = $chestPos->getFloorX()+0.5;
		$spawnY = $chestPos->getFloorY();
		$spawnZ = $chestPos->getFloorZ()+0.5;
		$spawnPos = new Vector3($spawnX, $spawnY+1, $spawnZ);

		$item = new Item($itemId, $itemMeta, $name);

		//add enchantments
		foreach ($enchantments as $ench){
			$id = $ench["id"];
			$lvl = $ench["level"];

			$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($id), $lvl));
		}

		//set lore
		$item->setLore([$lore, "§7Pickup: §cfalse"]);

		//create itemEntity
		$itemTag = $item->nbtSerialize();
		$itemTag->setName("CrateItem");
		$motion = new Vector3(00, 1, 0);
		$nbt= Entity::createBaseNBT($chestPos);
		$nbt->setShort("Health", 5);
		$nbt->setTag($itemTag);

		$itemEntity = CrateItem::activateEntity($level, $nbt, $spawnPos);


		if($itemEntity instanceof CrateItem){
			$itemEntity->spawnToAll();
		}

	}

}