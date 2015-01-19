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
	
	public $config;
	
	
	public function onEnable() {
		@mkdir($this->getDataFolder());
		if(!$this->getServer()->getPluginManager()->getPlugin("TapToDo") == true) {
			$this->getLogger()->info( TextFormat::RED . "TapToDo Not Found! You may be using PrizeWin incorrectly!" );
		}
		$this->config = new Config($this->getDataFolder() . "config.yml", CONFIG::YAML, array(
				"message-when-player-wins" => "{player} Won '{areaname}'!",
		));
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
							if($this->playerHasWon(strtolower($player), $args[1])) {
								$sender->sendMessage("[PrizeWin] You already won '$args[1]'!");
								return true;
							}
							$this->areas = new Config($this->getDataFolder() . $args[1] . "/" . "players.yml", CONFIG::YAML);
							$this->areasc = new Config($this->getDataFolder() . $args[1] . "/" . "settings.yml", CONFIG::YAML);
							$getarea = $this->getArea($args[1]);
							$name = strtolower($sender->getName());
							$ip = $this->getServer()->getPlayer($player)->getAddress();
							if($getarea == true) {
								$this->areas->set((
									"$ip"
								), (
									"$name"
								)
								);
								$this->areas->save();
								$areaget = $args[1];
								$array = $this->areasc->get("Commands");
								foreach($array as $value) {
									$cmdpw = str_replace ( "{player}", $sender->getName(), $value );
									$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmdpw);
								}
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
								@mkdir($this->getDataFolder() . "$args[1]/", 0777, true);
								$this->areasc = new Config($this->getDataFolder() . $args[1] . "/" . "settings.yml", CONFIG::YAML, array(
									"unlimited-winning" => false,
									"Commands" => [
										"give {player} diamond 1",
									]
								));
								$this->areasc->save();
								$sender->sendMessage("[PrizeWin] '$args[1]' added successfully!\nPlease set commands in config.");
							}
						}
						if($args[0] == "revokeall") {
							$ip = $this->getServer()->getPlayer($player)->getAddress();
							if(empty($args[1])) {
								$sender->sendMessage("[PrizeWin] Usage:\n/pw revokeall <area>");
								return true;
							}							
							if(!$this->getArea($args[1])) {
								$sender->sendMessage("[PrizeWin] Area not found! Case sensitive.");
								return true;
							}
							if($this->getArea($args[1])) {
								$this->areas->setAll(null);
								$this->areas->save();
								$sender->sendMessage("[PrizeWin] All players on '$args[1]' have been revoked!");
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
								$this->areas->remove($ip);
								$this->areas->save();
								$sender->sendMessage("[PrizeWin] '$args[1]' has been revoked\nand can now win again on '$args[2]'!");
								return true;
							}
						}
					}
				}
		}
	}
	public function playerHasWon($player, $args) {
		$ip = $this->getServer()->getPlayer($player)->getAddress();
		$this->areas = new Config($this->getDataFolder() . $args . "/" . "players.yml", CONFIG::YAML);
		$this->areasc = new Config($this->getDataFolder() . $args . "/" . "settings.yml", CONFIG::YAML);
		if($this->areasc->get("unlimited-winning")) {
			return false;
		}
		elseif(!$this->areasc->get("unlimited-winning") && !$this->areas->get("$ip")) {
			return false;
		}else{
			return true;
		}
	}
	public function areaExists($area) {
		if(!(file_exists($this->getDataFolder() . $area . "/" . "settings.yml"))) {
		return false;
		}else{
			return true;
		}
	}
	public function getArea($area) {
		if(!$this->areaExists($area)) {
            return false;
		}
		if(file_exists($this->getDataFolder() . $area . "/" . "setting.yml"));
		return $area;
	}
	public function getCMD($area, $key) {
		return $this->areas->getAll()["$area"][5];
	}
}
