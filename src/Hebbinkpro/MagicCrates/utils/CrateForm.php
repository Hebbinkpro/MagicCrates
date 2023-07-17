<?php


namespace Hebbinkpro\MagicCrates\utils;


use Hebbinkpro\MagicCrates\Main;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;

class CrateForm
{
    private Main $plugin;

    private Config $crates;

    private array $types = [];

    private ?string $type = null;
    private Config $config;

    public function __construct(private int $x, private int $y, private int $z, private string $world)
    {
        $this->plugin = Main::getInstance();

        $this->config = $this->plugin->getConfig();
        $this->crates = $this->plugin->crates;

        //set types in array
        foreach ($this->config->get("types") as $key => $crateType) {
            $this->types[] = $key;
        }
    }

    public function sendCreateForm(Player $player): void
    {

        $form = new CustomForm(function (Player $player, $data) {

            if (isset($data)) {
                $this->type = $this->types[$data[1]];
                $this->submitCreate($player);
            }

        });

        $form->setTitle(Main::prefix() . " §r - Create crate");

        $form->addLabel("Select the crate type for the new crate");
        $form->addDropdown("Crate type", $this->types);

        $player->sendForm($form);

    }

    public function submitCreate(Player $player): void
    {
        $form = new ModalForm(function (Player $player, $data) {
            if (isset($data)) {
                unset($this->plugin->createCrates[$player->getName()]);
                switch ($data) {
                    case false:
                        $player->sendMessage(Main::prefix() . " §aCrate creation is cancelled");
                        break;

                    case true:
                        $player->sendMessage(Main::prefix() . " §aThe crate is created");
                        $crates = $this->crates->get("crates");
                        $crates[] = [
                            "x" => $this->x,
                            "y" => $this->y,
                            "z" => $this->z,
                            "world" => $this->world,
                            "type" => $this->type
                        ];
                        $this->crates->set("crates", $crates);
                        $this->crates->save();
                        $this->crates->reload();

                        CrateUtils::reloadCrates();
                        FloatingTextUtils::loadAllParticles($player);
                        break;
                    default:
                        $player->sendMessage(Main::prefix() . " §aCrate creation is cancelled");
                }

            }
        });

        $form->setTitle(Main::prefix() . " §r - Create crate");

        $form->setContent("The $this->type §rcrate will be created on $this->x, $this->y, $this->z in the world $this->world.\nClick §asave§r to save the crate, or click §cCancel§r to cancel the action");
        $form->setButton1("Save crate");
        $form->setButton2("§cCancel");

        $player->sendForm($form);
    }

    public function sendRemoveForm(Player $player): void
    {

        $form = new ModalForm(function (Player $player, $data) {
            if (isset($data)) {
                switch ($data) {

                    case true:

                        unset($this->plugin->removeCrates[$player->getName()]);

                        $crateKey = null;
                        $crateType = null;
                        $crateList = $this->crates->get("crates");

                        foreach ($crateList as $key => $crate) {
                            if ($crate["x"] === $this->x and $crate["y"] === $this->y and $crate["z"] === $this->z and $crate["world"] === $this->world) {
                                $crateKey = $key;
                                $crateType = $crate["type"];
                            }
                        }

                        unset($crateList[$crateKey]);

                        $this->crates->set("crates", $crateList);
                        $this->crates->save();
                        $this->crates->reload();
                        $player->sendMessage(Main::prefix() . " §aThe §e$crateType §r§acrate is removed");

                        CrateUtils::reloadCrates();
                        FloatingTextUtils::loadAllParticles();
                        break;

                    case false:
                        $player->sendMessage(Main::prefix() . " §aThe crate isn't removed");
                        unset($this->plugin->removeCrates[$player->getName()]);
                        break;

                    default:

                }
            }
        });

        $form->setTitle(Main::prefix() . " §r - Create crate");

        $form->setContent("Do you want to delete the §e$this->type §rcrate on $this->x, $this->y, $this->z in the world $this->world?\nClick §aDelete§r to delete the crate, or click §cCancel§r to cancel this action");
        $form->setButton1("Delete crate");
        $form->setButton2("§cCancel");

        $player->sendForm($form);
    }

}