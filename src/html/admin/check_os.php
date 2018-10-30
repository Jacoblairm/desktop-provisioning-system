<?php

if($_SESSION['username']!= "admin")
{
	header("Location: ../index.php");
}


if($files = scandir($base_image_location))
{

	foreach($files as $file)
	{
		if($file != "." && $file != ".." && pathinfo($file, PATHINFO_EXTENSION)=="qcow2")
		{
			$exists = false;
			$oss = $db->query('SELECT * FROM operating_systems');
			foreach ($oss as $os)
			{
				if($os['file_location'] == $base_image_location.$file)
				{
					$exists = true;
				}
			}
			if(!$exists)
			{
				try 
				{  		
					$statement = $db->prepare("INSERT INTO operating_systems (name, file_location) VALUES (:name, :file_location)");
					$statement->bindValue(':name', pathinfo($file, PATHINFO_FILENAME));
					$statement->bindValue(':file_location', $base_image_location.$file);
					$statement->execute();
					
				} 
				catch(PDOException $e) 
				{
					echo $e->getMessage();
				}
			}
		}
	}
	
	$oss = $db->query('SELECT * FROM operating_systems'); //check if database entry is still valid
	foreach ($oss as $os)
	{
		if(!file_exists($os['file_location']))
		{
			try 
			{  		
				$st = $db->prepare("DELETE FROM operating_systems WHERE file_location = :fileloc");
				$st->bindValue(':fileloc', $os['file_location']);
				$st->execute();
			} 
			catch(PDOException $e) 
			{
				echo $e->getMessage();
			}
		}
	}
}



?>