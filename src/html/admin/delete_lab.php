<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


include "../header.php";

if($_SESSION['username'] != "admin")
{
	header("Location: ../index.php");
}

$labid = $_GET['lab'];

chdir("../");



try 
{  		
	$statement = $db->query("DELETE FROM labs WHERE labid='".$labid."'");

}
catch(PDOException $e) 
{
	echo $e->getMessage();
}

chdir("admin");


header("Location: index.php?action=labs");





function rrmdir($dir)
{ 
echo $dir;
   if (is_dir($dir))
   {
	   
	   $objects = scandir($dir);
	   foreach ($objects as $object)
	   {
		   if ($object != "." && $object != "..") 
		   {
			   if (is_dir($dir."/".$object))
			   {
				   rrmdir($dir."/".$object);
			   }
			   else
			   {
				   unlink($dir."/".$object); 
			   }
			} 
		}
		rmdir($dir);
	} 
}
 
?>