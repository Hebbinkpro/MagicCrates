<?php

namespace Hebbinkpro\MagicCrates\crate;

use Hebbinkpro\MagicCrates\event\CrateOpenEvent;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
use Hebbinkpro\MagicCrates\utils\EntityUtils;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
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
     * Open the crate and remove the key from the players inventory
     * @param Player $player
     * @param Item|null $item
     * @return void
     */
    public function openWithKey(Player $player, Item $item = null): void {
        $inv = $player->getInventory();

        if ($item === null) $item = $this->getType()->getKeyFromPlayer($player);

        // it is an invalid crate key, or the player does not have it in their inventory
        if ($item === null || !$this->getType()->isValidKey($item) || !$inv->contains($item)) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cYou don't have a valid {$this->getType()->getId()} crate key!");
            return;
        }

        // the player has less than 2 free slots
        if (!$inv->canAddItem(VanillaItems::DIAMOND_SWORD()->setCount(2))) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cYour inventory is full, come back later when your have enough space in your inventory!");
            return;
        }

        // get the key as a single item
        $key = (clone $item)->setCount(1);

        // remove the key from the inventory
        $player->getInventory()->removeItem($key);

        // open the crate
        $this->open($player);
    }

    /**
     * Open the crate
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

        $player->sendMessage(MagicCrates::getPrefix() . " §eYou are opening a §6{$this->type->getId()} §ecrate...");
        $this->opener = $player;
        $this->hideFloatingText();

        (new CrateOpenEvent($this, $player))->call();

        MagicCrates::scheduleAnimationTask(new StartCrateAnimationTask($this, $reward, $nbt));
    }

    /**
     * @return CrateType
     */
    public function getType(): CrateType
    {
        return $this->type;
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