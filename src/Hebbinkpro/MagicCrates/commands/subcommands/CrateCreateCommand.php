<?php


namespace Hebbinkpro\MagicCrates\commands\subcommands;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use Hebbinkpro\MagicCrates\MagicCrates;
use Hebbinkpro\MagicCrates\utils\PlayerData;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CrateCreateCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cUse this command in-game!");
            return;
        }

        $action = PlayerData::getInstance()->getInt($sender, PlayerData::ACTION_TAG, PlayerData::ACTION_NONE);

        switch ($action) {
            case PlayerData::ACTION_CRATE_REMOVE:
                $sender->sendMessage(MagicCrates::getPrefix() . " §cCrate remove mode is currently §aenabled§c! Disable the crate remove mode using '/mc remove' and try again");
                break;

            case PlayerData::ACTION_CRATE_CREATE:
                // disable crate creation mode
                PlayerData::getInstance()->setInt($sender, PlayerData::ACTION_TAG, PlayerData::ACTION_NONE);
                $sender->sendMessage(MagicCrates::getPrefix() . " §eCrate creation mode is now §cdisabled");
                break;

            default:
                PlayerData::getInstance()->setInt($sender, PlayerData::ACTION_TAG, PlayerData::ACTION_CRATE_CREATE);
                $sender->sendMessage(MagicCrates::getPrefix() . " §eCrate creation mode is now §aenabled§e. Click on a chest to create a crate");
        }
    }

    protected function prepare(): void
    {
        $this->setPermission("magiccrates.cmd.create");
        $this->addConstraint(new InGameRequiredConstraint($this));
    }
}