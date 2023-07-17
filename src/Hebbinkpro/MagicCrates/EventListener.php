<?php


namespace Hebbinkpro\MagicCrates;

use Hebbinkpro\MagicCrates\tasks\CreateEntityTask;
use Hebbinkpro\MagicCrates\utils\CrateForm;
use Hebbinkpro\MagicCrates\utils\CrateUtils;
use Hebbinkpro\MagicCrates\utils\EntityUtils;
use Hebbinkpro\MagicCrates\utils\FloatingTextUtils;
use pocketmine\block\Chest;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class EventListener implements Listener
{

    private Main $plugin;
    private Config $config;

    public function __construct()
    {
        $this->plugin = Main::getInstance();
        $this->config = $this->plugin->getConfig();
    }

    public function onInteractChest(PlayerInteractEvent $e): void
    {
        $player = $e->getPlayer();
        $block = $e->getBlock();
        $item = $e->getItem();

        if (!$block instanceof Chest) return;

        $x = $block->getPosition()->getFloorX();
        $y = $block->getPosition()->getFloorY();
        $z = $block->getPosition()->getFloorZ();
        $world = $block->getPosition()->getWorld();

        $crateType = CrateUtils::getCrateType($block);

        // check if player is creating a crate
        if (isset($this->plugin->createCrates[$player->getName()])) {
            $e->cancel();

            if (is_null($crateType)) {
                $form = new CrateForm($x, $y, $z, $world->getFolderName());
                $form->sendCreateForm($player);
                return;
            }

            $player->sendMessage(Main::prefix() . " §cThis crate is already registerd");
            return;
        }

        // check if player is removing a crate
        if (isset($this->plugin->removeCrates[$player->getName()])) {
            $e->cancel();

            if (!is_null($crateType)) {
                $form = new CrateForm($x, $y, $z, $world->getFolderName());
                $form->sendRemoveForm($player);
                return;
            }

            $player->sendMessage(Main::prefix() . " §cThis chest isn't a crate");
            return;
        }

        if (is_null($crateType)) return;

        $e->cancel();

        $crateKey = CrateUtils::getCrateKey($block);
        if (isset($this->plugin->openCrates[$crateKey])) {
            $user = $this->plugin->openCrates[$crateKey];
            $player->sendMessage(Main::prefix() . " §cYou have to wait. §e$user §r§cis opening a crate");
            return;
        }

        if ($item->getTypeId() != ItemTypeIds::PAPER) {
            $player->sendMessage(Main::prefix() . " §cUse a crate key to open this §e$crateType §r§ccrate");
            return;
        }

        if (!in_array("§6Magic§cCrates §7Key - " . $crateType, $item->getLore()) or $item->getCustomName() != "§e" . $crateType . " §r§dCrate Key") {
            $player->sendMessage(Main::prefix() . " §cUse a crate key to open this §e$crateType §r§ccrate");
            return;
        }

        if (!$player->getInventory()->canAddItem(VanillaItems::PAPER())) {
            $player->sendMessage(Main::prefix() . " §cYour inventory is full, come back later when your inventory is cleared!");
            return;
        }

        $item->pop();

        $crate = CrateUtils::getCrateContent($crateType);
        if (is_null($crate)) {
            $player->sendMessage(Main::prefix() . " §cSomething went wrong");
            return;
        }

        $reward = CrateUtils::getReward($crate["rewards"]);
        if (is_null($reward)) {
            $player->sendMessage(Main::prefix() . " §cSomething went wrong");
            return;
        }


        $rewardItem = $reward["item"];
        $item = StringToItemParser::getInstance()->parse($rewardItem["id"]);

        $name = $rewardItem["name"] ?? $item->getName();
        if (isset($rewardItem["name"])) $item->setCustomName($name);

        $count = $rewardItem["amount"] ?? 0;

        $lore = $rewardItem["lore"] ?? "";
        $item->setLore([$lore, "\n§a$crateType §r§6Crate", "§7Pickup: §cfalse"]);

        if (isset($rewardItem["enchantments"])) {
            foreach ($rewardItem["enchantments"] as $enchArray) {
                $ench = VanillaEnchantments::getAll()[strtoupper($enchArray["name"])] ?? null;
                if ($ench instanceof Enchantment)
                    $item->addEnchantment(new EnchantmentInstance($ench, intval($enchArray["level"])));
            }
        }

        $commands = [];
        if (isset($reward["commands"])) $commands = $reward["commands"];

        $spawnPos = new Vector3($x + 0.5, $y + 1, $z + 0.5);

        $nbt = EntityUtils::createBaseNBT($spawnPos);
        $nbt->setString("Owner", $player->getName());
        $nbt->setShort("SpawnY", $spawnPos->getY());
        $nbt->setShort("ItemCount", $count);
        $nbt->setShort("CrateKey", $crateKey);
        $nbt->setString("RewardCommands", json_encode($commands));
        $nbt->setTag("Item", $item->nbtSerialize());

        $delay = $this->config->get("delay") * 20;
        if (!is_int($delay)) $delay = 0;

        $player->sendMessage("§eYou are opening a $crateType crate...");
        $this->plugin->openCrates[$crateKey] = $player->getName();

        $this->plugin->getScheduler()->scheduleDelayedTask(new CreateEntityTask($name, $world, $nbt, $count), $delay);
    }

    public function onPickup(EntityItemPickupEvent $e): void
    {
        $item = $e->getItem();
        if (in_array("§7Pickup: §cfalse", $item->getLore())) $e->cancel();
    }

    public function onBlockBreak(BlockBreakEvent $e): void
    {
        $player = $e->getPlayer();
        $block = $e->getBlock();
        if ($this->plugin->createCrates[$player->getName()]) {
            $player->sendMessage(Main::prefix() . " §cYou can't break blocks while creating a crate");
            $e->cancel();
            return;
        }

        if ($player->hasPermission("magiccrates.break.remove") and !is_null(CrateUtils::getCrateType($block))) {
            $x = $block->getPosition()->getFloorX();
            $y = $block->getPosition()->getFloorY();
            $z = $block->getPosition()->getFloorZ();
            $world = $block->getPosition()->getWorld();

            $form = new CrateForm($x, $y, $z, $world->getFolderName());
            $form->sendRemoveForm($player);
            $e->cancel();
        }
    }

    public function onJoin(PlayerJoinEvent $e): void
    {
        $player = $e->getPlayer();
        FloatingTextUtils::loadAllParticles($player);
    }

    public function onWorldChange(EntityTeleportEvent $e): void
    {
        $player = $e->getEntity();
        if (!$player instanceof Player) return;

        $to = $e->getTo();
        $from = $e->getFrom();
        if ($to->getWorld()->getFolderName() === $from->getWorld()->getFolderName()) return;

        FloatingTextUtils::loadAllParticles($player, $to->getWorld());
    }
}
