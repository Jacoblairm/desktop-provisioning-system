<?php

include "../header.php";
if($_SESSION['username']!= "admin" && isset($_POST['labname']))
{
	header("Location: ../index.php");
}

$labname = $_POST['labname'];

$labname = str_replace(" ", "_", preg_replace("/[^ \w]+/", "", $labname));

$labinfo = $_POST['lab_info'];
$operating_systems = array();
$vms = 4;
parse_str(parse_url($_SERVER['HTTP_REFERER'])['query']);

for($i = 0; $i<$vms; $i++)
{
	$operating_systems[$i] = $_POST['os_'.$i];
}


chdir("../");
try 
{  		
	$statement = $db->prepare("insert into labs (labname, lab_info) values (:labname, :labinfo)");
	$statement->bindValue(':labname', $labname);
	$statement->bindValue(':labinfo', $labinfo);
	$statement->execute();
	
} 
catch(PDOException $e) 
{
	echo $e->getMessage();
}

$id = $db->lastInsertID();


try 
{  			
	for($i = 0; $i<$vms; $i++)
	{
		$statement = $db->prepare("insert into labs_os (labid, os_name) values (:labid, :os_name)");
		$statement->bindValue(':labid', $id);
		$statement->bindValue(':os_name', $operating_systems[$i]);
		$statement->execute();
	}
	
} 
catch(PDOException $e) 
{
	echo $e->getMessage();
}


chdir("admin");

header("Location: index.php?action=labs&error=0");




?>