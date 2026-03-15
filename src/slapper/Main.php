<?php

declare(strict_types=1);

namespace slapper;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemBlock;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use slapper\entities\SlapperBoat;
use slapper\entities\SlapperEndCrystal;
use slapper\entities\SlapperBat;
use slapper\entities\SlapperBlaze;
use slapper\entities\SlapperCaveSpider;
use slapper\entities\SlapperChicken;
use slapper\entities\SlapperCow;
use slapper\entities\SlapperCreeper;
use slapper\entities\SlapperDonkey;
use slapper\entities\SlapperElderGuardian;
use slapper\entities\SlapperEnderman;
use slapper\entities\SlapperEndermite;
use slapper\entities\SlapperEntity;
use slapper\entities\SlapperEvoker;
use slapper\entities\SlapperFallingSand;
use slapper\entities\SlapperGhast;
use slapper\entities\SlapperGuardian;
use slapper\entities\SlapperHorse;
use slapper\entities\SlapperHuman;
use slapper\entities\SlapperIronGolem;
use slapper\entities\SlapperHusk;
use slapper\entities\SlapperLavaSlime;
use slapper\entities\SlapperLlama;
use slapper\entities\SlapperMinecart;
use slapper\entities\SlapperMule;
use slapper\entities\SlapperMushroomCow;
use slapper\entities\SlapperOcelot;
use slapper\entities\SlapperPig;
use slapper\entities\SlapperPigZombie;
use slapper\entities\SlapperPolarBear;
use slapper\entities\SlapperPrimedTNT;
use slapper\entities\SlapperRabbit;
use slapper\entities\SlapperSheep;
use slapper\entities\SlapperShulker;
use slapper\entities\SlapperSilverfish;
use slapper\entities\SlapperSkeletonHorse;
use slapper\entities\SlapperSkeleton;
use slapper\entities\SlapperSlime;
use slapper\entities\SlapperSnowman;
use slapper\entities\SlapperSpider;
use slapper\entities\SlapperSquid;
use slapper\entities\SlapperStray;
use slapper\entities\SlapperVex;
use slapper\entities\SlapperVillager;
use slapper\entities\SlapperVindicator;
use slapper\entities\SlapperWitch;
use slapper\entities\SlapperWither;
use slapper\entities\SlapperWitherSkeleton;
use slapper\entities\SlapperWolf;
use slapper\entities\SlapperZombie;
use slapper\entities\SlapperZombieHorse;
use slapper\entities\SlapperZombieVillager;
use slapper\events\SlapperCreationEvent;
use slapper\events\SlapperDeletionEvent;
use slapper\events\SlapperHitEvent;
// Импорт FormAPI
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener {

    const ENTITY_TYPES = [
        "Chicken", "Pig", "Sheep", "Cow",
        "MushroomCow", "Wolf", "Enderman", "Spider",
        "Skeleton", "PigZombie", "Creeper", "Slime",
        "Silverfish", "Villager", "Zombie", "Human",
        "Bat", "CaveSpider", "LavaSlime", "Ghast",
        "Ocelot", "Blaze", "ZombieVillager", "Snowman",
        "Minecart", "FallingSand", "Boat", "PrimedTNT",
        "Horse", "Donkey", "Mule", "SkeletonHorse",
        "ZombieHorse", "Witch", "Rabbit", "Stray",
        "Husk", "WitherSkeleton", "IronGolem", "Snowman",
        "LavaSlime", "Squid", "ElderGuardian", "Endermite",
        "Evoker", "Guardian", "PolarBear", "Shulker",
        "Vex", "Vindicator", "Wither", "Llama", "EndCrystal"
    ];

    const ENTITY_ALIASES = [
        "MagmaCube" => "LavaSlime",
        "ZombiePigman" => "PigZombie",
        "Mooshroom" => "MushroomCow",
        "Player" => "Human",
        "VillagerZombie" => "ZombieVillager",
        "SnowGolem" => "Snowman",
        "FallingBlock" => "FallingSand",
        "FakeBlock" => "FallingSand",
        "VillagerGolem" => "IronGolem",
        "EGuardian" => "ElderGuardian",
        "Emite" => "Endermite"
    ];
    
    const PREFIX = TextFormat::GREEN . "[" . TextFormat::YELLOW . "Slapper" . TextFormat::GREEN . "] ";
    const MSG_NO_PERM = TextFormat::GREEN . "[" . TextFormat::YELLOW . "Slapper" . TextFormat::GREEN . "]" . TextFormat::RED . " You don't have permission.";

    /** @var array<string, true> */
    public array $hitSessions = [];
    /** @var array<string, true> */
    public array $idSessions = [];
    public string $helpHeader =
        TextFormat::YELLOW . "---------- " .
        TextFormat::GREEN . "[" . TextFormat::YELLOW . "Slapper Help" . TextFormat::GREEN . "] " .
        TextFormat::YELLOW . "----------";

    /** @var string[] */
    public array $mainArgs = [
        "help: /slapper help",
        "spawn: /slapper spawn <type> [name]",
        "edit: /slapper edit [id] [args...]",
        "id: /slapper id",
        "remove: /slapper remove [id]",
        "version: /slapper version",
        "cancel: /slapper cancel",
        "entities: /slapper entities",
    ];
    /** @var string[] */
    public array $editArgs = [
        "helmet: /slapper edit <eid> helmet <item_name>",
        "chestplate: /slapper edit <eid> chestplate <item_name>",
        "leggings: /slapper edit <eid> leggings <item_name>",
        "boots: /slapper edit <eid> boots <item_name>",
        "skin: /slapper edit <eid> skin",
        "name: /slapper edit <eid> name <name>",
        "addcommand: /slapper edit <eid> addcommand <command>",
        "delcommand: /slapper edit <eid> delcommand <command>",
        "listcommands: /slapper edit <eid> listcommands",
        "blockid: /slapper edit <eid> block <block_name>",
        "scale: /slapper edit <eid> scale <size>",
        "tphere: /slapper edit <eid> tphere",
        "tpto: /slapper edit <eid> tpto",
        "menuname: /slapper edit <eid> menuname <name/remove>"
    ];

    private SlapperCommandSender $commandSender;

    /**
     * @return void
     */
    public function onEnable(): void {
        $this->commandSender = new SlapperCommandSender($this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->registerEntities();
	$this->checkUpdate();
    }

    public function registerEntities(): void {
        $entityFactory = EntityFactory::getInstance();
        foreach ([
             SlapperCreeper::class, SlapperBat::class, SlapperSheep::class,
             SlapperPigZombie::class, SlapperGhast::class, SlapperBlaze::class,
             SlapperIronGolem::class, SlapperSnowman::class, SlapperOcelot::class,
             SlapperZombieVillager::class, SlapperCow::class,
             SlapperZombie::class, SlapperSquid::class, SlapperVillager::class,
             SlapperSpider::class, SlapperPig::class, SlapperMushroomCow::class,
             SlapperWolf::class, SlapperLavaSlime::class, SlapperSilverfish::class,
             SlapperSkeleton::class, SlapperSlime::class, SlapperChicken::class,
             SlapperEnderman::class, SlapperCaveSpider::class, SlapperBoat::class,
             SlapperMinecart::class, SlapperMule::class, SlapperWitch::class,
             SlapperPrimedTNT::class, SlapperHorse::class, SlapperDonkey::class,
             SlapperSkeletonHorse::class, SlapperZombieHorse::class, SlapperRabbit::class,
             SlapperStray::class, SlapperHusk::class, SlapperWitherSkeleton::class,
             SlapperFallingSand::class, SlapperElderGuardian::class, SlapperEndermite::class,
             SlapperEvoker::class, SlapperGuardian::class, SlapperLlama::class,
             SlapperPolarBear::class, SlapperShulker::class, SlapperVex::class,
             SlapperVindicator::class, SlapperWither::class, SlapperEndCrystal::class
        ] as $className){
            $stringPos = strpos($className, 'Slapper');
            if($stringPos === false){
                throw new AssumptionFailedError("$className should always contain the word 'Slapper'");
            }
            $entityName = substr($className, $stringPos);
            $entityFactory->register($className, static function(World $world, CompoundTag $nbt) use($className): SlapperEntity{
                /** @var SlapperEntity $entityClass */
                $entityClass = new $className(EntityDataHelper::parseLocation($nbt, $world), $nbt);
                return $entityClass;
            }, [$entityName]);
        }
        $entityFactory->register(SlapperHuman::class, static function(World $world, CompoundTag $nbt): SlapperHuman{
            return new SlapperHuman(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ['Human']);
    }

	public function checkUpdate(): void {
		$this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask($this->getDescription()->getName(), $this->getDescription()->getVersion()));
	}

	/**
     * @param CommandSender $sender
     * @param Command       $command
     * @param string        $label
     * @param string[]      $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch (strtolower($command->getName())) {
            case "nothing":
                return true;
            case "rca":
                if (count($args) < 2) {
                    $sender->sendMessage(self::PREFIX . "Please enter a player and a command.");
                    return true;
                }
                $player = $this->getServer()->getPlayerExact(array_shift($args));
                if ($player instanceof Player) {
                    $this->getServer()->dispatchCommand($player, trim(implode(" ", $args)));
		} else {
                    $sender->sendMessage(self::PREFIX . "Player not found.");
		}
		return true;
	    case "slapper":
                if ($sender instanceof Player) {
                    if (!isset($args[0])) {
                        $sender->sendMessage(self::PREFIX . "Please type '/slapper help'.");
                        return true;
                    }
                    $arg = array_shift($args);
                    switch ($arg) {
                        case "id":
                            if (!$sender->hasPermission("slapper.id")) {
                                $sender->sendMessage(self::MSG_NO_PERM);
                                return true;
                            }
                            $this->idSessions[$sender->getName()] = true;
                            $sender->sendMessage(self::PREFIX . "Hit an entity to get its ID!");
                            return true;
                        case "version":
                            if (!$sender->hasPermission("slapper.version")) {
                                $sender->sendMessage(self::MSG_NO_PERM);
                                return true;
                            }
                            $desc = $this->getDescription();
                            $sender->sendMessage(self::PREFIX . TextFormat::BLUE . $desc->getName() . " " . $desc->getVersion() . " " . TextFormat::GREEN . "by " . TextFormat::GOLD . "jojoe77777");
                            return true;
                        case "cancel":
                        case "stopremove":
                        case "stopid":
                            unset($this->hitSessions[$sender->getName()]);
                            unset($this->idSessions[$sender->getName()]);
                            $sender->sendMessage(self::PREFIX . "Cancelled.");
                            return true;
                        case "list":
                        case "entities":
                            if (!$sender->hasPermission("slapper.list")) {
                                $sender->sendMessage(self::MSG_NO_PERM);
                                return true;
                            }
                            $sender->sendMessage(self::PREFIX . "Entity List: "
. TextFormat::BLUE . "Bat, Blaze, Boat, CaveSpider, Chicken, Cow, Creeper, Donkey, ElderGuardian, EndCrystal, Enderman, Endermite, Evoker, FallingSand, Ghast, Guardian, Horse, Human, Husk, IronGolem, LavaSlime, Llama, Minecart, Mule, MushroomCow, Ocelot, Pig, PigZombie, PolarBear, PrimedTNT, Rabbit, Sheep, Shulker, Silverfish, Skeleton, SkeletonHorse, Slime, Snowman, Spider, Squid, Stray, Vex, Villager, Vindicator, Witch, Wither, WitherSkeleton, Wolf, Zombie, ZombieHorse, ZombieVillager");
                            return true;
                        case "remove":
                            if (!$sender->hasPermission("slapper.remove")) {
                                $sender->sendMessage(self::MSG_NO_PERM);
                                return true;
                            }
                            if (!isset($args[0])) {
                                $this->hitSessions[$sender->getName()] = true;
                                $sender->sendMessage(self::PREFIX . "Hit an entity to remove it.");
                                return true;
                            }
                            $entity = $sender->getWorld()->getEntity((int) $args[0]);
                            if ($entity !== null) {
                                if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
                                    (new SlapperDeletionEvent($entity))->call();
                                    $entity->close();
                                    $sender->sendMessage(self::PREFIX . "Entity removed.");
                                } else {
                                    $sender->sendMessage(self::PREFIX . "That entity is not handled by Slapper.");
                                }
                            } else {
                                $sender->sendMessage(self::PREFIX . "Entity does not exist.");
                            }
                            return true;
                        case "edit":
                            if (!$sender->hasPermission("slapper.edit")) {
                                $sender->sendMessage(self::MSG_NO_PERM);
                                return true;
                            }
                            if (isset($args[0])) {
                                $world = $sender->getWorld();
                                $entity = $world->getEntity((int) $args[0]);
                                if ($entity !== null) {
                                    if ($entity instanceof SlapperInterface) {
                                        if (isset($args[1])) {
                                            switch ($args[1]) {
                                                case "helm":
                                                case "helmet":
                                                case "head":
                                                case "hat":
                                                case "cap":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $item = StringToItemParser::getInstance()->parse($args[2]);
                                                            if ($item === null){
                                                                $sender->sendMessage(self::PREFIX . "There is no such item with name $args[2]");
                                                                return true;
                                                            }
                                                            $entity->getArmorInventory()->setHelmet($item);
                                                            $sender->sendMessage(self::PREFIX . "Helmet updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "Please enter an item ID.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can not wear armor.");
                                                    }
                                                    return true;
                                                case "chest":
                                                case "shirt":
                                                case "chestplate":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $item = StringToItemParser::getInstance()->parse($args[2]);
                                                            if ($item === null){
                                                                $sender->sendMessage(self::PREFIX . "There is no such item with name $args[2]");
                                                                return true;
                                                            }
                                                            $entity->getArmorInventory()->setChestplate($item);
                                                            $sender->sendMessage(self::PREFIX . "Chestplate updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "Please enter an item ID.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can not wear armor.");
                                                    }
                                                    return true;
                                                case "pants":
                                                case "legs":
                                                case "leggings":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $item = StringToItemParser::getInstance()->parse($args[2]);
                                                            if ($item === null){
                                                                $sender->sendMessage(self::PREFIX . "There is no such item with name $args[2]");
                                                                return true;
                                                            }
                                                            $entity->getArmorInventory()->setLeggings($item);
                                                            $sender->sendMessage(self::PREFIX . "Leggings updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "Please enter an item ID.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can not wear armor.");
                                                    }
                                                    return true;
                                                case "feet":
                                                case "boots":
                                                case "shoes":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $item = StringToItemParser::getInstance()->parse($args[2]);
                                                            if ($item === null){
                                                                $sender->sendMessage(self::PREFIX . "There is no such item with name $args[2]");
                                                                return true;
                                                            }
                                                            $entity->getArmorInventory()->setBoots($item);
                                                            $sender->sendMessage(self::PREFIX . "Boots updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "Please enter an item ID.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can not wear armor.");
                                                    }
                                                    return true;
                                                case "hand":
                                                case "item":
                                                case "holding":
                                                case "arm":
                                                case "held":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $item = StringToItemParser::getInstance()->parse($args[2]);
                                                            if ($item === null){
                                                                $sender->sendMessage(self::PREFIX . "There is no such item with name $args[2]");
                                                                return true;
                                                            }
                                                            $entity->getInventory()->setItemInHand($item);
                                                            $sender->sendMessage(self::PREFIX . "Item updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "Please enter an item ID.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can not wear armor.");
                                                    }
                                                    return true;
                                                case "setskin":
                                                case "changeskin":
                                                case "editskin";
                                                case "skin":
                                                    if ($entity instanceof SlapperHuman) {
                                                        $entity->setSkin($sender->getSkin());
                                                        $entity->sendData($entity->getViewers());
                                                        $sender->sendMessage(self::PREFIX . "Skin updated.");
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can't have a skin.");
                                                    }
                                                    return true;
                                                case "name":
                                                case "customname":
                                                    if (isset($args[2])) {
                                                        array_shift($args);
                                                        array_shift($args);
                                                        $entity->setNameTag(str_replace(["{color}", "{line}"], ["§", "\n"], trim(implode(" ", $args))));
                                                        $entity->sendData($entity->getViewers());
                                                        $sender->sendMessage(self::PREFIX . "Name updated.");
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "Please enter a name.");
                                                    }
                                                    return true;
                                                case "listname":
                                                case "nameonlist":
                                                case "menuname":
                                                    if ($entity instanceof SlapperHuman) {
                                                        if (isset($args[2])) {
                                                            $type = 0;
                                                            array_shift($args);
                                                            array_shift($args);
                                                            $input = trim(implode(" ", $args));
                                                            switch (strtolower($input)) {
                                                                case "remove":
                                                                case "":
                                                                case "disable":
                                                                case "off":
                                                                case "hide":
                                                                    $type = 1;
                                                            }
                                                            if ($type === 0) {
                                                                $entity->setMenuName($input);
                                                            } else {
                                                                $entity->setMenuName("");
                                                            }
                                                            $entity->respawnToAll();
                                                            $sender->sendMessage(self::PREFIX . "Menu name updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "Please enter a menu name.");
                                                            return true;
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity can not have a menu name.");
                                                    }
                                                    return true;
                                                case "addc":
                                                case "addcmd":
                                                case "addcommand":
                                                    if (isset($args[2])) {
                                                        array_shift($args);
                                                        array_shift($args);
                                                        $input = trim(implode(" ", $args));

                                                        if ($entity->hasCommand($input)) {
                                                            $sender->sendMessage(self::PREFIX . "That command has already been added.");
                                                            return true;
                                                        }
                                                        $entity->addCommand($input);
                                                        $sender->sendMessage(self::PREFIX . "Command added.");
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "Please enter a command.");
                                                    }
                                                    return true;
                                                case "delc":
                                                case "delcmd":
                                                case "delcommand":
                                                case "removecommand":
                                                    if (isset($args[2])) {
                                                        array_shift($args);
                                                        array_shift($args);
                                                        $input = trim(implode(" ", $args));

                                                        $entity->removeCommand($input);
                                                        $sender->sendMessage(self::PREFIX . "Command removed.");
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "Please enter a command.");
                                                    }
                                                    return true;
                                                case "listcommands":
                                                case "listcmds":
                                                case "listcs":
                                                    $commands = $entity->getCommands();
                                                    if (count($commands) > 0) {
                                                        $id = 0;

                                                        foreach ($commands as $slapperCommand) {
                                                            $id++;
                                                            $sender->sendMessage(TextFormat::GREEN . "[" . TextFormat::YELLOW . "S" . TextFormat::GREEN . "] " . TextFormat::YELLOW . $id . ". " . TextFormat::GREEN . $slapperCommand . "\n");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "That entity does not have any commands.");
                                                    }
                                                    return true;
                                                case "block":
                                                case "tile":
                                                case "blockid":
                                                case "tileid":
                                                    if (isset($args[2])) {
                                                        if ($entity instanceof SlapperFallingSand) {
                                                            $data = explode(":", $args[2]);
                                                            $blockItem = StringToItemParser::getInstance()->parse($data[0] ?? "stone");
                                                            if (!$blockItem instanceof ItemBlock) {
                                                                $sender->sendMessage(self::PREFIX . "There is no such block with name {$data[0]}");
                                                                return false;
                                                            }
                                                            $entity->setBlock($blockItem->getBlock());
                                                            $sender->sendMessage(self::PREFIX . "Block updated.");
                                                        } else {
                                                            $sender->sendMessage(self::PREFIX . "That entity is not a block.");
                                                        }
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "Please enter a value.");
                                                    }
                                                    return true;
                                                case "teleporthere":
                                                case "tphere":
                                                case "movehere":
                                                case "bringhere":
                                                    $entity->teleport($sender->getLocation());
                                                    $sender->sendMessage(self::PREFIX . "Teleported entity to you.");
                                                    $entity->respawnToAll();
                                                    return true;
                                                case "teleportto":
                                                case "tpto":
                                                case "goto":
                                                case "teleport":
                                                case "tp":
                                                    $sender->teleport($entity->getLocation());
                                                    $sender->sendMessage(self::PREFIX . "Teleported you to entity.");
                                                    return true;
                                                case "scale":
                                                case "size":
                                                    if (isset($args[2]) && (float)$args[2] > 0) {
                                                        $scale = (float) $args[2];
                                                        $entity->setScale($scale);
                                                        $sender->sendMessage(self::PREFIX . "Updated scale.");
                                                    } else {
                                                        $sender->sendMessage(self::PREFIX . "Please enter a value.");
                                                    }
                                                    return true;
                                                default:
                                                    $sender->sendMessage(self::PREFIX . "Unknown command.");
                                                    return true;
                                            }
                                        } else {
                                            $sender->sendMessage($this->helpHeader);
                                            foreach ($this->editArgs as $msgArg) {
                                                $sender->sendMessage(str_replace("<eid>", $args[0], TextFormat::GREEN . " - " . $msgArg . "\n"));
                                            }
                                            return true;
                                        }
                                    } else {
                                        $sender->sendMessage(self::PREFIX . "That entity is not handled by Slapper.");
                                    }
                                } else {
                                    $sender->sendMessage(self::PREFIX . "Entity does not exist.");
                                }
			    } else {
                                $sender->sendMessage($this->helpHeader);
                                foreach ($this->editArgs as $msgArg) {
                                    $sender->sendMessage(TextFormat::GREEN . " - " . $msgArg . "\n");
                                }
			    }
			    return true;
			case "help":
                        case "?":
                            $sender->sendMessage($this->helpHeader);
                            foreach ($this->mainArgs as $msgArg) {
                                $sender->sendMessage(TextFormat::GREEN . " - " . $msgArg . "\n");
                            }
                            return true;
                        case "add":
                        case "make":
                        case "create":
                        case "spawn":
                        case "apawn":
                        case "spanw":
                            $type = array_shift($args);
                            $name = str_replace(["{color}", "{line}"], ["§", "\n"], trim(implode(" ", $args)));
                            if ($type === null || trim($type) === "") {
                                $sender->sendMessage(self::PREFIX . "Please enter an entity type.");
                                return true;
                            }
                            if ($name === "") {
                                $name = $sender->getDisplayName();
                            }
                            $types = self::ENTITY_TYPES;
                            $aliases = self::ENTITY_ALIASES;
                            $chosenType = null;
                            foreach ($types as $t) {
                                if (strtolower($type) === strtolower($t)) {
                                    $chosenType = $t;
                                }
                            }
                            if ($chosenType === null) {
                                foreach ($aliases as $alias => $t) {
                                    if (strtolower($type) === strtolower($alias)) {
                                        $chosenType = $t;
                                    }
                                }
                            }
                            if ($chosenType === null) {
                                $sender->sendMessage(self::PREFIX . "Invalid entity type.");
                                return true;
                            }


                            $slapperClass = __NAMESPACE__ . "\\entities\\Slapper$chosenType";
                            /** @phpstan-ignore-next-line  */
                            Utils::testValidInstance($slapperClass, SlapperInterface::class);

                            $location = $sender->getLocation();
                            if(is_a($slapperClass, SlapperHuman::class, true)){
                                $entity = new $slapperClass($location, $sender->getSkin());
                            }else{
                                /** @var SlapperEntity $entity */
                                $entity = new $slapperClass($location);
                            }
                            $entity->setNameTag($name);
                            $entity->setSlapperVersion($this->getDescription()->getVersion());
                            (new SlapperCreationEvent($entity, $slapperClass, $sender, SlapperCreationEvent::CAUSE_COMMAND))->call();
                            $entity->spawnToAll();
                            $sender->sendMessage(self::PREFIX . $chosenType . " entity spawned with name " . TextFormat::WHITE . "\"" . TextFormat::BLUE . $name . TextFormat::WHITE . "\"" . TextFormat::GREEN . " and entity ID " . TextFormat::BLUE . $entity->getId());
                            return true;
                        default:
                            $sender->sendMessage(self::PREFIX . "Unknown command. Type '/slapper help' for help.");
                            return true;
                    }
                } else {
                    $sender->sendMessage(self::PREFIX . "This command only works in game.");
                    return true;
                }
        }
        return true;
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @return void
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof SlapperInterface) {
            $event->cancel();
            if (!$event instanceof EntityDamageByEntityEvent) {
                return;
            }
            $damager = $event->getDamager();
            if (!$damager instanceof Player) {
                return;
            }

            // Создаем и вызываем событие SlapperHitEvent
            $hitEvent = new SlapperHitEvent($entity, $damager);
            $hitEvent->call();
            
            // Если событие отменено (например, другим плагином), ничего не делаем
            if ($hitEvent->isCancelled()) {
                return;
            }

            $damagerName = $damager->getName();

            // Стандартная логика Slapper (сессии удаления/id)
            if (isset($this->hitSessions[$damagerName])) {
                if ($entity instanceof SlapperHuman) {
                    $entity->getInventory()->clearAll();
                }
                $entity->close();
                unset($this->hitSessions[$damagerName]);
                $damager->sendMessage(self::PREFIX . "Entity removed.");
                return;
            }
            
            if (isset($this->idSessions[$damagerName])) {
                $damager->sendMessage(self::PREFIX . "Entity ID: " . $entity->getId());
                unset($this->idSessions[$damagerName]);
                return;
            }

            // Выполнение команд, если они есть у NPC
            if (count($commands = $entity->getCommands()) > 0) {
                $server = $this->getServer();
                foreach ($commands as $command) {
                    $server->dispatchCommand($this->commandSender, str_replace("{player}", '"' . $damagerName . '"', $command));
                }
            }
        }
    }

    /**
     * @param EntityMotionEvent $event
     *
     * @return void
     */
    public function onEntityMotion(EntityMotionEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof SlapperEntity || $entity instanceof SlapperHuman) {
            $event->cancel();
        }
    }

    /**
     * НОВЫЙ ОБРАБОТЧИК: Срабатывает при ударе по NPC
     * 
     * @param SlapperHitEvent $event
     * @priority HIGHEST
     * @return void
     */
    public function onSlapperHit(SlapperHitEvent $event): void {
        $player = $event->getDamager();
        $entity = $event->getEntity();

        // Проверяем, содержит ли NameTag текст "FFA"
        if (stripos($entity->getNameTag(), "FFA") !== false) {
            $event->cancel(); // Отменяем, чтобы команды из конфига не выполнялись
            $this->openFFAMenu($player); // Открываем меню
        }
    }

    /**
     * Открывает меню FFA игроку и телепортирует его в мир "ffa" при нажатии на кнопку.
     *
     * @param Player $player
     * @return void
     */
    private function openFFAMenu(Player $player): void {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) {
                // Игрок закрыл форму
                return;
            }
            // Игрок нажал на кнопку (data будет 0, так как кнопка одна)
            if ($data === 0) {
                // Пытаемся получить или загрузить мир "ffa"
                $worldManager = $this->getServer()->getWorldManager();
                if (!$worldManager->isWorldLoaded("ffa")) {
                    $worldManager->loadWorld("ffa");
                }
                
                $targetWorld = $worldManager->getWorldByName("ffa");
                if ($targetWorld instanceof World) {
                    // Получаем спавн мира
                    $spawn = $targetWorld->getSpawnLocation();
                    $player->teleport($spawn);
                    $player->sendMessage(self::PREFIX . TextFormat::GREEN . "Телепортация в мир FFA...");
                } else {
                    $player->sendMessage(self::PREFIX . TextFormat::RED . "Мир 'ffa' не найден или не может быть загружен.");
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . TextFormat::GOLD . "FFA");
        $form->setContent(TextFormat::GRAY . "Нажмите кнопку, чтобы присоединиться к битве!");
        $form->addButton("§c§lИГРАТЬ В FFA");
        
        $player->sendForm($form);
    }
}
```
