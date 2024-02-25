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

namespace Hebbinkpro\MagicCrates\migrate;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;

/**
 * Class that handles migration of old versions to new versions
 */
class Migrator
{
    public static function migrate(PluginBase $plugin): void
    {
        self::migrateCratesToDb($plugin);
        self::migrateRewardsToDb($plugin);
    }

    /**
     * Migrate the crates from crates.json to the database
     * @param PluginBase $plugin
     * @return void
     */
    private static function migrateCratesToDb(PluginBase $plugin): void
    {
        $file = $plugin->getDataFolder() . "crates.json";
        if (!file_exists($file)) return; // already migrated

        $contents = json_decode(file_get_contents($file), true);
        if ($contents === null) {
            $plugin->getLogger()->error("Could not migrate the crates in 'crates.json', invalid JSON data.");
            return;
        }

        $invalid = 0;
        foreach ($contents as $crateData) {
            $crate = Crate::parse($crateData);

            if ($crate === null) {
                // could not load the crate
                $plugin->getLogger()->warning("Could not parse crate " . json_encode($crateData));
                $invalid++;
                continue;
            }

            $pos = Position::fromObject($crate->getPos(), $crate->getWorld());

            if (Crate::getByPosition($pos) !== null) {
                // crate already exists at this position
                $plugin->getLogger()->warning("Cannot migrate the crate at " . $pos->getFloorX() . "," . $pos->getFloorY() . "," . $pos->getFloorZ() . "," . " in " . $crate->getWorldName() . ", there already exists a crate at this position.");
                $invalid++;
                continue;
            }

            // check if there already is an instance of this crate inside the db
            MagicCrates::getDatabase()->addCrate($crate)->onCompletion(function () use ($plugin, $crate) {
                // register the crate to the cache
                Crate::registerCrate($crate);
            }, function () use ($plugin, $crate, $pos) {
                $plugin->getLogger()->warning("Something went wrong while migrating the crate at '" . $crate->getType()->getId() . "' at " . $pos->getFloorX() . "," . $pos->getFloorY() . "," . $pos->getFloorZ() . "," . " in " . $crate->getWorldName() . ".");
            });

        }

        // remove the crates file
        unlink($file);

        if ($invalid == 0) {
            $plugin->getLogger()->info("All crates are successfully migrated to the database");
            return;
        }

        $total = sizeof($contents);
        $plugin->getLogger()->notice("Migrated " . ($total - $invalid) . "/$total crates");
    }


    /**
     * Migrate the player rewards in rewarded_players.json to the database
     * @param PluginBase $plugin
     * @return void
     */
    private static function migrateRewardsToDb(PluginBase $plugin): void
    {
        $file = $plugin->getDataFolder() . "rewarded_players.json";
        if (!file_exists($file)) return; // already rewarded

        $contents = json_decode(file_get_contents($file), true);
        if ($contents === null) {
            // invalid file content
            $plugin->getLogger()->error("Could not migrate the crates in 'rewarded_players.json', invalid JSON data.");
            return;
        }

        foreach ($contents as $typeId => $rewards) {
            foreach ($rewards as $rewardId => $players) {
                foreach ($players as $playerUUID => $amount) {
                    MagicCrates::getDatabase()->setRawPlayerRewards($typeId, $playerUUID, $rewardId, $amount)->onCompletion(fn() => null,
                        function () use ($plugin, $typeId, $playerUUID, $rewardId, $amount) {
                            // something went wrong while setting the amount
                            $plugin->getLogger()->warning("Cannot set the amount of rewards of player $playerUUID for reward $rewardId in crate $typeId");
                        });
                }
            }
        }

        unlink($file);
        $plugin->getLogger()->notice("All player rewards are migrated to the database.");
    }
}