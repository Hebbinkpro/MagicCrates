# MagicCrates [![](https://poggit.pmmp.io/shield.dl.total/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)

Add customizable crates to your server

## Downloads

#### Stable release: [![](https://poggit.pmmp.io/shield.api/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)

#### Newest release: [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/MagicCrates/MagicCrates)

## Information

- Create and remove crates with a simple form
- No Chest menu, but a beautiful animation instead when you open a crate
- You can create infinite custom crate types
- Add infinite items to your crate type with custom names, enchantments and lore's
- Add infinite commands that execute when a crate is opened
- Open crates with keys

## Commands

Command aliases: `/magiccrates`, `/mc`

| Command                                                 | Description      | Permission-             |
|---------------------------------------------------------|------------------|-------------------------|
| `/magiccrates create`                                   | Create a crate   | magiccrates.cmd.create  |
| `/magiccrates remove`                                   | Remove a crate   | magiccrates.cmd.remove  |
| `/magiccrates makekey <crate_type> \[amount] \[player]` | Make a crate key | magiccrates.cmd.makekey |

## Permissions

| Permission                 | Description                                  | Default |
|----------------------------|----------------------------------------------|---------|
| `magiccrates.cmd`          | Access to the `/magiccrates` command         | OP      |
| `magiccrates.cmd.create`   | Access to the `/magiccrates create` command  | OP      |
| `magiccrates.cmd.remove`   | Access to the `/magiccrates remove` command  | OP      |
| `magiccrates.cmd.makekey`  | Access to the `/magiccrates makekey` command | OP      |
| `magiccrates.break.remove` | Permission to remove a crate by breaking it  | OP      |

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

- Create a crate key with te command `/mc makekey <crate_type> [amount] [player]`

#### Open crate

1. Click on a crate with the crate key for that crate type
2. Watch the animation
3. You received the item(s) in your inventory

## TO DO

- Adding more options for rewards
- One time rewards

## Additional Information

- This plugin uses [Commando](https://github.com/CortexPE/Commando) and [FormsUI](https://github.com/Vecnavium/FormsUI)
