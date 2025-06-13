# Barotrauma Legacy Master Server
Quick links:
- [Overview](#overview)
- [Here to use a new Barotrauma Legacy master server?](#here-to-use-a-new-barotrauma-legacy-master-server)
- [Running](#running)
- [Master Server operation documentation](#master-server-operation-documentation)
- [Differences](#differences)

## Overview
This project aims to be a [Barotrauma Legacy](https://github.com/FakeFishGames/Barotrauma/tree/legacy) master server replacement.

The master server controls the in game server list, without it you need an outside way of discovering servers to know the IP and the port to join.

While it isn't 100% faithful to the operation of the original master server I believe the changes are for the better. See [Differences](#differences) for more info.

## Here to use a new Barotrauma Legacy master server?
Edit `config.xml` inside of your Barotrauma Legacy folder and change the value of `masterserverurl` so that it looks like `masterserverurl=https://example.com` where https://example.com is your master server of choice.

I run a copy of this master server at https://baromaster.catsarch.com and plan to keep it running for the forseeable future as I already have plenty of web based things being hosted that aren't going away anytime soon.

## Running
All you need to run this is a PHP capable webserver that can work with PDO sqlite databases. Using external databases is currently not supported but pull requests are welcome.

As soon as it's being served by something capable of processing PHP and handling PDO sqlite databases you're in business!
When either masterserver2.php or masterserver3.php is accessed they'll automatically create a database with all of the necessary columns.

## Master Server operation and interaction documentation
To see my findings on how the master server is expected to operate and interact with clients and servers check out the [Barotrauma Legacy Master Server Interaction Documentation file](Barotrauma%20Legacy%20Master%20Server%20Interaction%20Documentation.md) file.

It explains what I've found the master server's responibilties to be, how it should deal with them, and how this master server implementation handles them.

## Differences

#### More players
Because it appears to be possible to mod a server to allow more players without any client side modification, this master server implementation allows a max player count of up to 99 to be displayed in the server list.

#### Incompatibilities
In a couple of places this master server implementation differs from what the Barotrauma client and server allow you to do in the name of security and preventing impersonation of existing servers.

1. **Server names must be unique.** This is to prevent easy impersonation of another server by using the same name.

2. **Leading and trailing spaces are trimmed from the name in the server list.** This is to try and enforce keeping server names unique and prevent easily bypassing it by adding a space at the beginning or end of a name.

3. **All space server names are not allowed.** This is to prevent servers with seemingly blank names from appearing in the server list.

4. **Updates will not be served via the launcher (by default).** This is to avoid licensing problems for redistributing the game. It also avoids security issues because you'd be in control of both the files being served and their expected checksums. This is just default behavior, but anyone could easily edit `versioninfo.xml` and change this. If you choose another master server please think twice before clicking that "Download" button in the launcher.

5. **The launcher will not show the real changelog if the game is out of date.** This is because the included `version.xml` file will instead show a message on how to update manually because the files aren't served.
