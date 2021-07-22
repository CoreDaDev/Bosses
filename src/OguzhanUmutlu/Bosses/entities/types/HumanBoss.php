<?php

namespace OguzhanUmutlu\Bosses\entities\types;

use InvalidStateException;
use OguzhanUmutlu\Bosses\entities\BossAttributes;
use OguzhanUmutlu\Bosses\entities\BossEntity;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\Player;
use pocketmine\utils\UUID;
use ReflectionClass;

class HumanBoss extends BossEntity {
    public const NETWORK_ID = self::PLAYER;
    /** @var UUID */
    protected $uuid;
    /** @var Skin */
    protected $skin;
    public function __construct(Level $level, CompoundTag $nbt, ?BossAttributes $attributes = null) {
        if($this->skin === null){
            $skinTag = $nbt->getCompoundTag("Skin");
            if($skinTag === null)
                throw new InvalidStateException((new ReflectionClass($this))->getShortName() . " must have a valid skin set");
            $this->skin = self::deserializeSkinNBT($skinTag);
        }
        parent::__construct($level, $nbt, $attributes);
    }

    public function getName(): string {
        return "HumanBoss";
    }

    public function getUniqueId() : ?UUID{
        return $this->uuid;
    }

    protected static function deserializeSkinNBT(CompoundTag $skinTag) : Skin{
        $skin = new Skin(
            $skinTag->getString("Name"),
            $skinTag->hasTag("Data", StringTag::class) ? $skinTag->getString("Data") : $skinTag->getByteArray("Data"), //old data (this used to be saved as a StringTag in older versions of PM)
            $skinTag->getByteArray("CapeData", ""),
            $skinTag->getString("GeometryName", ""),
            $skinTag->getByteArray("GeometryData", "")
        );
        $skin->validate();
        return $skin;
    }

    public function getSkin() : Skin{
        return $this->skin;
    }

    public function setSkin(Skin $skin) : void{
        $skin->validate();
        $this->skin = $skin;
        $this->skin->debloatGeometryData();
    }

    public function sendSkin(?array $targets = null) : void{
        $pk = new PlayerSkinPacket();
        $pk->uuid = $this->getUniqueId();
        $pk->skin = SkinAdapterSingleton::get()->toSkinData($this->skin);
        $this->server->broadcastPacket($targets ?? $this->hasSpawned, $pk);
    }

    protected function initHumanData() : void{
        if($this->namedtag->hasTag("NameTag", StringTag::class)){
            $this->setNameTag($this->namedtag->getString("NameTag"));
        }
        $this->uuid = UUID::fromData((string) $this->getId(), $this->skin->getSkinData(), $this->getNameTag());
    }

    public function saveNBT() : void{
        parent::saveNBT();
        if($this->skin !== null){
            $this->namedtag->setTag(new CompoundTag("Skin", [
                new StringTag("Name", $this->skin->getSkinId()),
                new ByteArrayTag("Data", $this->skin->getSkinData()),
                new ByteArrayTag("CapeData", $this->skin->getCapeData()),
                new StringTag("GeometryName", $this->skin->getGeometryName()),
                new ByteArrayTag("GeometryData", $this->skin->getGeometryData())
            ]));
        }
    }
    protected function sendSpawnPacket(Player $player) : void{
        $this->skin->validate();
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_ADD;
        $pk->entries = [PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), SkinAdapterSingleton::get()->toSkinData($this->skin))];
        $player->dataPacket($pk);
        $pk = new AddPlayerPacket();
        $pk->uuid = $this->getUniqueId();
        $pk->username = $this->getName();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->item = ItemStackWrapper::legacy(Item::get(0));
        $pk->metadata = $this->propertyManager->getAll();
        $player->dataPacket($pk);
        $this->sendData($player, [self::DATA_NAMETAG => [self::DATA_TYPE_STRING, $this->getNameTag()]]);
        $this->armorInventory->sendContents($player);
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
        $player->dataPacket($pk);
    }
    protected function initEntity() : void{
        parent::initEntity();
        $this->propertyManager->setBlockPos(self::DATA_PLAYER_BED_POSITION, null);
        $this->initHumanData();
    }
}