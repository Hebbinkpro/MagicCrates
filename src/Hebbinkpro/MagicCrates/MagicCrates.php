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
use Hebbinkpro\MagicCrates\db\DBController;
use Hebbinkpro\MagicCrates\entity\CrateRewardItemEntity;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
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
    private DBController $database;

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
     * Get the database controller
     * @return DBController
     */
    public static function getDatabase(): DBController
    {
        return self::$instance->database;
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
        $this->saveDefaultConfig();
        self::$prefix = $this->getConfig()->get("prefix", self::$prefix);
        self::$keyName = $this->getConfig()->get("key-prefix", self::$keyName);

        $this->database = new DBController($this);

        $p = $this->database->getAllCrates();
        $p->onCompletion(fn(array $crates) => var_dump($crates), fn() => var_dump("Oh No"));


        // load the types and created crates
        $this->loadCrateTypes();

        $this->loadCrates();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command", ["mc"]));

        // store the data every 6000 ticks (~5 minutes)
//        $this->getScheduler()->scheduleRepeatingTask(new StoreDataTask($this), 6000);
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

        // decode all crate types
        foreach ($crateTypes as $id => $typeData) {

            $errorMsg = "";
            $crateType = CrateType::parse($id, $typeData, $errorMsg);
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
        $promise = $this->database->getAllCrates();
        $promise->onCompletion(function (array $crates) {
            // register all crates
            foreach ($crates as $crate) {
                Crate::registerCrate($crate);
            }

            $this->getLogger()->info("All crates are loaded");
        }, fn() => $this->getLogger()->error("Could not get the crates from the database"));


    }

    public function onDisable(): void
    {
        // let all crate reward entities despawn
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof CrateRewardItemEntity) $entity->flagForDespawn();
            }
        }

        // close the database
        $this->database->unload();
    }
}