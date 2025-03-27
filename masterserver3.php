<?php

// Shared code
require 'all_shared.php';
require 'masterserver_shared.php';

// Both updating and refreshing server info needs current_players, current_time, and port. ip comes from masterserver_shared.php

// Set current_players from GET query
$current_players = $params['currplayers'];

// Get current time
$current_time = time();

// Set port from GET query
$port = $params['serverport'];


function check_current_players_and_port()
{
	global $current_players, $port;
	
	// Check if current_players is actually a number, and if it is check if it's in our range
	if (filter_var($current_players, FILTER_VALIDATE_INT, array("options" => array("min_range"=>0, "max_range"=>99))) === FALSE)
	{
		// Set response to generic server failure code if this is a server refresh because the refresh function needs a non 200 code to know something went wrong
		if ($action == 'refreshserver') { http_response_code(500); }

		// If this errors on an addserver action this will be sent to the console
		echo 'Invalid current player count "'.$current_players.'" it needs to be between 0 and 99';

		// Quit processing
		exit();
	}
	
	// Check if the port is actually a number, and if it is check if it's in our range
	if (filter_var($port, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "max_range"=>65535))) === FALSE)
	{
		// Send error message to Barotrauma console
		echo 'Invalid server port "'.$port.'" it needs to be between 1 and 65535';

		// Quit processing
		exit();
	}
}

// Handle potential database error messages
function handle_database_error_messages($error_message)
{
	// Grab global variables for the server settings
	global $ip, $port, $name, $game_started, $current_players, $max_players, $password_required;

	/* The database requires unique IP and port pair and has constraints on what numbers can be entered for certain entries.
	While these number limits should be handled before they hit the database we'll still deal with them here just in case.
	The database also requires that server names are unique, similar to the IP and port pair.
	This will echo which one we errored on back to the Barotrauma server/client so it'll show in the console and the user will know what went wrong.
	https://www.php.net/manual/en/function.str-contains.php */

	/* echos are a 200 response code, meaning those messages will show in the Barotrauma server/client console upon error.
	This is wrong from an HTTP standpoint but I'm doing it like this because it's a better user experience to get actual in game errors. */

	// https://stackoverflow.com/a/4175935
	$issue_map = array('UNIQUE constraint failed: servers.ip, servers.port'=>'Server with IP "'.$ip.'" and port "'.$port.'" already exists',
				'UNIQUE constraint failed: servers.name'=>'Server with the name "'.$name.'" already exists',
				'CHECK constraint failed: port'=>'Invalid server port "'.$port.'" it needs to be between 1 and 65535',
				'CHECK constraint failed: game_started'=>'Invalid game started state "'.$game_started.'" it needs to be either 0 or 1',
				'CHECK constraint failed: current_players'=>'Invalid current player count "'.$current_players.'" it needs to be between 0 and 99',
				'CHECK constraint failed: max_players'=>'Invalid max player number "'.$max_players.'" it needs to be between 1 and 99',
				'CHECK constraint failed: password_required'=>'Invalid password requirement flag "'.$password_required.'" it needs to be either 0 or 1');
	// Go over each issue in issue_map and if it's found in error_message print out that issue's specific error message
	foreach ($issue_map as $to_find => $specific_error_message)
	{
		if (str_contains($error_message, $to_find))
		{
			// Send error message to Barotrauma console
			echo $specific_error_message;
		}
	}
}

// Choose between addserver and refreshserver actions
switch ($action)
{
	// addserver action to add server to the list
	case 'addserver':
		// Remove all old servers before trying to add to list
		delete_old_servers();

		// Check that current players and server port values are valid
		check_current_players_and_port();

		/* These variables are only needed on server creation.
		trim() removes leading and trailing whitespace so you can't easily impersonate a server by using spaces to have a different name.
		Also lets us not allow all space names as checked slightly further down. */
		$name = trim($params['servername']); //

		/* htmlspecialchars() encodes &, ", ', <, and > to avoid XSS since these pages can easily be accessed in a web browser. If these stay encoded they show up weird in the server browser so we can't keep them like that.
		If htmlspecialchars() encoded any of those characters it will no longer match the original name variable, and we should inform the user that they entered invalid characters. */
		if ($name != htmlspecialchars($name)) {
			// Send error message to Barotrauma console
			echo 'Server name cannot include &, ", \', <, or >';
			exit();
		}

		/* Don't allow completely empty or all spaces names.
		The game already disallows empty names, but all spaces are technically allowed. We're blocking them in the interest of user experience when browsing the server list.
		The "Server name shouldn't be empty or all spaces" message will show up in the console. */
		if ($name == '') {
			// Send error message to Barotrauma console
			echo 'Server name cannot be blank or all spaces';
			exit();
		}

		// The server name can only be up to 14 characters, mb_strlen counts the amount of characters (not the amount of bytes, so multibyte characters are counted correctly)
		if (mb_strlen($name) > 14) {
			// Send error message to Barotrauma console
			echo 'Server name cannot be longer than 14 characters';
			exit();
		}


		// Set max_players from GET query
		$max_players = $params['maxplayers'];

		// Check if max_players is actually a number, and if it is check if it's in our range
		if (filter_var($max_players, FILTER_VALIDATE_INT, array("options" => array("min_range"=>1, "max_range"=>99))) === FALSE) {
			// Send error message to Barotrauma console
			echo 'Invalid max player number '.$max_players.' it needs to be between 1 and 99';
			exit();
		}

		// Set password_required from GET query
		$password_required = $params['password'];
		// Check that password_required is actually, and if it is check that it's either 0 or 1
		if (filter_var($password_required, FILTER_VALIDATE_INT, array("options" => array("min_range"=>0, "max_range"=>1))) === FALSE) {
			// Send error message to Barotrauma console
			echo "Invalid password requirement flag '.$password_required.' it needs to be either 0 or 1";
			exit();
		}


		// Prepare statement, game_started is always 0 on server creation
		$stmt = $db->prepare("INSERT INTO servers (ip, port, name, game_started, current_players, max_players, password_required, last_update_time) VALUES (:ip, :port, :name, 0, :current_players, :max_players, :password_required, :last_update_time)");

		// Execute database query, catching any errors
		try
		{
			$stmt->execute(array('ip'=>$ip, 'port'=>$port, 'name'=>$name, 'current_players'=>$current_players, 'max_players'=>$max_players, 'password_required'=>$password_required, 'last_update_time'=>$current_time));
		}
		catch (Exception $error_message)
		{
			// Send back a proper response for database errors if applicable
			handle_database_error_messages($error_message);
		}

		// Don't fall through to refreshserver
		break;
	// refreshserver action to update game started state and current player count
	case 'refreshserver':
		// Check that current players and server port values are valid
		check_current_players_and_port();

		// Only needed when refreshing info
		$game_started = $params['gamestarted'];
		// Check that game_started is actually a number, and if it is check that it's either 0 or 1
		if (filter_var($game_started, FILTER_VALIDATE_INT, array("options" => array("min_range"=>0, "max_range"=>1))) === FALSE) {
			// Set response to generic server failure code because the refresh function needs a non 200 code to know something went wrong
			http_response_code(500);
			// While this error won't be printed in the Barotrauma console, it's useful for debugging
			echo "Invalid game started state '.$game_started.' it needs to be either 0 or 1";
			exit();
		}

		// refreshserver updates gamestarted, currplayers, and maxplayers, but maxplayers can't change once the server is started so we ignore it.
		$stmt = $db->prepare("UPDATE servers SET game_started = :game_started, current_players = :current_players, last_update_time = :last_update_time WHERE ip = :ip AND port = :port");

		// Execute above database query, catching any errors
		try
		{
			$stmt->execute(array('ip'=>$ip, 'port'=>$port, 'game_started'=>$game_started, 'current_players'=>$current_players, 'last_update_time'=>$current_time));
		}
		catch (Exception $error_message)
		{
			// Set response to generic server failure code because the refresh function needs a non 200 code to know something went wrong
			http_response_code(500);

			// Send back a proper response for database errors if applicable
			handle_database_error_messages($error_message);
		}

		/* If the UPDATE updated 0 rows then send a body of "Error: server not found" which the Barotrauma server will recognize and then resend an addserver.
		https://riptutorial.com/php/example/8320/pdo--get-number-of-affected-rows-by-a-query */
		if ($stmt->rowCount() == 0) { echo 'Error: server not found'; }

		// Don't fall through to default action
		break;
	default:
		// Bad request
		http_response_code(400);
}

?>
