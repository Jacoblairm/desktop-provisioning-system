<?php

	session_start();
	if ( isset( $_SESSION['username'] ) ) 
	{
		
		require("header.php");
		require ('includes/start_libvirt.php');
	
		$username = $_SESSION['username'];
		$doms = $lv->get_domains();
		
		
		echo "<h1>Select Laboratory</h1>";
		
		$labs_in_progress = $db->query('SELECT * FROM users_labs where username="'.$username.'"');
		$available_labs = $db->query('SELECT * FROM labs');
		echo "<table>";
		
		foreach($available_labs as $lab)
		{
			$active = false;
			$in_progress = false;
			
			/*foreach($labs_in_progress as $lab_in_prog)
			{
				if($lab_in_prog['labid'] == $lab['labid'])
				{
					$in_progress = true;
					$vms_id = $db->query('SELECT * FROM lab_vmids where username="'.$username.'"');
					foreach($vms_id as $vm_id)
					{	
						foreach($doms as $name)
						{
							$dom = $lv->get_domain_object($name);
							if($vm_id['vmid'] == $lv->libvirt_domain_get_uuid_string($dom))
							{
								if($lv->domain_is_active($dom))
								{
									$active = true;
								}
							}
						}
					}
				}
			}*/
			
			echo "<tr><td>#".$lab['labid']." - <a href='laboratory.php?id=" . $lab['labid'] . "'>".$lab['labname']."</a></td></tr>";
		
		}

		echo "</table>";

	}
	else
	{
	   echo '<form action="login.php" method="post">
		<input type="text" name="username" placeholder="Enter your username" required>
		<input type="password" name="password" placeholder="Enter your password" required>
		<input type="submit" value="Submit">
		</form>';
		if($_GET['e'] == 1)
		{
			echo '<font color="red">Incorrect username/password.</font>';
		}
	}
?>

