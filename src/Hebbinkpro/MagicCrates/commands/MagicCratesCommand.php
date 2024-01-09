<?php


namespace Hebbinkpro\MagicCrates\commands;

use CortexPE\Commando\BaseCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateCreateCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateKeyCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateRemoveCommand;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;

class MagicCratesCommand extends BaseCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(MagicCrates::getPrefix() . " §eHelp:");
        $sender->sendMessage("- /mc create => Create a crate");
        $sender->sendMessage("- /mc remove => Remove a crate");
        $sender->sendMessage("- /mc key => Make a crate key");
    }

    protected function prepare(): void
    {
        $this->setAliases(["mc"]);

        $this->setPermission("magiccrates.cmd");

        /** @var MagicCrates $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new CrateCreateCommand($plugin, "create", "Create a crate"));
        $this->registerSubCommand(new CrateRemoveCommand($plugin, "remove", "Remove a crate"));
        $this->registerSubCommand(new CrateKeyCommand($plugin, "key", "Create a crate key", ["makekey"]));
    }


}