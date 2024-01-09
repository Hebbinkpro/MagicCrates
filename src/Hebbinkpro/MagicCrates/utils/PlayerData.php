<?php

namespace Hebbinkpro\MagicCrates\utils;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

/**
 * A class to store temporary player data
 */
class PlayerData
{
    public const ACTION_TAG = "magic-crates-action";
    public const ACTION_NONE = 0;
    public const ACTION_CRATE_CREATE = 1;
    public const ACTION_CRATE_REMOVE = 2;

    use SingletonTrait;

    /** @var array<string, array<string, mixed>> */
    private array $values = [];

    public function setInt(Player $player, string $key, int $value): void
    {
        $this->set($player, $key, $value);
    }

    /**
     * Set data for a player
     * @param Player $player
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(Player $player, string $key, mixed $value): void
    {
        if (!isset($this->values[$key])) $this->values[$key] = [];
        $this->values[$key][$player->getName()] = $value;
    }

    public function setString(Player $player, string $key, int $value): void
    {
        $this->set($player, $key, $value);
    }

    public function setFloat(Player $player, string $key, int $value): void
    {
        $this->set($player, $key, $value);
    }

    public function setBool(Player $player, string $key, bool $value): void
    {
        $this->set($player, $key, $value);
    }

    public function getAll(): array
    {
        return $this->values;
    }

    public function getInt(Player $player, string $key, int $default = 0): int
    {
        return intval($this->get($player, $key, $default));
    }

    /**
     * Get a data value of a player
     * @param Player $player
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(Player $player, string $key, mixed $default = null): mixed
    {
        return $this->getAllValues($key)[$player->getName()] ?? $default;
    }

    public function getAllValues(string $key): array
    {
        return $this->values[$key] ?? [];
    }

    public function getString(Player $player, string $key, string $default = ""): string
    {
        return strval($this->get($player, $key, $default));
    }

    public function getFloat(Player $player, string $key, float $default = 0.0): float
    {
        return floatval($this->get($player, $key, $default));
    }

    public function getBool(Player $player, string $key, bool $default = false): bool
    {
        return boolval($this->get($player, $key, $default));
    }

    /**
     * Check if the player has data with the given key
     * @param Player $player
     * @param string $key
     * @return bool
     */
    public function has(Player $player, string $key): bool
    {
        return isset($this->values[$key]) && isset($this->values[$key][$player->getName()]);
    }
}