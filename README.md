# MagicCrates [![](https://poggit.pmmp.io/shield.dl.total/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)

Add customizable crates to your server.
- Add as many crate types as you want
- Item and command rewards
- Open crates with a nice animation
- In-game preview menu with the crate contents including the rarity of each item (NEW in v2.1.0)
- [Customies](https://github.com/CustomiesDevs/Customies) support (NEW in v2.1.0)

## Downloads

- Stable release: [![](https://poggit.pmmp.io/shield.api/MagicCrates)](https://poggit.pmmp.io/p/MagicCrates)
- Newest release: [Poggit CI](https://poggit.pmmp.io/ci/Hebbinkpro/MagicCrates/MagicCrates)

## How to use

### Commands

Command aliases: `/magiccrates`, `/mc`

| Command                                                 | Description      | Permission-             |
|---------------------------------------------------------|------------------|-------------------------|
| `/magiccrates create`                                   | Create a crate   | magiccrates.cmd.create  |
| `/magiccrates remove`                                   | Remove a crate   | magiccrates.cmd.remove  |
| `/magiccrates makekey <crate_type> \[amount] \[player]` | Make a crate key | magiccrates.cmd.makekey |

### Permissions

| Permission                 | Description                                  | Default |
|----------------------------|----------------------------------------------|---------|
| `magiccrates.cmd`          | Access to the `/magiccrates` command         | OP      |
| `magiccrates.cmd.create`   | Access to the `/magiccrates create` command  | OP      |
| `magiccrates.cmd.remove`   | Access to the `/magiccrates remove` command  | OP      |
| `magiccrates.cmd.makekey`  | Access to the `/magiccrates makekey` command | OP      |
| `magiccrates.break.remove` | Permission to remove a crate by breaking it  | OP      |

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
You can create keys using the command `/mc makekey <crate_type> [amount] [player]`<br>
When you have a crate key, you can open the matching crate by clicking on the crate.

## Options

Here you can see all options inside the `config.yml`

- `delay: int` Amount of ticks it will take until the opening animation starts after a player opens a crate.
- `types: type[]` A list of all available crate types in your server. You can add as many types as you want.

### Adding crate types

Each crate type you want to add has its own unique identifier. You can choose this identifier yourself, but make sure
that it is a valid array key.

```yml
<id>:
  name: string
  rewards: reward[]
  commands: string[] # Optional
```

- `name: string` The name shown above a crate
- `rewards: reward[]` A list of all rewards inside the crate
- `commands: string[]` A list of commands that will always be executed when opening this type of crate

#### Adding rewards

A crate type can have many rewards, and you can set the rarity of each reward individually.

```yml
 - name: string
   item:
     id: item_id
     name: string          # Optional
     amount: int           # Optional
     lore: string|string[] # Optional
     enchantments: # Optional
       - name: string
         level: int
   commands: string[]
   amount: int
   icon: string           # Optional
```

- `name: string` The name of the reward.
- `item:` The item that will be given to the player as reward.
    - `id: string` The minecraft ID of the item (e.g. `"diamond"` or `"ender_pearl"`)
    - `name: string` The custom name of the item.
    - `amount: int` The amount of the item.
    - `lore: string|string[]` The item lore.
        - You can add a single line by providing a string e.g. `"My lore"` or multiple lines by providing an array
          e.g. `["First", "Second"]`.
    - `enchantments:` A list of all enchantments on the item.
        - `name: string` The name of the enchantment.
        - `level: int` The level of the enchantment
- `commands: string[]` A list of commands when the player gets this reward.
- `amount: int` How many times this reward is inside the crate. This makes you able to set rarities to rewards.
    - e.g. if you have a reward with amount `1` and a reward with amount `2`, there are a total of `3` items in the
      crate.
      The probability that you win the first item is `1/3` and for the second item it is `2/3`.
- `icon: string` The path/url to a texture or image. The texture/image will be displayed on the preview menu.
    - For minecraft textures you can use `textures/items/<name>` for items or `textures/blocks/<name>` for blocks.
    - It is also possible to use other images instead of the minecraft textures. This have to be urls that start
      with`http://` or `https://` to work.
    - When no icon is provided, the icon of the item will be used. When there are only commands in the reward, a command
      block icon is shown.
    - Notice that for some blocks, e.g. logs, concrete and wool (Most of the blocks added to later versions of mc don't
      have this problem), there will no icon be found, if you want red wool for example, you have to
      use `textures/blocks/wool_colored_red`.
    - You can find all vanilla resource pack textures under the `textures/` path. You can see all the textures in
      the [bedrock-samples](https://github.com/Mojang/bedrock-samples/) resource pack.

#### Command options

The commands that you can place in the command lists in the type or reward are the same commands as you can execute in
the console.<br>
It is also possible to add some parameters to the commands to make them more usable. The options are:

- `{player}` The name of the player
- `{crate_type}` The id of the crate type
- `{crate}` The name of the crate type
- `{reward}` The name of the reward

#### Example Config

```yml
delay: 1

types:
  common:
    name: "§eCommon §6Crate"
    rewards:
      - name: "Diamond"
        item:
          id: "minecraft:diamond"
          name: "Diamond"
          amount: 1
          lore: "Diamond from a crate"
          enchantments:
            - name: "efficiency"
              level: 1
        commands:
          - "msg {player} be carefull with this diamond!"

        amount: 1
      - name: "Dirt"
        item:
          id: "dirt"
          amount: 2
        commands: [ ]
        amount: 2
    commands:
      - "say {player} won {reward} from a {crate_type} crate"

```

## Credits

- This plugin uses [Commando](https://github.com/CortexPE/Commando) and [FormsUI](https://github.com/Vecnavium/FormsUI)
