<?php

namespace ItzLightyHD\KnockbackFFA\listeners;

use ItzLightyHD\KnockbackFFA\API;
use ItzLightyHD\KnockbackFFA\event\PlayerDeadEvent;
use ItzLightyHD\KnockbackFFA\event\PlayerKilledEvent;
use ItzLightyHD\KnockbackFFA\event\PlayerKillEvent;
use ItzLightyHD\KnockbackFFA\event\PlayerKillstreakEvent;
use ItzLightyHD\KnockbackFFA\Utils;
use ItzLightyHD\KnockbackFFA\utils\GameSettings;
use ItzLightyHD\KnockbackFFA\utils\KnockbackKit;
use ItzLightyHD\KnockbackFFA\utils\KnockbackPlayer;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\math\Vector3;
use pocketmine\world\sound\GlassBreakSound;
use pocketmine\utils\TextFormat;

class DamageListener implements Listener
{
    /** @var self $instance */
    protected static DamageListener $instance;

    /** @var array Cooldown tracking for hits (10-11 CPS limit) */
    private array $hitCooldown = [];

    /** @var float Attack cooldown in seconds for ~11 CPS */
    private const ATTACK_COOLDOWN = 0.09;

    /** @var float Custom knockback strength (horizontal) - БЕЗОПАСНОЕ ЗНАЧЕНИЕ */
    private const KNOCKBACK_XZ = 0.4; // Было 0.385 - оставим 0.4

    /** @var float Custom knockback strength (vertical) for Air Combo - БЕЗОПАСНОЕ ЗНАЧЕНИЕ */
    private const KNOCKBACK_Y = 0.42; // Было 0.40 - чуть увеличил для разнообразия 😉

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @param EntityDamageEvent $event
     * @return void
     * @priority HIGH
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $player = $event->getEntity();
        $gameWorld = GameSettings::getInstance()->world;
        
        if (($player instanceof Player) && $event->getEntity()->getWorld()->getFolderName() === $gameWorld) {
            $world = Server::getInstance()->getWorldManager()->getWorldByName($gameWorld);
            if ($world instanceof World) {
                
                if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                    $event->cancel();
                    $event->getEntity()->teleport($world->getSpawnLocation());
                    
                    if (KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())] === "none") {
                        $deadevent = new PlayerDeadEvent($player);
                        $deadevent->call();
                        new KnockbackKit($player);
                        $player->getWorld()->addSound($player->getPosition(), new GlassBreakSound());
                        KnockbackPlayer::getInstance()->killstreak[strtolower($player->getName())] = 0;
                        
                        if (GameSettings::getInstance()->scoretag) {
                            $event->getEntity()->setScoreTag(str_replace(["{kills}"], [KnockbackPlayer::getInstance()->killstreak[strtolower($player->getName())]], GameSettings::getInstance()->getConfig()->get("scoretag-format")));
                        }
                        EssentialsListener::$cooldown[$player->getName()] = 0;
                        $player->sendPopup(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§cYou died");
                    } else {
                        KnockbackPlayer::getInstance()->killstreak[strtolower($player->getName())] = 0;
                        $killedBy = Server::getInstance()->getPlayerExact(KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())]);
                        
                        if ($killedBy instanceof Player) {
                            KnockbackPlayer::getInstance()->killstreak[strtolower($killedBy->getName())]++;
                            
                            if ((int)KnockbackPlayer::getInstance()->killstreak[strtolower($killedBy->getName())] % 5 === 0) {
                                $players = $event->getEntity()->getWorld()->getPlayers();
                                $killevent = new PlayerKillEvent($killedBy, $player);
                                $killevent->call();
                                $killstreakevent = new PlayerKillstreakEvent($killedBy);
                                $killstreakevent->call();
                                
                                foreach ($players as $p) {
                                    Utils::playSound("random.levelup", $p);
                                    $p->sendPopup(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§f" . Server::getInstance()->getPlayerExact(KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())])?->getDisplayName() . "§r§6 is at §e" . KnockbackPlayer::getInstance()->killstreak[KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())]] . "§6 kills");
                                }
                                
                                if (GameSettings::getInstance()->scoretag) {
                                    $killedBy->setScoreTag(str_replace(["{kills}"], [KnockbackPlayer::getInstance()->killstreak[strtolower($killedBy->getName())]], GameSettings::getInstance()->getConfig()->get("scoretag-format")));
                                }
                            } else {
                                if (GameSettings::getInstance()->scoretag) {
                                    $killedBy->setScoreTag(str_replace(["{kills}"], [KnockbackPlayer::getInstance()->killstreak[strtolower($killedBy->getName())]], GameSettings::getInstance()->getConfig()->get("scoretag-format")));
                                }
                                $killevent = new PlayerKillEvent($killedBy, $player);
                                $killevent->call();
                                $killedBy->sendPopup(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§aYou killed §f" . $player->getDisplayName());
                            }
                            
                            Utils::playSound("note.pling", $killedBy);
                            $killedevent = new PlayerKilledEvent($player, $killedBy);
                            $killedevent->call();
                        }
                        
                        new KnockbackKit($player);
                        $player->getWorld()->addSound($player->getPosition(), new GlassBreakSound());
                        
                        if (GameSettings::getInstance()->scoretag) {
                            $event->getEntity()->setScoreTag(str_replace(["{kills}"], [KnockbackPlayer::getInstance()->killstreak[strtolower($player->getName())]], GameSettings::getInstance()->getConfig()->get("scoretag-format")));
                        }
                        EssentialsListener::$cooldown[$player->getName()] = 0;
                        $player->sendPopup(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§cYou were killed by §f" . $killedBy?->getDisplayName());
                    }
                    
                    KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())] = "none";
                } 
                elseif ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
                    $event->cancel();
                }
            }
        }
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function KnockBackFFAKillEvent(PlayerKillEvent $event): void
    {
        $player = $event->getPlayer();
        if (API::isSnowballsEnabled()) {
            $snowballs = VanillaItems::SNOWBALL();
            $player->getInventory()->addItem($snowballs);
        }
    }

    public function entityAttacked(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        
        if (!($player instanceof Player) || $player->getWorld()->getFolderName() !== GameSettings::getInstance()->world) {
            return;
        }

        // CPS лимит
        $playerName = $player->getName();
        $currentTime = microtime(true);
        
        if (isset($this->hitCooldown[$playerName]) && ($currentTime - $this->hitCooldown[$playerName]) < self::ATTACK_COOLDOWN) {
            $event->cancel();
            return;
        }
        $this->hitCooldown[$playerName] = $currentTime;

        $player->setHealth(20);
        $player->getHungerManager()->setSaturation(20);
        
        if ($damager instanceof Player) {
            
            if (!Utils::canTakeDamage($player)) {
                $damager->sendMessage(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§cYou can't hit the players here!");
                $event->cancel();
                return;
            }
            
            if ($damager->getName() === $player->getName()) {
                $event->cancel();
                return;
            }
            
            KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())] = strtolower($damager->getName());
            
            $event->setKnockBack(0);
            
            // Применяем отдачу с проверкой на null
            $this->applyCustomKnockback($player, $damager);
            
            if (GameSettings::getInstance()->massive_knockback && $damager->getInventory()->getItemInHand()->getTypeId() === ItemTypeIds::STICK) {
                $this->applyCustomKnockback($player, $damager, 0.6);
            }
        }
    }

    /**
     * БЕЗОПАСНОЕ применение отдачи с защитой от ошибок
     */
    private function applyCustomKnockback(Player $player, Player $damager, ?float $multiplier = null): void
    {
        try {
            // Защита от null
            if ($player === null || $damager === null || !$player->isOnline() || !$damager->isOnline()) {
                return;
            }
            
            $xz = $multiplier ?? self::KNOCKBACK_XZ;
            $y = ($multiplier !== null) ? $multiplier * 0.67 : self::KNOCKBACK_Y;
            
            // Получаем позиции с защитой от деления на ноль
            $playerPos = $player->getPosition();
            $damagerPos = $damager->getPosition();
            
            if ($playerPos === null || $damagerPos === null) {
                return;
            }
            
            $subtract = $playerPos->subtractVector($damagerPos);
            
            // Защита от нулевого вектора (если игроки в одной точке)
            if ($subtract->x == 0 && $subtract->y == 0 && $subtract->z == 0) {
                $subtract = new Vector3(0.1, 0, 0.1); // Даем небольшое смещение
            }
            
            $direction = $subtract->normalize();
            
            // Финальная защита: если нормализация сломалась
            if ($direction === null || ($direction->x == 0 && $direction->z == 0)) {
                $direction = new Vector3(0.1, 0, 0.1);
            }
            
            $knockback = new Vector3(
                $direction->x * $xz,
                $y,
                $direction->z * $xz
            );
            
            $player->setMotion($knockback);
            
        } catch (\Throwable $e) {
            // Логируем ошибку, но не крашим сервер
            Server::getInstance()->getLogger()->error("Knockback error: " . $e->getMessage());
        }
    }

    public function projectileAttack(EntityDamageByChildEntityEvent $event): void
    {
        $player = $event->getEntity();
        $damager = $event->getDamager();
        
        if (($player instanceof Player && $damager instanceof Player) && ($player->getWorld()->getFolderName() === GameSettings::getInstance()->world)) {
            
            if ($damager->getName() === $player->getName()) {
                $event->cancel();
                $damager->sendMessage(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§cYou can't hit yourself.");
                return;
            }
            
            $event->setBaseDamage(0);
            $player->setHealth(20);
            $player->getHungerManager()->setSaturation(20);
            
            if (!Utils::canTakeDamage($player)) {
                $event->cancel();
                $damager->sendMessage(GameSettings::getInstance()->getConfig()->get("prefix") . "§r§cYou can't hit the players here!");
                return;
            }
            
            KnockbackPlayer::getInstance()->lastDmg[strtolower($player->getName())] = strtolower($damager->getName());
            
            $event->setKnockBack(0);
            $this->applyCustomKnockback($player, $damager, 0.3);
            
            Utils::playSound("random.orb", $damager);
        }
    }

    // ===============================================
    // МЕТОДЫ ДЛЯ ЗАЩИТЫ КНИГИ СТАТИСТИКИ
    // ===============================================

    public function onDrop(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        
        $gameWorld = GameSettings::getInstance()->world;
        if ($player->getWorld()->getFolderName() !== $gameWorld) {
            return;
        }
        
        if ($item->getTypeId() !== 340) {
            return;
        }
        
        $namedTag = $item->getNamedTag();
        if (!$namedTag->getTag("kbffa") || $namedTag->getString("kbffa") !== "stats_book") {
            return;
        }
        
        $event->cancel();
        $player->sendMessage(TextFormat::RED . "Ты не можешь выбросить книгу статистики!");
    }

    public function onTransaction(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        
        $gameWorld = GameSettings::getInstance()->world;
        if (!$player instanceof Player || $player->getWorld()->getFolderName() !== $gameWorld) {
            return;
        }
        
        foreach ($transaction->getActions() as $action) {
            $sourceItem = $action->getSourceItem();
            $targetItem = $action->getTargetItem();
            
            $itemsToCheck = [$sourceItem, $targetItem];
            
            foreach ($itemsToCheck as $item) {
                if ($item->getTypeId() !== 340) {
                    continue;
                }
                
                $namedTag = $item->getNamedTag();
                if ($namedTag->getTag("kbffa") && $namedTag->getString("kbffa") === "stats_book") {
                    if ($action instanceof \pocketmine\inventory\transaction\action\SlotChangeAction) {
                        $inventory = $action->getInventory();
                        
                        if (!($inventory instanceof \pocketmine\inventory\PlayerInventory) && 
                            !($inventory instanceof \pocketmine\inventory\BaseInventory && $inventory->getHolder() === $player)) {
                            $event->cancel();
                            $player->sendMessage(TextFormat::RED . "Нельзя перемещать книгу статистики в другие контейнеры!");
                            return;
                        }
                    } else {
                        $event->cancel();
                        return;
                    }
                }
            }
        }
    }
}
