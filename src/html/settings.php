<?php
//Please ensure all files / directory has 777 permissions.
$sqlite_db_location = "/var/www/html/includes/dps.db"; //Database location
$base_image_location = "/home/jacob/vm/"; //location of base images
$user_path_for_backing_images = "/var/www/html/users"; //User snapshot image location
$vm_mem_mb = 2048; //Memory each for each machine
$vm_max_mem_mb = 2560; //Max memory for each machine
$vm_vcpus = 2; //vCPU cores each machine will use
$vm_arch="x86_64"; //Architecture 
$vm_virtio_disk = "/var/www/html/includes/virtio-win-0.1.149.iso";
$vm_network_ip_range_start = 122; //The 24 bit network range start - ie 192.168.122.0/24
$vm_inactive_session_length = 600; //Time in seconds of inactivity to shutdown the machines
?>