# MagicCrates [![](https://poggit.pmmp.io/shield.downloads/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)

Add customizable crates to your server.

- Add as many crate types as you want
- Item and command rewards
- Open crates with a nice animation
- In-game preview menu with the crate contents including the rarity of each item
- Dynamic rewards
- [Customies](https://github.com/CustomiesDevs/Customies) support

## Downloads

- Stable release: [![Poggit](https://poggit.pmmp.io/shield.api/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)
- Latest
  release: [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/MagicCrates/MagicCrates)

## How to use

### Commands

Command aliases: `/magiccrates`, `/mc`

| Command                                                           | Description                                                      | Permission-                  |
|-------------------------------------------------------------------|------------------------------------------------------------------|------------------------------|
| `/magiccrates`                                                    | MagicCrates Command                                              | magiccrates.cmd              |
| `/magiccrates create`                                             | Toggle crate Create mode                                         | magiccrates.cmd.create       |
| `/magiccrates remove`                                             | Toggle crate Remove mode                                         | magiccrates.cmd.remove       |
| `/magiccrates key <crate_type> [amount] [player]`                 | Give a crate key to a player                                     | magiccrates.cmd.key          |
| `/magiccrates key all <crate_type> [amount]`                      | Give a crate key to all online players                           | magiccrates.cmd.key.all      |
| `/magiccrates reward set <crate_type> <player> <reward> <amount>` | Set the amount of times a reward is received                     | magiccrates.cmd.reward.set   |
| `/magiccrates reward reset crate <crate_type>`                    | Reset the amount of times all rewards in the crate are received  | magiccrates.cmd.reward.reset |
| `/magiccrates reward reset reward <crate_type> <reward>`          | Reset the amount of times the reward is received in a crate type | magiccrates.cmd.reward.reset |
| `/magiccrates reward reset player <crate_type> <player> [reward]` | Reset the amount of times a player has received rewards          | magiccrates.cmd.reward.reset |

### Create a new crate

1. Use the command `/mc create`
2. Click on the chest where you want your crate. (double chests do not work)
3. Select the crate type in the dropdown menu and click _Submit_.
4. Click **save crate**

### Remove an existing crate

You can remove crates with a command:

1. Use the command `/mc remove`
2. Click on the crate you want to delete
3. Click _Delete crate_

You can also remove crates by breaking the crate:

1. Destroy a crate
2. Click _Delete crate_

### Create a crate key

You can only open crates by using a crate key. Each crate type has its own key.<br>
You can create keys using the command `/mc key <crate_type> [amount] [player]`<br>
When you have a crate key, you can open the matching crate by clicking on the crate.

### Crates

All crates from the same Crate Type have the exact same content,
and when opening a crate of a certain type, a random reward will be given to the player.

#### Crate Types

You can customize or add your own crates in [crate_types.json](#crate-types).

#### Crate Rewards

In a crate type, you can specify multiple rewards. However, not every reward will behave the same.<br>
Normal rewards have a static amount of them in the crate which results in a fixed probability of getting the item.<br>
However, you can also create **Dynamic** rewards.
The probability and amount of getting these rewards depends on the amount
of times the reward has been received by the
player or all players.<br>
Crate rewards have to be added in [crate_types.json](#crate-types), and there you can find more information about
creating the rewards.

## Config

- `delay` The delay in ticks before the crate-opening animation starts
- `prefix`   - The prefix in front of all messages the plugin sends
- `key-name` - The name format used for all the crate keys
- `save-data-ticks` - The number of ticks after which all data (crates and rewarded players) gets stored

### Default values

```yml
delay: 20
prefix: "§r[§6Magic§cCrates§r]"
key-name: "§r[§6Crate §cKey§r] §e{crate}"
save-data-ticks: 6000
```

## Data

All data of the plugin is (currently) stored in JSON files, but only the following file is safe to edit:

- `crate_types.json` - Stores all your crate types
  It is not recommended to touch the other JSON files, as this can break the plugin:
- `crates.json` - Stores all crates you have created on your server with their position, world and crate type
- `rewarded_players.json` - Stores the amount of dynamic rewards a player has received

## Changelogs

List of most changes in the new version (v3.0.0)

- \+ Added reward id's
- \+ Added dynamic rewards
- \+ Added `keyall` command
- \+ Improved the crate opening animation
  - The chest will now open, and a sound will be played
  - If there are multiple items inside the reward, all items will hover out of the chest
  - Command only rewards will now display a command block as item
- \| Deprecated rewards without ID's
- \- Removed crate type yaml->json conversion for plugins before 2.2.1

## Todo

- In-game UI for managing crate types

## Credits

- This plugin uses [Commando](https://github.com/Paroxity/Commando) and [FormsUI](https://github.com/Vecnavium/FormsUI)

# Documentation

## Permissions

| Permission                 | Description                                 | Default |
|----------------------------|---------------------------------------------|---------|
| `magiccrates.cmd`          | Access to the `/magiccrates` command        | OP      |
| `magiccrates.cmd.create`   | Access to the `/magiccrates create` command | OP      |
| `magiccrates.cmd.remove`   | Access to the `/magiccrates remove` command | OP      |
| `magiccrates.cmd.key`      | Access to the `/magiccrates key` command    | OP      |
| `magiccrates.cmd.key.all`  | Access to the `/magiccrates keyall` command | OP      |
| `magiccrates.break.remove` | Permission to remove a crate by breaking it | OP      |

## Crate Types

The `crate_types.json` file contains all crate types, including the rewards.
You can find all the specifications about how to create your own custom crate types below.

_If there is something wrong with the file (e.g. invalid json, a type is missing required values, or invalid data values
are given)
the plugin will notify you in the server console on startup indicating what went wrong, so that you can fix the error._

### Crate Types

The basic structure in `crate_types.json` is defined like the following:

```json5
{
  "<type_id>": "CrateType",
  ...
}
```

You can add as many crate types (`<type_id>: CrateType`) to this file as you need, but every `CrateType` needs an ID!

### Crate Type

```json5
{
  // the name shown above the crate
  "name": "string",
  // the crate rewards
  "rewards": "CrateReward[]",
  //  commands executed after a player opened the crate
  "commands": "Command[]",
  // [optional] a reward or reward id that should be used as the replacement reward
  "replacement": "CrateReward|string"
}
```

The replacement defined in the `CrateType` is used as a global replacer for all `DynamicCrateReward`'s (unless there is
a replacement specified in the `DynamicCrateReward`).

#### Registration of Crate Rewards

Since v3.0.0, it is possible to register crate rewards with unique ID's. It is recommended to use unique ID's as those
will prevent issues which can occur when renaming dynamic rewards without ID.<br>
But because all versions before 3.0.0 do not make use of this system, I decided to make it backwards compatible until
v4.0.0 releases.

**Recommended:**

```json5
{
  "rewards": {
    "<reward_id>": "CrateReward",
    ...
  }
}
```

**Deprecated:** (will be removed in v4.0.0)

```json5
{
  "rewards": [
    "CrateReward",
    ...
  ]
}
```

#### Command

A command is a string representing the command you want the CONSOLE to execute, but it is possible to include some
parameters in the command.

##### Command Parameters

to use the parameter, include `{<parameter>}` in the string.

| parameter | description                                  |
|-----------|----------------------------------------------| 
| player    | The name of the player that opened the crate |
| crate     | The name of the crate type that was opened   |
| crate_id  | The ID of the crate type that was opened     |
| reward    | The name of the reward the player received   |
| reward_id | The id of the reward the player received     |

A Command will look something like this: `"say {player} received {reward} from a {crate}"`.<br>
Executing this command will result
in `say Hebbinkpro received Unique Diamond from a Rare Crate` when `Hebbinkpro` opened
a `Rare Crate` and won a `Unique Diamond`.

### Crate Reward

This is the data structure of a `CrateReward`

```json5
{
  // name of the reward
  "name": "string",
  // amount of this reward inside the crate.
  "amount": "int",
  // the item(s) the player gets
  "items": "Item[]",
  // commands executed when the player wins this reward
  "commands": "string[]",
  // [optional] path/url to an image that will be displayed in the crate UI
  "icon": "string"
}
```

At least one item or command should be given

This is the data structure of a `DynamicCrateReward`

```json5
{
  // name of the reward
  "name": "string",
  // [optional] amount of this reward inside the crate.
  "amount": "int",
  // [optional] maximum amount of this reward the player can get
  "player_max": "int",
  // [optional] maximum amount of this reward that can be awarded server wide
  "global_max": "int",
  // [optional] amount of the replacement reward inside the crate
  "replace_amount": "int",
  // [optional] if the reward should be replaced by a replacment reward when the maximum is reached
  "replace": "bool",
  // the item(s) the player gets
  "items": "Item[]",
  // commands executed when the player wins this reward
  "commands": "string[]",
  // [optional] path/url to an image that will be displayed in the crate UI
  "icon": "string",
  // [optional] a reward or reward id that should be used as the replacement reward
  "replacement": "CrateReward|string"
}
```

The dynamic reward is a more complex version of the default reward.

- The `player_max` AND/OR `global_max` should be specified (`>0`), otherwise it is a normal `CrateReward`
- If `amount` is given, the amount will be used for the reward distribution, otherwise the amount of times the player is
  allowed to get the reward will be used.
- If `replace_amount` is given, this is used for the max amount of replacements
- If `replace` is `false`, the reward will not be replaced
- If `replacement` is given, this will be used as the replacement for the reward
- If no `amount` is specified, there will be some of the `replacement` reward inside the crate based upon the amount of
  times the player has received the reward.

WARNING You can only use a normal `CrateReward` as a replacement reward.

#### Icon

Icons are used for the UI when you interact with a crate without a key. In this UI, all items available in the chest are
displayed with their icon.<br>
By default, the icon of the rewarded item will be displayed, but you can customize this by providing an url.<br>
You can use minecraft textures, in this case you only have to provide the path that points to a texture inside
the [texture pack](https://github.com/mojang/bedrock-samples) (e.g. `textures/items/diamond` or `textures/blocks/dirt`)

### Item

This is the data structure of an `Item` inside a `CrateReward`.<br>
Items registered using Customies are also supported, but make sure you identify them correctly.

```json5
{
  // id of the item (e.g. minecraft:dirt or customies:example)
  "id": "string",
  // [optional] custom name of the item
  "name": "string",
  // [optional] amount of the item the player will receive
  "amount": "int",
  // [optional] add one or more lines of lore to the item
  "lore": "string | string[]",
  // [optional] list of enchantments applied to the item
  "enchantments": "Enchantment[]"
}
```

### Enchantment

This is the data structure of an `Enchantment` inside an `Item`

```json5
{
  // name of the enchantment
  "name": "string",
  // level of the enchantment
  "level": "int"
}
```

_**Note:** The enchantment name should be registered in PMMP, otherwise the crate type will not be loaded_

### Example Crate Types

#### Common Crate

```json
{
  "common": {
    "name": "§cCommon §6Crate",
    "rewards": {
      "iron": {
        "name": "Common Iron",
        "amount": 5,
        "item": {
          "id": "iron_ingot"
        }
      },
      "dirt": {
        "name": "Dirt",
        "amount": 15,
        "item": {
          "id": "dirt",
          "amount": 2
        }
      }
    }
  }
}
```

If a player opens this crate:

- In this common crate are 5x `iron` and 15x `dirt`
- The total amount of rewards inside the crate is 20.
- The probability of getting `iron` is `5/20 * 100% = 25%`

#### Rare Crate

```json
{
  "rare": {
    "name": "§bRare §6Crate",
    "rewards": {
      "diamond": {
        "name": "Unique Diamond",
        "global_max": 1,
        "replacement": "dirt",
        "item": {
          "id": "diamond",
          "name": "Unique Diamond",
          "amount": 1,
          "lore": "The only Unique Diamond available in the server",
          "enchantments": [
            {
              "name": "efficiency",
              "level": 1
            }
          ]
        },
        "commands": [
          "msg {player} you are the only player in the server with this diamond."
        ]
      },
      "dirt": {
        "name": "Dirt",
        "amount": 9,
        "item": {
          "id": "dirt",
          "amount": 2
        }
      }
    },
    "commands": [
      "say {player} won {reward} from a {crate} crate"
    ]
  }
}
```

If a player opens this crate:

- In this rare crate are 1x `diamond` and 9x `dirt`
- If another user (or self) has already received the `diamond`, replace the `diamond` by 1x `dirt`
  - If there is no `amount`, or `replacement_amount` given, `global_max` or `player_max` is used
  - So now we have 10x `dirt`

#### Magic Crate

```json
{
  "magic": {
    "name": "§cMagic §6Crate",
    "replacement": "dirt",
    "rewards": {
      "shard": {
        "name": "Magic Shard",
        "player_max": 1,
        "amount": 5,
        "replace_amount": 10,
        "item": {
          "id": "amethyst_shard",
          "name": "Magic Amethyst",
          "amount": 1,
          "lore": "A Shard full of Magic"
        },
        "commands": [
          "msg {player} you have received your only magic shared."
        ]
      },
      "dirt": {
        "name": "Dirt",
        "amount": 40,
        "item": {
          "id": "dirt",
          "amount": 2
        }
      }
    },
    "commands": [
      "say {player} won {reward} from a {crate} crate"
    ]
  }
}
```

If a player opens this crate:

- In this magic crate are 5x `shard` and 40x `dirt`
- If the player has already received 1x `shard`, the shard is replaced by 10x `dirt` (`replace_amount`x `replacement`)
  - The crate now contains 50x `dirt` and no shards

