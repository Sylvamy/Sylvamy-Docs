The utility module contains a wide range of commands you might find useful.

# Commands
----
| Command | Description | Permission |
|---------|-------------|------------|
| /ping [player] | Show your ping or another player’s | zephyr.command.ping |
| /zephyr reload [messages] | Reload the plugin, or just messages when specified | zephyr.reload |
| /seen <player> | Show last seen/online info (admin view if you also have admin perm) | zephyr.command.seen |
| /serverinfo | Display RAM, uptime, entities, player counts, TPS/MSPT | zephyr.command.serverinfo |
| /heal [player] | Heal yourself or another player, clearing hunger and effects (if enabled) | zephyr.command.heal |
| /feed [player] | Restore hunger/saturation for you or another player | zephyr.command.feed |
| /god [player] | Toggle god mode for yourself or another player | zephyr.command.god |
| /fly [player] | Toggle flight for yourself or another player | zephyr.command.fly |
| /gamemode <mode> [player] | Set your or another player’s gamemode | zephyr.gamemode.<mode> |
| /gmc [player] | Set creative mode | self: zephyr.gamemode.creative |
| /gms [player] | Set survival mode | self: zephyr.gamemode.survival |
| /gma [player] | Set adventure mode | self: zephyr.gamemode.adventure |
| /gmsp [player] | Set spectator mode | self: zephyr.gamemode.spectator |
| /weather <weather> [world] | Change weather in a world | zephyr.command.weather |
| /sun [world] | Set weather to sun/clear | zephyr.command.weather |
| /rain [world] | Set weather to rain | zephyr.command.weather |
| /storm [world] | Set weather to storm | zephyr.command.weather |
| /time <time> [world] | Set time in a world (tick accepts 0–24000) | zephyr.command.time |
| /day [world] | Set time to day | zephyr.command.time |
| /night [world] | Set time to night | zephyr.command.time |
| /noon [world] | Set time to noon | zephyr.command.time |
| /midnight [world] | Set time to midnight | zephyr.command.time |
| /morning [world] | Set time to morning | zephyr.command.time |

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