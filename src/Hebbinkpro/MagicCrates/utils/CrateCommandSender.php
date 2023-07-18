<?php

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