<?php

namespace OguzhanUmutlu\Bosses\entities;

use OguzhanUmutlu\Bosses\events\boss\BossDamageEvent;
use OguzhanUmutlu\Bosses\events\boss\BossDeathEvent;
use OguzhanUmutlu\Bosses\events\boss\BossShootEvent;
use OguzhanUmutlu\Bosses\events\minion\MinionDamageEvent;
use OguzhanUmutlu\Bosses\events\minion\MinionDeathEvent;
use OguzhanUmutlu\Bosses\events\minion\MinionShootEvent;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\Solid;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Binary;

abstract class BossEntity extends Living {
    public $lifeTicks = 0;
    public $shootTicks = 0;
    private $minionTicks = 0;
    private $targetTicks = 0;
    public $itemGiven = false;
    public $isAggressive = false;
    /*** @var BossAttributes|null */
    public $attributes = null;
    /*** @var Player */
    public $targetEntity = null;
    /*** @var BossEntity[] */
    protected $minions = [];
    public $isNew = false;
    private $damages = [];
    public function __construct(Level $level, CompoundTag $nbt, ?BossAttributes $attributes = null) {
        $this->attributes = $attributes ?? new BossAttributes();
        parent::__construct($level, $nbt);
    }
    protected function initEntity(): void {
        if($this->namedtag->hasTag("Health", FloatTag::class))
            $this->setHealth($this->namedtag->getFloat("Health"));
        if($this->namedtag->hasTag("MaxHealth", FloatTag::class))
            $this->setMaxHealth($this->namedtag->getFloat("MaxHealth"));
        if($this->namedtag->hasTag("Scale", FloatTag::class))
            $this->setScale($this->namedtag->getFloat("Scale"));
        $this->attributes = BossAttributes::fromCompoundTag($this->namedtag);
        parent::initEntity();
    }
    public function onCollideWithPlayer(Player $player): void {
        parent::onCollideWithPlayer($player);
        $player->setMotion($this->getDirectionVectorCopy($this->lookAtCopyYaw($this, $player), $this->lookAtCopyPitch($this, $player))->multiply($this->getScale()));
    }

    public function onUpdate(int $currentTick): bool {
        if(!$this->attributes instanceof BossAttributes) {
            $this->attributes = new BossAttributes();
            $this->saveAttributes();
        }
        if($this->attributes->isAlwaysAggressive)
            $this->isAggressive = true;
        if(!$this->isNew)
            $this->flagForDespawn();
        if($this->isClosed())
            return false;
        if($this->attributes->isMinion && !$this->getOwningEntity() instanceof BossEntity)
            $this->flagForDespawn();
        if(!$this->itemGiven && $this->attributes->canShoot)
            $this->refreshItem();
        if(($this->targetEntity instanceof Player && ($this->targetEntity->isClosed() || $this->targetEntity->isCreative() || $this->targetEntity->isSpectator())) || !$this->isAggressive) {
            $this->targetEntity = null;
            $this->shootTicks = 0;
        }
        if($this->isAggressive && $this->targetEntity instanceof Player) {
            if(!$this->attributes->canShoot)
                $this->targetEntity->attack(new EntityDamageEvent($this, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->attributes->damageAmount));
            else {
                $this->shootTicks++;
                if($this->shootTicks >= 35) {
                    $this->shootTicks = 0;
                    $arrow = Entity::createEntity("Arrow", $this->level, Entity::createBaseNBT(
                        $this->add(0, $this->getEyeHeight()),
                        $this->getDirectionVector(),
                        ($this->yaw > 180 ? 360 : 0) - $this->yaw,
                        -$this->pitch
                    ), $this, !$this->isOnGround());
                    if($arrow instanceof Arrow) {
                        if($this->attributes->damageFire)
                            $arrow->setOnFire(10);
                        if($this->attributes->isMinion)
                            $ev = new MinionShootEvent($this, $arrow);
                        else $ev = new BossShootEvent($this, $arrow);
                        $ev->call();
                        if(!$ev->isCancelled()) {
                            $arrow = $ev->getProjectile();
                            $this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BOW);
                            $arrow->spawnToAll();
                        }
                    }
                }
            }
            $this->targetEntity->setMotion($this->targetEntity->getMotion()->add($this->attributes->hitMotionX, $this->attributes->hitMotionY, $this->attributes->hitMotionZ));
            $this->targetEntity->setMotion($this->targetEntity->getMotion()->add($this->getDirectionVectorCopy($this->lookAtCopyYaw($this->targetEntity), $this->lookAtCopyPitch($this->targetEntity))->multiply($this->attributes->hitMotion)));
        }
        $this->lifeTicks++;
        $this->targetTicks++;
        if(empty($this->getMinions()) && $this->attributes->canSpawnMinions && !$this->attributes->isMinion) {
            if($this->minionTicks >= $this->attributes->minionSpawnTickAmount) {
                $this->minionTicks = 0;
                $class = get_class($this);
                for($i=-1;$i<2;$i+=2) {
                    $vector3 = $this->asVector3();
                    $yaw = $this->yaw+($i*90);
                    if($yaw > 360)
                        $yaw-=360;
                    $directionVector = $this->getDirectionVectorCopy($yaw, 0);
                    $minion = new $class($this->level, self::createBaseNBT($vector3->add($directionVector)));
                    $minion->setScale($this->getScale()/2);
                    $attributes = clone $this->attributes;
                    $attributes->isMinion = true;
                    $minion->attributes = $attributes;
                    $minion->setOwningEntity($this);
                    $this->minions[] = $minion;
                }
            } else $this->minionTicks++;
        }
        if($this->targetTicks >= 20) {
            $this->targetTicks = 0;
            $this->recalculateTargetEntity();
        }
        if($this->isAggressive && $this->targetEntity instanceof Player && $this->targetEntity->isAlive() && !$this->targetEntity->isClosed() && $this->targetEntity->isOnline()) {
            $this->lookAt($this->targetEntity);
            $directionVector = $this->getDirectionVector();
            $this->move($directionVector->x, $directionVector->y, $directionVector->z);
        }
        return parent::onUpdate($currentTick);
    }
    public function getMinions(): array {
        return array_filter($this->minions, function($minion){return $minion instanceof BossEntity && !$minion->isClosed();});
    }
    public function move(float $dx, float $dy, float $dz): void {
        $dx*=$this->attributes->speed;
        $dy*=$this->attributes->speed;
        $dz*=$this->attributes->speed;
        parent::move($dx, $dy, $dz);
        $target = $this->getTargetBlock(2);
        if(is_null($target)) return;
        if($this->boundingBox && $target->collidesWithBB($this->boundingBox) && $this->attributes->canClimb || (!empty(array_filter($this->level->getCollisionBlocks($this->getBoundingBox()), function($block){return $block instanceof Liquid;})) && $this->attributes->canSwim))
            $this->setMotion(new Vector3(0, 0.2));
    }
    public function recalculateTargetEntity(): void {
        foreach($this->getViewers() as $player)
            if(!$player->isClosed() && !$player->isCreative() && !$player->isSpectator() && $player->distance($player) <= $this->attributes->visionReach && !$this->getTargetBlock($this->attributes->visionReach) instanceof Solid)
                if(!$this->targetEntity instanceof Player || $this->targetEntity->isClosed() || $this->targetEntity->distance($this->asVector3()) > $player->distance($this->asVector3())) {
                    if($this->attributes->isAlwaysAggressive) {
                        $this->targetEntity = $player;
                    } else if($this->attributes->eyeAggressive) {
                        $similarity = abs((($player->yaw+$player->pitch)/2)-(($this->lookAtCopyYaw($player->add(0, -$this->eyeHeight))*$this->lookAtCopyPitch($player->add(0, -$this->eyeHeight)))/2));
                        if($similarity <= 7 && !$player->isClosed())
                            $this->targetEntity = $player;
                    }
                }
    }

    /*** @param BossAttributes|null $attributes */
    public function setAttributes(?BossAttributes $attributes): void {
        $this->attributes = $attributes;
    }
    public $onDamage = [];
    public function attack(EntityDamageEvent $source): void {
        if($source->getCause() == EntityDamageEvent::CAUSE_FALL && !$source->isCancelled() && !$this->attributes->fallDamage)
            $source->setCancelled();
        if($source instanceof EntityDamageByEntityEvent) {
            if($this->attributes->isMinion)
                $ev = new MinionDamageEvent($this, $source);
            else $ev = new BossDamageEvent($this, $source);
            $ev->call();
        }
        parent::attack($source);
        if(!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent && ($p = $source->getDamager()) && $p instanceof Player) {
            $this->damages[$p->getName()] += $source->getFinalDamage();
            $this->mostDamages = $this->damages;
            rsort($this->mostDamages);
            foreach($this->onDamage as $item)
                $item();
            $this->targetEntity = $p;
        }
    }
    public function saveNBT(): void {
        $this->saveAttributes();
        $this->namedtag->setString("id", $this->getSaveId(), true);
        if($this->getNameTag() !== "") {
            $this->namedtag->setString("CustomName", $this->getNameTag());
            $this->namedtag->setByte("CustomNameVisible", $this->isNameTagVisible() ? 1 : 0);
        } else
            $this->namedtag->removeTag("CustomName", "CustomNameVisible");
        $this->namedtag->setTag(new ListTag("Pos", [
            new DoubleTag("", $this->x),
            new DoubleTag("", $this->y),
            new DoubleTag("", $this->z)
        ]));
        $this->namedtag->setTag(new ListTag("Motion", [
            new DoubleTag("", $this->motion->x),
            new DoubleTag("", $this->motion->y),
            new DoubleTag("", $this->motion->z)
        ]));
        $this->namedtag->setTag(new ListTag("Rotation", [
            new FloatTag("", $this->yaw),
            new FloatTag("", $this->pitch)
        ]));
        $this->namedtag->setFloat("FallDistance", $this->fallDistance);
        $this->namedtag->setShort("Fire", $this->fireTicks);
        $this->namedtag->setShort("Air", $this->propertyManager->getShort(self::DATA_AIR));
        $this->namedtag->setByte("OnGround", $this->onGround ? 1 : 0);
        $this->namedtag->setByte("Invulnerable", 0);
        if(count($this->effects) > 0){
            $effects = [];
            foreach($this->effects as $effect){
                $effects[] = new CompoundTag("", [
                    new ByteTag("Id", $effect->getId()),
                    new ByteTag("Amplifier", Binary::signByte($effect->getAmplifier())),
                    new IntTag("Duration", $effect->getDuration()),
                    new ByteTag("Ambient", $effect->isAmbient() ? 1 : 0),
                    new ByteTag("ShowParticles", $effect->isVisible() ? 1 : 0)
                ]);
            }
            $this->namedtag->setTag(new ListTag("ActiveEffects", $effects));
        } else
            $this->namedtag->removeTag("ActiveEffects");
    }
    public function setHealth(float $amount): void {
        parent::setHealth($amount);
        $this->namedtag->setFloat("Health", $amount);
    }
    public function setMaxHealth(int $amount): void {
        parent::setMaxHealth($amount);
        $this->namedtag->setInt("MaxHealth", $amount);
    }
    public function setScale(float $value): void {
        parent::setScale($value);
        $this->namedtag->setFloat("Scale", $value);
    }
    public function getTargetBlockCopy(float $yaw, float $pitch, float $eyeHeight): ?Block {
        $line = $this->getLineOfSightCopy($yaw, $pitch, $eyeHeight);
        if(count($line) > 0)
            return array_shift($line);
        return null;
    }
    public function getLineOfSightCopy(float $yaw, float $pitch, float $eyeHeight): array {
        $maxDistance = 50;
        $blocks = [];
        $nextIndex = 0;
        foreach(VoxelRayTrace::inDirection($this->add(0, $eyeHeight), $this->getDirectionVectorCopy($yaw, $pitch), $maxDistance) as $vector3){
            $block = $this->level->getBlockAt($vector3->x, $vector3->y, $vector3->z);
            $blocks[$nextIndex++] = $block;
            if(count($blocks) > 1){
                array_shift($blocks);
                --$nextIndex;
            }
            if($block instanceof Solid)
                break;
        }
        return $blocks;
    }
    public function lookAtCopyYaw(Vector3 $target, ?Vector3 $from = null): float {
        $from = $from ?? $this;
        $xDist = $target->x - $from->x;
        $zDist = $target->z - $from->z;
        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($yaw < 0)
            $yaw += 360.0;
        return $yaw;
    }
    public function lookAtCopyPitch(Vector3 $target, ?Vector3 $from = null): float {
        $from = $from ?? $this;
        $horizontal = sqrt(($target->x - $from->x) ** 2 + ($target->z - $from->z) ** 2);
        $vertical = $target->y - $from->y;
        return -atan2($vertical, $horizontal) / M_PI * 180;
    }
    public function getDirectionVectorCopy(float $yaw, float $pitch): Vector3 {
        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));
        return $this->temporalVector->setComponents($x, $y, $z)->normalize();
    }
    public function refreshItem(): void {
        $this->itemGiven = true;
        $pk = new MobEquipmentPacket();
        $pk->item = ItemStackWrapper::legacy(Item::get(Item::BOW));
        $pk->hotbarSlot = $pk->inventorySlot = 0;
        $pk->entityRuntimeId = $this->id;
        Server::getInstance()->broadcastPacket($this->getViewers(), $pk);
    }
    public function spawnTo(Player $player): void {
        parent::spawnTo($player);
        $this->refreshItem();
    }
    public function saveAttributes(): void {
        $this->namedtag->setTag(($this->attributes ?? new BossAttributes())->toCompoundTag());
    }
    public function getOnlineDamagePlayers(): array {
        $sort = array_filter(
            array_keys($this->mostDamages),
            function($playerName) {
                return Server::getInstance()->getPlayerExact($playerName);
            }
        );
        rsort($sort);
        return $sort;
    }
    public $onDie = [];
    public $mostDamages = [];
    public $isKilled = false;
    public function kill(): void {
        $this->isKilled = true;
        $this->setHealth(0);
        $this->scheduleUpdate();
        $this->startDeathAnimation();
        $drops = $this->getDrops();
        $this->mostDamages = $this->damages;
        rsort($this->mostDamages);
        if($this->attributes->isMinion) {
            $ev = new MinionDeathEvent($this, $drops);
            $drops = $this->attributes->minionDrops;
        } else {
            $ev = new BossDeathEvent($this, $drops);
            $drops = $this->attributes->drops;
        }
        $ev->call();
        foreach($drops as $drop)
            $this->level->dropItem($this, $drop);
        foreach($this->onDie as $die)
            $die();
    }
    public function flagForDespawn(): void {
        if(!$this->isKilled)
            foreach($this->onDie as $die)
                $die();
        parent::flagForDespawn();
    }
    public function setNameTag(string $name): void {
        parent::setNameTag(str_replace("\\n", "\n", $name));
    }
}