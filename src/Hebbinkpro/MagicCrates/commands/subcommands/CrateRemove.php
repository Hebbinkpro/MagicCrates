<?php


namespace Hebbinkpro\MagicCrates\commands\subcommands;

use Hebbinkpro\MagicCrates\Main;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class CrateRemove extends BaseSubCommand
{
	private $main;

    protected function prepare(): void
    {
		$this->main = Main::getInstance();

		$this->setPermission("mc.command.remove");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
    	if(!$sender instanceof Player){
    		$sender->sendMessage("[§6Magic§cCrates§r] §cUse this command in-game!");
    		return;
		}

    	if(isset($this->main->createCrates[$sender->getName()])){
    		if($this->main->createCrates[$sender->getName()] === true){
				$sender->sendMessage("[§6Magic§cCrates§r] §cCrate create mode is enabled! Disable the crate create mode with '/mc create' and try again");
				return;
			}
		}
    	if(!isset($this->main->removeCrates[$sender->getName()])){
			$this->main->removeCrates[$sender->getName()] = true;
			$sender->sendMessage("[§6Magic§cCrates§r] §aCrate remove mode enabled, click on a crate to remove it");
			return;
		}
    	if($this->main->removeCrates[$sender->getName()] === false){
			$this->main->removeCrates[$sender->getName()] = true;
			$sender->sendMessage("[§6Magic§cCrates§r] §aCrate remove mode enabled, click on a crate to remove it");
			return;
		}

		if($this->main->removeCrates[$sender->getName()] === true){
			$this->main->removeCrates[$sender->getName()] = false;
			$sender->sendMessage("[§6Magic§cCrates§r] §cCrate remove mode disabeld");
		}
    }
}