# Barotrauma Legacy Master Server and it's interaction with Barotrauma clients and dedicated servers

Quick links:
- [Master server responsibilities](#master-server-responsibilities)
- [Launcher](#launcher)
	- [Detecting updates](#detecting-updates)
	- [Serving updates](#serving-updates)
	- [Launcher crashes](#launcher-crashes)
- [Server list](#server-list)
	- [Displaying server list to client](#displaying-server-list-to-client)
	- [Adding servers to the list](#adding-servers-to-the-list)
	- [Updating server info](#updating-server-info)
	- [Removing servers from the list](#removing-servers-from-the-list)

## Master server responsibilities
The master server is responsible for:
- Notifying the launcher of game updates
- Serving game updates
- Sending the server list to clients
- Adding servers to the server list
- Updating server info in the server list
- Removing servers from the server list

## Launcher
Relevant source file: `Barotrauma/Launcher/LauncherMain.cs`

#### Detecting updates
If launcher is set to automatically check for updates it sends an HTTP GET request for `/versioninfo.xml`
It expects a structure like the following:
```
<versioninfo latestversion="0.8.2.3" latestversionfolder="http://www.undertowgames.com/baromaster/Barotrauma v0.8.2.3/" latestversionfilelist="http://www.undertowgames.com/baromaster/filelist.xml" updaterversion="1.1">
	<patchnotes>
		<patch version="0.8.2.3">Text</patch>
		<patch version="0.8.2.2">Text</patch>
		...
		<patch version="0.2.1.0">Text</patch>
		<patch version="0.2.0.0">Text</patch>
	</patchnotes>
</versioninfo>
```

At the time of writing, Undertow still serves a copy of versioninfo.xml on their website at https://www.undertowgames.com/baromaster/versioninfo.xml.

#### Serving updates
If the launcher finds a newer version via the versioninfo file then a changelog and a download button appear in the launcher.
Pressing the download button makes an HTTP GET to the URL specified in "latestversionfilelist" and the launcher expects a format like the following:
```
<filelist>
	<file path="Barotrauma.exe" md5="AB7995B782A90381130EE6773A735068"/>
	<file path="Barotrauma.pdb" md5="BADE54218FBCEE2470A90977009BFE21"/>
	...
	<file path="x64\sqlite3.dll" md5="616E115A29CA6F1C9EB49A481A23D204"/>
	<file path="x86\sqlite3.dll" md5="4F4D0EC567743AD4AE97260BC7959D7E"/>
</filelist>
```

This example omits a lot of lines for brevity, but there's a line for each file the client needs.

When the download button is pressed the launcher begins to try and download all the files listed inside of the "latestversionfilelist" file one at a time.

If the "latestversionfolder" and "latestversionfilelist" attributes are missing then the download button does nothing.

At the time of writing, Undertow still serves a copy of this file list on their website at https://www.undertowgames.com/baromaster/filelist.xml.

Undertow no longer hosts a copy of the latestversionfolder, and it probably shouldn't be hosted anywhere to avoid licensing and security issues.
Therefore this implementation of the master server simply opts to omit those options and display a warning in place of the changelog with instructions on how to update if the game it's older than 0.8.2.3, the last version of legacy Barotrauma.

#### Launcher crashes
The launcher can crash when versioninfo.xml is slightly malformed.

These are the two circumstances I ran into that caused a crash:
- If any `<patch>` block is missing a version number
- If "latestversion" isn't specified

I didn't extensively test what can cause crashes to happen, so it's likely that there's more.

## Server list
Relevant source files:
- `Barotrauma/BarotraumaClient/Source/Screens/ServerListScreen.cs`
- `Barotrauma/BarotraumaShared/Source/Networking/GameServer.cs`

It's important to note that the IP and port of the server need to be unique and that this is enforced in the database.
Otherwise you'd be implying you have multiple servers bound to the same port on one host which isn't possible.

Although I don't believe this was part of the original master server, server names must also be unique.
As part of this goal, leading spaces in the server name are trimmed so that you can't impersonate another server by adding a space before the name.

#### Displaying server list to client
When the client goes into the server list it sends an HTTP GET request that looks like this: `/masterserver2.php?gamename=barotrauma&action=listservers`

It expects a reply that details the following server info:
- IP
- Port
- Name
- If the round is started
- Current player count
- Max player count
- If a password is required

It expects an HTTP 200 response code and a response body formatted like the following:
```
IP|Port|Name|If the round is started|Current player count|Max player count|If a password is required
IP|Port|Name|If the round is started|Current player count|Max player count|If a password is required
...
IP|Port|Name|If the round is started|Current player count|Max player count|If a password is required
IP|Port|Name|If the round is started|Current player count|Max player count|If a password is required
```
Where each line is a seperate server.

The client will display the server list in the same order they appear in the response body, meaning they effectively end up sorted by creation time.

In this master server implementation the gamename parameter is ignored. I'm unsure of it's original usage.

For the server to actually be joinable via the list only the IP and port are actually required, the rest can be missing and you can still join the server.
This would result in missing info however, but would certainly enable the main functionality of the server list.

If current player count or max player count aren't specified then they'll appear as 0.

If the round is started and if a password is required are "loosely" boolean for lack of a better term.
While other numbers can be put in this place they only appear as yes in game if it's a 1, all other numbers are a no and the game itself will only ever send 0 or 1 for this.
However, this could be ommitted from the server list unless it's a 1 and still properly display in the client's server list.

The client also doesn't care if there's a trailing seperator at the end of a server's info before the newline.

#### Adding servers to the list
When the server is made it makes an HTTP GET request like this: `/masterserver3.php?action=addserver&servername=test&serverport=14242&currplayers=0&maxplayers=8&password=0&version=0.8.2.3&contentpackage=Vanilla%200.8`

This request details:
- The server's IP (the requester's IP)
- That this needs to be added to the list (action=addserver)
- The server's name (servername=test)
- The server's port (serverport=14242)
- The current number of players on the server (currplayers=0, this is always 0 on server creation)
- The maximum number of players allowed (maxplayers=8)
- The version of the game the server is running (version=0.8.2.3)
- The content package the server is using (contentpackage=Vanilla%200.8)

"version" and "contentpackage" are never requested by the client via the master server and most likely were only included for analytic purposes. Therefore this master server implementation ignores them.

For addserver actions it's possible to have a proper error message sent to console in the event something is wrong by responding with a 200 status code and a body containing the message to be sent.
Any other response code will result in the game showing an error that contains the response code and it's description from here https://learn.microsoft.com/en-us/windows/win32/winhttp/http-status-codes.

The Barotrauma client UI only allows as many characters as fit in the name box when setting up a server. However the serversettings.xml file easily allows you to set more. Therefore this master server implementation will try and mimic what the modern version of the game does and truncate names that are too long. I'm basing this value off of filling the entire in game name column with all W's, which will fit 21. After this it'll be truncated with '...'.
The client UI allows a name that's all spaces, but in the interest of user experience when viewing the server list this master server won't allow an all spaces name and will return an error to the server when they try and do this.
We'll also strip any leading or trailing spaces of the server name to prevent putting weird looking names or impersonating another server (since nothing seperates them at a glance except name) by adding an unseen space.

While the client UI only allows a max player count between 1 and 16 it appears to be possible to mod a dedicated server to allow more players without needing any client side modifications.
Therefore this master server implentation will allow any max players number from 1 to 99.
99 is an arbitrarily chosen number to prevent people from entering huge numbers.
Too high and the database will start storing it in exponentional format, which leads to the client displaying a max player number of 0.
Plus the numbers stop properly aligning with the players column in the Barotrauma client server list if the number is too large.

#### Updating server info
The server will send an HTTP GET request to the master server every 30 seconds to do a refresh of server info that looks like this: `/masterserver3.php?action=refreshserver&serverport=14242&gamestarted=0&currplayers=4&maxplayers=8`

This request details:
- The server's IP (the requester's IP)
- The server's info needs to be refreshed (action=refreshserver)
- The server's port (serverport=14242)
- Whether the game is currently started or not (gamestarted=0)
- The current number of players on the server (currplayers=4)
- The maximum number of players allowed (maxplayers=8)

It'll rerun the full request when a player leaves the server, but not when a player joins.

The maximum number of players can't change once the server is created so this is completely ignored in this implementation of the master server.

If the server doesn't exist in the master server's list the game expects the server to return a body of "Error: server not found" and it'll then attempt to reregister itself to the master server.
The server doesn't care what response code this is returned with.

#### Removing servers from the list
When a server is properly shut down it makes a request like this: `/masterserver2.php?action=removeserver&serverport=14242`

This request details:
- The server's IP (the requester's IP)
- That the server wants to be removed (action=removeserver)
- The server's port (serverport=14242)

We use that to immediately remove that server from the list.

The server doesn't care about getting any sort of response back.

In the event a server doesn't cleanly shut down this implentation of the master server uses a UNIX timestamp in the database with the last time that server's info was updated.
If it's more than 45 seconds ago we delete it to prevent servers staying forever and preventing others with the same IP and port from getting made.
