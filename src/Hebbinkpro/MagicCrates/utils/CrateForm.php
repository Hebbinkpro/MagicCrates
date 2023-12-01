<?php


namespace Hebbinkpro\MagicCrates\utils;


use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;

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

    /**
     * Send a form to the player to create a crate
     * @param Player $player
     * @return void
     */
    public function sendCreateForm(Player $player): void
    {
        $types = CrateType::getAllTypeIds();

        $form = new CustomForm(function (Player $player, $data) use ($types) {

            if (isset($data)) {
                $this->type = CrateType::getById($types[$data[1]]);
                $this->submitCreate($player);
            }

        });

        $form->setTitle(MagicCrates::getPrefix() . " - Create crate");

        $form->addLabel("Select the crate type for the new crate");
        $form->addDropdown("Crate type", $types);

        $player->sendForm($form);

    }

    /**
     * Send a form to the player to confirm the creation of a crate
     * @param Player $player
     * @return void
     */
    public function submitCreate(Player $player): void
    {
        $form = new ModalForm(function (Player $player, $data) {
            if (!is_bool($data)) return;

            if ($data) {
                $crate = new Crate($this->pos, $this->type);
                $crate->showFloatingText();
                $player->sendMessage(MagicCrates::getPrefix() . " §aThe {$crate->getType()->getName()}§r§a crate is created");
                $this->plugin->saveCrates();
                return;
            }

            $player->sendMessage(MagicCrates::getPrefix() . " §aCrate creation is cancelled");
        });

        $form->setTitle(MagicCrates::getPrefix() . " - Create crate");

        $form->setContent("Are you sure you want to create a §e{$this->type->getId()}§r crate?\n\nClick §asave§r to save the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§aSave");
        $form->setButton2("§eCancel");

        $player->sendForm($form);
    }

    /**
     * Send a form to a player to remove a crate
     * @param Player $player
     * @return void
     */
    public function sendRemoveForm(Player $player): void
    {
        $crate = Crate::getByPosition($this->pos);
        if ($crate === null) {
            $player->sendMessage(MagicCrates::getPrefix() . " §cNo crate found at this position.");
            return;
        }

        $form = new ModalForm(function (Player $player, $data) use ($crate) {
            if (!is_bool($data)) return;

            if ($data) {
                $crate->remove();
                $crate->hideFloatingText();
                $this->plugin->saveCrates();

                $player->sendMessage(MagicCrates::getPrefix() . " §aThe §e{$crate->getType()->getId()} §r§acrate is removed");
                return;
            }

            $player->sendMessage(MagicCrates::getPrefix() . " §aThe crate isn't removed");
        });

        $form->setTitle(MagicCrates::getPrefix() . " - Create crate");

        $form->setContent("Do you want to delete this §e{$crate->getType()->getId()}§r crate?\n\nClick §cDelete§r to delete the crate, or click §eCancel§r to cancel.");
        $form->setButton1("§cDelete");
        $form->setButton2("§eCancel");

        $player->sendForm($form);
    }

    /**
     * Send a form to the player containing the contents of the crate
     * @param Player $player
     * @return void
     */
    public function sendPreviewForm(Player $player): void
    {
        $crate = Crate::getByPosition($this->pos);
        if ($crate === null) {
            $player->sendMessage(MagicCrates::getPrefix() . " No crate found at this position.");
            return;
        }

        $type = $crate->getType();


        $form = new SimpleForm(function (Player $player, $data) use ($crate, $type) {
            {
                if (!is_string($data) || $data === "close") return;

                if ($data === "open") {
                    $crate->openWithKey($player);
                    return;
                }
            }
        });

        // set the form title
        $form->setTitle(MagicCrates::getPrefix() . " - {$crate->getType()->getName()}§r Preview");
        // add the open button if the player has a valid key in their inventory
        if ($type->getKeyFromPlayer($player) !== null) $form->addButton("§aOpen Crate", -1, "", "open");
        // add a close button
        $form->addButton("§cClose", -1, "", "close");

        // add buttons for all rewards inside the crate
        foreach ($type->getRewardDistribution(true) as $name => $p) {
            $reward = $type->getRewardByName($name);

            $item = $reward->getItem();
            $count = $item?->getCount() ?? 1;

            $buttonName = "§l" . $reward->getName() . "§r";
            if ($count > 1) $buttonName .= " x$count";

            $buttonName .= "\nProbability: §6" . round($p, 1);

            $image = $reward->getIcon() ?? $reward->getItemIcon();

            $imageType = SimpleForm::IMAGE_TYPE_PATH;
            if (str_starts_with($image, "http")) $imageType = SimpleForm::IMAGE_TYPE_URL;

            $form->addButton($buttonName, $imageType, $image);
        }

        $player->sendForm($form);

    }
}