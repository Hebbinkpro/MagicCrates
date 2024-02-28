<?php
/*
 *   __  __             _       _____           _
 *  |  \/  |           (_)     / ____|         | |
 *  | \  / | __ _  __ _ _  ___| |     _ __ __ _| |_ ___  ___
 *  | |\/| |/ _` |/ _` | |/ __| |    | '__/ _` | __/ _ \/ __|
 *  | |  | | (_| | (_| | | (__| |____| | | (_| | ||  __/\__ \
 *  |_|  |_|\__,_|\__, |_|\___|\_____|_|  \__,_|\__\___||___/
 *                 __/ |
 *                |___/
 *
 * Copyright (c) 2024 Hebbinkpro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Hebbinkpro\MagicCrates\crate;

use Hebbinkpro\MagicCrates\event\CrateOpenEvent;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
use pocketmine\block\tile\Chest;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ChestCloseSound;
use pocketmine\world\sound\ChestOpenSound;
use pocketmine\world\World;

class Crate
{


    /** @var array<string, Crate[][][]> */
    private static array $crates = [];

    private Vector3 $pos;
    private string $world;
    private CrateType $type;
    private FloatingTextParticle $floatingText;
    private ?Player $opener;

    public function __construct(Vector3 $pos, World|string $world, CrateType $type)
    {
        if (!is_string($world)) $world = $world->getFolderName();

        $this->pos = $pos;
        $this->world = $world;
        $this->type = $type;
        $this->floatingText = new FloatingTextParticle("", $this->type->getName());
        $this->opener = null;
    }

    /**
     * Parse a crate
     * @param array{x: int, y: int, z: int, world: string, type: string} $crate
     * @return Crate|null
     */
    public static function parse(array $crate): ?Crate
    {
        // check if the world with the given world name exists
        $worldName = $crate["world"];
        if (!is_dir(Server::getInstance()->getDataPath() . "worlds/$worldName")) {
            return null;
        }

        // get the vector 3 position of the crate
        $pos = new Vector3($crate["x"], $crate["y"], $crate["z"]);

        // get the crate type
        $type = CrateType::getById($crate["type"]);
        if ($type === null) return null;

        // create and cache the crate
        $crate = new Crate($pos, $worldName, $type);

        // there already exists a crate at this position
        if (!self::registerCrate($crate)) return null;

        return $crate;
    }

    /**
     * Add a crate to the cache
     * @param Crate $crate
     * @return bool
     */
    public static function registerCrate(Crate $crate): bool
    {
        $world = $crate->getWorldName();

        $pos = $crate->getPos();
        [$x, $y, $z] = [$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()];

        // There already exists a crate at this position
        if (isset(self::$crates[$world][$x][$y][$z])) return false;

        // add the crate to the cache
        if (!isset(self::$crates[$world])) self::$crates[$world] = [];
        if (!isset(self::$crates[$world][$x])) self::$crates[$world][$x] = [];
        if (!isset(self::$crates[$world][$x][$y])) self::$crates[$world][$x][$y] = [];
        self::$crates[$world][$x][$y][$z] = $crate;

        return true;
    }

    /**
     * Get the world name of the crates world
     * @return string
     */
    public function getWorldName(): string
    {
        return $this->world;
    }

    /**
     * Get the position of the crate
     * @return Position
     */
    public function getPos(): Position
    {
        return Position::fromObject($this->pos, $this->getWorld());
    }

    /**
     * @return World|null
     */
    public function getWorld(): ?World
    {
        return Server::getInstance()->getWorldManager()->getWorldByName($this->world);
    }

    /**
     * Show all floating text particles in the world to the player
     * @param Player $player
     * @param World|null $world
     * @return void
     */
    public static function showAllFloatingText(Player $player, ?World $world = null): void
    {
        if ($world === null) $world = $player->getWorld();

        $crates = self::$crates[$world->getFolderName()] ?? [];
        foreach ($crates as $yCrates) {
            foreach ($yCrates as $zCrates) {
                foreach ($zCrates as $crate) {
                    $crate->showFloatingText();
                }
            }
        }
    }

    /**
     * Show the crates floating text particle to the player
     * @param Player|null $player
     * @return void
     */
    public function showFloatingText(?Player $player = null): void
    {
        $this->floatingText->setInvisible(false);
        $this->addFloatingText($player);
    }

    /**
     * @param Player|Player[]|null $player
     * @return void
     */
    private function addFloatingText(null|Player|array $player = null): void
    {
        if ($player === null) $players = null;
        else if (is_array($player)) $players = $player;
        else $players = [$player];

        // only add particles if the world is loaded (not null)
        $this->getWorld()?->addParticle($this->getPos()->add(0.5, 1, 0.5), $this->floatingText, $players);
    }

    /**
     * Get a crate by its position
     * @param Position $pos
     * @return Crate|null
     */
    public static function getByPosition(Position $pos): ?Crate
    {
        $world = $pos->getWorld()->getFolderName();
        [$x, $y, $z] = [$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()];

        if (!isset(self::$crates[$world][$x][$y][$z])) return null;
        return self::$crates[$world][$x][$y][$z];
    }

    /**
     * Get the player that is currently opening the crate
     * @return Player|null
     */
    public function getOpener(): ?Player
    {
        return $this->opener;
    }

    /**
     * Check if the crate is open
     * @return bool
     */
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
    public function openWithKey(Player $player, Item $item = null): void
    {
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
     * Get the crate type
     * @return CrateType
     */
    public function getType(): CrateType
    {
        return $this->type;
    }

    /**
     * Open the crate
     * @param Player $player
     * @return void
     */
    public function open(Player $player): void
    {
        // get the random reward and execute the opening after the reward is fetched
        $this->type->getRandomReward($player, function (CrateReward $reward, int $playerRewarded) use ($player) {
            // reward the player
            $this->type->rewardPlayer($player, $reward, $playerRewarded);

            // send the reward message
            $player->sendMessage(MagicCrates::getPrefix() . " §eYou are opening §6{$this->type->getName()}§r§e...");
            $this->opener = $player;
            $this->hideFloatingText();

            (new CrateOpenEvent($this, $player))->call();

            MagicCrates::scheduleAnimationTask(new StartCrateAnimationTask($this, $reward, $player));

            // open the chest for all viewers
            $chest = $this->getWorld()?->getTile($this->pos);
            if ($chest instanceof Chest) {
                $chest->getInventory()->animateBlock(true);
                $this->getWorld()->addSound($this->pos->add(0.5, 0.5, 0.5), new ChestOpenSound());
            }
        });
    }

    /**
     * Hide the floating text for a player
     * @param Player|null $player
     * @return void
     */
    public function hideFloatingText(?Player $player = null): void
    {
        $this->floatingText->setInvisible();
        $this->addFloatingText($player);
    }

    /**
     * Close the crate
     * @return void
     */
    public function close(): void
    {
        $this->opener = null;
        $this->showFloatingText();


        // close the chest for all viewers
        $chest = $this->getWorld()?->getTile($this->pos);
        if ($chest instanceof Chest) {
            $chest->getInventory()->animateBlock(false);
            $this->getWorld()->addSound($this->pos->add(0.5, 0.5, 0.5), new ChestCloseSound());
        }
    }

    /**
     * Remove the crate
     * @return void
     */
    public function remove(): void
    {
        [$x, $y, $z] = [$this->pos->getFloorX(), $this->pos->getFloorY(), $this->pos->getFloorZ()];
        unset(self::$crates[$this->world][$x][$y][$z]);
        MagicCrates::getDatabase()->removeCrate($this);
    }
}