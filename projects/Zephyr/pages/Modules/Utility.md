The utility module contains a wide range of commands you might find useful.

# Commands
----
| Command | Description | Permission | Aliases |
|---------|-------------|------------|---------|
| /ping [player] | Show your ping or another player’s | zephyr.command.ping | |
| /zephyr reload [messages] | Reload the plugin, or just messages when specified | zephyr.reload | |
| /seen <player> | Show last seen/online info (admin view if you also have admin perm) | zephyr.command.seen | |
| /serverinfo | Display RAM, uptime, entities, player counts, TPS/MSPT | zephyr.command.serverinfo | |
| /heal [player] | Heal yourself or another player, clearing hunger and effects (if enabled) | zephyr.command.heal | |
| /feed [player] | Restore hunger/saturation for you or another player | zephyr.command.feed | |
| /god [player] | Toggle god mode for yourself or another player | zephyr.command.god (others: zephyr.command.god.other) | |
| /fly [player] | Toggle flight for yourself or another player | zephyr.command.fly | |
| /gamemode <mode> [player] | Set your or another player’s gamemode | self: zephyr.gamemode.all or zephyr.gamemode.<mode> | /gmc, /gms, /gma, /gmsp |
| /weather <sun|rain|storm> [world] (aliases: /sun, /rain, /storm) | Change weather in a world | zephyr.command.weather | |
| /time <day|night|noon|midnight|morning|evening|tick> [world] | Set time in a world (tick accepts 0–24000) | zephyr.command.time | /day, /night, /noon, /midnight, /morning |


# Config
----
The config can be found at `plugins/Zephyr/Modules/Utility.yml`.

## Heal
### ClearEffects
If true, healing clears all active potion effects in addition to restoring health/food.

## God
### RemoveOnLogout
If true, disables god mode for players when they log out.

## Fly
### RemoveOnLogout
If true, removes flight ability when players log out.
### RemoveOnDeath
If true, removes flight ability when players die.

## Modules/Utility.yml
----
```yaml
Heal:
  ClearEffects: true

God:
  RemoveOnLogout: true

Fly:
  RemoveOnLogout: true
  RemoveOnDeath: true
```