<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);


// $timeout = 300;

// Configurações de conexão com o banco de dados

$host = "100.64.248.50";
$port = "5432";
$dbname = "posto";
$username = "neylon";
$password = "H2TiLFcWQ28Yqz";

// $con = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);


$timeout = 0;

$con = new PDO(
    "pgsql:host=$host;port=$port;dbname=$dbname",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);

// Remove o timeout para esta conexão
$con->exec("SET statement_timeout = 0;");


?>