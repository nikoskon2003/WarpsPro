<?php

namespace WarpsPro;

use pocketmine\command\Command;
//use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;

use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Server;

class WarpsPro extends PluginBase{ //implements CommandExecutor{
    /** @var Config */
    public $warps;
    /** @var int */
    public $warp_id;
    /** @var string */
    public $warp_name;
    /** @var Position[] */
    public $config;
    /** @var int[] */
    public $player_cords;
    /** @var string */
    public $world;
	/** @var bool */
    public $enable_wild;
    
    public function WarpID($name){
        $data = $this->warps->getAll();

        for($i = 0; $i < count($data) + 1; $i++)
            if(isset($data[$i]))
                if($data[$i]["name"] == $name)
                    return $i;
        return -1;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
        switch($cmd->getName()){
            case 'warp':
                if (!$sender->hasPermission("warpspro.command.warp")) {
                    $sender->sendMessage("§c[WarpsPro] No permission.");
                    return true;
                }
                if ($sender instanceof Player)
                {
                    if (count($args) == 0)
                    {
                        $warp_list = null;
                        $data = $this->warps->getAll();

                        for($i = 0; $i < count($data) + 1; $i++)
                            if(isset($data[$i]))
                                $warp_list .= '§a[§f' . $data[$i]["name"] . '§a]';
                        if($warp_list != null){
                            $sender->sendMessage("§fWarps: " . $warp_list);
                            return true;
                        }else{
                            $sender->sendMessage("§cThis server has no warps.");
                            return true;
                        }
                    }else{
                        $this->warp_name = $args[0];
                        $this->warp_id = $this->WarpID($this->warp_name);
                        $data = $this->warps->getAll();

                        if($this->warp_id <= -1){
                            $sender->sendMessage("§cThere is no warp by that name listed.");
							return true;
                        }
						if(isset($data[$this->warp_id])){
                            if(Server::getInstance()->loadLevel($data[$this->warp_id]["world"]) != false){
                                $curr_world = Server::getInstance()->getLevelByName($data[$this->warp_id]["world"]);
                                $pos = new Position((int)$data[$this->warp_id]["x"],
                                                    (int)$data[$this->warp_id]["y"],
                                                    (int)$data[$this->warp_id]["z"], $curr_world);

                                $sender->sendMessage("§aYou warped to:§f " . $this->warp_name);
                                $sender->teleport($pos);
                                return true;
                            }else{
                                $sender->sendMessage("§cCould not load chunk.§f It's not safe to teleport there.");
                                return true;
                            }
						}else{
							$sender->sendMessage("§cThere is no warp by that name listed.");
							return true;
						}
                    }
                } else{
					if (count($args) == 0)
                    {
                        $warp_list = null;
                        $data = $this->warps->getAll();
 
                        for($i = 0; $i < count($data) + 1; $i++)
                            if(isset($data[$i]))
                                $warp_list .= '§a[§f' . $data[$i]["name"] . '§a]';
                        if($warp_list != null){
                            $sender->sendMessage("§6[WarpsPro] §fWarps: " . $warp_list);
                            return true;
                        }else{
                            $sender->sendMessage("§6[WarpsPro] §cThis server has no warps.");
                            return true;
                        }
                    }else{
						$sender->sendMessage("§cThis command can only be used in-game.");
						return true;
					}
                }
                break;
            case 'setwarp':
                if (!$sender->hasPermission("warpspro.command.setwarp")) {
                    $sender->sendMessage("§cYou don't have permission.");
                    return true;
                }
                if ($sender instanceof Player)
                {
                    if((count($args) != 0) && (count($args) < 2))
                    {
                        $data = $this->warps->getAll();
                        if($this->WarpID($args[0]) > -1){
                            $sender->sendMessage("§cWarp already exists!");
                            return true;
                        }

                        $this->player_cords = array('x' => (int) $sender->getX(),'y' => (int) $sender->getY(),'z' => (int) $sender->getZ());
                        $this->world = $sender->getLevel()->getName();
                        $this->warp_name = $args[0];
                        $this->warp_id = count($data);

                        if(isset($data[$this->warp_id])){
                            $this->warp_id++;
                            if(isset($data[$this->warp_id])){
                                $sender->sendMessage("§cThere is a problem with §fwarps.yml§c. A manual reset must be made!");
                                return true;
                            }
                        }
                        
                        $data[$this->warp_id]["world"] = $this->world;
                        $data[$this->warp_id]["x"] = $this->player_cords["x"];
                        $data[$this->warp_id]["y"] = $this->player_cords["y"];
                        $data[$this->warp_id]["z"] = $this->player_cords["z"];
                        $data[$this->warp_id]["name"] = $this->warp_name;

                        $this->warps->setAll($data);
                        $this->warps->save();

                        $sender->sendMessage("§aWarp set as:§r " . $args[0]);
                        return true;
                    }
                    else
                    {
                        $sender->sendMessage("§cINVALID USAGE:");
                        return false;
                    }
                }
                else
                {
                    $sender->sendMessage("§cThis command can only be used in-game.");
                    return true;
                }
                break;
            case 'delwarp':
                if (!$sender->hasPermission("warpspro.command.delwarp")) {
                    $sender->sendMessage("§cYou don't have permission.");
                    return true;
                }
                if((count($args) != 0) && (count($args) < 2))
                {
                    $data = $this->warps->getAll();
                    $this->warp_name = $args[0];
                    $this->warp_id = $this->WarpID($this->warp_name);

                    if($this->warp_id <= -1){
                        $sender->sendMessage("§cNo warps with that name in this server.");
                        return true;
                    }
                    
                    if(isset($data[$this->warp_id]))
                    {
                        unset($data[$this->warp_id]);

                        $rtarray = [];

                        for($i = 0; $i < count($data) + count($data); $i++){
                            if(isset($data[$i]))
                                $rtarray[] = $data[$i];
                        }
                        //print_r($rtarray); //use this for debugging!
                        $this->warps->setAll($rtarray);
                        $this->warps->save();

                        $sender->sendMessage("§aWarp [§f" . $this->warp_name . "§r§a] has been deleted.");
                        return true;
                    }
                    else
                    {
                        $sender->sendMessage("§cNo warps with that name in this server.");
                        return true;
                    }
                }
                else
                {
                    $sender->sendMessage("§cINVALID USAGE!");
                    return false;
                }
                break;
            case 'wild':
				if($this->enable_wild === "true"){
					if (!$sender->hasPermission("warpspro.command.wild")) {
						$sender->sendMessage("§cYou don't have permission.");
						return true;
					}
					if ($sender instanceof Player)
					{
						$this->world = $sender->getLevel()->getName();
						foreach($this->getServer()->getLevels() as $aval_world => $curr_world)
						{
							if ($this->world == $curr_world->getName())
							{
								$pos = $sender->getLevel()->getSafeSpawn(new Vector3(rand('-'.$this->config->get("wild-MaxX"), $this->config->get("wild-MaxX")),rand(1,256),rand('-'.$this->config->get("wild-MaxZ"), $this->config->get("wild-MaxZ"))));
									$pos->getLevel()->loadChunk($pos->getX(),$pos->getZ());
									$pos->getLevel()->getChunk($pos->getX(),$pos->getZ(),true);
									$pos = $pos->getLevel()->getSafeSpawn(new Vector3($pos->getX(),rand(1,256),$pos->getZ()));
								if($pos->getLevel()->isChunkLoaded($pos->getX(),$pos->getZ()))
								{
									$sender->teleport($pos);
									$sender->sendMessage("§aTeleported you somewhere wild.");
									return true;
								}
								else
								{
									$sender->sendMessage("§cCould not load chunk. §fIt isn't safe to teleport there.");
									return true;
								}

							}
						}

					}
					else
					{
						$sender->sendMessage("§cThis command can only be used in-game.");
						return true;
					}
				}
				else
				{
					$sender->sendMessage("§f/wild §cis not enabled in this Server!");
					return true;
				}
				break;
				
            default:
                return false;
            }
            return false;
        }

    public function check_config(){
        $this->saveDefaultConfig();

        $defaults = [
            "plugin-name" => "WarpsPro",
            "enable-wild-command" => false,
            "wild-MaxX" => 250,
            "wild-MaxZ" => 250
        ];

        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, $defaults);
        $this->config->set('plugin-name',"WarpsPro");
        $this->config->save();
    }

    public function onEnable(){
        $this->getLogger()->info("§6WarpsPro §bis loading...");
        @mkdir($this->getDataFolder());
        $this->saveResource("warps.yml");
        $this->warps = new Config($this->getDataFolder() . "warps.yml", Config::YAML);
        $this->check_config();

        $this->getLogger()->info("§6WarpsPro §ahas been loaded!");
		$this->enable_wild = $this->config->get("enable-wild-command");
    }

    public function onDisable(){
        $this->warps->save();
        $this->getLogger()->info("§6WarpsPro §cdisabled");
    }
}
