<?php
ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);
require 'Adp_conect.php';

$q = $pdo->prepare("SELECT SLEEP(120)");
$q->execute();
echo "OK";
?>