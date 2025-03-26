<?php
date_default_timezone_set('America/Sao_Paulo');
set_time_limit(0);
//include ('conexao.php');
date_default_timezone_set('America/Sao_Paulo');

//date_default_timezone_set('America/Sao_Paulo');
include ('SUsuario.php');
$codigo_usuario = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$nome_completo = $_SESSION["nome_completo"];
$ultima_atualização = '20/01/2020';

//listando
require('../../src/config/conexao.php');
$email = $_POST['email'];


$pesquisa = mysql_query("SELECT codigo FROM sys_usuarios
	WHERE email = '$email'") or die(mysql_error());
$qtd_reg = mysql_num_rows($pesquisa);
if ($qtd_reg == 0) {
	$resultado = 0;
} else {
	$resultado = 1;
}
echo $resultado;
?>