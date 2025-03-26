<?php
//verifiando session 
require('../../src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];

//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $data_niver = $_POST['data_niver'];
    $telefone = $_POST['telefone'];
    $instagram = $_POST['instagram'];

    $created_at = date("Y-m-d H:i:s");

    require('../config/conexao.php');

    //registrando evento
    $result = mysql_query("UPDATE sys_usuarios SET data_niver = '$data_niver',  instagram_pessoal = '$instagram', telefone = '$telefone' WHERE codigo = $usuario_codigo ") or die(mysql_error());

    if ($result) {
        $_SESSION["usuario_data_niver"] = $data_niver;
    }
}

echo $result;
