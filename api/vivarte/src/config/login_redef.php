<?php
ob_start();
session_start(); //iniciando a sessao
require('conexao.php');

$resultado_login = 0;
//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["pin"])) {
        $pin = $_POST["pin"];
        $post_pwd1 = $_POST["password1"];
        $post_pwd2 = $_POST["password2"];

        $result_reset = mysql_query("UPDATE sys_usuarios SET
            senha='$post_pwd1', pin_reset_pwd='resetado', status='A' WHERE pin_reset_pwd = '$pin'") or die(mysql_error());
        $resultado_login = 4; // login sucesso

    } else {
        $post_usuario = $_POST["usuario"];
        $post_pwd = $_POST["password"];
        $post_pwd1 = $_POST["password1"];
        $post_pwd2 = $_POST["password2"];

        //Consultando usuario no sistema
        $pesquisa = mysql_query("SELECT * FROM sys_usuarios WHERE usuario = '$post_usuario' ");
        $linhas = mysql_num_rows($pesquisa);
        if ($linhas == 0) {
            $resultado_login = 0; //usuario nao encontrado
        } else {
            while ($dados = mysql_fetch_array($pesquisa)) {

                $cd_usuario = $dados["codigo"];
                $usuario = $dados["usuario"];
                $nome_completo = $dados["nome_completo"];
                $status = $dados["status"];
                $senha = $dados["senha"];
            }

            if ($senha != $post_pwd) {
                $resultado_login = 2; // Senha invalida
            } else {

                $_SESSION["codigo_usuario"] = $cd_usuario;
                $_SESSION["usuario"] = $usuario;
                $_SESSION["status"] = $status;
                $_SESSION["nome_completo"] = $nome_completo;

                $result = mysql_query("UPDATE sys_usuarios SET
            senha='$post_pwd1',  status='A' WHERE usuario = '$post_usuario'") or die(mysql_error());
                $status = 'A';
                $resultado_login = 4; // login sucesso
            }

            if ($status != 'A') {
                $resultado_login = 1; // Usuario nao liberado para utilizar o sistema.
            }

            if ($status == 'P') {
                $resultado_login = 3; // Usuario precisa alterar senha
            }
        }
    }
} //fim do POST

echo $resultado_login;
