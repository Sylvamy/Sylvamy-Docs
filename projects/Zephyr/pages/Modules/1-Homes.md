The Homes module allows players to set locations as homes they can teleport back to.

# Commands
----
| Command  | Description | Permission |
|----------|-------------|------------|
| /sethome [name] | Sets a home, default name is "home" | zephyr.homes.set |
| /home [name] | Teleport to your home, default name is "home" | zephyr.homes.use |
| /delhome [name] | Delete your home, default name is "home" | zephyr.homes.delete |

# Config
----
The config can be found at `plugins/Zephyr/Modules/Homes.yml`.

## MaxHomes
The maximum amount of homes a player can set. Setting this to 0 will allow unlimited homes.
This can also be controlled with a permission `zephyr.homes.max.<number>` alternatively `zephyr.homes.max.unlimited`

## TeleportDelay
A delay in seconds before the user gets teleported. Setting this to 0 will teleport the user instantly.

## ConfirmOverwrite
Should users be required to confirm if they are trying to set a home with the same name as one that already exists for them.

## ConfirmDelete
Should users be required to confirm when using `/delhome`.

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


## Modules/Homes.yml
----
```yaml
MaxHomes: 5
TeleportDelay: 3
ConfirmOverwrite: true
ConfirmDelete: true
MoveCancelsTeleport: true

TeleportMount:
  Enabled: true
  RequireOwner: true
  Boats: false
  Minecarts: false
```