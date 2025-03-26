<?php 
$data = date("d/m/Y");
$nome = 'Portal Biv - Evento de conexão';
$email_from = 'biv@vetromani.com.br';

$msg = "mensagem do email";

// emails para quem será enviado o formulário
$emailenviar = "fco.renatogomes@gmail.com";
$destino = $emailenviar;
$assunto = "BIV - Orçamento convertido em pedido.";

// É necessário indicar que o formato do e-mail é html
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
$headers .= 'From: BIV Vetromani <' . $email_from . '>';
//$headers .= "Bcc: $EmailPadrao\r\n";

$enviaremail = mail($destino, $assunto, $msg, $headers);

?>