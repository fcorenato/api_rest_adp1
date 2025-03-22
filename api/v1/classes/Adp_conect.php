<?php

// Configurações de conexão com o banco de dados

$host = "10.226.0.1";
$port = "5432";
$dbname = "g7carioca_db";
$username = "g7carioca_ro";
$password = "d2ss1efnjerk";

$con = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
