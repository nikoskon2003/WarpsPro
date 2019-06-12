
[![WarpsPro](https://i.imgur.com/K3jomxC.jpg)](https://github.com/nikoskon2003/WarpsPro/)
# WarpsPro
A simple warp plugin for PocketMine-MP

# FEAUTURES
  - /warp
	* Main command. Displays server warps
	* Permission: `warpspro.command.warp`, default
  - /warp add <warp name>
	* Registers new warp for the server
	* Permission: `warpspro.command.setwarp`, op
  - /warp del <warp name>
	* Removes warp from the server
	* Permission: `warpspro.command.delwarp`, op
  - /warp edit <warp name> <open, pos, name> \[state\]
	* Edit warp information
	* open: 
	  * Sets open status of warp
	  * state: true, false (or none to return current status)
	* pos:
	  * Updates position of warp to player position
	  * No state is needed
	* name:
	  * Updates name of warp
	  * state: New warp name
    * Permission: `warpspro.command.edit`, op
	
If a warp is closed, players with permission: `warpspro.command.warp.<warp name>` will be able to warp to it. 
Note that `<warp name>` is the name of the warp and it is case sensitive