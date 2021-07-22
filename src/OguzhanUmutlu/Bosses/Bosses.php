<?php

namespace OguzhanUmutlu\Bosses;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\element\StepSlider;
use dktapps\pmforms\element\Toggle;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use OguzhanUmutlu\Bosses\entities\BossAttributes;
use OguzhanUmutlu\Bosses\entities\BossEntity;
use OguzhanUmutlu\Bosses\entities\types\AgentBoss;
use OguzhanUmutlu\Bosses\entities\types\BatBoss;
use OguzhanUmutlu\Bosses\entities\types\BlazeBoss;
use OguzhanUmutlu\Bosses\entities\types\CaveSpiderBoss;
use OguzhanUmutlu\Bosses\entities\types\ChickenBoss;
use OguzhanUmutlu\Bosses\entities\types\CowBoss;
use OguzhanUmutlu\Bosses\entities\types\CreeperBoss;
use OguzhanUmutlu\Bosses\entities\types\DolphinBoss;
use OguzhanUmutlu\Bosses\entities\types\DonkeyBoss;
use OguzhanUmutlu\Bosses\entities\types\ElderGuardianBoss;
use OguzhanUmutlu\Bosses\entities\types\EnderDragonBoss;
use OguzhanUmutlu\Bosses\entities\types\EndermanBoss;
use OguzhanUmutlu\Bosses\entities\types\EndermiteBoss;
use OguzhanUmutlu\Bosses\entities\types\GhastBoss;
use OguzhanUmutlu\Bosses\entities\types\GuardianBoss;
use OguzhanUmutlu\Bosses\entities\types\HorseBoss;
use OguzhanUmutlu\Bosses\entities\types\HuskBoss;
use OguzhanUmutlu\Bosses\entities\types\IronGolemBoss;
use OguzhanUmutlu\Bosses\entities\types\LlamaBoss;
use OguzhanUmutlu\Bosses\entities\types\MagmaCube;
use OguzhanUmutlu\Bosses\entities\types\MooshroomBoss;
use OguzhanUmutlu\Bosses\entities\types\MuleBoss;
use OguzhanUmutlu\Bosses\entities\types\NPCBoss;
use OguzhanUmutlu\Bosses\entities\types\OcelotBoss;
use OguzhanUmutlu\Bosses\entities\types\ParrotBoss;
use OguzhanUmutlu\Bosses\entities\types\PhantomBoss;
use OguzhanUmutlu\Bosses\entities\types\PigBoss;
use OguzhanUmutlu\Bosses\entities\types\PolarBearBoss;
use OguzhanUmutlu\Bosses\entities\types\RabbitBoss;
use OguzhanUmutlu\Bosses\entities\types\SheepBoss;
use OguzhanUmutlu\Bosses\entities\types\ShulkerBoss;
use OguzhanUmutlu\Bosses\entities\types\SilverfishBoss;
use OguzhanUmutlu\Bosses\entities\types\SkeletonBoss;
use OguzhanUmutlu\Bosses\entities\types\SkeletonHorseBoss;
use OguzhanUmutlu\Bosses\entities\types\SlimeBoss;
use OguzhanUmutlu\Bosses\entities\types\SnowGolemBoss;
use OguzhanUmutlu\Bosses\entities\types\SpiderBoss;
use OguzhanUmutlu\Bosses\entities\types\SquidBoss;
use OguzhanUmutlu\Bosses\entities\types\StrayBoss;
use OguzhanUmutlu\Bosses\entities\types\VillagerBoss;
use OguzhanUmutlu\Bosses\entities\types\VindicatorBoss;
use OguzhanUmutlu\Bosses\entities\types\WitchBoss;
use OguzhanUmutlu\Bosses\entities\types\WitherBoss;
use OguzhanUmutlu\Bosses\entities\types\WitherSkeletonBoss;
use OguzhanUmutlu\Bosses\entities\types\WolfBoss;
use OguzhanUmutlu\Bosses\entities\types\ZombieBoss;
use OguzhanUmutlu\Bosses\entities\types\ZombieHorseBoss;
use OguzhanUmutlu\Bosses\entities\types\ZombiePigmanBoss;
use OguzhanUmutlu\Bosses\entities\types\ZombieVillager;
use OguzhanUmutlu\Bosses\tasks\BossTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Bosses extends PluginBase {
    /*** @var Config */
    public static $config;
    /*** @var Bosses */
    public static $instance;
    /*** @var string */
    public static $bossSaves = [];
    /*
     * bossType => bossClass
     * */
    public function onEnable() {
        self::$config = $this->getConfig();
        self::$instance = $this;
        $def = $this->getServer()->getDefaultLevel();
        $vec = $def->getSpawnLocation()->asVector3();
        $def->loadChunk($vec->x >> 4, $vec->z >> 4);
        foreach([
                    new AgentBoss($def, Entity::createBaseNBT($vec)),
                    new BatBoss($def, Entity::createBaseNBT($vec)),
                    new BlazeBoss($def, Entity::createBaseNBT($vec)),
                    new CaveSpiderBoss($def, Entity::createBaseNBT($vec)),
                    new ChickenBoss($def, Entity::createBaseNBT($vec)),
                    new CowBoss($def, Entity::createBaseNBT($vec)),
                    new CreeperBoss($def, Entity::createBaseNBT($vec)),
                    new DolphinBoss($def, Entity::createBaseNBT($vec)),
                    new DonkeyBoss($def, Entity::createBaseNBT($vec)),
                    new ElderGuardianBoss($def, Entity::createBaseNBT($vec)),
                    new EnderDragonBoss($def, Entity::createBaseNBT($vec)),
                    new EndermanBoss($def, Entity::createBaseNBT($vec)),
                    new EndermiteBoss($def, Entity::createBaseNBT($vec)),
                    new GhastBoss($def, Entity::createBaseNBT($vec)),
                    new GuardianBoss($def, Entity::createBaseNBT($vec)),
                    new HorseBoss($def, Entity::createBaseNBT($vec)),
                    new HuskBoss($def, Entity::createBaseNBT($vec)),
                    new IronGolemBoss($def, Entity::createBaseNBT($vec)),
                    new LlamaBoss($def, Entity::createBaseNBT($vec)),
                    new MagmaCube($def, Entity::createBaseNBT($vec)),
                    new MooshroomBoss($def, Entity::createBaseNBT($vec)),
                    new MuleBoss($def, Entity::createBaseNBT($vec)),
                    new NPCBoss($def, Entity::createBaseNBT($vec)),
                    new OcelotBoss($def, Entity::createBaseNBT($vec)),
                    new ParrotBoss($def, Entity::createBaseNBT($vec)),
                    new PhantomBoss($def, Entity::createBaseNBT($vec)),
                    new PigBoss($def, Entity::createBaseNBT($vec)),
                    new PolarBearBoss($def, Entity::createBaseNBT($vec)),
                    new RabbitBoss($def, Entity::createBaseNBT($vec)),
                    new SheepBoss($def, Entity::createBaseNBT($vec)),
                    new ShulkerBoss($def, Entity::createBaseNBT($vec)),
                    new SilverfishBoss($def, Entity::createBaseNBT($vec)),
                    new SkeletonBoss($def, Entity::createBaseNBT($vec)),
                    new SkeletonHorseBoss($def, Entity::createBaseNBT($vec)),
                    new SlimeBoss($def, Entity::createBaseNBT($vec)),
                    new SnowGolemBoss($def, Entity::createBaseNBT($vec)),
                    new SpiderBoss($def, Entity::createBaseNBT($vec)),
                    new SquidBoss($def, Entity::createBaseNBT($vec)),
                    new StrayBoss($def, Entity::createBaseNBT($vec)),
                    new VillagerBoss($def, Entity::createBaseNBT($vec)),
                    new VindicatorBoss($def, Entity::createBaseNBT($vec)),
                    new WitchBoss($def, Entity::createBaseNBT($vec)),
                    new WitherBoss($def, Entity::createBaseNBT($vec)),
                    new WitherSkeletonBoss($def, Entity::createBaseNBT($vec)),
                    new WolfBoss($def, Entity::createBaseNBT($vec)),
                    new ZombieBoss($def, Entity::createBaseNBT($vec)),
                    new ZombieHorseBoss($def, Entity::createBaseNBT($vec)),
                    new ZombiePigmanBoss($def, Entity::createBaseNBT($vec)),
                    new ZombieVillager($def, Entity::createBaseNBT($vec))
                ] as $entity)
            if($entity instanceof BossEntity) {
                Entity::registerEntity(get_class($entity), true, [$entity->getName()]);
                self::$bossSaves[$entity->getName()] = get_class($entity);
            }
        foreach(self::$config->getNested("bosses", []) as $boss) {
            $boss["isMinion"] = false;
            $attributes = BossAttributes::fromArray($boss["attributes"]);
            if(!$this->getServer()->isLevelLoaded($boss["position"]["level"]))
                $this->getServer()->loadLevel($boss["position"]["level"]);
            if($this->getServer()->getLevelByName($boss["position"]["level"]) instanceof Level && $boss["autospawn"])
                new BossTask($attributes, $boss["scale"], $boss["health"], $boss["maxHealth"], $boss["name"], new Position($boss["position"]["x"], $boss["position"]["y"], $boss["position"]["z"], $this->getServer()->getLevelByName($boss["position"]["level"])), $boss["autospawn"], null, $boss["id"]);
        }
    }
    /*** @var BossEntity */
    public $entities = [];
    /*** @var BossTask[] */
    private $tasks = [];
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() != "boss" || !$sender->hasPermission($command->getPermission())) return true;
        if(!$sender instanceof Player) {
            $sender->sendMessage("§c> Use this command in-game.");
            return true;
        }
        $sender->sendForm(new MenuForm(
            "Boss Menu",
            "Select an action:",
            [
                new MenuOption("Create new Boss"),
                new MenuOption("Manage existing Boss"),
                new MenuOption("Remove existing Boss"),
                new MenuOption("List of existing Bosses")
            ],
            function(Player $player, int $res): void {
                switch($res) {
                    case 0:
                        $player->sendForm(new CustomForm(
                            "Boss Menu > Create new boss",
                            [
                                new Dropdown("type", "Boss type", array_keys(self::$bossSaves)),
                                new Label("ssss", "\n\nGeneral Attributes:"),
                                new Input("nametag", "Nametag", "My boss", "My boss"),
                                new Toggle("nametagvisible", "Name tag visible", true),
                                new Toggle("nametagalwaysvisible", "Name tag always visible", true),
                                new Input("health", "HP (2 HP = 1 Heart)", "20", "20"),
                                new Input("maxhealth", "Maximum HP (2 HP = 1 Heart)", "20", "20"),
                                new Input("scale", "Scale", "1", "1"),
                                new Input("seconds", "Auto spawn countdown(seconds/none) [Optional]", "60", "none"),
                                new Label("sssss", "\n\nBoss attributes:"),
                                new Input("speed", "Speed", "1", "1"),
                                new Toggle("canclimb", "Can climb"),
                                new Toggle("canswim", "Can swim"),
                                new StepSlider("hitchance", "Hit chance", array_map(function($n){return (string)$n;}, range(0, 100)), 50),
                                new Toggle("falldamage", "Fall damage", true),
                                new Toggle("canspawnminions", "Can spawn minions"),
                                new Input("visionreach", "How far can boss see players from?", "10", "10"),
                                new Input("hitreach", "Hit reach", "1.2", "1.2"),
                                new Input("damageamount", "Damage amount HP (2 HP = 1 Heart)", "2", "2"),
                                new Toggle("damagefire", "Can boss set on fire players when boss hits them?"),
                                new Input("hitmotionx", "Hit extra knockback X", "0", "0"),
                                new Input("hitmotiony", "Hit extra knockback Y", "0", "0"),
                                new Input("hitmotionz", "Hit extra knockback Z", "0", "0"),
                                new Input("hitmotion", "Hit extra knockback General", "0", "0"),
                                new Toggle("canshoot", "Can boss shoot", false),
                                new Input("minionspawnseconds", "Minion spawn countdown(seconds) [If minions are disabled you don't need to complete this]", "60", "60"),
                                new Toggle("isalwaysaggressive", "Is always aggressive", true),
                                new Toggle("eyeaggressive", "Is it being aggressive when player looks his eyes", true),
                                new Label("drops", "Boss and minion's drops are configurable in config.yml"),
                                new Toggle("spawnnow", "Spawn now?")
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $attributes = BossAttributes::fromArray([
                                    "speed" => (float)$response->getString("speed"),
                                    "canClimb" => $response->getBool("canclimb"),
                                    "canSwim" => $response->getBool("canswim"),
                                    "hitChance" => $response->getInt("hitchance"),
                                    "fallDamage" => $response->getBool("falldamage"),
                                    "canSpawnMinions" => $response->getBool("canspawnminions"),
                                    "visionReach" => (float)$response->getString("visionreach"),
                                    "hitReach" => (float)$response->getString("hitreach"),
                                    "damageAmount" => (float)$response->getString("damageamount"),
                                    "damageFire" => $response->getBool("damagefire"),
                                    "hitMotionX" => (float)$response->getString("hitmotionx"),
                                    "hitMotionY" => (float)$response->getString("hitmotiony"),
                                    "hitMotionZ" => (float)$response->getString("hitmotionz"),
                                    "hitMotion" => (float)$response->getString("hitmotion"),
                                    "canShoot" => $response->getBool("canshoot"),
                                    "minionSpawnTickAmount" => (float)$response->getString("minionspawnseconds")*20,
                                    "isAlwaysAggressive" => $response->getBool("isalwaysaggressive"),
                                    "eyeAggressive" => $response->getBool("canshoot"),
                                    "drops" => [],
                                    "minionDrops" => []
                                ]);
                                $entity = Entity::createEntity(array_keys(self::$bossSaves)[$response->getInt("type")], $player->level, Entity::createBaseNBT($player), $attributes);
                                if(!$entity instanceof BossEntity) return;
                                $entity->isNew = true;
                                $id = floor(microtime(true));
                                if(isset($this->entities[$id]) && $this->entities[$id] instanceof BossEntity && !$this->entities[$id]->isClosed())
                                    $this->entities[$id]->flagForDespawn();
                                $this->entities[$id] = $entity;
                                $entity->setAttributes($attributes);
                                $entity->setNameTag($response->getString("nametag"));
                                $entity->setNameTagVisible($response->getBool("nametagvisible"));
                                $entity->setNameTagAlwaysVisible($response->getBool("nametagalwaysvisible"));
                                $entity->setHealth((float)$response->getString("health"));
                                $entity->setMaxHealth((float)$response->getString("maxhealth"));
                                $entity->setScale((float)$response->getString("scale"));
                                $conf = $this->getConfig();
                                $list = $conf->getNested("bosses", []);
                                $autoSpawn = strlen($response->getString("seconds")) < 1 || !is_numeric($response->getString("seconds")) ? null : (float)$response->getString("seconds")*20;
                                $l = [
                                    "id" => $id,
                                    "type" => self::$bossSaves[array_keys(self::$bossSaves)[$response->getInt("type")]],
                                    "attributes" => $attributes->toArray(),
                                    "nametag" => $response->getString("nametag"),
                                    "nametagvisible" => $response->getBool("nametagvisible"),
                                    "nametagalwaysvisible" => $response->getBool("nametagalwaysvisible"),
                                    "health" => (float)$response->getString("health"),
                                    "maxHealth" => (float)$response->getString("maxhealth"),
                                    "scale" => (float)$response->getString("scale"),
                                    "position" => [
                                        "x" => $player->x,
                                        "y" => $player->y,
                                        "z" => $player->z,
                                        "level" => $player->level->getFolderName()
                                    ],
                                    "autospawn" => $autoSpawn,
                                    "addedby" => $player->getName(),
                                    "managers" => []
                                ];
                                $list[$id] = $l;
                                $conf->setNested("bosses", $list);
                                $conf->save();
                                $conf->reload();
                                if($autoSpawn)
                                    $this->tasks[$l["id"]] = new BossTask($attributes, $l["scale"], $l["health"], $l["maxHealth"], $l["type"], $player, $autoSpawn, $entity, $l["id"]);
                                $player->sendMessage("§a> Boss updated and saved!");
                            }
                        ));
                        break;
                    case 1:
                        $bosses = $this->getConfig()->getNested("bosses", []);
                        $player->sendForm(new MenuForm(
                            "Boss Menu > Manage existing Boss",
                            "Select a boss:",
                            array_map(function($n){return new MenuOption($n["nametag"]);}, $bosses),
                            function(Player $player, int $i) use ($bosses): void {
                                $boss = $bosses[$i];
                                $inc = false;
                                foreach($this->getConfig()->getNested("bosses", []) as $b)
                                    if($b["id"] == $boss["id"])
                                        $inc = true;
                                if(!$inc) {
                                    $player->sendMessage("§c> This boss is deleted while you are trying to manage it!");
                                    return;
                                }
                                $attributes = BossAttributes::fromArray($boss["attributes"]);
                                $player->sendForm(new CustomForm(
                                    "Boss Menu > Manage existing Boss > ".$boss["id"],
                                    [
                                        new Dropdown("type", "Boss type", array_keys(self::$bossSaves), array_search(array_search(self::$bossSaves, $boss["type"]), array_keys(self::$bossSaves))),
                                        new Label("ssss", "\n\nGeneral Attributes:"),
                                        new Input("nametag", "Nametag", "My boss", $boss["nametag"]),
                                        new Toggle("nametagvisible", "Name tag visible", $boss["nametagvisible"]),
                                        new Toggle("nametagalwaysvisible", "Name tag always visible", $boss["nametagalwaysvisible"]),
                                        new Input("health", "HP (2 HP = 1 Heart)", "20", $boss["health"]),
                                        new Input("maxhealth", "Maximum HP (2 HP = 1 Heart)", "20", $boss["maxHealth"]),
                                        new Input("scale", "Scale", "1", $boss["scale"]),
                                        new Input("seconds", "Auto spawn countdown(seconds/none) [Optional]", "60", $boss["autoSpawn"] ?? "none"),
                                        new Label("sssss", "\n\nBoss attributes:"),
                                        new Input("speed", "Speed", "1", $attributes->speed),
                                        new Toggle("canclimb", "Can climb", $attributes->canClimb),
                                        new Toggle("canswim", "Can swim", $attributes->canSwim),
                                        new StepSlider("hitchance", "Hit chance", array_map(function($n){return (string)$n;}, range(0, 100)), $attributes->hitChance),
                                        new Toggle("falldamage", "Fall damage", $attributes->fallDamage),
                                        new Toggle("canspawnminions", "Can spawn minions", $attributes->canSpawnMinions),
                                        new Input("visionreach", "How far can boss see players from?", "10", $attributes->visionReach),
                                        new Input("hitreach", "Hit reach", "1.2", $attributes->hitReach),
                                        new Input("damageamount", "Damage amount HP (2 HP = 1 Heart)", "2", $attributes->damageAmount),
                                        new Toggle("damagefire", "Can boss set on fire players when boss hits them?", $attributes->damageFire),
                                        new Input("hitmotionx", "Hit extra knockback X", "0", $attributes->hitMotionX),
                                        new Input("hitmotiony", "Hit extra knockback Y", "0", $attributes->hitMotionY),
                                        new Input("hitmotionz", "Hit extra knockback Z", "0", $attributes->hitMotionZ),
                                        new Input("hitmotion", "Hit extra knockback General", "0", $attributes->hitMotion),
                                        new Toggle("canshoot", "Can boss shoot", $attributes->canShoot),
                                        new Input("minionspawnseconds", "Minion spawn countdown(seconds) [If minions are disabled you don't need to complete this]", "60", $attributes->minionSpawnTickAmount/20),
                                        new Toggle("isalwaysaggressive", "Is always aggressive", $attributes->isAlwaysAggressive),
                                        new Toggle("eyeaggressive", "Is it being aggressive when player looks his eyes", $attributes->eyeAggressive),
                                        new Label("drops", "Boss and minions' drops are configurable in config.yml")
                                    ],
                                    function(Player $player, CustomFormResponse $response) use ($boss): void {
                                        $inc = false;
                                        foreach($this->getConfig()->getNested("bosses", []) as $b)
                                            if($b["id"] == $boss["id"])
                                                $inc = true;
                                        if(!$inc) {
                                            $player->sendMessage("§c> This boss is deleted while you are trying to manage it!");
                                            return;
                                        }
                                        $attributes = BossAttributes::fromArray([
                                            "speed" => (float)$response->getString("speed"),
                                            "canClimb" => $response->getBool("canclimb"),
                                            "canSwim" => $response->getBool("canswim"),
                                            "hitChance" => $response->getInt("hitchance"),
                                            "fallDamage" => $response->getBool("falldamage"),
                                            "canSpawnMinions" => $response->getBool("canspawnminions"),
                                            "visionReach" => (float)$response->getString("visionreach"),
                                            "hitReach" => (float)$response->getString("hitreach"),
                                            "damageAmount" => (float)$response->getString("damageamount"),
                                            "damageFire" => $response->getBool("damagefire"),
                                            "hitMotionX" => (float)$response->getString("hitmotionx"),
                                            "hitMotionY" => (float)$response->getString("hitmotiony"),
                                            "hitMotionZ" => (float)$response->getString("hitmotionz"),
                                            "hitMotion" => (float)$response->getString("hitmotion"),
                                            "canShoot" => $response->getBool("canshoot"),
                                            "minionSpawnTickAmount" => (float)$response->getString("minionspawnseconds")*20,
                                            "isAlwaysAggressive" => $response->getBool("isalwaysaggressive"),
                                            "eyeAggressive" => $response->getBool("canshoot"),
                                            "drops" => [],
                                            "minionDrops" => []
                                        ]);
                                        $conf = $this->getConfig();
                                        $list = $conf->getNested("bosses", []);
                                        $autoSpawn = strlen($response->getString("seconds")) < 1 || !is_numeric($response->getString("seconds")) ? null : (float)$response->getString("seconds")*20;
                                        $l = [
                                            "id" => $boss["id"],
                                            "type" => self::$bossSaves[array_keys(self::$bossSaves)[$response->getInt("type")]],
                                            "attributes" => $attributes->toArray(),
                                            "nametag" => $response->getString("nametag"),
                                            "nametagvisible" => $response->getBool("nametagvisible"),
                                            "nametagalwaysvisible" => $response->getBool("nametagalwaysvisible"),
                                            "health" => (float)$response->getString("health"),
                                            "maxHealth" => (float)$response->getString("maxhealth"),
                                            "scale" => (float)$response->getString("scale"),
                                            "position" => [
                                                "x" => $player->x,
                                                "y" => $player->y,
                                                "z" => $player->z,
                                                "level" => $player->level->getFolderName()
                                            ],
                                            "autospawn" => $autoSpawn,
                                            "addedby" => $list[$boss["id"]]["addedby"],
                                            "manager" => array_merge($list[$boss["id"]]["manager"], [$player->getName()])
                                        ];
                                        $list[$boss["id"]] = $l;
                                        $conf->setNested("bosses", $list);
                                        $entities = [];
                                        if($this->tasks[$boss["id"]] ?? null) {
                                            $this->tasks[$boss["id"]]->attributes = $attributes;
                                            $entities[] = $this->tasks[$boss["id"]]->info["entity"];
                                            $this->tasks[$boss["id"]]->info = [
                                                "scale" => (float)$response->getString("scale"),
                                                "health" => (float)$response->getString("health"),
                                                "maxHealth" => (float)$response->getString("maxhealth"),
                                                "name" => self::$bossSaves[array_keys(self::$bossSaves)[$response->getInt("type")]],
                                                "position" => $this->tasks[$boss["id"]]->info["position"],
                                                "ticks" => $autoSpawn,
                                                "entity" => $this->tasks[$boss["id"]]->info["entity"],
                                                "id" => $boss["id"]
                                            ];
                                        }
                                        if(!isset($this->entities[$boss["id"]]))
                                            $entities[] = $this->entities[$boss["id"]];
                                        foreach($entities as $entity) {
                                            $entity->setAttributes($attributes);
                                            $entity->setNameTag($response->getString("nametag"));
                                            $entity->setNameTagVisible($response->getBool("nametagvisible"));
                                            $entity->setNameTagAlwaysVisible($response->getBool("nametagalwaysvisible"));
                                            $entity->setHealth((float)$response->getString("health"));
                                            $entity->setMaxHealth((float)$response->getString("maxhealth"));
                                            $entity->setScale((float)$response->getString("scale"));
                                        }
                                    }
                                ));
                            }
                        ));
                        break;
                    case 2:
                        $bosses = $this->getConfig()->getNested("bosses", []);
                        $player->sendForm(new MenuForm(
                            "Boss Menu > Remove existing Boss",
                            "Select a boss:",
                            array_map(function($n){return $n["nametag"];}, $bosses),
                            function(Player $player, int $i) use ($bosses): void {
                                $boss = $bosses[$i];
                                $inc = false;
                                foreach ($this->getConfig()->getNested("bosses", []) as $b)
                                    if ($b["id"] == $boss["id"])
                                        $inc = true;
                                if (!$inc) {
                                    $player->sendMessage("§c> This boss is deleted while you are trying to manage it!");
                                    return;
                                }
                                $this->getConfig()->removeNested("bosses.".$boss["id"]);
                                $this->getConfig()->save();
                                $this->getConfig()->reload();
                                if(isset($this->entities[$boss["id"]]) && $this->entities[$boss["id"]] instanceof BossEntity && !$this->entities[$boss["id"]]->isClosed())
                                    $this->entities[$boss["id"]]->flagForDespawn();
                                if($this->tasks[$boss["id"]] ?? null) {
                                    if($this->tasks[$boss["id"]]->info["entity"] instanceof BossEntity && !$this->tasks[$boss["id"]]->info["entity"]->isClosed())
                                        $this->tasks[$boss["id"]]->info["entity"]->flagForDespawn();
                                    if($this->tasks[$boss["id"]]->getHandler())
                                        $this->tasks[$boss["id"]]->getHandler()->cancel();
                                }
                                $player->sendMessage("§a> Boss removed and saved!");
                            }));
                        break;
                    case 3:
                        $player->sendForm(new MenuForm(
                            "Boss Menu > List of existing Bosses",
                            implode("\n\n", array_map(function($boss) {
                                return "ID: ".$boss["id"]."\nType: ".array_search($boss["type"], self::$bossSaves)."\nAdded by: ".$boss["addedby"]."\nManaged by: ".implode(", ",array_unique($boss["managers"]))."\n";
                                }, $this->getConfig()->getNested("bosses", []))),
                            [
                                new MenuOption("Back"),
                                new MenuOption("Exit")
                            ],
                            function(Player $player, int $response): void {
                                if($response == 0)
                                    $player->chat("/boss");
                            }
                        ));
                        break;
                }
            }
        ));
        return true;
    }
}