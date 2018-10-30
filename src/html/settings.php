<?php
$sqlite_db_location = "/var/www/html/includes/dps.db";
$base_image_location = "/mnt/admin/Jacob/vm/";
$user_path_for_backing_images = "/var/www/html/users";
$vm_mem_mb = 2048;
$vm_max_mem_mb = 2560;
$vm_vcpus = 2;
$vm_arch="x86_64";
$vm_virtio_disk = "/var/www/html/includes/virtio-win-0.1.149.iso";
$vm_network_ip_range_start = 122;
$vm_inactive_session_length = 600; //time in seconds
?>