<?php

namespace OguzhanUmutlu\Bosses\tasks;

use Exception;
use OguzhanUmutlu\Bosses\Bosses;
use OguzhanUmutlu\Bosses\entities\BossAttributes;
use OguzhanUmutlu\Bosses\entities\BossEntity;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\scheduler\Task;

class BossTask extends Task {
    public $attributes;
    public $info = [];
    public function __construct(BossAttributes $attributes, float $scale, float $health, float $maxHealth, string $name, Position $position, int $ticks, ?BossEntity $entity, int $id) {
        $this->attributes = $attributes;
        $this->info = [
            "scale" => $scale,
            "health" => $health,
            "maxHealth" => $maxHealth,
            "name" => $name,
            "position" => $position,
            "ticks" => $ticks,
            "entity" => $entity,
            "id" => $id
        ];
        Bosses::$instance->getScheduler()->scheduleDelayedTask($this, $ticks);
    }

    /**
     * @throws Exception
     */
    public function onRun(int $currentTick) {
        if(!isset(Bosses::$config->getNested("bosses")[$this->info["id"]])) return;
        $attributes = $this->attributes;
        $entity = Entity::createEntity($this->info["name"], $this->info["position"]->level, Entity::createBaseNBT($this->info["position"]));
        if(!$entity instanceof BossEntity) throw new Exception(BossEntity::class." excepted ".get_class($entity) ." provided.");
        $entity->isNew = true;
        if(isset(Bosses::$instance->entities[$this->info["id"]]) && Bosses::$instance->entities[$this->info["id"]] instanceof BossEntity && !Bosses::$instance->entities[$this->info["id"]]->isClosed())
            Bosses::$instance->entities[$this->info["id"]]->flagForDespawn();
        Bosses::$instance->entities[$this->info["id"]] = $entity;
        $entity->attributes = $attributes;
        $entity->setScale($this->info["scale"]);
        $entity->setMaxHealth($this->info["maxHealth"]);
        $entity->setHealth($this->info["health"]);
        $entity->onDie[] = function() {
            Bosses::$instance->getScheduler()->scheduleDelayedTask($this, $this->info["ticks"]);
        };
        if($this->info["entity"] instanceof BossEntity && !$this->info["entity"]->isClosed())
            $this->info["entity"]->onDie[] = function() use ($entity) {
                $entity->spawnToAll();
            };
    }
}