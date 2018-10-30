<?php
chdir(dirname(__FILE__));

require("../settings.php");
require("database_connection.php");
require("start_libvirt.php");

$active_sessions = $db->query('SELECT * FROM users_labs where active=1');

foreach($active_sessions as $session)
{
	$elapsed_time = time() - strtotime($session['lastpolled']);
	if($elapsed_time > $vm_inactive_session_length)
	{
		stop_machines($session['username'],$session['labid']);
	}
	echo $elapsed_time;
}


function stop_machines($userid, $labid)
{
	global $lv, $db;
	$vms = $db->query('SELECT * FROM lab_vmids where username="'.$userid.'" AND labid="'.$labid.'"');
	foreach($vms as $vm)
	{
		$domName = $lv->domain_get_name_by_uuid($vm['vm_id']);
		$lv->domain_destroy($domName);
	}
	
	try 
	{  		
		$st = $db->prepare("UPDATE users_labs SET active = :active WHERE username = :username AND labid = :labid");
		$st->bindValue(':active', 0);
		$st->bindValue(':username', $userid);
		$st->bindValue(':labid', $labid);
		$st->execute();
	} 
	catch(PDOException $e) 
	{
		echo $e->getMessage();
		exit("<br>UPDATE users_labs SET active = 0 WHERE username = $userid AND labid = $labid");
	}
	$lv->set_network_active($userid, false);
	$net = $lv->get_network_res($userid);
	libvirt_network_undefine($net);
}
?>