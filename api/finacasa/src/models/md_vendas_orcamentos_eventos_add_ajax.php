<?php
date_default_timezone_set('America/Sao_Paulo');
//verifiando session 
require('../../src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];

//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $iddoc = strtoupper($_POST['iddoc']);
    $descricao = $_POST['descricao'];
    $created_at = date("Y-m-d H:i:s");

    require('../config/conexao.php');

    //registrando evento
    $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento, descricao) VALUES ('$iddoc', '$created_at', '$usuario_codigo', 'Manual','$descricao')") or die(mysql_error());
}

echo $result;
