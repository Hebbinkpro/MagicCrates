<?php


namespace Hebbinkpro\MagicCrates\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\utils\PlayerData;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CrateCreate extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("[§6Magic§cCrates§r] §cUse this command in-game!");
            return;
        }

        $action = PlayerData::getInstance()->getInt($sender, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);

        if ($action == MagicCrates::ACTION_CRATE_REMOVE) {
            $sender->sendMessage("[§6Magic§cCrates§r] §cCrate remove mode is §aenabled§c! Disable the crate remove mode with '/mc remove' and try again");
            return;
        }

        if ($action == MagicCrates::ACTION_CRATE_CREATE) {
            PlayerData::getInstance()->setInt($sender, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);
            $sender->sendMessage("[§6Magic§cCrates§r] §eCrate create mode §cdisabled");
            return;
        }

        if ($action == MagicCrates::ACTION_NONE) {
            PlayerData::getInstance()->setInt($sender, MagicCrates::ACTION_TAG, MagicCrates::ACTION_CRATE_CREATE);
            $sender->sendMessage("[§6Magic§cCrates§r] §eCrate create mode §aenabled§e, click on a chest to create a crate");
        }
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.create");
    }
}