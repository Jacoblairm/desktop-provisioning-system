<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../header.php";
if($_SESSION['username']!= "admin")
{
	header("Location: ../index.php");
}

$password = $_POST['password'];
$user = $_POST['username'];
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
chdir("../");


if (!is_dir('users/'.$user)) {
    mkdir('users/'.$user, 0777, true);
	
	try 
	{  		
		$statement = $db->prepare("insert into users (username, password) values (:username, :password)");
		$statement->bindValue(':username', $user);
		$statement->bindValue(':password', $hashed_password );
		$statement->execute();
		
	} 
	catch(PDOException $e) 
	{
		echo $e->getMessage();
	}
}
chdir("admin");
header("Location: index.php?action=users");




?>