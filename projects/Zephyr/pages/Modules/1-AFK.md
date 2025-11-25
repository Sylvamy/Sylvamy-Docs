The AFK module lets players mark themselves as AFK, automatically toggles AFK after inactivity, and can optionally kick long-idle players.

# Commands
----
| Command | Description | Permission |
|---------|-------------|------------|
| /afk | Toggle your AFK status | zephyr.afk |
| /afk <player> | Toggle another player’s AFK status | zephyr.afk.other |
| (auto AFK/kick bypass) | Prevent automatic AFK/kick handling | zephyr.afk.bypass |

# Config
----
The config can be found at `plugins/Zephyr/Modules/AFK.yml`.

## AFKTimeout
Seconds of inactivity before a player is marked AFK automatically. Set to 0 to disable auto-AFK. Players with `zephyr.afk.bypass` are ignored.

## KickTimeout
Seconds after being AFK before a player is kicked. Set to 0 to disable. Players with `zephyr.afk.bypass` are ignored.

## Invincibile
If true, AFK players cannot take damage.

## Immovable
If true, AFK players cannot be pushed/moved by others.

## UseCommands
If false, attempting any command while AFK will remove AFK status; if true, AFK players may run commands without cancelling AFK.

## Modules/AFK.yml
----
```yaml
AFKTimeout: 300
KickTimeout: 900

Invincibile: true
Immovable: true
UseCommands: false
```