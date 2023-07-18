<?php

namespace Hebbinkpro\MagicCrates\event;

use Hebbinkpro\MagicCrates\crate\Crate;
use Hebbinkpro\MagicCrates\crate\CrateReward;
use pocketmine\player\Player;

/**
 * Called when the crate reward is given to the player after the opening animation.
 */
class CrateRewardEvent extends CrateEvent
{
    private Player $player;
    private CrateReward $reward;

    public function __construct(Crate $crate, Player $player, CrateReward $reward)
    {
        parent::__construct($crate);

        $this->player = $player;
        $this->reward = $reward;

    }

    /**
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return CrateReward
     */
    public function getReward(): CrateReward
    {
        return $this->reward;
    }
}