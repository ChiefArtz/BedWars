<?php

namespace BedWars;

use pocketmine\block\Bed;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\event\player\PlayerInteractEvent;

class Main extends PluginBase implements Listener{
    public $mode = 0;
    public $arenaname;
    public $blueBed;
    public $redBed;
    public $greenBed;
    public $yellowBed;
    public $blueSpawn;
    public $redSpawn;
    public $greenSpawn;
    public $yellowSpawn;
    public $regname;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info(TextFormat::GREEN .  "Enabled");
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "/arenas");
    }

    public function onCommand(CommandSender $player, Command $cmd, $label, array $args){
        switch($cmd->getName()){
            case "bw":
                if($player->isOp()){
                    if($this->mode == 0 and $args[0] == "addarena"){
                        $this->mode = 1;
                        $player->sendMessage(TextFormat::YELLOW . "Use /bw add [worldname] to add an arena");
                    }
                    elseif($this->mode == 1 and $args[0] == "add"){
                        if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[0])){
                            if(!$this->getServer()->getLevelByName($args[1]) instanceof Level){
                                $this->getServer()->loadLevel($args[1]);
                            }
                            $spawn = $this->getServer()->getLevelByName($args[1])->getSafeSpawn();
                            $this->getServer()->getLevelByName($args[1])->loadChunk($spawn->x, $spawn->z);
                            if($player instanceof Player){
                                $player->teleport($spawn);
                            }
                            $this->arenaname = $args[1];
                            $player->sendMessage(TextFormat::GREEN . "You have successfully entered the arena! Now you have to set 4 bases");
                            $player->sendMessage(TextFormat::GOLD . "Touch" .TextFormat::BLUE . "Team blue" .TextFormat::GOLD . "'s bed now");
                            $this->mode = 2;
                        }else{
                            $player->sendMessage(TextFormat::RED . "This is not a valid name!");
                        }
                    }
                    elseif($this->mode == 0 and $args[0] == "regsign" and isset($args[1])){
                        if(file_exists($this->getDataFolder() . "/arenas/" . $args[1])) {
                            $player->sendMessage(TextFormat::YELLOW . "You are about to register a sign for the arena $args[1]. Tap a sign to activate it!");
                            $this->regname = $args[1];
                            $this->mode = 10;
                        }else{
                            $player->sendMessage(TextFormat::RED . "This is not a valid name!");
                        }
                    }elseif($this->mode == 0 and $args[0] == "regsign"){
                        $player->sendMessage(TextFormat::YELLOW . "Usage: /bw regsign [worldname]");
                    }elseif($args[0] == "cancel"){
                        $this->mode = 0;
                        $player->sendMessage(TextFormat::GREEN . "Cancelled!");
                    }
                }
            return true;
        }
        return true;
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if($player->isOp()) {
            if ($block instanceof Bed) {
                if ($this->mode == 2) {
                    $this->blueBed = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::RED . "Team red" . TextFormat::GOLD . "'s bed now");
                    $this->mode = 3;
                } elseif ($this->mode == 3) {
                    $this->redBed = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::GREEN . "Team green" . TextFormat::GOLD . "'s bed now");
                    $this->mode = 4;
                } elseif ($this->mode == 4) {
                    $this->greenBed = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::YELLOW . "Team yellow" . TextFormat::GOLD . "'s bed now");
                    $this->mode = 5;
                } elseif ($this->mode == 5) {
                    $this->yellowBed = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::YELLOW . "Now you have to set the spawn positions");
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::BLUE . "Team blue" . TextFormat::GOLD . "'s spawn now");
                    $this->mode = 6;
                }
            } else {
                if ($this->mode == 6) {
                    $this->blueSpawn = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::RED . "Team red" . TextFormat::GOLD . "'s spawn now");
                    $this->mode = 7;
                } elseif ($this->mode == 7) {
                    $this->redSpawn = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::GREEN . "Team green" . TextFormat::GOLD . "'s spawn now");
                    $this->mode = 8;
                } elseif ($this->mode == 8) {
                    $this->greenSpawn = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GOLD . "Touch" . TextFormat::YELLOW . "Team yellow" . TextFormat::GOLD . "'s spawn now");
                    $this->mode = 9;
                } elseif ($this->mode == 9) {
                    $this->yellowSpawn = new Vector3($block->getX() + 0.5, $block->getY() + 2, $block->getZ() + 0.5);
                    $player->sendMessage(TextFormat::GREEN . "you have successfully set a new arena!");
                    $this->saveArena();
                    $this->mode = 0;
                }
            }
        }
    }

    public function saveArena(){
        $arena = new Config($this->getDataFolder() . "/arenas/" . $this->arenaname, Config::YAML);
        $arena->set("blueBed", $this->blueBed);
        $arena->set("redBed", $this->redBed);
        $arena->set("greenBed", $this->greenBed);
        $arena->set("yellowBed", $this->yellowBed);
        $arena->set("blueSpawn", $this->blueSpawn);
        $arena->set("redSpawn", $this->redSpawn);
        $arena->set("greenSpawn", $this->greenSpawn);
        $arena->set("yellowSpawn", $this->yellowSpawn);
        $arena->set("lobby-time", 120);
        $arena->set("play-time", 600);
        $arena->save();
    }
}
