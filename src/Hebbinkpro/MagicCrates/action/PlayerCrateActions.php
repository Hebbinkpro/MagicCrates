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

namespace Hebbinkpro\MagicCrates\action;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

/**
 * A class to store the current player crate actions
 */
class PlayerCrateActions
{

    use SingletonTrait;

    /** @var array<string, CrateAction> */
    private array $players;

    public function __construct()
    {
        $this->players = [];
    }

    /**
     * Set the player's crate action
     * @param Player $player
     * @param CrateAction $action
     * @return void
     */
    public function setAction(Player $player, CrateAction $action): void
    {
        $uuid = $player->getUniqueId()->getBytes();

        // if the action is none, remove the entry
        if ($action === CrateAction::NONE) unset($this->players[$uuid]);
        else $this->players[$player->getUniqueId()->getBytes()] = $action;
    }

    /**
     * Get the player's current crate action
     * @param Player $player
     * @return CrateAction
     */
    public function getAction(Player $player): CrateAction
    {
        return $this->players[$player->getUniqueId()->getBytes()] ?? CrateAction::NONE;
    }
}