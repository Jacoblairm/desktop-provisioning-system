<?php
require("settings.php");
require("includes/database_connection.php");

session_start();
$return = 1;

if ( ! empty( $_POST ) ) {
    if ( isset( $_POST['username'] ) && isset( $_POST['password'] ) ) {
        // Getting submitted user data from database
		try 
		{  
			$results = $db->query('SELECT * FROM users WHERE username="'.$_POST['username'].'"');
			foreach ($results as $result)
			{
				$password = $result['password'];
				$username = $result['username'];
			}
			if ( password_verify( $_POST['password'], $password ) ) 
			{
				$_SESSION['username'] = $username;
				$return = 0;
				header("Location: index.php?e=0");
			}
		} 
		catch(PDOException $e) 
		{
			echo $e->getMessage();
		}
		

    }
}

	header("Location: index.php?e=".$return);




?>