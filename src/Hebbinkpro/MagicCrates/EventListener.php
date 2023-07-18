<?php


namespace Hebbinkpro\MagicCrates;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\event\CrateOpenEvent;
use Hebbinkpro\MagicCrates\tasks\StartCrateAnimationTask;
use Hebbinkpro\MagicCrates\utils\CrateForm;
use Hebbinkpro\MagicCrates\utils\EntityUtils;
use Hebbinkpro\MagicCrates\utils\PlayerData;
use pocketmine\block\Chest;
use pocketmine\block\tile\Chest as TileChest;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\ChestPairEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\Position;

class EventListener implements Listener
{
    private MagicCrates $plugin;
    private int $delay;

    public function __construct(MagicCrates $plugin)
    {
        $this->plugin = $plugin;
        $this->delay = $plugin->getConfig()->get("delay");
    }

    public function onInteractChest(PlayerInteractEvent $e): void
    {
        $player = $e->getPlayer();
        $block = $e->getBlock();
        $item = $e->getItem();

        // when block isn't a chest or when it's a left click interaction return
        if (!$block instanceof Chest || $e->getAction() == PlayerInteractEvent::LEFT_CLICK_BLOCK) return;

        $pos = $block->getPosition();

        // get the chest tile
        $tile = $pos->getWorld()->getTile($pos);
        // the crate isn't a TileChest, or it's a double chest
        if (!$tile instanceof TileChest) return;


        $playerAction = PlayerData::getInstance()->getInt($player, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);

        if ($tile->isPaired() && $playerAction > MagicCrates::ACTION_NONE) {
            $player->sendMessage(MagicCrates::PREFIX . "§c You cannot interact with a paired chest!");
            $e->cancel();
            return;
        }

        $crate = Crate::getByPosition($block->getPosition());

        // check if player is creating a crate
        if ($playerAction == MagicCrates::ACTION_CRATE_CREATE) {
            $this->createCrate($player, $crate, $pos);
            $e->cancel();
            return;
        }

        // check if player is removing a crate
        if ($playerAction == MagicCrates::ACTION_CRATE_REMOVE) {
            $this->removeCrate($player, $crate);
            $e->cancel();
            return;
        }

        if ($crate !== null) {
            $this->openCrate($player, $crate, $item);
            $e->cancel();
        }
    }


    private function createCrate(Player $player, ?Crate $crate, Position $pos): void
    {

        if ($crate !== null) {
            $player->sendMessage(MagicCrates::PREFIX . " §cThere is already a crate on this position.");
            return;
        }

        PlayerData::getInstance()->setInt($player, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);

        $form = new CrateForm($this->plugin, $pos);
        $form->sendCreateForm($player);
    }

    private function removeCrate(Player $player, ?Crate $crate): void
    {

        if ($crate === null) {
            $player->sendMessage(MagicCrates::PREFIX . " §cThere is no crate on this position.");
            return;
        }

        PlayerData::getInstance()->setInt($player, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);

        $form = new CrateForm($this->plugin, $crate->getPos());
        $form->sendRemoveForm($player);
    }

    private function openCrate(Player $player, Crate $crate, Item $item): void
    {

        if ($crate->isOpen()) {
            $playerName = $crate->getOpener()->getName();
            $player->sendMessage(MagicCrates::PREFIX . " §cYou have to wait, §e$playerName §r§cis opening the crate");
            return;
        }

        $type = $crate->getType();
        $typeId = $type->getId();


        if ($item->getTypeId() != ItemTypeIds::PAPER || $item->getNamedTag()->getString(MagicCrates::KEY_NBT_TAG, "") !== $typeId) {
            $player->sendMessage(MagicCrates::PREFIX . " §cUse a §e$typeId §r§ccrate key to open this crate");
            return;
        }

        if (!$player->getInventory()->canAddItem(VanillaItems::PAPER())) {
            $player->sendMessage(MagicCrates::PREFIX . " §cYour inventory is full, come back later when your inventory is cleared!");
            return;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item);

        $reward = $crate->getType()->getRandomReward();

        $pos = $crate->getPos();
        $spawnPos = $pos->add(0.5, 1, 0.5);

        $nbt = EntityUtils::createBaseNBT($spawnPos);
        $nbt->setString("owner", $player->getName());
        $nbt->setFloat("spawn-y", $spawnPos->getY());
        $nbt->setString("crate-pos", serialize($pos->asVector3()));
        $nbt->setString("crate-type", $type->getId());
        $nbt->setString("reward", $reward->getName());

        $delay = $this->delay * 20;
        if (!is_int($delay)) $delay = 0;

        $player->sendMessage(MagicCrates::PREFIX . " §eYou are opening a §6$typeId §ecrate...");
        $crate->setOpener($player);
        $crate->hideFloatingText();

        (new CrateOpenEvent($crate, $player))->call();

        $this->plugin->getScheduler()->scheduleDelayedTask(new StartCrateAnimationTask($crate, $reward, $nbt), $delay);
    }

    public function onBlockBreak(BlockBreakEvent $e): void
    {
        $player = $e->getPlayer();
        $block = $e->getBlock();
        $playerAction = PlayerData::getInstance()->getInt($player, MagicCrates::ACTION_TAG, MagicCrates::ACTION_NONE);

        if ($playerAction > MagicCrates::ACTION_NONE) {
            $player->sendMessage(MagicCrates::PREFIX . " §cYou can't break blocks while creating or removing a crate");
            $e->cancel();
            return;
        }

        $crate = Crate::getByPosition($block->getPosition());

        if ($player->hasPermission("magiccrates.break.remove") && $crate !== null) {
            $form = new CrateForm($this->plugin, $crate->getPos());
            $form->sendRemoveForm($player);
            $e->cancel();
        }
    }

    public function onChestPair(ChestPairEvent $e): void
    {
        $left = Crate::getByPosition($e->getLeft()->getPosition());
        $right = Crate::getByPosition($e->getRight()->getPosition());
        if ($left !== null || $right !== null) $e->cancel();
    }

    public function onJoin(PlayerJoinEvent $e): void
    {
        $player = $e->getPlayer();
        Crate::showAllFloatingText($player);
    }

    public function onWorldChange(EntityTeleportEvent $e): void
    {
        $player = $e->getEntity();
        if (!$player instanceof Player || $e->isCancelled()) return;

        $to = $e->getTo();
        $from = $e->getFrom();
        if ($to->getWorld()->getFolderName() === $from->getWorld()->getFolderName()) return;

        Crate::showAllFloatingText($player, $to->getWorld());
    }
}
