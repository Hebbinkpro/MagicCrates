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
            $sender->sendMessage(MagicCrates::getPrefix()." §cUse this command in-game!");
            return;
        }

        $action = PlayerData::getInstance()->getInt($sender, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);

        if ($action == MagicCrates::ACTION_CRATE_REMOVE) {
            $sender->sendMessage(MagicCrates::getPrefix()." §cCrate remove mode is §aenabled§c! Disable the crate remove mode with '/mc remove' and try again");
            return;
        }

        if ($action == MagicCrates::ACTION_CRATE_CREATE) {
            PlayerData::getInstance()->setInt($sender, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);
            $sender->sendMessage(MagicCrates::getPrefix()." §eCrate create mode §cdisabled");
            return;
        }

        if ($action == MagicCrates::ACTION_NONE) {
            PlayerData::getInstance()->setInt($sender, MagicCrates::ACTION_TAG, MagicCrates::ACTION_CRATE_CREATE);
            $sender->sendMessage(MagicCrates::getPrefix()." §eCrate create mode §aenabled§e, click on a chest to create a crate");
        }
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.create");
    }
}