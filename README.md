# WarpsPro
A simple warp plugin for PocketMine-MP

# FEAUTURES
  - /warp \[warp name\]
    * If you don't input any arguments it will dispaly all the server's warps.
    * Inputing a name after the command will teleport you in the warp. Example: **/warp shop**
  - /setwarp <warp name> | **Only In-Game** | 
    * Sets a warp at the player ,who run the command, current position. Example: **/setwarp shop** 
    * Only players with **warpspro.command.setwarp** permission can run this command.
  - /delwarp <warp name>
    * Deletes the specified warp. Example: **/delwarp shop**
    * Only Console or players with **warpspro.command.delwarp** permission can run this command.
  - /wild | **Only In-Game**
    * Teleports the player ,who run the command, somewhere inside the specified range in **config.yml**
