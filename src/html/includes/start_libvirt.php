<?php
require ('libvirt.php');
		$lv = new Libvirt();

		if ($lv->connect("qemu:///system") == false) {die('<html><body>Cannot open connection to hypervisor</body></html>');}
		$hn = $lv->get_hostname();

		if ($hn == false) die('<html><body>Cannot get hostname</body></html>');


?>