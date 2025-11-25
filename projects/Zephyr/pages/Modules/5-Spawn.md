The Spawns module allows controlling how and where players spawn, and any messages sent when players join.

# Commands
----
| Command  | Description | Permission |
|----------|-------------|------------|
| /setspawn [name] | Sets a spawnpoint, default name is "spawn" | zephyr.spawn.set |
| /spawn [name] | Teleport to a spawn, default name is "spawn" | zephyr.spawn.teleport.[name] |
| /delspawn [name] | Delete a spawn, default name is "spawn" | zephyr.spawn.delete |
[name] is optional in all commands, omitting it will simply use "spawn"

# Config
----
The config can be found at `plugins/Zephyr/Modules/Spawn.yml`.

## FirstSpawn
Which spawn players will teleport to when they join the server for the first time.

## TeleportDelay
A delay in seconds before the user gets teleported. Setting this to 0 will teleport the user instantly.

## MoveCancelsTeleport
If true, and the user moves before the TeleportDelay is over, the teleportation will be cancelled.

## TeleportMount

### Enabled
If enabled, the entity the player is riding will teleport with them.

### RequireOwner
If enabled, only entities the player owns will teleport with them, this can prevent people stealing other players horses and ghasts.

### Boats
If set to false, boats won't be teleported even with TeleportMount enabled.

### Minecarts
If set to false, minecarts won't be teleported even with TeleportMount enabled.

## SpawnOnJoin

### Enabled
If enabled, any players joining the server will be sent to the specified spawn point.

### Spawn
The name of the spawn point ot send players to when they join.

## JoinMessages:

### Join
If a custom join message should be sent when players log in, configured in [Messages.yml](/project/Zephyr/Messages)

### Leave
If a custom leave message should be sent when players log out, configured in [Messages.yml](/project/Zephyr/Messages)

### First Join
If a custom message should be sent when players join for the first time, configured in [Messages.yml](/project/Zephyr/Messages)

## FirstJoinItems

### Enabled
If players should be given the items from the configured list.

### Items
A list of items to give the player, in the format of <item> <amount> <slot>.
If you do not specify an amount, it will default to just 1 item.
If you do not specify a slot, or the player already has an item in the slot, it will default to the first available slot in the players inventory.

Modules/Spawn.yml
```yaml
FirstSpawn: spawn
TeleportDelay: 3
MoveCancelsTeleport: true

TeleportMount:
  Enabled: false
  RequireOwner: true
  Boats: false
  Minecarts: false

SpawnOnJoin:
  Enabled: false
  Spawn: spawn

JoinMessages:
  Join: true
  Leave: true
  FirstJoin: true

FirstJoinItems:
  Enabled: false
  Items:
  - bread 32 1
  - torch 16 2
  - oak_log 64
  - iron_sword

```