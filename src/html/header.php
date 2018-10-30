<style>
body {
  font-family: Arial, Helvetica, sans-serif;
}
</style>
<?php

if(session_id() == '' || !isset($_SESSION)) 
{
    session_start();
}
if ( isset( $_SESSION['username'] ) ) {
	
	$name = $_SESSION['username'] == "admin" ? "<a href='/admin/index.php'>admin</a>" : $_SESSION['username'];
	
	echo "<a href='/index.php'>Home</a>                             ";
    echo "You are logged in as ". $name;
	echo " | <a href='/logout.php'>Logout</a><br>";
	
} else {
    // Redirect them to the login page
    header("Location: index.php");
}
require("settings.php");
require("includes/database_connection.php");

?>