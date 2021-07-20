<?php

namespace OguzhanUmutlu\Bosses\events;

use OguzhanUmutlu\Bosses\entities\BossEntity;
use pocketmine\event\Event;

class BossDeathEvent extends Event {
    private $boss;
    public function __construct(BossEntity $boss) {
        $this->boss = $boss;
    }

    /*** @return BossEntity */
    public function getBoss(): BossEntity {
        return $this->boss;
    }
}