<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);


// $timeout = 300;

// Configurações de conexão com o banco de dados

$host = "10.226.0.1";
$port = "5432";
$dbname = "g7carioca_db";
$username = "g7carioca_ro";
$password = "d2ss1efnjerk";

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


$q = $con->prepare("SELECT pg_sleep(120)");
$q->execute();
echo "OK";
