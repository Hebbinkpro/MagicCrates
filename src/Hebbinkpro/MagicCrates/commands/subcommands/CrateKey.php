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

class CrateKey extends BaseSubCommand
{

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $plugin = $this->getOwningPlugin();

        if (!$sender instanceof Player) {
            if (!isset($args["player"])) {
                $sender->sendMessage(MagicCrates::getPrefix()." §cUsage: /mc makekey <type> <amount> <player>");
                return;
            }
            if (is_null($plugin->getServer()->getPlayerExact($args["player"]))) {
                $sender->sendMessage(MagicCrates::getPrefix()." §cThe given player is not online");
                return;
            }
        }

        $types = [];

        foreach ($plugin->getConfig()->get("types") as $key => $content) {
            $types[] = $key;
        }
        if (!isset($args["type"]) or !in_array($args["type"], $types)) {
            $msg = "";
            foreach ($types as $type) {
                $msg = $msg . $type . ", ";
            }
            $sender->sendMessage(MagicCrates::getPrefix()." §cThat is not a valid crate type");
            $sender->sendMessage("§6Valid types:§r $msg");
            return;
        }

        $key = CrateType::getById($args["type"])->getCrateKey();

        if (!isset($args["amount"])) $count = 1;
        else $count = $args["amount"];

        if (isset($args["player"])) {
            if ($plugin->getServer()->getPlayerExact($args["player"]) instanceof Player) {
                $player = $plugin->getServer()->getPlayerExact($args["player"]);
                $i = 0;
                while ($i < $count) {
                    $i++;
                    $player->getInventory()->addItem($key);
                }

                $name = $player->getName();
                $type = $args["type"];
                $player->sendMessage(MagicCrates::getPrefix()." §aYou received the §e$type §r§acrate key");
                $sender->sendMessage(MagicCrates::getPrefix()." §e$name §r§a received the §e$type §r§acrate key");
                return;
            }
        }

        $i = 0;
        while ($i < $count) {
            $i++;
            $sender->getInventory()->addItem($key);
        }
        $type = $args["type"];
        $sender->sendMessage(MagicCrates::getPrefix()." §aYou received the crate key for the §e$type §r§acrate");
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