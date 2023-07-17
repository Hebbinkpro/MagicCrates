<?php


namespace Hebbinkpro\MagicCrates\utils;


use Hebbinkpro\MagicCrates\Main;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\World;

class FloatingTextUtils
{
    public static function loadAllParticles(?Player $player = null, ?World $world = null): void
    {
        self::disableAllParticles($player);

        $particles = self::initParticles();
        foreach ($particles as $crateParticle) {
            if (!is_null($world) and $crateParticle["world"] !== $world->getFolderName()) continue;

            if (is_null($world)) {
                if (is_null($player)) {
                    $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($crateParticle["world"]);
                    if (is_null($world)) continue;
                } else $world = $player->getWorld();
            }

            $particle = $crateParticle["particle"];
            if (!$particle instanceof FloatingTextParticle) continue;

            $particle->setInvisible(false);

            if (!is_null($player)) $world->addParticle($crateParticle["pos"], $particle, [$player]);
            else $world->addParticle($crateParticle["pos"], $particle);
        }
    }

    public static function disableAllParticles(?Player $player = null): void
    {
        $particles = Main::getInstance()->particles;
        foreach ($particles as $crateParticle) {
            $particle = $crateParticle["particle"];
            if (!$particle instanceof FloatingTextParticle) continue;

            $particle->setInvisible();
            if (!is_null($player)) $player->getWorld()->addParticle($crateParticle["pos"], $particle, [$player]);
            else {
                if (!Main::getInstance()->getServer()->getWorldManager()->isWorldLoaded($crateParticle["world"])) continue;

                $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($crateParticle["world"]);
                if (is_null($world)) continue;

                $world->addParticle($crateParticle["pos"], $particle);
            }
        }
    }

    public static function initParticles(): array
    {
        Main::getInstance()->particles = [];

        foreach (Main::getInstance()->crates->get("crates") as $key => $crate) {
            $pos = new Vector3($crate["x"] + 0.5, $crate["y"] + 1, $crate["z"] + 0.5);
            $world = $crate["world"];
            $types = Main::getInstance()->getConfig()->get("types");
            if (isset($types[$crate["type"]]["name"])) {
                $title = $types[$crate["type"]]["name"];
                if (!is_string($title)) $title = $crate["type"] . " crate";
            } else $title = $crate["type"] . " crate";

            $particle = new FloatingTextParticle("", $title);
            Main::getInstance()->particles[$key] = [
                "particle" => $particle,
                "pos" => $pos,
                "world" => $world
            ];

        }

        return Main::getInstance()->particles;
    }
}