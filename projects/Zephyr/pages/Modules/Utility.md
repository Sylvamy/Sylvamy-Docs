The utility module contains a wide range of commands you might find useful.

# Commands
----
| Command | Description | Permission |
|---------|-------------|------------|
| /ping [player] | Show your ping, or another player’s if provided | zephyr.command.ping |
| /zephyr reload [messages] | Reload the plugin, or only message files when `messages` is specified | zephyr.reload |
| /seen <player> | Show last seen/online info for a player; extra details if you also have admin view | zephyr.command.seen (admin view: zephyr.command.seen.admin) |
| /serverinfo | Display RAM, uptime, entities, player counts, TPS/MSPT | zephyr.command.serverinfo |
| /heal [player] | Heal yourself or another player, clearing hunger and effects (if enabled) | zephyr.command.heal (others: zephyr.command.heal.other) |
| /feed [player] | Restore hunger/saturation for you or another player | zephyr.command.feed (others: zephyr.command.feed.other) |
| /god [player] | Toggle god mode for yourself or another player | zephyr.command.god (others: zephyr.command.god.other) |
| /fly [player] | Toggle flight for yourself or another player | zephyr.command.fly (others: zephyr.command.fly.other) |

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