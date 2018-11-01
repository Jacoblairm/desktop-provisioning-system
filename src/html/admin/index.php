<?php

require("../header.php");
require ("../settings.php");
require ('admin_header.php');


if ($_SESSION['username'] != "admin")
{
    header("Location: ../index.php");
}

require ('../includes/start_libvirt.php');



?>
<html>
    <head>
        <title>Desktop provisioning system - Administration</title>
        <style>
            table {
                border-collapse: collapse;
            }

            td, th {
                border: 1px solid #dddddd;
                text-align: left;
                padding: 8px;
            }

            tr:nth-child(even) {
                background-color: #dddddd;
            }
        </style>
    </head>
<body>
<?php
if (scandir($base_image_location) == false)
{
    echo '<font color="red">Permission error with image directory! (' . $base_image_location . ')</font><br />';
}

$uri = $lv->get_uri();
$tmp = $lv->get_domain_count();

$action = array_key_exists('action', $_GET) ? $_GET['action'] : false;
$subaction = array_key_exists('subaction', $_GET) ? $_GET['subaction'] : false;

if ($action == 'virtual-networks')
{
    echo "<h2>Networks</h2>";
    $ret = false;
    if ($subaction)
    {
        $name = $_GET['name'];
        if ($subaction == 'start')
        {
            $ret = $lv->set_network_active($name, true) ? "Network has been started successfully" : 'Error while starting network: ' . $lv->get_last_error();
        }
        else
        if ($subaction == 'stop')
        {
            $ret = $lv->set_network_active($name, false) ? "Network has been stopped successfully" : 'Error while stopping network: ' . $lv->get_last_error();
        }
        else
        if (($subaction == 'dumpxml') || ($subaction == 'edit'))
        {
            $xml = $lv->network_get_xml($name, false);
            if ($subaction == 'edit')
            {
                if (@$_POST['xmldesc'])
                {
                    $ret = $lv->network_change_xml($name, $_POST['xmldesc']) ? "Network definition has been changed" : 'Error changing network definition: ' . $lv->get_last_error();
                }
                else
                {
                    $ret = 'Editing network XML description: <br/><br/><form method="POST"><table><tr><td>Network XML description: </td>' . '<td><textarea name="xmldesc" rows="25" cols="90%">' . $xml . '</textarea></td></tr><tr align="center"><td colspan="2">' . '<input type="submit" value=" Edit domain XML description "></tr></form>';
                }
            }
            else
            {
                $ret = 'XML dump of network <i>' . $name . '</i>:<br/><br/>' . htmlentities($lv->get_network_xml($name, false));
            }
        }
    }

    echo "<h3>List of networks</h3>";
    $tmp = $lv->get_networks(VIR_NETWORKS_ALL);
    echo "<table>" . "<tr>" . "<th>Network name </th>" . "<th> Network state </th>" . "<th> Gateway IP Address </th>" . "<th> IP Address Range </th>" . "<th> Forwarding </th>" . "<th> DHCP Range </th>" . "<th> Actions </th>" . "</tr>";
    for ($i = 0; $i < sizeof($tmp); $i++)
    {
        $tmp2 = $lv->get_network_information($tmp[$i]);
        $ip = '';
        $ip_range = '';
        $activity = $tmp2['active'] ? 'Active' : 'Inactive';
        $dhcp = 'Disabled';
        $forward = 'None';
        if (array_key_exists('forwarding', $tmp2) && $tmp2['forwarding'] != 'None')
        {
            if (array_key_exists('forward_dev', $tmp2)) $forward = $tmp2['forwarding'] . ' to ' . $tmp2['forward_dev'];
            else $forward = $tmp2['forwarding'];
        }

        if (array_key_exists('dhcp_start', $tmp2) && array_key_exists('dhcp_end', $tmp2)){$dhcp = $tmp2['dhcp_start'] . ' - ' . $tmp2['dhcp_end'];}
        if (array_key_exists('ip', $tmp2)){$ip = $tmp2['ip'];}
        if (array_key_exists('ip_range', $tmp2)){$ip_range = $tmp2['ip_range'];}
        $act = "<a href=\"?action={$_GET['action']}&amp;subaction=" . ($tmp2['active'] ? "stop" : "start");
        $act.= "&amp;name=" . urlencode($tmp2['name']) . "\">";
        $act.= ($tmp2['active'] ? "Stop" : "Start") . " network</a>";
        $act.= " | <a href=\"?action={$_GET['action']}&amp;subaction=dumpxml&amp;name=" . urlencode($tmp2['name']) . "\">Dump network XML</a>";
        if (!$tmp2['active']) 
        {
            $act.= ' | <a href="?action=' . $_GET['action'] . '&amp;subaction=edit&amp;name=' . urlencode($tmp2['name']) . '">Edit network</a>';
        }
        echo "<tr>" . "<td>{$tmp2['name']}</td>" . "<td align=\"center\">$activity</td>" . "<td align=\"center\">$ip</td>" . "<td align=\"center\">$ip_range</td>" . "<td align=\"center\">$forward</td>" . "<td align=\"center\">$dhcp</td>" . "<td align=\"center\">$act</td>" . "</tr>";
    }

    echo "</table>";
    if ($ret) echo "<pre>$ret</pre>";
}
else
if ($action == 'users')
{
    echo '<h2>Add User</h2>
        <form action="create_user.php" method="post">
        Username:<br />
        <input type="text" name="username" required><br />
        Password<br />
        <input type="password" name="password" required>
        <br /><br />
        <input type="submit" value="Submit">
        </form>';
    $users = $db->query('SELECT username FROM users');
    echo "<table>" . "<tr>" . "<th>Name</th>" . "<th>Active labs</th>" . "<th>Actions</th>" . "</tr>";
    foreach($users as $user)
    {
        $username = $user['username'];
        $labs = $db->query('SELECT labid FROM users_labs WHERE username="' . $username . '"');
        $labs_string = "";
        foreach($labs as $lab)
        {
            $labs_string .= $lab['labid'];
        }

        echo "<tr>" . "<td><b>" . $username . "</b></td>" . "<td>" . $labs_string . "</td>" . //possibly allow admins to interact with lab sessions
        "<td>";
        echo '<a href="delete_user.php">Delete user</a>  '
		.' <form style="display:inline" action="change_password.php" method="post">
		<input type="password" name="password" placeholder="Change password" required>
		<input type="hidden" name="username" value='.$username.'>
		<input type="submit" value="Submit">
		</form>';
        echo "</td></tr>";


    }

    echo "</table>";
}
else
if ($action == 'labs')
{
	include("check_os.php"); //checks if any os's have been added/deleted
    $vmcount = 4;
    if (isset($_GET['vms']))
    {
        $vmcount = $_GET['vms'];
    }

    echo '<h2>Add Lab</h2>
        <form action="" method="get">
        <input type="hidden" name="action" value="labs"><b>VM Count: </b>' . $vmcount . '<br />
        <input data-show-value="true" onchange="this.form.submit()" type="range" min="1" max="10" value="' . $vmcount . '" name="vms">
        </form>
        
            <form action="create_lab.php" method="post">
            <b>Lab name:</b><br />
            <input type="text" name="labname" required>
        <br />
        <b>Select Operating Systems</b><br />';
    $os_options = "";
    $oss = $db->query('SELECT * FROM operating_systems');
    foreach($oss as $os)
    {
        $os_options = $os_options . "<option value='" . $os['name'] . "'>" . $os['name'] . "</option>";
    }

    for ($i = 0; $i < $vmcount; $i++)
    {
        echo '<select name="os_' . $i . '">' . $os_options . '</select>';
    }

    echo '<br /><b>Additional lab info:</b><br /><textarea cols="50" rows="4" name="lab_info"></textarea><br />';
    echo '<input type="submit" value="Submit"></form>';
    $labs = $db->query('SELECT * FROM labs');
    echo "<h2>View Labs</h2><table>" . "<tr>" . "<th>Name</th>" . "<th>Environments</th>" . "<th>Actions</th>" . "</tr>";
    foreach($labs as $lab)
    {
        $labname = $lab['labname'];
        $labid = $lab['labid'];
        $operating_systems = $db->query('SELECT os_name FROM labs_os WHERE labid="' . $labid . '"');
        $os_string = "";
        foreach($operating_systems as $operating_system)
        {
            $os_string .= $operating_system['os_name'] . ", ";
        }

        echo "<tr>" . "<td>#" . $labid . " - <b>" . $labname . "</b></td>" . "<td>" . $os_string . "</td>" . "<td>";
        echo '<a href="delete_lab.php?lab=' . $labid . '">Delete lab</a>';
        echo "</td></tr>";
    }

    echo "</table>";
}
else
if ($action == 'os')
{
	include("check_os.php");
    $oss = $db->query('SELECT * FROM operating_systems');
    echo "<h2>View Installed Operating Systems</h2><table>" 
	. "<tr>" . "<th>Name</th>" 
	. "<th>Image Path</th>" 
	. "</tr>";
    foreach($oss as $os)
    {
        $osname = $os['name'];
        $file_location = $os['file_location'];
        echo "<tr>" . "<td><b>" . $osname . "</b></td>" . "<td>" . $file_location . "</td>";
    }
    echo "</table>";
}
else
{
    $ret = false;
    if ($action)
    {
        $domName = $lv->domain_get_name_by_uuid($_GET['uuid']);
        if ($action == 'domain-start')
        {
            $ret = $lv->domain_start($domName) ? "Domain has been started successfully" : 'Error while starting domain: ' . $lv->get_last_error();
        }
        else
        if ($action == 'domain-stop')
        {
            $ret = $lv->domain_shutdown($domName) ? "Domain has been stopped successfully" : 'Error while stopping domain: ' . $lv->get_last_error();
        }
        else
        if ($action == 'domain-destroy')
        {
            $ret = $lv->domain_destroy($domName) ? "Domain has been destroyed successfully" : 'Error while destroying domain: ' . $lv->get_last_error();
        }
        else
        if ($action == 'domain-undefine')
        {
            $ret = $lv->domain_undefine($domName) ? "Domain has been undefined successfully" : 'Error while undefining domain: ' . $lv->get_last_error();
        }
        else
        if (($action == 'domain-get-xml') || ($action == 'domain-edit'))
        {
            $inactive = (!$lv->domain_is_running($domName)) ? true : false;
            $xml = $lv->domain_get_xml($domName, $inactive);
            if ($action == 'domain-edit')
            {
                if (@$_POST['xmldesc'])
                {
                    $ret = $lv->domain_change_xml($domName, $_POST['xmldesc']) ? "Domain definition has been changed" : 'Error changing domain definition: ' . $lv->get_last_error();
                }
                else
                {
                    $ret = "Editing domain XML description: <br/><br/><form method=\"POST\"><table><tr><td>Domain XML description: </td>" . "<td><textarea name=\"xmldesc\" rows=\"25\" cols=\"90%\">" . $xml . "</textarea></t\></tr><tr align=\"center\"><td colspan=\"2\">" . "<input type=\"submit\" value=\" Edit domain XML description \"></tr></form>";
                }
            }
            else
            {
                $ret = "Domain XML for domain <i>$domName</i>:<br/><br/>" . htmlentities($xml);
            }
        }
    }

    $doms = $lv->get_domains();

    echo "<table>" . "<tr>" 
    . "<th>Name</th>" 
    . "<th>CPU#</th>" 
    . "<th>Memory</th>" 
    . "<th>State</th>"
	. "<th>VNC viewer</th>";
    

    echo "<th>Actions</th>" . "</tr>";
    foreach($doms as $name)
    {
        $dom = $lv->get_domain_object($name);
        $uuid = libvirt_domain_get_uuid_string($dom);
        $active = $lv->domain_is_active($dom);
        $info = $lv->domain_get_info($dom);
        $mem = number_format($info['memory'] / 1024, 2, '.', ' ') . ' MB';
        $cpu = $info['nrVirtCpu'];
        $state = $lv->domain_state_translate($info['state']);
        $id = $lv->domain_get_id($dom);
        $arch = $lv->domain_get_arch($dom);
        $vnc = $lv->domain_get_vnc_port($dom);
        $nics = $lv->get_network_cards($dom);
        if (($diskcnt = $lv->get_disk_count($dom)) > 0)
        {
            $disks = $diskcnt . ' / ' . $lv->get_disk_capacity($dom);
            $diskdesc = 'Current physical size: ' . $lv->get_disk_capacity($dom, true);
        }
        else
        {
            $disks = '-';
            $diskdesc = '';
        }

        if ($vnc < 0)
        {
            $vnc = '-';
        }
        else
        {
            $vnca = "<a href='http://" . $_SERVER['HTTP_HOST'] . "/includes/noVNC/vnc_lite.html?host=" . $_SERVER['HTTP_HOST'] . "&port=" . ($vnc + 180) . "'>VNC URL</a>";
        }

        unset($tmp);
        if (!$id)
        {
            $id = '-';
        }

        unset($dom);
        echo "<tr>" 
        . "<td><b>$name</b></td>" 
        . "<td>$cpu</td>" 
        . "<td>$mem</td>" 
        . "<td>$state</td>";

        if ($lv->supports('screenshot') && $active)
        {
            echo "<td align=\"center\"><iframe src=http://" . $_SERVER['HTTP_HOST'] . "/includes/noVNC/vnc_liter.html?host=" . $_SERVER['HTTP_HOST'] . "&port=" . ($vnc + 180) . " height=200 width=200></iframe></td>";
        }
        else
        {
            echo "<td></td>";
        }

        echo "<td align=\"center\">";
        if ($lv->domain_is_running($name))
        {
            echo "<a href=\"?action=domain-stop&amp;uuid=$uuid\">Stop</a> | <a href=\"?action=domain-destroy&amp;uuid=$uuid\">Destroy domain</a> |";
        }
        else
        {
            echo "<a href=\"?action=domain-start&amp;uuid=$uuid\">Start</a> |";
            echo "<a href=\"?action=domain-undefine&amp;uuid=$uuid\">Undefine</a> |";
        }

        echo "<a href=\"?action=domain-get-xml&amp;uuid=$uuid\">Dump</a>";
        if (!$lv->domain_is_running($name))
        {
            echo "| <a href=\"?action=domain-edit&amp;uuid=$uuid\">Edit XML</a>";
        }

        echo "</td></tr>\n";
    }

    echo "</table><br/><pre>$ret</pre>";
}

?>
</body>
</html>