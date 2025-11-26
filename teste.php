<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);

require 'Adp_conect.php';

$q = $con->prepare("SELECT pg_sleep(120)");
$q->execute();
echo "OK";

?>