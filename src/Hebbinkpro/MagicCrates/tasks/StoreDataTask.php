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

namespace Hebbinkpro\MagicCrates\tasks;

use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\scheduler\Task;

/**
 * Task to store data, to prevent storing data after each update
 */
class StoreDataTask extends Task
{
    private static bool $cratesUpdated = false;
    private static bool $rewardedPlayersUpdated = false;
    private MagicCrates $plugin;

    public function __construct(MagicCrates $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Notify that the crates are updated
     * @return void
     */
    public static function updateCrates(): void
    {
        self::$cratesUpdated = true;
    }

    /**
     * Notify that the rewarded players are updated
     * @return void
     */
    public static function updateRewardedPlayers(): void
    {
        self::$rewardedPlayersUpdated = true;
    }

    public function onRun(): void
    {
        // only save the notified data
        if (self::$cratesUpdated) $this->plugin->saveCrates();
        if (self::$rewardedPlayersUpdated) $this->plugin->saveRewardedPlayers();
    }
}