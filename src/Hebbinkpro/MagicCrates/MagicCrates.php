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
use Hebbinkpro\MagicCrates\entity\CrateItemEntity;
use Hebbinkpro\MagicCrates\entity\CrateRewardItemEntity;
use Hebbinkpro\MagicCrates\migrate\Migrator;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
use Hebbinkpro\MagicCrates\utils\ItemUtils;
use JackMD\ConfigUpdater\ConfigUpdater;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class MagicCrates extends PluginBase
{
    public const CONFIG_VERSION = 1;
    public const DEFAULT_PREFIX = "§r[§6Magic§cCrates§r]";
    public const DEFAULT_KEY = [
        "id" => "minecraft:paper",
        "name" => "§r[§6Crate §cKey§r] §e{crate}",
        "enchantments" => [
            "name" => "unbreaking"
        ]
    ];

    private static MagicCrates $instance;
    private string $prefix;
    private Item $keyItem;
    private DBController $database;


    /**
     * Get the MagicCrates prefix
     * @return string
     */
    public static function getPrefix(): string
    {
        return self::$instance->prefix;
    }

    /**
     * Get the crate key item
     * @return Item
     */
    public static function getKeyItem(): Item
    {
        // return a copy of the key item
        return clone self::$instance->keyItem;
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
     * Get the database controller
     * @return DBController
     */
    public static function getDatabase(): DBController
    {
        return self::$instance->database;
    }

    public function onLoad(): void
    {
        // register the crate item entities
        EntityFactory::getInstance()->register(CrateItemEntity::class, function (World $world, CompoundTag $nbt): CrateItemEntity {
            return new CrateItemEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CrateItem']);

        EntityFactory::getInstance()->register(CrateRewardItemEntity::class, function (World $world, CompoundTag $nbt): CrateRewardItemEntity {
            return new CrateRewardItemEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CrateRewardItem']);
    }

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        self::$instance = $this;

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);

        $this->loadConfig();

        // create the database controller
        $this->database = new DBController($this);
        $this->database->load();

        // load the crate types
        $this->loadCrateTypes();

        // migrate all things
        Migrator::migrate($this);

        // load all the crates
        $this->loadCrates();

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic crates command", ["mc"]));
    }

    /**
     * Load all values from the config
     * @return void
     */
    private function loadConfig(): void
    {
        // save the default config
        $this->saveDefaultConfig();
        // update the config if needed
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "version", self::CONFIG_VERSION);

        $this->prefix = $this->getConfig()->get("prefix", self::DEFAULT_PREFIX);

        $key = $this->getConfig()->get("key", self::DEFAULT_KEY);

        // check if the given item is valid, otherwise use the default item
        $err = "";
        $keyItem = ItemUtils::parseItem($key, $err);
        if ($keyItem === null) {
            $this->getLogger()->warning("No valid key given in the config.yml: $err");
            $this->getLogger()->warning("The default key will be used instead.");
            $keyItem = ItemUtils::parseItem(self::DEFAULT_KEY);
        }

        $this->keyItem = $keyItem;
    }

    /**
     * Load all crate types from the JSON file.
     * @return void
     */
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
     * Load all crates from the database
     * @return void
     */
    private function loadCrates(): void
    {
        $this->database->getAllCrates()->onCompletion(function (array $crates) {
            // register all crates
            foreach ($crates as $crate) {
                Crate::registerCrate($crate);
            }

            $this->getLogger()->info("All crates are loaded");
        }, function () {
            $this->getLogger()->error("Could not load the crates from the database");
        });
    }

    public function onDisable(): void
    {
        // let all crate reward entities de-spawn
        foreach ($this->getServer()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof CrateRewardItemEntity) $entity->flagForDespawn();
            }
        }

        // close the database
        $this->database->unload();
    }
}