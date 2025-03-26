<?php

/*
error_reporting(0);

$conectar = 0;
$count_connect = 0;
while ($conectar == 0) {
    // Dados do banco
    //$ip_server = gethostbyname("vetromani.com.br");
    $dbhost   = '191.37.68.150';   #Nome do host
    $db       = "P12PROD";   #Nome do banco de dados
    $user     = "sa"; #Nome do usuário
    $password = "r2d43636@vetro14";   #Senha do usuário

    $conmysql = @mysql_connect($dbhost, $user, $password);
    $db = @mysql_select_db("$db");

    if ($conmysql && $db) {
        //echo 'Parabens!! A conexão ao banco de dados ocorreu normalmente!';
        $conectar = 1;
    } else {
        //echo 'Nao foi possivel conectar ao banco mysql';
        $count_connect++;
        if ($count_connect > 5) {
            $conectar = 1;

            //reportando evento via email
            $data = date("d/m/Y H:m");
            $nome = 'Portal Biv - Evento de erro de conexão';
            $email_from = 'biv@vetromani.com.br';

            $msg = "<h3>Erro ao conectar ao banco SQL(Protheus)<h3></br>
            Usuário: $usuario_nome <br />
            Data: $data <br />
            Qtde Tentativas = $count_connect";

            // emails para quem será enviado o formulário
            $emailenviar = "fco.renatogomes@gmail.com,renato@vetromani.com.br";
            $destino = $emailenviar;
            $assunto = "BIV - Evento de erro de conexão PROTHEUS.";

            // É necessário indicar que o formato do e-mail é html
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: BIV Vetromani <' . $email_from . '>';
            //$headers .= "Bcc: $EmailPadrao\r\n";

            $enviaremail = mail($destino, $assunto, $msg, $headers);

        } 
    }
};
?>
