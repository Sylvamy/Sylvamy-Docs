The Teleport module contains various useful teleportation based commands and features.

# Commands
----
| Command | Description | Permission |
|---------|-------------|------------|
| /tp <player> [player2] | Teleport yourself to <player>, or teleport <player> to <player2> if both are provided | zephyr.command.tp |
| /tphere <player> | Teleport the target player to your location | zephyr.command.tphere |
| /tpall [player] | Teleport everyone online to you, or to the specified player | zephyr.command.tpall |
| /tpa <player> | Request to teleport to the target player | zephyr.command.tpa |
| /tpahere <player> | Request the target player to teleport to you | zephyr.command.tpahere |
| /tpaccept | Accept the latest pending TPA/TPAHere request | zephyr.command.tpa |
| /tpyes | Alias of /tpaccept | zephyr.command.tpa |
| /tpdeny | Deny the latest pending TPA/TPAHere request | zephyr.command.tpa |
| /tpno | Alias of /tpdeny | zephyr.command.tpa |
| /back | Teleport to your last saved location (recent teleports or death) | zephyr.command.back |
| /tppos <x> <y> <z> [world] [player] | Teleport to coordinates; optional world/player lets you teleport another player | zephyr.command.tppos |


# Config
----
The config can be found at `plugins/Zephyr/Modules/Teleport.yml`.

## TPA

### Delay
The delay in seconds before the player gets teleported after a request is accepted.

### Timeout
The time in seconds before a sent request will be automatically denied.

### Cooldown
The time in seconds a player must wait before sending another TPA request.

### MoveCancelsTeleport
If true, and the user moves before the TeleportDelay is over, the teleportation will be cancelled.

## Back

### History
How many previous locations should the server keep track of. Recommended not to set this too high.

### Delay
The delay in seconds before the player gets teleported.

### Cooldown
How long players must wait before they teleport.

### MoveCancelsTeleport
If true, and the user moves before the TeleportDelay is over, the teleportation will be cancelled.

## Modules/Teleport.yml
----
```yaml
TPA:
  Delay: 3
  Timeout: 120
  Cooldown: 0
  MoveCancelsTeleport: true

Back:
  History: 10
  Delay: 3
  Cooldown: 120
  MoveCancelsTeleport: true

```