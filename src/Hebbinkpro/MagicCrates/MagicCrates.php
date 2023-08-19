<?php


namespace Hebbinkpro\MagicCrates;


use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use Hebbinkpro\MagicCrates\commands\MagicCratesCommand;
use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\entity\CrateItem;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
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

    private static MagicCrates $instance;

    private array $notLoadedCrates = [];

    /**
     * Schedule the start crate animation task with the delay given in the config
     * @param StartCrateAnimationTask $task
     * @return void
     */
    public static function scheduleAnimationTask(StartCrateAnimationTask $task): void
    {
        $delay = self::$instance->getConfig()->get("delay");
        self::$instance->getScheduler()->scheduleDelayedTask($task, $delay);
    }

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
        self::$instance = $this;

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        CrateCommandSender::register($this);

        // store the config
        $this->saveResource("config.yml");

        // load the types and created crates
        $this->loadCrateTypes();
        $this->loadCrates();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command"));
    }

    private function loadCrateTypes(): void
    {
        $errorMsg = "";
        // decode all crate types
        foreach ($this->getConfig()->get("types") as $id => $type) {
            $crateType = CrateType::decode($id, $type, $errorMsg);
            if ($crateType === null) {
                $this->getLogger()->error("Could not load crate type: $id. $errorMsg");
            } else $this->getLogger()->info("Loaded crate type: $id");
        }
    }

    /**
     * Load all crates from the json file
     * @return void
     */
    private function loadCrates(): void
    {

        $crates = [];
        if (file_exists($this->getDataFolder() . "crates.json")) {
            // get the stored crates
            $fileData = file_get_contents($this->getDataFolder() . "crates.json");
            $crates = json_decode($fileData, true) ?? [];
        }

        // decode all crates
        foreach ($crates as $cd) {
            $crate = Crate::decode($cd ?? []);
            if ($crate === null) {
                // store the crate so that it will not be lost when all crates are saved
                $this->notLoadedCrates[] = $cd;
                $this->getLogger()->warning("Could not load crate of type '{$cd["type"]}' in world '{$cd["world"]}' at '{$cd["x"]},{$cd["y"]},{$cd["z"]}'.");
            }
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

        $crateData = $this->notLoadedCrates;
        foreach ($crates as $worldCrates) {
            foreach ($worldCrates as $crate) {
                $crateData[] = $crate->encode();
            }
        }

        // store the crates
        file_put_contents($this->getDataFolder() . "crates.json", json_encode($crateData));
    }


}