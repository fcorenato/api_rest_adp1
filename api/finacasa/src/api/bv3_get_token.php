<?php 
//buscando token no bando de dados
include('../config/conexao.php');
include('../../config/conexao.php');
$query1 = "SELECT bling_access_token FROM `sys_unidades` WHERE codigo = 1";
// echo '<hr>query token = '. $query1 . '<hr>';

$result_tk = mysql_query($query1) or die(mysql_error());
$linhas_result_tk = mysql_num_rows($result_tk);
if ($linhas_result_tk > 0) {
    while ($dados_tk = mysql_fetch_array($result_tk)) {
        $token = $dados_tk['bling_access_token'];
    }
}

?>