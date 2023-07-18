<?php


namespace Hebbinkpro\MagicCrates\utils;


use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;

class CrateForm
{
    private MagicCrates $plugin;
    private Position $pos;


    private ?CrateType $type = null;

    public function __construct(MagicCrates $plugin, Position $pos)
    {
        $this->plugin = $plugin;
        $this->pos = $pos;
    }

    public function sendCreateForm(Player $player): void
    {
        $types = CrateType::getAllTypeIds();

        $form = new CustomForm(function (Player $player, $data) use ($types) {

            if (isset($data)) {
                $this->type = CrateType::getById($types[$data[1]]);
                $this->submitCreate($player);
            }

        });

        $form->setTitle(MagicCrates::PREFIX . " §r - Create crate");

        $form->addLabel("Select the crate type for the new crate");
        $form->addDropdown("Crate type", $types);

        $player->sendForm($form);

    }

    public function submitCreate(Player $player): void
    {
        $form = new ModalForm(function (Player $player, $data) {
            if (isset($data)) {
                switch ($data) {
                    case false:
                        $player->sendMessage(MagicCrates::PREFIX . " §aCrate creation is cancelled");
                        break;

                    case true:
                        $crate = new Crate($this->pos, $this->type);
                        $crate->showFloatingText();
                        $player->sendMessage(MagicCrates::PREFIX . " §aThe {$crate->getType()->getName()}§r§a crate is created");
                        $this->plugin->saveCrates();
                        break;
                    default:
                        $player->sendMessage(MagicCrates::PREFIX . " §aCrate creation is cancelled");
                }

            }
        });

        $form->setTitle(MagicCrates::PREFIX . " §r - Create crate");

        $form->setContent("Are you sure you want to create a §e{$this->type->getId()}§r crate?\n\nClick §asave§r to save the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§aSave");
        $form->setButton2("§eCancel");

        $player->sendForm($form);
    }

    public function sendRemoveForm(Player $player): void
    {
        $crate = Crate::getByPosition($this->pos);

        $form = new ModalForm(function (Player $player, $data) use ($crate) {
            if (isset($data)) {
                switch ($data) {

                    case true:
                        $crate->remove();
                        $crate->hideFloatingText();
                        $this->plugin->saveCrates();

                        $player->sendMessage(MagicCrates::PREFIX . " §aThe §e{$crate->getType()->getId()} §r§acrate is removed");

                        break;

                    case false:
                        $player->sendMessage(MagicCrates::PREFIX . " §aThe crate isn't removed");
                        break;

                    default:

                }
            }
        });

        $form->setTitle(MagicCrates::PREFIX . " §r - Create crate");

        $form->setContent("Do you want to delete this §e{$crate->getType()->getId()}§r crate?\n\nClick §cDelete§r to delete the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§cDelete");
        $form->setButton2("§eCancel");

        $player->sendForm($form);
    }

}