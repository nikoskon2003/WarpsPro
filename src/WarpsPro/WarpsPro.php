<?php

namespace WarpsPro;

use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\Server;

class WarpsPro extends PluginBase  implements CommandExecutor, Listener {
    /** @var \SQLite3 */
    private $db2;
    /** @var string */
    public $username;
    /** @var string */
    public $world;
    /** @var string */
    public $warp_loc;
    /** @var Position[] */
    public $config;
    /** @var int[] */
    public $player_cords;
    /** @var \SQLite3Result */
    public $result;
    /** @var \SQLite3Stmt */
    public $prepare;
	/** @var bool */
	public $enable_wild;

    public function fetchall(){
        $row = array();

        $i = 0;

        while($res = $this->result->fetchArray(SQLITE3_ASSOC)){

            $row[$i] = $res;
            $i++;

        }
        return $row;
    }

    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
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
                        $this->prepare = $this->db2->prepare("SELECT x,y,z,world,title FROM warps");
                        $this->result = $this->prepare->execute();
                        $sql          = $this->fetchall();
                        $warp_list = null;
                        foreach ($sql as $ptu)
                        {
                            $warp_list .= '§a[§f' . $ptu['title'] . '§a]';
                        }
                        if($warp_list != null){
                            $sender->sendMessage("Warps: " . $warp_list);
                            return true;
                        }else{
                            $sender->sendMessage("§cThis server has no warps.");
                            return true;
                        }

                    }else{
                        $this->warp_loc = $args[0];
                        $this->prepare = $this->db2->prepare("SELECT title,x,y,z,world FROM warps WHERE title = :title");
                        $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql          = $this->fetchall();
						if(count($sql) > 0){
							$sql = $sql[0];
								if(isset($sql['world'])){
									if(Server::getInstance()->loadLevel($sql['world']) != false){
										$curr_world = Server::getInstance()->getLevelByName($sql['world']);
										$pos = new Position((int) $sql['x'], (int) $sql['y'], (int) $sql['z'], $curr_world);
										$sender->sendMessage("§aYou warped to:§f " . $sql['title']);
										$sender->teleport($pos);
										return true;
									}else{
										$sender->sendMessage("§cCould not load chunk.§f It's not safe to teleport.");
										return true;
									}
								}

						}else{
							$sender->sendMessage("§cThere is no warp by that name listed.");
							return true;
						}
                    }
                } else{
					if (count($args) == 0)
                    {
                        $this->prepare = $this->db2->prepare("SELECT x,y,z,world,title FROM warps");
                        $this->result = $this->prepare->execute();
                        $sql          = $this->fetchall();
                        $warp_list = null;
                        foreach ($sql as $ptu)
                        {
                            $warp_list .= '§a[§f' . $ptu['title'] . '§a]';
                        }
                        if($warp_list != null){
                            $sender->sendMessage("Warps: " . $warp_list);
                            return true;
                        }else{
                            $sender->sendMessage("§cThis server has no warps.");
                            return true;
                        }
                    }else{
						$sender->sendMessage("§cThis command can only be used in the game.");
						return true;
					}
                }
                break;
            case 'setwarp':
                if (!$sender->hasPermission("warpspro.command.setwarp")) {
                    $sender->sendMessage("§c[WarpsPro] No permission.");
                    return true;
                }
                if ($sender instanceof Player)
                {
                    if (!$sender->hasPermission("warpspro.command.setwarp")) {
                        $sender->sendMessage("§c[WarpsPro] No permission.");
                        return true;
                    }

                    if((count($args) != 0) && (count($args) < 2))
                    {
                        $this->player_cords = array('x' => (int) $sender->getX(),'y' => (int) $sender->getY(),'z' => (int) $sender->getZ());
                        $this->world = $sender->getLevel()->getName();
                        $this->warp_loc = $args[0];
                        $this->prepare = $this->db2->prepare("SELECT title,x,y,z,world FROM warps WHERE title = :title");
                        $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sql          = $this->fetchall();
                        if( count($sql) > 0 )
                        {
                            $sql = $sql[0];
                            $this->prepare = $this->db2->prepare("UPDATE warps SET world = :world, title = :title, x = :x, y = :y, z = :z WHERE title = :title");
                            $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();

                        }
                        else
                        {
                            $this->prepare = $this->db2->prepare("INSERT INTO warps (title, world, x, y, z) VALUES (:title, :world, :x, :y, :z)");
                            $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                            $this->prepare->bindValue(":world", $this->world, SQLITE3_TEXT);
                            $this->prepare->bindValue(":x", $this->player_cords['x'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":y", $this->player_cords['y'], SQLITE3_TEXT);
                            $this->prepare->bindValue(":z", $this->player_cords['z'], SQLITE3_TEXT);
                            $this->result = $this->prepare->execute();

                        }

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
                    $sender->sendMessage("§cThis command can only be used in the game.");
                    return true;
                }
                break;
            case 'delwarp':
                if (!$sender->hasPermission("warpspro.command.delwarp")) {
                    $sender->sendMessage("§c[WarpsPro] No permission.");
                    return true;
                }
                if((count($args) != 0) && (count($args) < 2))
                {

                    $this->warp_loc = $args[0];
                    $this->prepare = $this->db2->prepare("SELECT * FROM warps WHERE title = :title");
                    $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                    $this->result = $this->prepare->execute();
                    $sql          = $this->fetchall();
                    if( count($sql) > 0 )
                    {
                        $this->prepare = $this->db2->prepare("DELETE FROM warps WHERE title = :title");
                        $this->prepare->bindValue(":title", $this->warp_loc, SQLITE3_TEXT);
                        $this->result = $this->prepare->execute();
                        $sender->sendMessage("§aWarp named:§f " . $this->warp_loc . " §r§a,has been deleted.");
                        return true;
                        }
                        else
                        {
                        $sender->sendMessage("§cNo Warps matching that name for this server.");
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
						$sender->sendMessage("§c[WarpsPro] No permission.");
						return true;
					}
					if ($sender instanceof Player)
					{
						$this->world = $sender->getLevel()->getName();
						foreach($this->getServer()->getLevels() as $aval_world => $curr_world)
						{
							if ($this->world == $curr_world->getName())
							{
								$pos = $sender->getLevel()->getSafeSpawn(new Vector3(rand('-'.$this->config->get("wild-MaxX"), $this->config->get("wild-MaxX")),rand(70,100),rand('-'.$this->config->get("wild-MaxY"), $this->config->get("wild-MaxY"))));
									$pos->getLevel()->loadChunk($pos->getX(),$pos->getZ());
									$pos->getLevel()->getChunk($pos->getX(),$pos->getZ(),true);
									$pos->getLevel()->generateChunk($pos->getX(),$pos->getZ());
									$pos = $pos->getLevel()->getSafeSpawn(new Vector3($pos->getX(),rand(4,100),$pos->getZ()));
								if($pos->getLevel()->isChunkLoaded($pos->getX(),$pos->getZ()))
								{
									$sender->teleport($pos->getLevel()->getSafeSpawn(new Vector3($pos->getX(),rand(4,100),$pos->getZ())));
									$sender->sendMessage("§aTeleported you some where wild.");
									return true;
								}
								else
								{
									$sender->sendMessage("§cCould not load chunk.§fIt isn't safe to teleport.");
									return true;
								}

							}
						}

					}
					else
					{
						$sender->sendMessage("§cThis command can only be used in the game.");
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
	//In future save in .yml not .db
    public function create_db(){
        $this->prepare = $this->db2->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name='warps'");
        $this->result = $this->prepare->execute();
        $sql = $this->fetchall();
        $count = count($sql);
        if($count == 0){
            $this->prepare = $this->db2->prepare("CREATE TABLE warps (
                      id INTEGER PRIMARY KEY,
                      x TEXT,
                      y TEXT,
                      z TEXT,
                      world TEXT,
                      title TEXT)");
            $this->result = $this->prepare->execute();
            $this->getLogger()->info(TextFormat::AQUA."essentialsTP+ warps database created!");
        }

    }

    public function check_config(){
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, array());
        $this->config->set('plugin-name',"WarpsPro");
        $this->config->save();

        if(!$this->config->get("sqlite-dbname"))
        {
            $this->config->set("sqlite-dbname", "WarpsPro");
            $this->config->save();
        }
		if($this->config->get("enable-wild-command") == false)
        {
            $this->config->set("enable-wild-command", "true");
            $this->config->save();
        }
        if($this->config->get("wild-MaxX") == false)
        {
            $this->config->set("wild-MaxX", "300");
            $this->config->save();
        }
        if($this->config->get("wild-MaxY") == false)
        {
            $this->config->set("wild-MaxY", "300");
            $this->config->save();
        }
    }

    public function onEnable(){
        $this->getLogger()->info(TextFormat::GOLD."WarpsPro is loading...");
        @mkdir($this->getDataFolder());
        $this->check_config();
        try{ //In future, get .yml not .db
            if(!file_exists($this->getDataFolder().$this->config->get("sqlite-dbname").'.db')){
                $this->db2 = new \SQLite3($this->getDataFolder().$this->config->get("sqlite-dbname").'.db', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            }else{
                $this->db2 = new \SQLite3($this->getDataFolder().$this->config->get("sqlite-dbname").'.db', SQLITE3_OPEN_READWRITE);
            }
        }
        catch (\Throwable $e)
        {
            $this->getLogger()->critical($e->getMessage());
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->create_db();
        $this->getLogger()->info(TextFormat::GREEN."WarpsPro has been loaded!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		
		$this->enable_wild = $this->config->get("enable-wild-command");
    }

    public function onDisable(){
        if($this->prepare){
            $this->prepare->close();
        }
        $this->getLogger()->info("WarpsPro Disabled");
    }
}
