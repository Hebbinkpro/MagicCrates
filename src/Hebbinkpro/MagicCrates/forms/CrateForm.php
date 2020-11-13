<?php


namespace Hebbinkpro\MagicCrates\forms;

use Hebbinkpro\MagicCrates\Main;

use pocketmine\event\Event;

use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;

use pocketmine\Player;

class CrateForm
{
	private $main;

	private $config;
	private $crates;
	private $types = [];

	private $x;
	private $y;
	private $z;
	private $level;
	private $type = null;

	private $event = null;


	public function __construct(int $x, int $y, int $z, string $level, $event = null){

		//get main
		$this->main = Main::getInstance();

		//get chest position
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		$this->level = $level;

		//get configs
		$this->config = $this->main->getConfig();
		$this->crates = $this->main->crates;

		//set types in array
		foreach($this->config->get("types") as $key => $content){
			$this->types[] = $key;
			continue;
		}

		if($event instanceof Event){
			$this->event = $event;
		}

	}

	public function sendCreateForm(Player $player){

		$form = new CustomForm(function(Player $player, $data){

			if(isset($data)){
				$this->type = $this->types[$data[1]];
				$this->submitCreate($player);
			}

		});

		$form->setTitle("§6Magic §cCrates §r - Create crate");

		$form->addLabel("Select the crate type for the new crate");
		$form->addDropdown("Crate type", $this->types);

		$player->sendForm($form);

	}

	public function submitCreate(Player $player){
		$form = new ModalForm(function(Player $player, $data){
			if(isset($data)){
				//var_dump($data);
				switch($data){
					case false:
						$this->main->createCrates[$player->getName()] = false;
						$player->sendMessage("[§6Magic§cCrates§r] §aCrate creation is cancelled");
						break;

					case true:
						$this->main->createCrates[$player->getName()] = false;
						$player->sendMessage("[§6Magic§cCrates§r] §aThe crate is created");
						$crates = $this->crates->get("crates");
						$crates[] = [
							"x" => $this->x,
							"y" => $this->y,
							"z" => $this->z,
							"level" => $this->level,
							"type" => $this->type
						];
						$this->crates->set("crates", $crates);
						$this->crates->save();
						$this->crates->reload();

						$this->main->reloadCrates();
						$this->main->loadAllParticles($player);
						break;
					default:
						$this->main->createCrates[$player->getName()] = false;
						$player->sendMessage("[§6Magic§cCrates§r] §aCrate creation is cancelled");
				}

			}
		});

		$form->setTitle("§6Magic §cCrates §r - Create crate");

		$form->setContent("The $this->type §rcrate will be created on $this->x, $this->y, $this->z in the world $this->level.\nClick §asave§r to save the crate, or click §cCancel§r to cancel the action");
		$form->setButton1("Save crate");
		$form->setButton2("§cCancel");

		$player->sendForm($form);
	}

	public function sendRemoveForm(Player $player){

		$form = new ModalForm(function(Player $player, $data){
			if(isset($data)){
				switch ($data){

					case true:

						$this->main->removeCrates[$player->getName()] = false;

						$crate = null;
						$crateType = null;
						$cratesl = $this->crates->get("crates");

						foreach($cratesl as $key=>$con){
							if($con["x"] === $this->x and $con["y"] === $this->y and $con["z"] === $this->z and $con["level"] === $this->level){
								$crate = $key;
								$crateType = $con["type"];
							}
						}

						//$crates->unset($crate);
						unset($cratesl[$crate]);
						//var_dump($crates);

						$crates = [];
						foreach($cratesl as $con){
							$crates[] = [
								"x" => $con["x"],
								"y" => $con["y"],
								"z" => $con["z"],
								"level" => $con["level"],
								"type" => $con["type"]
							];
						}

						$this->crates->set("crates", $crates);
						$this->crates->save();
						$this->crates->reload();
						$player->sendMessage("[§6Magic§cCrates§r] §aThe §e$crateType §r§acrate is removed");
						if(!is_null($this->event)){
							$this->event->setCancelled(false);
						}

						$this->main->reloadCrates();
						$this->main->loadAllParticles(null);
						break;

					case false:
						$player->sendMessage("[§6Magic§cCrates§r] §aThe crate isn't removed");
						$this->main->removeCrates[$player->getName()] = false;
						if(!is_null($this->event)){
							$this->event->setCancelled();
						}
						break;

					default:

				}
			}
		});

		$form->setTitle("§6Magic §cCrates §r - Create crate");

		$form->setContent("Do you want to delete the §e$this->type §rcrate on $this->x, $this->y, $this->z in the world $this->level?\nClick §aDelete§r to delete the crate, or click §cCancel§r to cancel this action");
		$form->setButton1("Delete crate");
		$form->setButton2("§cCancel");

		$player->sendForm($form);
	}

}