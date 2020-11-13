<?php


namespace Hebbinkpro\MagicCrates\commands\subcommands;

use Hebbinkpro\MagicCrates\Main;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class CrateCreate extends BaseSubCommand
{
	private $main;

    protected function prepare(): void
    {
		$this->main = Main::getInstance();

		$this->setPermission("mc.cmd.create");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
		if(!$sender instanceof Player){
			$sender->sendMessage("[§6Magic§cCrates§r] §cUse this command in-game!");
			return;
		}

		if(isset($this->main->removeCrates[$sender->getName()])){
			if($this->main->removeCrates[$sender->getName()] === true){
				$sender->sendMessage("[§6Magic§cCrates§r] §cCrate remove mode is enabled! Disable the crate remove mode with '/mc remove' and try again");
				return;
			}
		}
		if(!isset($this->main->createCrates[$sender->getName()])){
			$this->main->createCrates[$sender->getName()] = true;
			$sender->sendMessage("[§6Magic§cCrates§r] §aCrate create mode enabled, click on a chest to create a crate");
			return;
		}
		if($this->main->createCrates[$sender->getName()] === false){
			$this->main->createCrates[$sender->getName()] = true;
			$sender->sendMessage("[§6Magic§cCrates§r] §aCrate create mode enabled, click on a chest to create a crate");
			return;
		}

		if($this->main->createCrates[$sender->getName()] === true){
			$this->main->createCrates[$sender->getName()] = false;
			$sender->sendMessage("[§6Magic§cCrates§r] §cCrate create mode disabeld");
		}
    }
}