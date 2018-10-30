<?php

echo '<meta http-equiv="refresh" content="30">';

require("../settings.php");
require("database_connection.php");
require("start_libvirt.php");

$labid = $_GET['labid'];
$userid = $_GET['user'];

try 
{  		
	$st = $db->prepare("UPDATE users_labs SET lastpolled = datetime('now') WHERE username = :username AND labid = :labid");
	$st->bindValue(':username', $userid);
	$st->bindValue(':labid', $labid);
	$st->execute();
} 
catch(PDOException $e) 
{
	echo $e->getMessage();
}

$doms = $lv->get_domains();

$networks = $lv->get_networks(VIR_NETWORKS_ALL);

$network_text = "";
for ($i = 0; $i < sizeof($networks); $i++)
{
	$network = $lv->get_network_information($networks[$i]);
	if($network['name']==$userid)
	{
		$network_text = "Network: ".$network['ip_range'];
	}
}

if($network_text=="")
{
	exit();
}
else
{
	echo $network_text;
}

$vms = $db->query('SELECT * FROM lab_vmids where username="'.$userid.'" AND labid="'.$labid.'"');
$vm_ids = array();
foreach($vms as $vm)
{
	$vm_ids[] = $vm; //create a separate array because using $vms pops the value after each use
}
	
foreach($doms as $name)
{
	$dom = $lv->get_domain_object($name);
	$uuid = libvirt_domain_get_uuid_string($dom);
	for($i = 0; $i < count($vm_ids); $i++)
	{
		if($uuid == $vm_ids[$i]['vm_id'])
		{
			$mac = libvirt_domain_get_interface_devices_mac($dom)[0];
			$ip = shell_exec('./getiface.sh "'.$mac.'"');
			echo "<li>$name | $ip</li>";
		}
	}
}

?>