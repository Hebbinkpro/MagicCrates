<?php

namespace Hebbinkpro\MagicCrates\event;

use Hebbinkpro\MagicCrates\crate\Crate;
use pocketmine\event\Event;

abstract class CrateEvent extends Event
{

    private Crate $crate;

    public function __construct(Crate $crate)
    {
        $this->crate = $crate;
    }

    /**
     * @return Crate
     */
    public function getCrate(): Crate
    {
        return $this->crate;
    }

}