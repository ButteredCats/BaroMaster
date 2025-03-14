<?php

// Shared code
require 'all_shared.php';


// https://stackoverflow.com/a/37213574
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

// If protocol is https and port is 443 OR protocol is http and port is 80 then don't specify the port, otherwise use the server port
if ( ($protocol == 'https://' && $_SERVER['SERVER_PORT'] == 443) || ($protocol == 'http://' && $_SERVER['SERVER_PORT'] == 80) ) {
  $port = "";
} else {
  $port = ':'.$_SERVER['SERVER_PORT'];
}

// Set the server URL
$server_url =  $protocol.$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];


// Before going through servers delete the old ones
delete_old_servers();

// Initialize current_servers and current_players
$current_servers = 0;
$current_players = 0;

// Select ip, port, and last_update_time from all servers in database https://www.phptutorial.net/php-pdo/php-fetchall/
$stmt = $db->prepare('SELECT current_players FROM servers');
$stmt->execute();
$servers = $stmt->fetchAll();

// For each server we process increment current_servers and add the server's current_players to the current_players variable
foreach ($servers as $row)
{
    $current_servers++;
    $current_players += $row['current_players'];
}

?>

<!DOCTYPE html>
<html lang="en-US">
  <head>
    <title>BaroMaster</title>
    <meta name="description" content="A Barotrauma Legacy Master Server replacement.">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Discord embeds https://stackoverflow.com/questions/59335731/how-to-create-own-embed-site-for-discord -->
    <meta content="BaroMaster" property="og:title" />
    <meta content="A Barotrauma Legacy Master Server replacement." property="og:description" />
    <meta content="<?=$server_url?>" property="og:url" />

    <!-- Preload our stylesheet -->
    <link rel="preload" href="style.css?v=1.0.1" as="style">

    <!-- Our stylesheet -->
    <link rel="stylesheet" href="style.css?v=1.0.1">
  </head>

  <body>
    <main>
      <h1>Barotrauma Legacy Master Server</h1>
      <div><p>The master server controls the in game server list, without it you need an outside way of discovering servers to know the IP and the port to join. To use this master server replacement and discover servers through the game, edit <strong>config.xml</strong> inside of your Barotrauma Legacy folder and change the value of "masterserverurl" so that it looks like "<strong>masterserverurl=<?=$server_url?></strong>"</p></div>
      <div><p>There are currently <?=$current_servers?> online servers that contain a total of <?=$current_players?> players.</p></div>
      <div><p>This master server's source code can be found at <a href="https://github.com/ButteredCats/BaroMaster" target="_blank">https://github.com/ButteredCats/BaroMaster</a></p></div>
    </main>
  </body>
</html>
