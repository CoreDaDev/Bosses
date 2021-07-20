<?php

namespace OguzhanUmutlu\Bosses\events;

use OguzhanUmutlu\Bosses\entities\BossEntity;
use pocketmine\event\Event;

class MinionDeathEvent extends Event {
    private $minion;
    public function __construct(BossEntity $minion) {
        $this->minion = $minion;
    }

    /*** @return BossEntity */
    public function getMinion(): BossEntity {
        return $this->minion;
    }
}