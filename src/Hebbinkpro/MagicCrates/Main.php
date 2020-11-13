<?php


namespace Hebbinkpro\MagicCrates;

use Hebbinkpro\MagicCrates\entity\CrateItem;
use Hebbinkpro\MagicCrates\commands\MagicCratesCommand;

use CortexPE\Commando\PacketHooker;
use CortexPE\Commando\exception\HookAlreadyRegistered;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\entity\Entity;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener {

	public $config;
	public $crates;
	public static $instance;

	public $createCrates = [];
	public $removeCrates = [];
	public $openCrates = [];

	public $particles = [];

	public static function getInstance(){
		return self::$instance;
	}

	public function onEnable(){
		self::$instance = $this;

		if(!PacketHooker::isRegistered()) {
			try {
				PacketHooker::register($this);
			} catch (HookAlreadyRegistered $e) {
			}
		}

		Entity::registerEntity(CrateItem::class, true, ["CrateItem"]);

		$this->saveResource("config.yml");
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$this->crates = new Config($this->getDataFolder() . "crates.yml", Config::YAML, [
		    "crates" => []
        ]);

		//register events
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

		//register command
		$this->getServer()->getCommandMap()->register("magiccrates", new MagicCratesCommand($this, "magiccrates", "Magic Crates Command"));


		$this->initParticles();

		//var_dump($this->particles);
	}

	public function reloadCrates(){
		$this->crates->reload();
	}

	public function initParticles(){
		$this->particles = [];

		foreach ($this->crates->get("crates") as $key=>$crate){
			$pos = new Vector3($crate["x"] + 0.5, $crate["y"] + 1, $crate["z"] + 0.5);
			$level = $crate["level"];
			$types = $this->getConfig()->get("types");
			if(isset($types[$crate["type"]]["name"])){
				$title = $types[$crate["type"]]["name"];
				if(!is_string($title)){
					$title = $crate["type"] . " crate";
				}
			}else{
				$title = $crate["type"] . " crate";
			}
			$particle = new FloatingTextParticle($pos, "", $title);
			$this->particles[$key] = [
				"particle" => $particle,
				"level" => $level
			];

		}
	}

	public function onDisable(){
		$this->crates->save();
		foreach($this->getServer()->getLevels() as $level){
			foreach($level->getEntities() as $entity){
				if($entity instanceof CrateItem){
					$entity->flagForDespawn();
				}
			}
		}

		$this->disableAllParticles();
	}

	public function loadAllParticles(?Player $player = null, $lev = null){
		if($player != null){
			$this->disableAllParticles($player);
		}else{
			$this->disableAllParticles();
		}
		$this->reloadCrates();
		$this->initParticles();
		if($lev instanceof Level){
			$level = $lev->getName();
		}
		$particles = $this->particles;
		foreach($particles as $crate){
			$particle = $crate["particle"];
			if($particle instanceof FloatingTextParticle){

				$particle->setInvisible(false);
				if($player != null){
					$level = $player->getLevel();
					$level->addParticle($particle, [$player]);
				}else{
					$level = $this->getServer()->getLevelByName($crate["level"]);
					$level->addParticle($particle);
				}
			}
		}
	}

	public function disableAllParticles(?Player $player = null){
		$levels = $this->getServer()->getLevels();
		$particles = $this->particles;
		foreach ($particles as $crate) {
			$level = $this->getServer()->getLevelByName($crate["level"]);
			$particle = $crate["particle"];
			if ($particle instanceof FloatingTextParticle) {
				$particle->setInvisible(true);
				if($player != null){
					$level->addParticle($particle, [$player]);
				}else{
					$level->addParticle($particle);
				}
			}
		}

	}


}