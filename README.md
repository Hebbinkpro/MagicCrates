# MagicCrates

Add customizable crates to your server

poggit: [![](https://poggit.pmmp.io/shield.state/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)[![Poggit-CI](https://poggit.pmmp.io/ci.shield/Hebbinkpro/MagicCrates/MagicCrates)](https://poggit.pmmp.io/ci/Hebbinkpro/MagicCrates/MagicCrates) 

- Create and remove crates with a simple form
- No Chest menu, but a beautiful animation instead when you open a crate
- You can create infinity custom crate types
- Add infinity items to your crate type with custom names, enchantments and lores
- Open crates with keys

## Commands
| Command | Description | Permission |
| --- | --- | --- |
| /mc create | Create a crate | mc.cmd.create |
| /mc remove | Remove a crate | mc.cmd.remove |
| /mc makekey <crate_type> \[amount] \[player] | Make a crate key | mc.cmd.makekey |

## Permissions
| Permission | Description | Default |
|  --- | --- | --- |
| mc.cmd | Access to the `/mc` command | OP |
| mc.cmd.create | Access to the `/mc create` command | OP |
| mc.cmd.remove | Access to the `/mc remove` command | OP |
| mc.cmd.makekey | Access to the `/mc makekey` command | OP |
| mc.break.remove | Permission to remove a crate by break it | OP |

## Usage
### Create crate
1. Use the command `/mc create`
2. Click on the chest where you your crate want
3. Select the crate type in the dropdown menu and click submit
4. Click **save crate**

### Remove crate
#### By command
1. Use the command `/mc remove`
2. Click the crate you want to delete
3. Click **Delete crate**
#### By block breaking
1. Destroy a crate
2. Click **Delete crate**

### Use crate
#### Create key
0. Create a crate key with te command `/mc makekey <crate_type> \[amount] \[player]`
#### Open crate
1. Click on a crate with the crate key for that crate type
2. Watch the animation
3. You received the item(s) in your inventory

## Additional Information
- This plugin uses [Commando](https://github.com/CortexPE/Commando) and [FormAPI](https://github.com/jojoe77777/FormAPI)
