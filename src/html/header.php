<?php

if(session_id() == '' || !isset($_SESSION)) 
{
    session_start();
}
if ( isset( $_SESSION['username'] ) ) {
	
	echo "<a href='index.php'>Home</a>                             ";
    echo "You are logged in as ". $_SESSION['username'];
	echo " | <a href='logout.php'>Logout</a><br>";
	
} else {
    // Redirect them to the login page
    header("Location: index.php");
}
require("settings.php");
require("includes/database_connection.php");

?>