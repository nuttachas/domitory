<?php

    $host = "demosql-7ea2ab1fc8-wpdbserver.mysql.database.azure.com";
	$username = "kcokhgqjfd";
	$password = "nu64311591.";
	$database = "nut";

// Create Connection
$con = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
