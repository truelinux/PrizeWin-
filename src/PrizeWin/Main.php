<?php


namespace PrizeWin;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\permission\Permissible;
use pocketmine\IPlayer;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
class Main extends PluginBase  implements CommandExecutor {
	
	public $db;
	public $config;
	
	
	public function onEnable() {
		@mkdir($this->getDataFolder());
		if(!$this->getServer()->getPluginManager()->getPlugin("TapToDo") == true) {
			$this->getLogger()->info( TextFormat::RED . "TapToDo Not Found! You may be using PrizeWin incorrectly!" );
		}
		$this->config = new Config($this->getDataFolder() . "config.yml", CONFIG::YAML, array(
				"message-when-player-wins" => "{player} Won '{areaname}'!",
		));
		$this->areas = new Config($this->getDataFolder() . "areas.yml", CONFIG::YAML, array(
				"Parkour" => [
				"give {player} diamond 1",
						]
		));
		$this->players = new Config($this->getDataFolder() . "win-players.yml", CONFIG::YAML);
		$this->getLogger()->info( TextFormat::GREEN . "PrizeWin - Enabled!" );
	}
	
	public function loadConfig() {
		$this->saveDefaultConfig();
		$this->fixConfigData ();
	}
	
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if($sender instanceof Player) {
			$player = $sender->getPlayer()->getName();
				if(strtolower($command->getName('pw'))) {
					if(empty($args)) {
					$sender->sendMessage("[PrizeWin] Usage:\n/pw prize <area-name>\n/pw revoke <player> <area-name>\n/pw add <area-name>");
					return true;
					}
					if(count($args == 2)) {
						if($args[0] == "prize") {
							if(empty($args[1])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw prize <area-name>");
								return true;
							}
							if($this->getArea($args[1]) == false) {
								$sender->sendMessage("[PrizeWin] Area not found! Case sensitive.");
								return true;
							}
							if($this->playerHasWon($player, $args[1])) {
								$sender->sendMessage("[PrizeWin] You already won '$args[1]'!");
								return true;
							}
							$getarea = $this->getArea($args[1]);
							$name = $sender->getName();
							$ip = $this->getServer()->getPlayer($player)->getAddress();
							if($getarea == true) {
								$this->players->set(($this->getServer()->getPlayer($player)->getAddress() . "won" . $args[1]), ("$name - $ip"));
								$this->players->save();
								$areaget = $args[1];
								if(isset($this->areas->getAll()["$areaget"][0])) {
									$cmdpw = $this->areas->getAll()["$areaget"][0];
								}else{
									$cmdpw = "null";
								}
								if(isset($this->areas->getAll()["$areaget"][1])) {
									$cmdpw1 = $this->areas->getAll()["$areaget"][1];
								}else{
									$cmdpw1 = "null";
								}
								if(isset($this->areas->getAll()["$areaget"][2])) {
									$cmdpw2 = $this->areas->getAll()["$areaget"][2];
								}else{
									$cmdpw2 = "null";
								}
								if(isset($this->areas->getAll()["$areaget"][3])) {
									$cmdpw3 = $this->areas->getAll()["$areaget"][3];
								}else{
									$cmdpw3 = "null";
								}
								if(isset($this->areas->getAll()["$areaget"][4])) {
									$cmdpw4 = $this->areas->getAll()["$areaget"][4];
								}else{
									$cmdpw4 = "null";
								}
								$cmdpw = str_replace ( "{player}", $sender->getName(), $cmdpw );
								$cmdpw1 = str_replace ( "{player}", $sender->getName(), $cmdpw1 );
								$cmdpw2 = str_replace ( "{player}", $sender->getName(), $cmdpw2 );
								$cmdpw3 = str_replace ( "{player}", $sender->getName(), $cmdpw3 );
								$cmdpw4 = str_replace ( "{player}", $sender->getName(), $cmdpw4 );
								$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdpw);
								$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdpw1);
								$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdpw2);
								$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdpw3);
								$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdpw4);
								$winning = $this->getConfig ()->get ( "message-when-player-wins" );
								$winning = str_replace ( "{player}", $sender->getName(), $winning );
								$winning = str_replace ( "{areaname}", $areaget, $winning );
								$sender->sendMessage("[PrizeWin] You Won $player !");
								$this->getServer()->dispatchCommand(new ConsoleCommandSender(), "say $winning");
								return true;
							}
						}
						elseif($args[0] == "add") {
							if(empty($args[1])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw add <area-name>");
								return true;
							}
							if($this->getArea($args[1])) {
								$sender->sendMessage("[PrizeWin] Area already exists!");
								return true;
							}else{
								$areaset = strtolower($args[1]);
								$this->areas->set($args[1], [
									"give {player} diamond 1",
								]);
								$this->areas->save();
								$sender->sendMessage("[PrizeWin] '$args[1]' added successfully!\nPlease set commands in config.");
							}
						}
						if($args[0] == "remove") {
							if(empty($args[1])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw remove <area>");
								return true;
							}							
							if(!$this->getArea($args[1])) {
								$sender->sendMessage("[PrizeWin] Area not found! Case sensitive.");
								return true;
							}
							if($this->getArea($args[1])) {
								$this->areas->remove($args[1]);
								$this->areas->save();
								$sender->sendMessage("[PrizeWin] '$args[1]' has been revoked\nand can now win again on '$args[2]'!");
								return true;
							}
						}
					}
					if(count($args == 3)) {
						if($args[0] == "revoke") {
							if(empty($args[1])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw revoke <player> <area>");
								return true;
							}
							if(empty($args[2])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw revoke <player> <area>");
								return true;
							}
							
							if(!$this->getArea($args[2])) {
								$sender->sendMessage("[PrizeWin] Area not found! Case sensitive.");
								return true;
							}
							$playername = $this->getServer()->getPlayerExact($args[1]);
							if(!$playername instanceof Player) {
								$sender->sendMessage("[PrizeWin] Player not online!");
								return true;
							}
							if(!$this->playerHasWon($args[1], $args[2])) {
								$sender->sendMessage("[PrizeWin] Player has not won yet!");
								return true;
							}
							if($this->playerHasWon($args[1], $args[2])) {
								$ip = $this->getServer()->getPlayer($args[1])->getAddress();
								$this->players->remove($ip . "won" . $args[2]);
								$this->players->save();
								$sender->sendMessage("[PrizeWin] '$args[1]' has been revoked\nand can now win again on '$args[2]'!");
								return true;
							}
						}
					}
					if(count($args == 5)) {
						if($args[0] == "cmd") {
							if(empty($args[1])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw cmd <add/del/delall> <area-name> <command>");
								return true;
							}
							if(empty($args[2])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw cmd <add/del/delall> <area-name> <command>");
								return true;
							}
							if(empty($args[3])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw cmd <add/del/delall> <area-name> <command>");
								return true;
							}
							if(empty($args[4])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw cmd <add/del/delall> <area-name> <command>");
								return true;
							}
							if(strtolower($args[1]) == "add") {
								if(!$this->getArea($args[2])) {
									$sender->sendMessage("[PrizeWin] Area not found! Case sensitive.");
									return true;
								}
								if($args[4] > 5) {
									$sender->sendMessage("[PrizeWin] You may only set 5 commands!");
									return true;
								}
								if(isset($this->areas->get[$args[2]][$args[4]])) {
									$sender->sendMessage("[PrizeWin] That command slot is already taken!");
									return true;
								}
								if($args[4] == "1") {
									$key = 0;
								}
								if($args[4] == "2") {
									$key = 1;
								}
								if($args[4] == "3") {
									$key = 2;
								}
								if($args[4] == "4") {
									$key = 3;
								}
								if($args[4] == "5") {
									$key = 4;
								}
								if($this->getArea($args[2])) {
									$this->areas->set($args[2], [strtolower($args[3]),][$key]);
									$this->areas->save;
									$sender->sendMessage("[PrizeWin] Command added to '$args[2]'!");
								}
							}
						}
					}
				}
		}
	}
	public function playerHasWon($player, $args) {
		$ip = $this->getServer()->getPlayer($player)->getAddress();
		if($this->players->exists($ip . "won" . $args)) {
			return true;
		}else{
			return false;
		}
	}
	public function areaExists($area) {
		return $this->areas->get($area);
	}
	public function getArea($area) {
		if(!$this->areaExists($area)) {
            return false;
		}
		return $this->areas->get($area);
	}
	public function getCMD($area, $key) {
		return $this->areas->getAll()["$area"][5];
	}
}
