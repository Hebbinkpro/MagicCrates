<?php


namespace Hebbinkpro\MagicCrates\utils;


use Hebbinkpro\MagicCrates\Main;
use pocketmine\block\Block;
use pocketmine\block\Chest;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\item\Item;
use pocketmine\player\Player;

class CrateUtils
{
    public static function getCrateType(Block $block): ?string
    {
        if ($block instanceof Chest) {
            $cX = $block->getPosition()->getFloorX();
            $cY = $block->getPosition()->getFloorY();
            $cZ = $block->getPosition()->getFloorZ();
            $cWorld = $block->getPosition()->getWorld()->getFolderName();

            foreach (Main::getInstance()->crates->get("crates") as $crate) {
                if ($crate["x"] === $cX and $crate["y"] === $cY and $crate["z"] === $cZ and $crate["world"] === $cWorld)
                    return $crate["type"];

            }
        }

        return null;
    }

    public static function getCrateKey(Block $block): ?int
    {
        if ($block instanceof Chest) {
            $cX = $block->getPosition()->getFloorX();
            $cY = $block->getPosition()->getFloorY();
            $cZ = $block->getPosition()->getFloorZ();
            $cWorld = $block->getPosition()->getWorld()->getFolderName();

            foreach (Main::getInstance()->crates->get("crates") as $key => $crate) {
                if ($crate["x"] === $cX and $crate["y"] === $cY and $crate["z"] === $cZ and $crate["world"] === $cWorld)
                    return $key;
            }
        }

        return null;
    }

    public static function getCrateContent($type): ?array
    {
        foreach (Main::getInstance()->getConfig()->get("types") as $key => $crateType) {
            if ($key === $type) return $crateType;
        }
        return null;
    }

    public static function getCrateTypes(): array
    {
        return array_keys(Main::getInstance()->getConfig()->get("types")) ?? [];
    }

    public static function getReward(array $items): ?array
    {

        $rewards = [];
        foreach ($items as $item) {
            $p = $item["probability"];
            $i = 0;
            while ($i < $p) {
                $i++;
                $rewards[] = $item;
            }
        }
        if ($rewards === []) return null;

        $reward = array_rand($rewards);
        return $rewards[$reward];
    }

    public static function reloadCrates(): void
    {
        Main::getInstance()->crates->reload();
    }

    public static function sendCommands(array $commands, string $crateType, Player $player, Item $reward, int $count = 1): void
    {
        $types = Main::getInstance()->getConfig()->get("types");
        if (!isset($types[$crateType])) return;

        foreach ($commands as $cmd) {
            $cmd = str_replace("{player}", $player->getName(), $cmd);
            $cmd = str_replace("{crate}", $crateType . " crate", $cmd);
            if ($count > 1) {
                if ($reward->hasCustomName()) {
                    $cmd = str_replace("{reward}", $reward->getCustomName() . " " . $count . "x", $cmd);
                } else {
                    $cmd = str_replace("{reward}", $reward->getName() . " " . $count . "x", $cmd);
                }

            } else {
                if ($reward->hasCustomName()) {
                    $cmd = str_replace("{reward}", $reward->getCustomName(), $cmd);
                }
                $cmd = str_replace("{reward}", $reward->getName(), $cmd);
            }

            $consoleSender = new ConsoleCommandSender(Main::getInstance()->getServer(), Main::getInstance()->getServer()->getLanguage());
            $consoleSender->recalculatePermissions();
            Main::getInstance()->getServer()->dispatchCommand($consoleSender, $cmd);
        }
    }
}