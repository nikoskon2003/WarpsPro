<?php

namespace nikoskon\WarpsPro;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\Server;

class WarpsPro extends PluginBase
{
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
    
    public function WarpID($name)
    {
        $data = $this->warps->getAll();
        for($i = 0; $i < count($data) + 1; $i++)
            if(isset($data[$i]))
                if($data[$i]["name"] == $name)
                    return $i;
        return -1;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool
    {
        if($cmd->getName() != 'warp' && $cmd->getname() != 'warps') return true;

        if(count($args) == 0)
        {
            if (!$sender->hasPermission("warpspro.command.warp"))
            {
                $sender->sendMessage("§cYou don't have permission.");
                return true;
            }

            if ($sender instanceof Player)
            {
                $warp_list = "";
                $data = $this->warps->getAll();

                for($i = 0; $i < count($data) + 1; $i++)
                    if(isset($data[$i]) && ($sender->hasPermission("warpspro.command.warp." . $data[$i]["name"]) || $data[$i]["open"]))
                        $warp_list .= '§a[§f' . $data[$i]["name"] . '§r§a]';
                
                if($warp_list != "") $sender->sendMessage("§fWarps: " . $warp_list);
                else $sender->sendMessage("§cThis server has no warps.");            
            } 
            else
            {
                $warp_list = "";
                $data = $this->warps->getAll();

                for($i = 0; $i < count($data) + 1; $i++)
                    if(isset($data[$i]))
                        $warp_list .= '§a[§f' . $data[$i]["name"] . '§r§a]';
                
                if($warp_list != "")$sender->sendMessage("§fWarps: " . $warp_list);
                else$sender->sendMessage("§cThis server has no warps.");
            }
        }
        else
        {
            switch($args[0])
            {
                case 'add':
                case 'set':
                    if (!$sender->hasPermission("warpspro.command.setwarp")) 
                    {
                        $sender->sendMessage("§cYou don't have permission.");
                        return true;
                    }
                    if ($sender instanceof Player)
                    {
                        if(count($args) != 2)
                        {
                            $sender->sendMessage("§cInvalid usage!");
                            return true;
                        }

                        if($args[1] == 'add' || $args[1] == 'set' || $args[1] == 'del' || $args[1] == 'edit')
                        {
                            $sender->sendMessage("§cWarps cannot be named: §4add§c, §4set§c, §4del §cnor §4edit§c!");
                            return true;
                        }

                        $data = $this->warps->getAll();
                        if($this->WarpID($args[1]) > -1)
                        {
                            $sender->sendMessage("§cWarp already exists!");
                            return true;
                        }

                        $this->player_cords = array('x' => $sender->getX(), 'y' => $sender->getY(), 'z' => $sender->getZ());
                        $this->world = $sender->getLevel()->getName();
                        $this->warp_name = $args[1];
                        $this->warp_id = count($data);

                        if(isset($data[$this->warp_id]))
                        {
                            $this->warp_id++;
                            if(isset($data[$this->warp_id]))
                            {
                                $sender->sendMessage("§cThere is a problem with §fwarps.yml§c. A manual fix must be made!");
                                return true;
                            }
                        }
                        
                        $data[$this->warp_id]["world"] = $this->world;
                        $data[$this->warp_id]["x"] = $this->player_cords["x"];
                        $data[$this->warp_id]["y"] = $this->player_cords["y"];
                        $data[$this->warp_id]["z"] = $this->player_cords["z"];
                        $data[$this->warp_id]["name"] = $this->warp_name;
                        $data[$this->warp_id]["open"] = true;

                        $this->warps->setAll($data);
                        $this->warps->save();

                        $sender->sendMessage("§aWarp set as:§r " . $args[1]);
                    }
                    else $sender->sendMessage("§cThis command can only be used in-game.");
                break;
                case 'del':
                    if (!$sender->hasPermission("warpspro.command.delwarp")) 
                    {
                        $sender->sendMessage("§cYou don't have permission.");
                        return true;
                    }
                    if(count($args) == 2)
                    {
                        $data = $this->warps->getAll();
                        $this->warp_name = $args[1];
                        $this->warp_id = $this->WarpID($this->warp_name);

                        if($this->warp_id < 0)
                        {
                            $sender->sendMessage("§cThere is no warp by that name listed.");
                            return true;
                        }
                        
                        if(isset($data[$this->warp_id]))
                        {
                            unset($data[$this->warp_id]);

                            $rtarray = [];

                            for($i = 0; $i < count($data) + count($data); $i++)
                                if(isset($data[$i]))
                                    $rtarray[] = $data[$i];
                                
                            $this->warps->setAll($rtarray);
                            $this->warps->save();

                            $sender->sendMessage("§aWarp [§f" . $this->warp_name . "§r§a] has been deleted.");
                        }
                        else $sender->sendMessage("§cThere is no warp by that name listed.");
                    }
                    else $sender->sendMessage("§cINVALID USAGE!");
                break;
                case 'edit':
					// /warp edit <warp name> <action> [state]
                    // /warp edit <name> open <true/false>
                    // /warp edit <name> pos
                    // /warp edit <name> name <new name>
                    if (!$sender->hasPermission("warpspro.command.edit")) 
                    {
                        $sender->sendMessage("§cYou don't have permission.");
                        return true;
                    }

                    if(count($args) < 3)
                    {
                        $sender->sendMessage("§cInvalid usage!");
                        return true;
                    }

                    $data = $this->warps->getAll();
                    $this->warp_name = $args[1];
                    $this->warp_id = $this->WarpID($this->warp_name);

                    if($this->warp_id < 0)
                    {
                        $sender->sendMessage("§cThere is no warp by that name listed.");
                        return true;
                    }

                    switch(strtolower($args[2]))
                    {
                        case "open":
                            if(count($args) == 3)
                            {
                                $sender->sendMessage("Warp open status: " . var_export($data[$this->warp_id]["open"], true));
                                return true;
                            }
                            
                            switch(strtolower($args[3]))
                            {
                                case "true":
                                case "t":
                                case "yes":
                                case "y":
                                    $data[$this->warp_id]["open"] = true;
                                    $this->warps->setAll($data);
                                    $this->warps->save();
                                    $sender->sendMessage("§aWarp is now open to everyone.");
                                break;
                                case "false":
                                case "f":
                                case "no":
                                case "n":
                                    $data[$this->warp_id]["open"] = false;
                                    $this->warps->setAll($data);
                                    $this->warps->save();
                                    $sender->sendMessage("§aWarp is now closed for players without permission.");
                                break;
                                default:
                                    $sender->sendMessage("§cWarp open state can be either §4true §cor §4false§c!");
                                break;
                            }
                        break;
                        case "pos":
                            if ($sender instanceof Player)
                            {
                                $this->player_cords = array('x' => $sender->getX(), 'y' => $sender->getY(), 'z' => $sender->getZ());
                                $this->world = $sender->getLevel()->getName();
                                
                                $data[$this->warp_id]["world"] = $this->world;
                                $data[$this->warp_id]["x"] = $this->player_cords["x"];
                                $data[$this->warp_id]["y"] = $this->player_cords["y"];
                                $data[$this->warp_id]["z"] = $this->player_cords["z"];

                                $this->warps->setAll($data);
                                $this->warps->save();

                                $sender->sendMessage("§aWarp position updated to current location");
                            }
                            else $sender->sendMessage("§cThis command can only be used in-game.");
                            
                        break;
                        case "name":
                            if(count($args) < 3)
                            {
                                $sender->sendMessage("§cWarp name cannot be empty");
                                return true;
                            }
                            if($this->WarpID($args[3]) > 0)
                            {
                                $sender->sendMessage("§cWarp with that name already exists!");
                                return true;
                            }

                            $data[$this->warp_id]["name"] = $args[3];
                            $this->warps->setAll($data);
                            $this->warps->save();

                            $sender->sendMessage("§aWarp name updated to: §r" . $args[3]);
                        break;
                    }
                break;
                default:
					if ($sender instanceof Player)
					{
						$this->warp_name = $args[0];
						$this->warp_id = $this->WarpID($this->warp_name);
						$data = $this->warps->getAll();

						if($this->warp_id <= -1)
						{
							$sender->sendMessage("§cThere is no warp by that name listed.");
							return true;
						}
						if(isset($data[$this->warp_id]))
						{
							if(!$sender->hasPermission("warpspro.command.warp." . $data[$this->warp_id]["name"]) && !$data[$this->warp_id]["open"])
							{
								$sender->sendMessage("§cYou don't have permission to warp to " . $this->warp_name . "§r§c!");
								return true;
							}

							if(Server::getInstance()->loadLevel($data[$this->warp_id]["world"]) != false)
							{
								$curr_world = Server::getInstance()->getLevelByName($data[$this->warp_id]["world"]);
								$pos = new Position(
									$data[$this->warp_id]["x"],
									$data[$this->warp_id]["y"],
									$data[$this->warp_id]["z"], $curr_world
								);

								$sender->sendMessage("§aYou warped to:§f " . $this->warp_name);
								$sender->teleport($pos);
							}
							else $sender->sendMessage("§cCould not load chunk.§f It's not safe to teleport there.");
						}
						else $sender->sendMessage("§cThere is no warp by that name listed.");
					}else $sender->sendMessage("§cThis command can only be used in-game.");
                break;
            }
        }
		
		return true;
    }

    public function check_config()
    {
        $this->saveDefaultConfig();

        $defaults = [
            "plugin-name" => "WarpsPro",
            "warps-version" => 1.1
        ];

        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, $defaults);
        $this->config->set('plugin-name',"WarpsPro");
        $this->config->save();
    }

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveResource("warps.yml");
        $this->warps = new Config($this->getDataFolder() . "warps.yml", Config::YAML);
        $this->check_config();

        $this->UpdateWarps();
    }

    public function UpdateWarps()
    {
        $data = $this->warps->getAll();

        for($i = 0; $i < count($data) + 1; $i++)
            if(isset($data[$i]))
                if(!isset($data[$i]["open"]))
                    $data[$i]["open"] = true;

        $this->warps->setAll($data);
        $this->warps->save();
    }

    public function onDisable()
    {
        $this->warps->save();
    }
}
