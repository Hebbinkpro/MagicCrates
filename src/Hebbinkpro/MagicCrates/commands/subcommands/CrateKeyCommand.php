<?php


namespace Hebbinkpro\MagicCrates\commands\subcommands;


use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\TargetPlayerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Hebbinkpro\MagicCrates\commands\args\CrateTypeArgument;
use Hebbinkpro\MagicCrates\crate\CrateType;
use Hebbinkpro\MagicCrates\MagicCrates;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class CrateKeyCommand extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $plugin = $this->getOwningPlugin();

        // console sender has to give the player
        if (!$sender instanceof Player && !isset($args["player"])) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cUsage: /mc makekey <type> <amount> <player>");
            return;
        }

        // check if the crate type exists, you never know
        if (($type = CrateType::getById($args["type"])) === null) {
            $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid crate type");
            return;
        }

        // get the amount of keys
        if (!isset($args["amount"])) $amount = 1;
        else {
            $amount = $args["amount"];

            // negative or zero amount is given
            if ($amount <= 0) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cInvalid amount, should be >= 1");
                return;
            }
        }

        // get the type name, used in the messages
        $typeName = $type->getName();

        if (isset($args["player"])) {
            // get the online player
            $player = $plugin->getServer()->getPlayerExact($args["player"]);

            // the player is not  online
            if ($player === null) {
                $sender->sendMessage(MagicCrates::getPrefix() . " §cThe given player is not online");
                return;
            }

            $this->giveKeys($player, $type, $amount);

            $name = $player->getName();
            $player->sendMessage(MagicCrates::getPrefix() . " §aYou received $amount $typeName §r§akey" . ($amount > 1 ? "s" : ""));
            $sender->sendMessage(MagicCrates::getPrefix() . " §e$name received $amount $typeName §r§ekey" . ($amount > 1 ? "s" : ""));
            return;
        }

        $this->giveKeys($sender, $type, $amount);
        $sender->sendMessage(MagicCrates::getPrefix() . " §aYou received $amount §e$typeName §r§akey" . ($amount > 1 ? "s" : ""));
    }

    /**
     * Give keys to the specified player
     * @param Player $player
     * @param CrateType $type
     * @param int $amount
     * @return void
     */
    private function giveKeys(Player $player, CrateType $type, int $amount): void
    {
        $key = $type->getCrateKey();
        $key->setCount($amount);
        $player->getInventory()->addItem($key);
    }

    /**
     * @throws ArgumentOrderException
     */
    protected function prepare(): void
    {

        $this->setPermission("magiccrates.cmd.key");

        $this->registerArgument(0, new CrateTypeArgument("type"));
        $this->registerArgument(1, new IntegerArgument("amount", true));
        $this->registerArgument(2, new TargetPlayerArgument(true, "player"));


    }
}