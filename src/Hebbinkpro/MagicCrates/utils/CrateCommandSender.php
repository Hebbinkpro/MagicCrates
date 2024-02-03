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

namespace Hebbinkpro\MagicCrates\utils;

use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;

class CrateCommandSender
{
    private static PluginBase $plugin;
    private static CommandSender $sender;

    public static function register(PluginBase $plugin): void
    {
        self::$plugin = $plugin;
        self::$sender = new ConsoleCommandSender($plugin->getServer(), $plugin->getServer()->getLanguage());
        self::$sender->recalculatePermissions();
    }

    public static function executePreparedCommand(string $command, array $values): void
    {
        self::executeCommand(self::prepare($command, $values));
    }

    public static function executeCommand(string $command): void
    {
        self::$plugin->getServer()->dispatchCommand(self::$sender, $command);
    }

    /**
     * Replaces all occurrences of value keys with the given value inside the given command(s).
     * @param string $command
     * @param array<string, string> $values
     * @return string
     */
    public static function prepare(string $command, array $values): string
    {

        foreach ($values as $name => $value) {
            $command = str_replace("{" . $name . "}", $value, $command);
        }

        return $command;
    }

}