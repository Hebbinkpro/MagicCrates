<?php


namespace Hebbinkpro\MagicCrates\commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateCreate;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateKey;
use Hebbinkpro\MagicCrates\commands\subcommands\CrateRemove;
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

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {
        $this->setAliases(["mc"]);

        $this->setPermission("magiccrates.cmd");

        $this->registerSubCommand(new CrateCreate($this->plugin, "create", "Create a crate"));
        $this->registerSubCommand(new CrateRemove($this->plugin, "remove", "Remove a crate"));
        $this->registerSubCommand(new CrateKey($this->plugin, "makekey", "Create a crate key"));
    }


}