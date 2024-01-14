<?php


namespace Hebbinkpro\MagicCrates\commands;

use CortexPE\Commando\BaseCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateCreateCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateKeyAllCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateKeyCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateRemoveCommand;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;

class MagicCratesCommand extends BaseCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(MagicCrates::getPrefix() . " §eHelp:");
        $sender->sendMessage("- /mc create => Toggle crate create mode");
        $sender->sendMessage("- /mc remove => Toggle crate remove mode");
        $sender->sendMessage("- /mc key <type> [amount] [player] => Give a crate key to a player");
        $sender->sendMessage("- /mc keyall <type> [amount] => Give crate keys to all online players");


    }

    protected function prepare(): void
    {
        $this->setAliases(["mc"]);

        $this->setPermission("magiccrates.cmd");

        /** @var MagicCrates $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new CrateCreateCommand($plugin, "create", "Toggle crate create mode"));
        $this->registerSubCommand(new CrateRemoveCommand($plugin, "remove", "Toggle crate remove mode"));
        $this->registerSubCommand(new CrateKeyCommand($plugin, "key", "Give a crate key to a player"));
        $this->registerSubCommand(new CrateKeyAllCommand($plugin, "keyall", "Give crate keys to all online players"));
    }


}