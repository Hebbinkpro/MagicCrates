<?php


namespace Hebbinkpro\MagicCrates\tasks;

use Hebbinkpro\MagicCrates\entity\CrateItem;
use pocketmine\entity\EntityDataHelper;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\Task;
use pocketmine\world\World;

class CreateEntityTask extends Task
{
    public function __construct(private string $name, private World $world, private CompoundTag $nbt, private int $count = 1)
    {

    }

    public function onRun(): void
    {
        $itemEntity = new CrateItem(EntityDataHelper::parseLocation($this->nbt, $this->world), $this->nbt);

        if ($itemEntity instanceof CrateItem) {
            if ($this->count > 1) $itemEntity->setNameTag($this->name . " §r§6$this->count" . "x");
            else $itemEntity->setNameTag($this->name);

            $itemEntity->spawnToAll();
        }
    }
}