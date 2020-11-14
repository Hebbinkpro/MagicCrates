<?php


namespace Hebbinkpro\MagicCrates\commands;

use Hebbinkpro\MagicCrates\commands\subcommands\CrateCreate;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateRemove;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateKey;

use CortexPE\Commando\BaseCommand;

use pocketmine\command\CommandSender;

class MagicCratesCommand extends BaseCommand
{

    protected function prepare(): void
    {
    	$this->setAliases(["mc"]);

    	$this->setPermission("mc.cmd");

    	$this->registerSubCommand(new CrateCreate("create", "Create a crate"));
		$this->registerSubCommand(new CrateRemove("remove", "Remove a crate"));
		$this->registerSubCommand(new CrateKey("makekey", "Create a crate key"));

    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
		$sender->sendMessage("[§6Magic§cCrates§r] §eHelp:");
		$sender->sendMessage("- /mc create => Create a crate");
		$sender->sendMessage("- /mc remove => Remove a crate");
		$sender->sendMessage("- /mc makekey => Make a create key");
    }
}