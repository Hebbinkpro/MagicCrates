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
use Hebbinkpro\MagicCrates\crate\CrateReward;
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
        $this->database->executeGeneric("table.receivedRewards");
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
     * Get all crates
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
        $this->database->executeSelect("data.rewards.getPlayerRewards", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->getBytes()
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
        $this->database->executeGeneric("data.rewards.setPlayerRewards", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->getBytes(),
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
        $this->database->executeGeneric("data.rewards.setPlayerRewards", [
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
     * Get the total number of received rewards by the given type
     * @param CrateType $type
     * @return Promise<array<string, int>>
     */
    public function getRewardTotal(CrateType $type): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeSelect("data.rewards.getRewardTotal", [
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
    public function resetCrateRewards(CrateType $type): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetCrateRewards", [
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
    public function resetCrateReward(CrateType $type, DynamicCrateReward $reward): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetCrateReward", [
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
    public function resetPlayerCrateRewards(CrateType $type, Player $player): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetPlayerCrateRewards", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->getBytes()
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
    public function resetPlayerCrateReward(CrateType $type, Player $player, DynamicCrateReward $reward): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetPlayerCrateReward", [
            "type" => $type->getId(),
            "player" => $player->getUniqueId()->getBytes(),
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
     * Remove all received rewards from a player
     * @param Player $player the player to remove the rewards from
     * @return Promise
     */
    public function resetPlayerRewards(Player $player): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.rewards.resetPlayerRewards", [
            "player" => $player->getUniqueId()->getBytes()
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Add a received reward for a player
     * @param Player|string $player
     * @param CrateType $type
     * @param CrateReward $reward
     * @return Promise
     */
    public function addReceivedReward(Player|string $player, CrateType $type, CrateReward $reward): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.received.addReward", [
            "player" => is_string($player) ? $player : $player->getUniqueId()->getBytes(),
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
     * Get all the received rewards of a player
     * @param Player $player
     * @return Promise<array<array{id: int, player: string, type: string, reward: string}>>
     */
    public function getReceivedRewards(Player $player): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeSelect("data.received.getRewards", [
            "player" => $player->getUniqueId()->getBytes()
        ], function (array $rows) use ($promiseResolver) {
            $promiseResolver->resolve($rows);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }

    /**
     * Remove a received reward with the given ID
     * @param int $id
     * @return Promise
     */
    public function removeReceivedReward(int $id): Promise
    {
        $promiseResolver = new PromiseResolver();
        $this->database->executeGeneric("data.received.removeReward", [
            "id" => $id
        ], function () use ($promiseResolver) {
            $promiseResolver->resolve(null);
        }, function (SqlError $error) use ($promiseResolver) {
            $this->plugin->getLogger()->warning($error->getErrorMessage());
            $promiseResolver->reject();
        });

        return $promiseResolver->getPromise();
    }


}