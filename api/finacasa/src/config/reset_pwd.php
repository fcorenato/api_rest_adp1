<?php
ob_start();
session_start(); //iniciando a sessao
require('conexao.php');

$resultado_login = 0;
//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_usuario = trim($_POST["usuario"]);

    //Consultando usuario no sistema
    $pesquisa = mysql_query("SELECT codigo, usuario, email, nome_completo, status FROM `sys_usuarios`
    WHERE email = '$post_usuario'");
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_login = 0; //usuario nao encontrado
    } else {
        while ($dados = mysql_fetch_array($pesquisa)) {

            $cd_usuario = $dados["codigo"];
            $usuario = $dados["usuario"];
            $nome_completo = $dados["nome_completo"];
            $status = $dados["status"];
        }

        if ($status == 'A' or $status == 'P') {
            //pin a (16 numeros aleatorios)
            $a = "";
            for ($i = 0; $i < 16; $i++) {
                $a .= mt_rand(0, 9);
            }
            $data_emissao = $today = date("Y-m-d");
            $stmp = time();
            $pin_reset = $cd_usuario.'-'.$cd_usuario.'-'.$post_usuario . $a . $data_emissao . $stmp;
            $pin_reset_senha = $cd_usuario. $a . $stmp;

            $grava_pin = mysql_query("UPDATE `sys_usuarios` SET `pin_reset_pwd`= '$pin_reset', senha = '$pin_reset_senha' WHERE email = '$post_usuario' and (status = 'A' or status = 'P')");

            if ($grava_pin) {
                //enviar email
                $data = date("d/m/Y");
                $nome = 'Sistema Biv - Vetromani';
                $email_from = 'biv@vetromani.com.br';

                $msg = "
                <h3>Solicitação de reset de senha</h3>
                Email: $post_usuario <br />
                Data: $data <br />
                Clique no link abaixo para criar sua nova senha: <br />
                <a href='https://renovesolucoes.com.br/biv/vivarte/sys_redefinir_senha_step2.php?pin=$pin_reset&state=readyonly'>Redefinir senha</a><br /><br />
                ";

                // emails para quem será enviado o formulário
                $emailenviar = $post_usuario.",renato@vetromani.com.br";
                $destino = $emailenviar;
                $assunto = "BIV - Redefinir Senha.";

                // É necessário indicar que o formato do e-mail é html
                $headers  = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                $headers .= 'From: BIV Vetromani <' . $email_from . '>';
                //$headers .= "Bcc: $EmailPadrao\r\n";

                $enviaremail = mail($destino, $assunto, $msg, $headers);


                $resultado_login = 4;
            }
        } else {
            $resultado_login = 1; // Usuario nao liberado para utilizar o sistema.
        }
    }
} //fim do POST

echo $resultado_login;
