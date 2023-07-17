<?php


namespace Hebbinkpro\MagicCrates;


use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use Hebbinkpro\MagicCrates\commands\MagicCratesCommand;
use Hebbinkpro\MagicCrates\entity\CrateItem;
use Hebbinkpro\MagicCrates\utils\FloatingTextUtils;
use JsonException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;

class Main extends PluginBase
{
    public static self $instance;
    public Config $crates;
    public array $createCrates = [];
    public array $removeCrates = [];
    public array $openCrates = [];
    public array $particles = [];

    public static function prefix(): string
    {
        return "[§6Magic§cCrates§r]";
    }

    public function onLoad(): void
    {
        EntityFactory::getInstance()->register(CrateItem::class, function (World $world, CompoundTag $nbt): CrateItem {
            return new CrateItem(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CrateItem']);
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        self::$instance = $this;

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);

        $this->saveResource("config.yml");

        $this->crates = new Config($this->getDataFolder() . "crates.yml", Config::YAML, [
            "crates" => []
        ]);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command"));

        FloatingTextUtils::initParticles();
    }

    /**
     * @throws JsonException
     */
    public function onDisable(): void
    {
        $this->crates->save();
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof CrateItem) $entity->flagForDespawn();
            }
        }
    }
}