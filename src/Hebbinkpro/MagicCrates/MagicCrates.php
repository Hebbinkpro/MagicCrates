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

namespace Hebbinkpro\MagicCrates;


use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use Hebbinkpro\MagicCrates\commands\MagicCratesCommand;
use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\crate\DynamicCrateReward;
use Hebbinkpro\MagicCrates\entity\CrateRewardItemEntity;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
use Hebbinkpro\MagicCrates\tasks\StoreDataTask;
use Hebbinkpro\MagicCrates\utils\CrateCommandSender;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\World;

class MagicCrates extends PluginBase
{
    private static string $prefix = "§r[§6Magic§cCrates§r]";
    private static string $keyName = "§r[§6Crate §cKey§r] §e{crate}";
    private static MagicCrates $instance;

    private array $notLoadedCrates = [];
    /** @var array<string, array<string, array<string, int>>> */
    private array $rewardedPlayers = [];

    public static function getPrefix(): string
    {
        return self::$prefix;
    }

    public static function getKeyName(): string
    {
        return self::$keyName;
    }

    /**
     * Schedule the start crate animation task with the delay given in the config
     * @param StartCrateAnimationTask $task
     * @return void
     */
    public static function scheduleAnimationTask(StartCrateAnimationTask $task): void
    {
        $delay = self::$instance->getConfig()->get("delay");
        self::$instance->getScheduler()->scheduleDelayedTask($task, $delay);
    }

    /**
     * Check if a plugin is enabled
     * @param string $pluginName
     * @return bool
     */
    public static function isPluginEnabled(string $pluginName): bool
    {

        $plManager = Server::getInstance()->getPluginManager();
        $plugin = $plManager->getPlugin($pluginName);

        return $plugin !== null && $plugin->isEnabled();
    }

    /**
     * Update the global rewarded players list
     * @param CrateType $type
     * @param DynamicCrateReward $reward
     * @return void
     */
    public static function setRewardedPlayers(CrateType $type, DynamicCrateReward $reward): void
    {
        $rewardedPlayers = self::$instance->rewardedPlayers[$type->getId()] ?? [];
        $rewardedPlayers[$reward->getId()] = $reward->getRewardedPlayers();
        self::$instance->rewardedPlayers[$type->getId()] = $rewardedPlayers;
        StoreDataTask::updateRewardedPlayers();
    }

    public function onLoad(): void
    {
        // register the crate item entity
        EntityFactory::getInstance()->register(CrateRewardItemEntity::class, function (World $world, CompoundTag $nbt): CrateRewardItemEntity {
            return new CrateRewardItemEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CrateItem']);
    }

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        self::$instance = $this;

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        CrateCommandSender::register($this);

        // store the config
        $this->saveResource("config.yml");
        self::$prefix = $this->getConfig()->get("prefix", self::$prefix);
        self::$keyName = $this->getConfig()->get("key-prefix", self::$keyName);

        // load the types and created crates
        $this->loadCrateTypes();
        $this->loadCrates();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command", ["mc"]));

        // store the data every 6000 ticks (~5 minutes)
        $this->getScheduler()->scheduleRepeatingTask(new StoreDataTask($this), 6000);
    }

    private function loadCrateTypes(): void
    {
        // check if crate_types.json exists, otherwise load the file
        $filePath = $this->getDataFolder() . "crate_types.json";
        if (!is_file($filePath)) $this->saveResource("crate_types.json");

        $file = file_get_contents($this->getDataFolder() . "crate_types.json");
        $crateTypes = json_decode($file, true);

        if ($crateTypes === null) {
            $this->getLogger()->warning("crate_types.json is corrupted, cannot load the crate types!");
            return;
        }

        $this->rewardedPlayers = [];
        if (is_file($this->getDataFolder() . "rewarded_players.json")) {
            $this->rewardedPlayers = json_decode(file_get_contents($this->getDataFolder() . "rewarded_players.json"), true) ?? [];
        }

        $errorMsg = "";
        // decode all crate types
        foreach ($crateTypes as $id => $type) {
            $crateType = CrateType::parse($id, $type, $this->rewardedPlayers[$id] ?? [], $errorMsg);
            if ($crateType === null) {
                $this->getLogger()->error("Could not load crate type: $id. $errorMsg");
            } else {
                $this->getLogger()->info("Loaded crate type: $id");
            }
        }
    }

    /**
     * Load all crates from the json file
     * @return void
     */
    private function loadCrates(): void
    {

        $crates = [];
        if (file_exists($this->getDataFolder() . "crates.json")) {
            // get the stored crates
            $fileData = file_get_contents($this->getDataFolder() . "crates.json");
            $crates = json_decode($fileData, true) ?? [];
        }

        // decode all crates
        $errorMsg = "";
        foreach ($crates as $cd) {
            $crate = Crate::decode($cd ?? [], $errorMsg);
            if ($crate === null) {
                // store the crate so that it will not be lost when all crates are saved
                $this->notLoadedCrates[] = $cd;
                $this->getLogger()->warning("Could not load crate of type '{$cd["type"]}' in world '{$cd["world"]}' at '{$cd["x"]},{$cd["y"]},{$cd["z"]}'.");
                $this->getLogger()->warning($errorMsg);
            }
        }

        $total = sizeof($crates);
        $loaded = $total - sizeof($this->notLoadedCrates);
        if ($total == $loaded) $this->getLogger()->info("Loaded all crates");
        else $this->getLogger()->info("Loaded $loaded out of $total crates");
    }

    public function onDisable(): void
    {
        // save the crates and players
        $this->saveCrates();
        $this->saveRewardedPlayers();

        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof CrateRewardItemEntity) $entity->flagForDespawn();
            }
        }
    }

    /**
     * Save all crates to the json file
     * @return void
     */
    public function saveCrates(): void
    {
        $crates = Crate::getAllCrates();

        $crateData = $this->notLoadedCrates;
        foreach ($crates as $worldCrates) {
            foreach ($worldCrates as $crate) {
                $crateData[] = $crate->encode();
            }
        }

        // store the crates
        file_put_contents($this->getDataFolder() . "crates.json", json_encode($crateData));
    }

    /**
     * Save the rewarded players list
     * @return void
     */
    public function saveRewardedPlayers(): void
    {
        file_put_contents($this->getDataFolder() . "rewarded_players.json", json_encode($this->rewardedPlayers));
    }
}