# MagicCrates [![](https://poggit.pmmp.io/ci.badge/Hebbinkpro/MagicCrates/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)

Add customizable crates to your server.
- Add as many crate types as you want
- Item and command rewards
- Open crates with a nice animation
- In-game preview menu with the crate contents including the rarity of each item
- [Customies](https://github.com/CustomiesDevs/Customies) support

## Downloads

- Stable release: [![Poggit](https://poggit.pmmp.io/shield.api/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)
- Newest
  release: [![Poggit CI](https://poggit.pmmp.io/shield.state/MagicCrates)](https://poggit.pmmp.io/ci/Hebbinkpro/MagicCrates/MagicCrates)

## How to use

### Commands

Command aliases: `/magiccrates`, `/mc`

| Command                                           | Description      | Permission-            |
|---------------------------------------------------|------------------|------------------------|
| `/magiccrates create`                             | Create a crate   | magiccrates.cmd.create |
| `/magiccrates remove`                             | Remove a crate   | magiccrates.cmd.remove |
| `/magiccrates key <crate_type> [amount] [player]` | Make a crate key | magiccrates.cmd.key    |

### Permissions

| Permission                 | Description                                 | Default |
|----------------------------|---------------------------------------------|---------|
| `magiccrates.cmd`          | Access to the `/magiccrates` command        | OP      |
| `magiccrates.cmd.create`   | Access to the `/magiccrates create` command | OP      |
| `magiccrates.cmd.remove`   | Access to the `/magiccrates remove` command | OP      |
| `magiccrates.cmd.key`      | Access to the `/magiccrates key` command    | OP      |
| `magiccrates.break.remove` | Permission to remove a crate by breaking it | OP      |

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

## Options

### Config

Here you can see all options inside the `config.yml`

```yml
# The delay in ticks before the item animation begins
delay: 20
# Prefix for messages send by the plugin
prefix: "§r[§6Magic§cCrates§r]"
# Prefix for a crate key item name
key-name: "§r[§6Crate §cKey§r] §e{crate}"
```

### Adding crate types

You can add your own crate types to the `crate_types.json` file.<br>
Each crate type you want to add has its own unique identifier. You can choose this identifier yourself, but make sure
that it is a valid array key.

```json5
{
  "<id>": {
    // replace <id> with a custom id
    "name": string,
    // the name shown above the crate
    "rewards": reward[],  // the rewards inside the crate
"commands": string[]  // [optional] commands executed after a player opened the crate
}
}
```

At least one reward OR command has to be inside the crate type

#### Adding rewards

A crate type can have many rewards, and you can set the rarity of each reward individually.<br>
A reward can also only have commands without an item

```json5
"rewards": [
{
"name": string, // name of the reward
"amount": int, // amount of this reward inside the crate
"item": item|item[], // the item(s) the player gets
"commands": string[], // commands executed when the player wins this reward
"icon": string         // [optional] path/url to an image that will be displayed in the crate UI
},
... // more rewards      
]
```

#### Reward item

```json5
"item": {
"id": string, // id of the item (e.g. minecraft:dirt or customies:example)
"name": string, // [optional] custom name of the item
"amount": int, // [optional] amount of the item the player will receive
"lore": string|string[], // [optional] add one or more lines of lore to the item
"enchantments": enchantment[]   // [optional] list of enchantments applied to the item
}
```

##### Enchantments

You can add multiple enchantments to your crate reward items. You can add multiple enchantments by providing a list of
the following object:

```json5
"enchantments": [
{
"name": string, // name of the enchantment
"level": int    // level of the enchantment
},
... // other enchantments
]
```

#### Icons

Icons are used for the UI when you interact with a crate without a key. In this UI, all items available in the chest are
displayed with their icon.<br>
By default, the icon of the rewarded item will be displayed, but you can customize this by providing an url.<br>
You can use minecraft textures, in this case you only have to provide the path that points to a texture inside
the [texture pack](https://github.com/mojang/bedrock-samples) (e.g. `textures/items/diamond` or `textures/blocks/dirt`)

#### Commands

The commands that you can place in the command lists in the type or reward are the same commands as you can execute in
the console.<br>
It is also possible to add some parameters to the commands to make them more usable. The options are:

- `{player}` The name of the player
- `{crate_type}` The id of the crate type
- `{crate}` The name of the crate type
- `{reward}` The name of the reward

You can use multiple of these options in a command, or none of them.

##### Commands example

```json5
"commands": [
"magiccrates key {crate_type} 5 {player}", // this gives the player that won the reward 5 crate keys of the crate type the player opened
"say {player} won {reward} in a {crate}", // sends a server wide message that the player won a reward from the crate
... // more commands
]
```

#### Example Crate Types

```json
{
  "common": {
    "name": "§eCommon §6Crate",
    "rewards": [
      {
        "name": "Diamond",
        "item": {
          "id": "minecraft:diamond",
          "name": "Common Diamond",
          "amount": 1,
          "lore": "Diamond from a crate",
          "enchantments": [
            {
              "name": "efficiency",
              "level": 1
            }
          ]
        },
        "commands": [
          "msg {player} be carefully with this diamond!"
        ],
        "amount": 1
      },
      {
        "name": "dirt",
        "item": {
          "id": "minecraft:dirt",
          "amount": 2
        }
      }
    ],
    "commands": [
      "say {player} won {reward} from a {crate_type} crate"
    ]
  }
}

```

## Credits

- This plugin uses [Commando](https://github.com/Paroxity/Commando) and [FormsUI](https://github.com/Vecnavium/FormsUI)
