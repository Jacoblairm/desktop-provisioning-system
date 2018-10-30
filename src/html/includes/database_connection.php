<?php
$db = new PDO('sqlite:'.$sqlite_db_location);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA foreign_keys = ON;");
?>