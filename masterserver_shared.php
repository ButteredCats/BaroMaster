<?php

// Use parse_str() to put the query string parameters into the $params array https://www.geeksforgeeks.org/how-to-get-parameters-from-a-url-string-in-php/
parse_str($_SERVER['QUERY_STRING'], $params);

// Set IP based on remote IP address
$ip = $_SERVER['REMOTE_ADDR'];

// Set password_required from GET query
$action = $params['action'];

?>
