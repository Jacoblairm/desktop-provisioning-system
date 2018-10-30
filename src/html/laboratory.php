<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require("includes/start_libvirt.php");
include "header.php";

$labid = $_GET['id'];
$userid = $_SESSION['username'];

$labs_info = $db->query('SELECT * FROM labs WHERE labid="'.$labid.'"');
foreach($labs_info as $lab_info)
{
	$lab = $lab_info;
}

if(!isset ($lab['labid']))
{
	exit("lab doesnt exist");
	header("Location: index.php");
}

if(isset($_POST['initialise']))
{
	start_machines();
}
elseif(isset($_POST['wipe']))
{
	wipe_lab($labid, $userid);
}
elseif(isset($_POST['stop']))
{
	stop_machines();
}

$lab_status = check_lab_status();

echo "<h1>".$lab['labname']."</h1>";

echo "<div style='width: 200px'>".$lab['lab_info']."</div>";

if($lab_status > 0)
{
	if($lab_status == 1)
	{
		echo '<form action="laboratory.php?id='.$labid.'" method="post">
		  <input type="submit" name="initialise" value="Boot machines" />
		  </form>';
		echo '<form action="laboratory.php?id='.$labid.'" method="post">
		<input type="submit" name="wipe" value="Wipe lab" />
		</form>';
	}
	else
	{
	
		echo '<form action="laboratory.php?id='.$labid.'" method="post">
		<input type="submit" name="stop" value="Stop lab" />
		</form>';
		
		echo '<iframe height="100px" width="450px" frameborder=0 src="includes/page_warning.php?labid='.$labid.'&user='.$userid.'"></iframe>';
		
		$vms = $db->query('SELECT * FROM lab_vmids where username="'.$userid.'" AND labid="'.$labid.'"');
		$vm_ids = array();
		foreach($vms as $vm)
		{
			$vm_ids[] = $vm['vm_id']; //create a separate array because using $vms pops the value after each use
		}
		
		echo '<table style="width:100%;height:100%">';
		$doms = $lv->get_domains();
		foreach($doms as $name)
		{
			$dom = $lv->get_domain_object($name);
			$vnca = "nill";
			for($i = 0; $i < count($vm_ids); $i++)
			{
				if(libvirt_domain_get_uuid_string($dom) == $vm_ids[$i])
				{	
					if($i % 2 == 0)
					{
						echo '<tr><td>';
					}
					else
					{
						echo '<td>';
					}
					$vnc = $lv->domain_get_vnc_port($dom);
					echo "<a href='http://" . $_SERVER['HTTP_HOST'] . "/includes/noVNC/vnc_lite.html?host=" . $_SERVER['HTTP_HOST'] . "&port=" . ($vnc + 180) . "' target='_blank'>$name - fullscreen</a>";
					echo "<iframe src=http://" . $_SERVER['HTTP_HOST'] . "/includes/noVNC/vnc_liter.html?host=" . $_SERVER['HTTP_HOST'] . "&port=" . ($vnc + 180) . " style='width:100%; height:95%'></iframe>";
					if($i % 2 == 0)
					{
						echo '</td>';
					}
					else
					{
						echo '</td></tr>';
					}
				}
			}
		}
		echo '</table>';
	}
}
else
{
	echo '<form action="laboratory.php?id='.$labid.'"  method="post">
		  <input type="submit" name="initialise" value="Intitialise Lab" />
		  </form>';
}

function start_machines()
{
	global $lv, $db;
	require("settings.php");
	$labid = $_GET['id'];
	$userid = $_SESSION['username'];
	if(check_lab_status()==1)
	{		
		$active_count = 0;
		$labs = $db->query('SELECT active FROM users_labs where username="'.$userid.'"');
		foreach($labs as $lab)
		{
			if($lab['active']>0)
			{
				$active_count++;
			}
		}
		
		if($active_count == 0)
		{
			start_network($userid);

			$vms = $db->query('SELECT * FROM lab_vmids where username="'.$userid.'" AND labid="'.$labid.'"');
			foreach($vms as $vm)
			{
				$domName = $lv->domain_get_name_by_uuid($vm['vm_id']);
				$lv->domain_start($domName);
			}
			
			try 
			{  		
				$st = $db->prepare("UPDATE users_labs SET active = :active, lastpolled = datetime('now') WHERE username = :username AND labid = :labid");
				$st->bindValue(':active', 1);
				$st->bindValue(':username', $userid);
				$st->bindValue(':labid', $labid);
				$st->execute();
			} 
			catch(PDOException $e) 
			{
				echo $e->getMessage();
				exit("<br>UPDATE users_labs SET active = 1 WHERE username = $userid AND labid = $labid");
			}
		}
		else
		{
			echo "<font color='red'>A lab already in progress, please stop it then try again.</font>";
		}
	}
	else
	{
		$vms = $db->query('SELECT os_name FROM labs_os WHERE labid="'.$labid.'"');
		$vmcount = 0;
		$uuid = false;
		
		try 
		{  		
			$st = $db->prepare("INSERT INTO users_labs (username, labid) values (:username, :labid)");
			$st->bindValue(':username', $userid);
			$st->bindValue(':labid', $labid);
			$st->execute();
		} 
		catch(PDOException $e) 
		{
			echo $e->getMessage();
			exit("<br>INSERT INTO users_labs (username, labid) values ($userid, $labid)");
		}
		
		foreach($vms as $vm)
		{
			$query = $db->query('SELECT file_location FROM operating_systems WHERE name="'.$vm['os_name'].'"');
			foreach($query as $os)
			{
				$os_path = $os['file_location'];
			}
			if(!is_writable($os_path))
			{
				wipe_lab($labid, $userid);
				exit("<font color='red'>Permission error with $os_path, please ensure path is readable/writable (or file might not even exist)</font>");
			}
			$domain_name = $vm['os_name']."-".$userid."-".$labid.$vmcount++;
			$backing_image_path = $user_path_for_backing_images."/".$userid."/".$labid."/".$domain_name.".qcow2";
			if(!is_dir(dirname($backing_image_path)))
			{
				mkdir(dirname($backing_image_path), 0777, true);
			}

			shell_exec("qemu-img create -f qcow2 -o backing_file=$os_path $user_path_for_backing_images/$userid/$labid/$domain_name.qcow2");
			$uuid = libvirt_domain_get_uuid_string($lv->domain_define(create_xml($domain_name, $vm_mem_mb, $vm_max_mem_mb, $vm_vcpus, $vm_arch, $vm_virtio_disk, $user_path_for_backing_images."/".$userid."/".$labid."/".$domain_name.".qcow2", "qcow2", "bridge", $userid)));
			if($uuid)
			{
				try 
				{  		
					$st = $db->prepare("INSERT INTO lab_vmids (username, labid, vm_id, os_name) values (:username, :labid, :vm_id, :osname)");
					$st->bindValue(':username', $userid);
					$st->bindValue(':labid', $labid);
					$st->bindValue(':vm_id', $uuid);
					$st->bindValue(':osname', $vm['os_name']);
					$st->execute();
					
				} 
				catch(PDOException $e) 
				{
					echo $e->getMessage();
					wipe_lab($labid, $userid);
					exit("INSERT INTO lab_vmids (username, labid, vm_id, os_name) values ($userid, $labid, $uuid, ".$vm['os_name'].")");
				}
			}
		}
	}
}

function stop_machines()
{
	global $lv, $db;
	require("settings.php");
	$labid = $_GET['id'];
	$userid = $_SESSION['username'];
	
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

function wipe_lab($labid, $userid)
{
	global $lv, $db;
	require("settings.php");
	
	$vms = $db->query('SELECT * FROM lab_vmids where username="'.$userid.'" AND labid="'.$labid.'"');
	foreach($vms as $vm)
	{
		$domName = $lv->domain_get_name_by_uuid($vm['vm_id']);
		$lv->domain_undefine($domName);
		try 
		{  		
			$st = $db->prepare("DELETE FROM lab_vmids WHERE vm_id = :vmid");
			$st->bindValue(':vmid', $vm['vm_id']);
			$st->execute();
		} 
		catch(PDOException $e) 
		{
			echo $e->getMessage();
		}
	}
	
	try 
	{  		
		$st = $db->prepare("DELETE FROM users_labs WHERE labid = :labid AND username = :username");
		$st->bindValue(':username', $userid);
		$st->bindValue(':labid', $labid);
		$st->execute();
	} 
	catch(PDOException $e) 
	{
		echo $e->getMessage();
	}
	require('settings.php');
	$path = $user_path_for_backing_images."/".$userid."/".$labid;
	$files = glob($path . '/*');
	foreach($files as $file)
	{
		if(is_file($file))
		{
			unlink($file);
		}
	}
	rmdir($path);
}

function start_network($name)
{
	global $lv;
	$lv->network_define(create_bridge_xml($name));
	$lv->set_network_active($name, true);
}

function check_lab_status() // 0 if not in progress, 1 if in progress, 2 if in progress and machines are running
{
	global $db, $userid, $labid, $lv;
	require("settings.php");
	$in_progress = 0;
	$machines_running = 0;
	//$doms = $lv->get_domains();
	$labs_in_progress = $db->query('SELECT * FROM users_labs where username="'.$userid.'" AND labid="'.$labid.'"');
    update_lab_status_db();
	foreach($labs_in_progress as $lab_in_prog)
	{
		$in_progress = 1;
		$machines_running = $lab_in_prog['active'];
	}
	return $in_progress + $machines_running;
}

function update_lab_status_db()
{
	global $db, $lv;
	require("settings.php");
	
	$doms = $lv->get_domains();
	$vms_in_progress = $db->query('SELECT * FROM lab_vmids');

	foreach($vms_in_progress as $defined_vm)
	{
		$uuid = $defined_vm['vm_id'];
		$userid = $defined_vm['username'];
		$labid = $defined_vm['labid'];
		$uuid_is_valid = false;
		
		foreach($doms as $name)
		{
			$dom = $lv->get_domain_object($name);
			if($uuid == libvirt_domain_get_uuid_string($dom))
			{
				$uuid_is_valid = true;
			}
		}
		if(!$uuid_is_valid)
		{
			wipe_lab($userid, $labid);
		}
		
	}
}

function create_bridge_xml($name)
{
	global $vm_network_ip_range_start, $lv;
	$tmp = $lv->get_networks(VIR_NETWORKS_ALL);
	$ip_network = sizeof($tmp) + $vm_network_ip_range_start;

	return "<network>
  <name>$name</name>
  <forward mode='nat'>
    <nat>
      <port start='1024' end='65535'/>
    </nat>
  </forward>
  <bridge name='$name' stp='on' delay='0'/>
  <ip address='192.168.$ip_network.1' netmask='255.255.255.0'>
    <dhcp>
      <range start='192.168.$ip_network.2' end='192.168.$ip_network.254'/>
    </dhcp>
  </ip>
</network>";
}

function create_xml($name, $mem, $max_mem, $vcpu, $arch, $virtio_disk, $os_image_path, $os_image_type, $if_type, $if_source)
{
	return "<domain type='kvm'>
  <name>$name</name>
  <memory unit='MiB'>$max_mem</memory>
  <currentMemory unit='MiB'>$mem</currentMemory>
  <vcpu placement='static'>$vcpu</vcpu>
  <os>
    <type arch='$arch'>hvm</type>
    <boot dev='hd'/>
  </os>
  <features>
    <acpi/>
    <apic/>
  </features>
  <cpu mode='host-model' check='partial'>
    <model fallback='allow'/>
  </cpu>
  <clock offset='utc'>
    <timer name='rtc' tickpolicy='catchup'/>
    <timer name='pit' tickpolicy='delay'/>
    <timer name='hpet' present='no'/>
  </clock>
  <on_poweroff>destroy</on_poweroff>
  <on_reboot>restart</on_reboot>
  <on_crash>destroy</on_crash>
  <pm>
    <suspend-to-mem enabled='no'/>
    <suspend-to-disk enabled='no'/>
  </pm>
  <devices>
    <emulator>/usr/bin/kvm-spice</emulator>
    <disk type='file' device='cdrom'>
      <driver name='qemu' type='raw'/>
      <source file='$virtio_disk'/>
      <target dev='hda' bus='ide'/>
      <readonly/>
      <address type='drive' controller='0' bus='0' target='0' unit='0'/>
    </disk>
    <disk type='file' device='disk'>
      <driver name='qemu' type='$os_image_type'/>
      <source file='$os_image_path'/>
      <target dev='hdb' bus='ide'/>
      <address type='drive' controller='0' bus='0' target='0' unit='1'/>
    </disk>

    <controller type='pci' index='0' model='pci-root'/>
    <controller type='ide' index='0'>
      <address type='pci' domain='0x0000' bus='0x00' slot='0x01' function='0x1'/>
    </controller>
    <interface type='$if_type'>
      <source bridge='$if_source'/>
    </interface>
    <input type='mouse' bus='ps2'/>
    <input type='keyboard' bus='ps2'/>
    <graphics type='vnc' port='-1' autoport='yes' listen='0.0.0.0'>
      <listen type='address' address='0.0.0.0'/>
    </graphics>
    <video>
      <model type='cirrus' vram='16384' heads='1' primary='yes'/>
      <address type='pci' domain='0x0000' bus='0x00' slot='0x02' function='0x0'/>
    </video>
    <memballoon model='virtio'>
      <address type='pci' domain='0x0000' bus='0x00' slot='0x05' function='0x0'/>
    </memballoon>
  </devices>
</domain>";
}



?>