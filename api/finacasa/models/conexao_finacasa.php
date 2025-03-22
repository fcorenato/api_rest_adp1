<?php
// error_reporting(0);
$conectar = 0;
$count_connect = 0;
while ($conectar == 0) {
    $conmysql = mysql_connect("srv950.hstgr.io","u502413668_bivdevsa_fin","M1HZ!fp2upFS");
    $db = mysql_select_db('u502413668_bivdev_finacas');
    mysql_query("SET NAMES 'utf8'");
    mysql_query('SET character_set_connection=utf8');
    mysql_query('SET character_set_client=utf8');
    mysql_query('SET character_set_results=utf8');
    header('Content-Type: text/html; charset=utf-8');


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

            $msg = "<h3>Erro ao conectar ao banco MySQL(Biv)<h3></br>
            Usuário: $usuario_nome <br />
            Data: $data <br />
            Qtde Tentativas = $count_connect";

            // emails para quem será enviado o formulário
            $emailenviar = "fco.renatogomes@gmail.com,renato@vetromani.com.br";
            $destino = $emailenviar;
            $assunto = "BIV - Evento de erro de conexão BIV.";

            // É necessário indicar que o formato do e-mail é html
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: BIV Vetromani <' . $email_from . '>';
            //$headers .= "Bcc: $EmailPadrao\r\n";

            $enviaremail = mail($destino, $assunto, $msg, $headers);

            header("location: sys_manutencao.php");

        } 
    }
};
?>
