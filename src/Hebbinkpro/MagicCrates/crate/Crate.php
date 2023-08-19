<?php

namespace Hebbinkpro\MagicCrates\crate;

use Hebbinkpro\MagicCrates\event\CrateOpenEvent;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
use Hebbinkpro\MagicCrates\utils\EntityUtils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class Crate
{
    /** @var Crate[][] */
    private static array $crates = [];

    private Position $pos;
    private CrateType $type;
    private FloatingTextParticle $floatingText;
    private ?Player $opener;

    public function __construct(Position $pos, CrateType $type)
    {
        $this->pos = $pos;
        $this->type = $type;
        $this->floatingText = new FloatingTextParticle("", $this->type->getName());
        $this->opener = null;

        self::$crates[$pos->getWorld()->getFolderName()][] = $this;
    }

    /**
     * Decode an array to a Crate object
     * @param array{x: int, y: int, z: int, world: string, type: string} $crate
     * @return Crate|null
     */
    public static function decode(array $crate): ?Crate
    {
        $x = intval($crate["x"]);
        $y = intval($crate["y"]);
        $z = intval($crate["z"]);
        $worldName = strval($crate["world"]);

        $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
        if ($world === null) return null;
        $pos = new Position($x, $y, $z, $world);

        $typeId = strval($crate["type"]);
        $type = CrateType::getById($typeId);
        if ($type === null) return null;

        return new Crate($pos, $type);
    }

    public static function showAllFloatingText(Player $player, ?World $world = null): void
    {
        if ($world === null) $world = $player->getWorld();

        foreach (self::getCratesInWorld($world) as $crate) {
            $crate->showFloatingText($player);
        }
    }

    /**
     * Get all crates inside the given world
     * @param World $world
     * @return Crate[]
     */
    public static function getCratesInWorld(World $world): array
    {
        return self::$crates[$world->getFolderName()] ?? [];
    }

    public function showFloatingText(?Player $player = null): void
    {
        $this->floatingText->setInvisible(false);
        $this->addFloatingText($player);
    }

    private function addFloatingText(?Player $player): void
    {
        $players = null;
        if ($player !== null) $players = [$player];
        $this->pos->getWorld()->addParticle($this->getPos()->add(0.5, 1, 0.5), $this->floatingText, $players);
    }

    /**
     * @return Position
     */
    public function getPos(): Position
    {
        return $this->pos;
    }

    public static function getByPosition(Position $pos): ?Crate
    {
        foreach (self::getCratesInWorld($pos->getWorld()) as $crate) {
            if ($pos->equals($crate->getPos())) return $crate;
        }

        return null;
    }

    /**
     * @return Crate[][]
     */
    public static function getAllCrates(): array
    {
        return self::$crates;
    }

    /**
     * @return CrateType
     */
    public function getType(): CrateType
    {
        return $this->type;
    }

    public function getOpener(): ?Player
    {
        return $this->opener;
    }

    public function setOpener(?Player $player): void
    {
        $this->opener = $player;
    }

    public function isOpen(): bool
    {
        return $this->opener !== null;
    }

    /**
     * Open a crate
     * @param Player $player
     * @return void
     */
    public function open(Player $player): void
    {
        $reward = $this->getType()->getRandomReward();

        $spawnPos = $this->pos->add(0.5, 1, 0.5);

        $nbt = EntityUtils::createBaseNBT($spawnPos);
        $nbt->setString("owner", $player->getName());
        $nbt->setFloat("spawn-y", $spawnPos->getY());
        $nbt->setString("crate-pos", serialize($this->pos->asVector3()));
        $nbt->setString("crate-type", $this->type->getId());
        $nbt->setString("reward", $reward->getName());

        $player->sendMessage(MagicCrates::PREFIX . " §eYou are opening a §6{$this->type->getId()} §ecrate...");
        $this->opener = $player;
        $this->hideFloatingText();

        (new CrateOpenEvent($this, $player))->call();

        MagicCrates::scheduleAnimationTask(new StartCrateAnimationTask($this, $reward, $nbt));
    }

    public function hideFloatingText(?Player $player = null): void
    {
        $this->floatingText->setInvisible();
        $this->addFloatingText($player);
    }

    public function remove(): void
    {
        $key = array_search($this, self::$crates[$this->pos->getWorld()->getFolderName()]);
        array_splice(self::$crates, $key, 1);
    }

    /**
     * Encode a Crate object into an array
     * @return array{x: int, y: int, z: int, world: string, type: string}
     */
    public function encode(): array
    {
        return [
            "x" => $this->pos->getX(),
            "y" => $this->pos->getY(),
            "z" => $this->pos->getZ(),
            "world" => $this->pos->getWorld()->getFolderName(),
            "type" => $this->type->getId()
        ];
    }
}