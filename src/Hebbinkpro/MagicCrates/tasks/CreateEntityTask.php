<?php


namespace Hebbinkpro\MagicCrates\tasks;

use Hebbinkpro\MagicCrates\entity\CrateItem;
use Hebbinkpro\MagicCrates\Main;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\Task;

class CreateEntityTask extends Task
{
	private $name;
	private $level;
	private $nbt;
	private $count;

	public function __construct(string $name, Level $level, CompoundTag $nbt, int $count = 1)
	{
		$this->name = $name;
		$this->level = $level;
		$this->nbt = $nbt;
		$this->count = $count;
	}

	public function onRun(int $currentTick)
    {
		$itemEntity = Entity::createEntity("CrateItem", $this->level, $this->nbt);

		if($itemEntity instanceof CrateItem){
			$itemEntity->setNameTag($this->name);
			if($this->count > 1){
				$itemEntity->setNameTag($this->name . " §r§6$this->count" . "x");
			}

			$itemEntity->spawnToAll();
		}
    }
}