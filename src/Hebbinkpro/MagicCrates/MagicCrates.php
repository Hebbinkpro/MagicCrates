<?php


namespace Hebbinkpro\MagicCrates;


use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use Hebbinkpro\MagicCrates\commands\MagicCratesCommand;
use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\entity\CrateItem;
use Hebbinkpro\MagicCrates\utils\CrateCommandSender;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class MagicCrates extends PluginBase
{
    public const PREFIX = "§r[§6Magic§cCrates§r]";

    public const KEY_NBT_TAG = "magic-crates-key";

    public const ACTION_TAG = "magic-crates-action";
    public const ACTION_NONE = 0;
    public const ACTION_CRATE_CREATE = 1;
    public const ACTION_CRATE_REMOVE = 2;

    public function onLoad(): void
    {
        // register the crate item entity
        EntityFactory::getInstance()->register(CrateItem::class, function (World $world, CompoundTag $nbt): CrateItem {
            return new CrateItem(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CrateItem']);
    }

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        CrateCommandSender::register($this);

        $this->saveResource("config.yml");
        $this->loadAllCrates();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command"));

    }

    /**
     * Load all crates from the json file
     * @return void
     */
    private function loadAllCrates(): void
    {
        // decode all crate types
        foreach ($this->getConfig()->get("types") as $id => $type) {
            $crateType = CrateType::decode($id, $type);
            if ($crateType === null) $this->getLogger()->warning("Could not load crate type: $id");
            else $this->getLogger()->info("Loaded crate type: $id");
        }


        $crates = [];
        if (file_exists($this->getDataFolder() . "crates.json")) {
            // get the stored crates
            $fileData = file_get_contents($this->getDataFolder() . "crates.json");
            $crates = json_decode($fileData, true) ?? [];
        }

        // decode all crates
        foreach ($crates as $cd) {
            $crate = Crate::decode($cd);
            if ($crate === null) $this->getLogger()->warning("Could not load crate: at '{$cd["x"]},{$cd["y"]},{$cd["z"]}', in world '{$cd["world"]}', of type '{$cd["type"]}'");
        }
    }

    public function onDisable(): void
    {
        $this->saveCrates();

        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof CrateItem) $entity->flagForDespawn();
            }
        }
    }

    /**
     * Save all crates to the json file
     * @return void
     */
    public function saveCrates(): void
    {
        $crates = Crate::getAllCrates();

        $crateData = [];
        foreach ($crates as $worldCrates) {
            foreach ($worldCrates as $crate) {
                $crateData[] = $crate->encode();
            }
        }

        // store the crates
        file_put_contents($this->getDataFolder() . "crates.json", json_encode($crateData));
    }


}