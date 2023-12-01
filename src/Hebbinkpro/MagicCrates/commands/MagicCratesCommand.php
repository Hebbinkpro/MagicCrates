<?php


namespace Hebbinkpro\MagicCrates\commands;

use CortexPE\Commando\BaseCommand;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateCreate;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateKey;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateRemove;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;

class MagicCratesCommand extends BaseCommand
{
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage("[§6Magic§cCrates§r] §eHelp:");
        $sender->sendMessage("- /mc create => Create a crate");
        $sender->sendMessage("- /mc remove => Remove a crate");
        $sender->sendMessage("- /mc makekey => Make a create key");
    }

    protected function prepare(): void
    {
        $this->setAliases(["mc"]);

        $this->setPermission("magiccrates.cmd");

        /** @var MagicCrates $plugin */
        $plugin = $this->getOwningPlugin();

        $this->registerSubCommand(new CrateCreate($plugin, "create", "Create a crate"));
        $this->registerSubCommand(new CrateRemove($plugin, "remove", "Remove a crate"));
        $this->registerSubCommand(new CrateKey($plugin, "key", "Create a crate key", ["makekey"]));
    }


}