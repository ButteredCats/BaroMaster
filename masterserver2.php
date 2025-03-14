<?php

// Shared code
require 'all_shared.php';
require 'masterserver_shared.php';

// Choose between listservers and removeserver actions
switch ($action)
{
	// listservers action for server browser
	case 'listservers':
		// Remove all old servers before sending list
		delete_old_servers();

		// Select ip, port, name, game_started, current_players, max_players, and password_required from all servers in database https://www.phptutorial.net/php-pdo/php-fetchall/
		$stmt = $db->prepare('SELECT ip, port, name, game_started, current_players, max_players, password_required FROM servers');
		$stmt->execute();
		$servers = $stmt->fetchAll();

		// For each Barotrauma server (each row in table) print out all the required info for the client server list and then add a newline
		foreach ($servers as $row)
		{
			// IP|Port|Name|If the round is started|Current player count|Max player count|If a password is required
			echo $row['ip'].'|'.$row['port'].'|'.$row['name'].'|'.$row['game_started'].'|'.$row['current_players'].'|'.$row['max_players'].'|'.$row['password_required'].PHP_EOL;
		}

		// Don't fall through to removeserver
		break;
	// removeserver action for deleting a server from the database
	case 'removeserver':
		// Set port from GET query
		$port = $params['serverport'];

		// Remove the server. ip comes from masterserver_shared.php
		removeserver($ip, $port);

		// Don't fall through to default action
		break;
	default:
		// Bad request
		http_response_code(400);
}

?>
