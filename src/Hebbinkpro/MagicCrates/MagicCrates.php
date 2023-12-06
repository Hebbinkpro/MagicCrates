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
use JsonException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class MagicCrates extends PluginBase
{
    public const KEY_NBT_TAG = "magic-crates-key";
    public const ACTION_TAG = "magic-crates-action";
    public const ACTION_NONE = 0;
    public const ACTION_CRATE_CREATE = 1;
    public const ACTION_CRATE_REMOVE = 2;
    private static string $prefix = "§r[§6Magic§cCrates§r]";
    private static string $keyName = "§r[§6Crate §cKey§r] §e{crate}";
    private static MagicCrates $instance;

    private array $notLoadedCrates = [];

    public static function getPrefix(): string
    {
        return self::$prefix;
    }

    public static function getKeyName(): string
    {
        return self::$keyName;
    }

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
        self::$prefix = $this->getConfig()->get("prefix", self::$prefix);
        self::$keyName = $this->getConfig()->get("key-prefix", self::$keyName);

        // load the types and created crates
        $this->loadCrateTypes();
        $this->loadCrates();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command", ["mc"]));
    }

    private function loadCrateTypes(): void
    {
        // check if crate_types.json exists, otherwise load the file
        $filePath = $this->getDataFolder() . "crate_types.json";
        if (!is_file($filePath)) $this->saveResource("crate_types.json");

        $file = file_get_contents($this->getDataFolder() . "crate_types.json");
        $crateTypes = json_decode($file, true);

        if ($crateTypes === null) {
            $this->getLogger()->warning("crate_types.json is corrupted, cannot load the crate types!");
            return;
        }

        $this->migrateCrateTypes($crateTypes);

        $errorMsg = "";
        // decode all crate types
        foreach ($crateTypes as $id => $type) {
            $crateType = CrateType::decode($id, $type, $errorMsg);
            if ($crateType === null) {
                $this->getLogger()->error("Could not load crate type: $id. $errorMsg");
            } else $this->getLogger()->info("Loaded crate type: $id");
        }
    }

    /**
     * Move all crate types from the config.yml to the crate_types.json
     * @param array $crateTypes
     * @return void
     */
    private function migrateCrateTypes(array $crateTypes): void
    {
        if (!$this->getConfig()->exists("types")) return;

        $this->getLogger()->notice("Found crates inside the config.yml, migrating them to crate_types.json");
        $this->getLogger()->notice("Please only create crates inside the crate_types.json file, as support for crates in the config.yml will be removed.");
        foreach ($this->getConfig()->get("types") as $id => $type) {
            if (isset($crateTypes[$id])) {
                $this->getLogger()->warning("Crate with id '$id' already exists in crate_types.json, the current crate in crate_types.json will be overwritten");
            }

            $crateTypes[$id] = $type;
        }

        // remove the types from the config
        $this->getConfig()->remove("types");
        try {
            $this->getConfig()->save();
        } catch (JsonException) {
            $this->getLogger()->error("Cannot update the config.yml");
        }

        // save the updated crate_types.json in pretty print
        file_put_contents($this->getDataFolder() . "crate_types.json", json_encode($crateTypes, JSON_PRETTY_PRINT));
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
        $errorMsg = "";
        foreach ($crates as $cd) {
            $crate = Crate::decode($cd ?? [], $errorMsg);
            if ($crate === null) {
                // store the crate so that it will not be lost when all crates are saved
                $this->notLoadedCrates[] = $cd;
                $this->getLogger()->warning("Could not load crate of type '{$cd["type"]}' in world '{$cd["world"]}' at '{$cd["x"]},{$cd["y"]},{$cd["z"]}'.");
                $this->getLogger()->warning($errorMsg);
            }
        }

        $total = sizeof($crates);
        $loaded = $total - sizeof($this->notLoadedCrates);
        if ($total == $loaded) $this->getLogger()->info("Loaded all crates");
        else $this->getLogger()->info("Loaded $loaded out of $total crates");
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