<?php


namespace Hebbinkpro\MagicCrates\tasks;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateReward;
use Hebbinkpro\MagicCrates\entity\CrateItem;
use pocketmine\entity\EntityDataHelper;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\Task;

class StartCrateAnimationTask extends Task
{
    private Crate $crate;
    private CrateReward $reward;
    private CompoundTag $nbt;

    public function __construct(Crate $crate, CrateReward $reward, CompoundTag $nbt)
    {
        $this->crate = $crate;
        $this->reward = $reward;
        $this->nbt = $nbt;
    }

    public function onRun(): void
    {
        $itemEntity = new CrateItem(EntityDataHelper::parseLocation($this->nbt, $this->crate->getPos()->getWorld()), $this->nbt);

        if ($itemEntity instanceof CrateItem) {
            $itemEntity->setNameTag($this->reward->getName());
            $itemEntity->spawnToAll();
        }
    }
}