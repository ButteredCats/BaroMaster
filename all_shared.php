<?php

/* I try to put sources that helped in spots where they were useful but these were either useful in many spots or I lost their place.
Extra sources that helped:
https://sqldocs.org/sqlite-database/php-sqlite/
https://www.php.net/manual/en/reserved.constants.php
https://www.php.net/manual/en/security.database.sql-injection.php
https://www.php.net/manual/en/pdo.prepared-statements.php
https://www.php.net/manual/en/pdostatement.execute.php
https://www.php.net/manual/en/function.filter-var */

/* Some important points for the database:

PRIMARY KEY("ip","port") means that every IP and port pair must be unique.
If they weren't then that'd imply multiple servers are bound to the same port on one host and you'd end up with two of the exact same servers but potentially showing different info.

STRICT  makes sure nothing other than the specified types of info (TEXT and INTEGER) can make into their respective columns.
This is to protect against people adding text into INTEGER columns to potentially be used for XSS attacks.

INTEGER columns that come from user input need to be limited in their range.
Otherwise if the user puts in too big of a number database will store it in scientific notation and it'll display incorrectly ingame. */

// Open/create database
$db = new PDO('sqlite:serverlist.db');

// If the servers table doesn't exist then create it.
$query = 'CREATE TABLE IF NOT EXISTS "servers" (
			"ip"				TEXT NOT NULL,
			"port"				INTEGER NOT NULL CHECK("port" BETWEEN 1 AND 65535),
			"name"				TEXT NOT NULL UNIQUE,
			"game_started"		INTEGER CHECK("game_started" IN (0, 1)),
			"current_players"	INTEGER CHECK("current_players" BETWEEN 0 AND 99),
			"max_players"		INTEGER CHECK("max_players" BETWEEN 1 AND 99),
			"password_required"	INTEGER CHECK("password_required" IN (0, 1)),
			"last_update_time"	INTEGER NOT NULL,
			PRIMARY KEY("ip","port")
		) STRICT';

// Execute the CREATE TABLE IF NOT EXISTS query
$db->exec($query);


// Remove server based on specified IP and port
function removeserver($ip, $port)
{
	// Access global $db variable
	global $db;

	// Prepare statement
	$stmt = $db->prepare("DELETE FROM servers WHERE ip = :ip AND port = :port");

	// Execute DELETE
	$stmt->execute(array('ip'=>$ip, 'port'=>$port));
}

// Check last update time of all servers and remove any that haven't been added or updated in the last 45 seconds. Happens when a server doesn't cleanly quit.
function delete_old_servers()
{
	// Access global $db variable
	global $db;

	// Select ip, port, and last_update_time from all servers in database https://www.phptutorial.net/php-pdo/php-fetchall/
	$stmt = $db->prepare('SELECT ip, port, last_update_time FROM servers');
	$stmt->execute();
	$servers = $stmt->fetchAll();

	/* For each Barotrauma server (each row in table) check if the server was last updated more than 45 seconds ago.
	If it was then delete it and restart the foreach loop with the next row */
	foreach ($servers as $row)
	{
		// If the last time this Barotrauma server was updated was more than 45 seconds ago delete it and restart the foreach loop with the next row
		if (time() - ($row['last_update_time']) > 45)
		{
			removeserver($row['ip'], $row['port']);
			continue;
		}
	}
}
