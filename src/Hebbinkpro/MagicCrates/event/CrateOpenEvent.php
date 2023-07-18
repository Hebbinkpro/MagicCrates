<?php

namespace Hebbinkpro\MagicCrates\event;


use Hebbinkpro\MagicCrates\crate\Crate;
use pocketmine\player\Player;

/**
 * Called when a crate is opened
 */
class CrateOpenEvent extends CrateEvent
{
    private Player $player;

    public function __construct(Crate $crate, Player $player)
    {
        parent::__construct($crate);

        $this->player = $player;

    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }
}