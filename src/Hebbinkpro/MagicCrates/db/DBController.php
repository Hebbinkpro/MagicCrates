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

namespace Hebbinkpro\MagicCrates\db;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\crate\DynamicCrateReward;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class DBController
{
    private PluginBase $plugin;
    private DataConnector $database;

    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
        $this->database = libasynql::create($plugin, $plugin->getConfig()->get("database"), [
            "sqlite" => "sql/sqlite.sql",
            "mysql" => "sql/mysql.sql"
        ]);
    }


    /**
     * Load the database
     * @return void
     */
    public function load(): void
    {
        $this->database->executeGeneric("table.crates");
        $this->database->executeGeneric("table.rewards");
    }

    /**
     * Unload the database
     * @return void
     */
    public function unload(): void
    {
        if (isset($this->database)) {
            $this->database->close();
        }
    }

    /**
     * Add a crate
     * @param Crate $crate
     * @return Promise
     */
    public function addCrate(Crate $crate): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.crates.add", [
            "x" => $crate->getPos()->getFloorX(),
            "y" => $crate->getPos()->getFloorY(),
            "z" => $crate->getPos()->getFloorZ(),
            "world" => $crate->getWorldName(),
            "type" => $crate->getType()->getId()
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Remove a crate
     * @param Crate $crate
     * @return Promise
     */
    public function removeCrate(Crate $crate): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.crates.remove", [
            "x" => $crate->getPos()->getFloorX(),
            "y" => $crate->getPos()->getFloorY(),
            "z" => $crate->getPos()->getFloorZ(),
            "world" => $crate->getWorldName()
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }


    /**
     * Get all crates in a world
     * @return Promise<Crate[]>
     */
    public function getAllCrates(): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeSelect("data.crates.getAll", [
        ], function (array $rows) use ($promiseResolver) {
            $crates = [];
            foreach ($rows as $row) {
                $crate = Crate::parse($row);
                if ($crate !== null) $crates[] = $crate;
            }
            $promiseResolver->resolve($crates);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Get all rewards a player has received from the given crate type
     * @param CrateType $type
     * @param Player $player
     * @return Promise<array<string, int>>
     */
    public function getPlayerRewards(CrateType $type, Player $player): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeSelect("data.rewards.get", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->toString()
        ], function (array $rows) use ($promiseResolver) {
            $rewards = [];
            foreach ($rows as $row) {
                $rewards[$row["reward"]] = $row["amount"];
            }
            $promiseResolver->resolve($rewards);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Set the amount of rewards the player has received
     * @param CrateType $type
     * @param Player $player
     * @param DynamicCrateReward $reward
     * @param int $amount
     * @return Promise
     */
    public function setPlayerRewards(CrateType $type, Player $player, DynamicCrateReward $reward, int $amount): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.set", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->toString(),
            "reward" => $reward->getId(),
            "amount" => $amount
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Set the amount of rewards the player has received
     * @param string $type the crate type id
     * @param string $player the player uuid
     * @param string $reward the reward id
     * @param int $amount
     * @return Promise
     * @internal
     */
    public function setRawPlayerRewards(string $type, string $player, string $reward, int $amount): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.set", [
            "type" => $type,
            "player" => $player,
            "reward" => $reward,
            "amount" => $amount
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Get the total amounts of all rewards of the given type
     * @param CrateType $type
     * @return Promise<array<string, int>>
     */
    public function getRewardTotal(CrateType $type): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeSelect("data.rewards.getTotal", [
            "type" => $type->getId()
        ], function (array $rows) use ($promiseResolver) {
            $totals = [];

            foreach ($rows as $row) {
                $totals[$row["reward"]] = $row["total"];
            }

            $promiseResolver->resolve($totals);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Remove all rewards from a given type
     * @param CrateType $type
     * @return Promise
     */
    public function resetRewards(CrateType $type): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.reset", [
            "type" => $type->getId(),
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Remove all rewards from a given type
     * @param CrateType $type
     * @param DynamicCrateReward $reward
     * @return Promise
     */
    public function resetReward(CrateType $type, DynamicCrateReward $reward): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetReward", [
            "type" => $type->getId(),
            "reward" => $reward->getId()
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Remove all rewards of a player from the given type
     * @param CrateType $type
     * @param Player $player
     * @return Promise
     */
    public function resetPlayerRewards(CrateType $type, Player $player): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetPlayer", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->toString()
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Remove a reward of a player from the given type and reward
     * @param CrateType $type
     * @param Player $player
     * @param DynamicCrateReward $reward
     * @return Promise
     */
    public function resetPlayerReward(CrateType $type, Player $player, DynamicCrateReward $reward): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetPlayerReward", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->toString(),
            "reward" => $reward->getId()
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }
}