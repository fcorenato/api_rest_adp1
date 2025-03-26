<?php
//verifiando session 
require('../../src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];

//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $DOC_ACAO = strtoupper($_POST['DOC_ACAO']);
    $ID_ORC = strtoupper($_POST['ID_ORC']);
    $ID_PED = strtoupper($_POST['ID_PED']);
    $MOTIVO_CANCELA = strtoupper($_POST['MOTIVO_CANCELA']);
    $MOTIVO_DESCRICAO = strtoupper($_POST['MOTIVO_DESCRICAO']);


    $pedido_conv_date = date("Y-m-d H:i:s");
    $pedido_conv_user = $usuario_codigo;

    require('../config/conexao.php');

    if ($DOC_ACAO == 'CONVERTER') {
        //upload foto
        $foramatos_permitidos = array("jpg", "jpeg", "png", "pdf", "docx", "doc", "bmp");

        //upload arquivo 1
        $extensao = pathinfo($_FILES['anexo_pedido_assinado']['name'], PATHINFO_EXTENSION);
        if (in_array($extensao, $foramatos_permitidos)) {
            $pasta = '../../uploads/comprovantes/';
            $temporario = $_FILES['anexo_pedido_assinado']['tmp_name'];

            $novo_nome1 = $ID_ORC . '_pedido_assinado.' . $extensao;
            if (move_uploaded_file($temporario, $pasta . $novo_nome1)) {
                $upload_result = 'ok';
            } else {
                $upload_result = 'erro1';
            }
        } else {
            $upload_result = 'erro2up1';
        }

        //upload arquivo 2
        if (isset($_FILES['anexo_comp_pgto'])) {
            $extensao2 = pathinfo($_FILES['anexo_comp_pgto']['name'], PATHINFO_EXTENSION);
            if (in_array($extensao2, $foramatos_permitidos)) {
                $pasta = '../../uploads/comprovantes/';
                $temporario = $_FILES['anexo_comp_pgto']['tmp_name'];

                $novo_nome2 = $ID_ORC . '_comprov_pgto.' . $extensao2;
                if (move_uploaded_file($temporario, $pasta . $novo_nome2)) {
                    $upload_result = 'ok';
                } else {
                    $upload_result = 'erro1';
                }
            } else {
                $upload_result = 'erro2up2';
            }
        }


        if ($upload_result == 'ok') {
            $result = mysql_query("UPDATE md_vendas_pedidos SET
            orc_updated_at='$pedido_conv_date', pedido_conv_date='$pedido_conv_date', pedido_conv_user='$pedido_conv_user', pedido_num='$ID_PED', anexo1 = '$novo_nome1', anexo2 = '$novo_nome2',  status='G' WHERE id = $ID_ORC") or die(mysql_error());

            //registrando evento
            $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Orçamento em análise para converter')") or die(mysql_error());
        } else {
            $result = $upload_result;
        }
    } else if ($DOC_ACAO == 'CONVERTER1') {

        $result = mysql_query("UPDATE md_vendas_pedidos SET
        orc_updated_at='$pedido_conv_date', pedido_conv_date='$pedido_conv_date', pedido_conv_user='$pedido_conv_user', pedido_num='$ID_PED', status='N' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento
        $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Pagamento Confirmado')") or die(mysql_error());

        //enviando email informando a confirmacao de pagto pro comercial
        if ($perfil = 1) {
            $data = date("d/m/Y");
            $nome = 'Sistema Biv - Vetromani';
            $email_from = 'biv@vetromani.com.br';

            $msg = "
                <h3>Confirmação de pagamento efetuada:</h3>
                Usuário: $usuario_nome <br />
                Data: $data <br />
                Orçamento nº: $ID_ORC
                <a href='http://www.vetromani.com.br/biv/src/relpdf/orcamento.php?id=$ID_ORC&state=view'>EXIBIR ORCAMENTO</a><br /><br />
                ";

            // emails para quem será enviado o formulário
            $emailenviar = "renato@vetromani.com.br,biv@vetromani.com.br";
            $destino = $emailenviar;
            $assunto = "BIV - Confirmação de pagamento de Orçamento.";

            // É necessário indicar que o formato do e-mail é html
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: BIV Vetromani <' . $email_from . '>';
            //$headers .= "Bcc: $EmailPadrao\r\n";

            $enviaremail = mail($destino, $assunto, $msg, $headers);
        }
    } else if ($DOC_ACAO == 'CONVERTER2') {

        $result = mysql_query("UPDATE md_vendas_pedidos SET
        orc_updated_at='$pedido_conv_date', pedido_conv_date='$pedido_conv_date', pedido_conv_user='$pedido_conv_user', pedido_num='$ID_PED', status='P' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento
        $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Convertido')") or die(mysql_error());

        //indenficando usuario que incluiu o orcamento para enviar email:
        $result0 = mysql_query("SELECT pv.id, usr.codigo, usr.nome_completo, usr.email FROM md_vendas_pedidos as pv
        LEFT JOIN sys_usuarios as usr ON pv.orc_created_user = usr.codigo
         where pv.id = $ID_ORC ") or die(mysql_error());
        $orc_email = $result0;
        $linhas = mysql_num_rows($result0);
        if ($linhas) {
            $dados_result = mysql_fetch_assoc($result0);
        }

        //enviando email informando a conversao do pedido se perfil = V = vendedor externo
        if ($perfil = 1 and !$baseteste) {
            $data = date("d/m/Y");
            $nome = 'Sistema Biv - Vetromani';
            $email_from = 'biv@vetromani.com.br';

            $msg = "
                <h3>Orçamento convertido em pedido:</h3>
                Data: $data <br />
                Orçamento nº: $ID_ORC
                <a href='http://www.vetromani.com.br/biv/src/relpdf/orcamento.php?id=$ID_ORC&state=view'>EXIBIR ORCAMENTO</a><br /><br />
                ";

            // emails para quem será enviado o formulário
            $emailenviar = 'renato@vetromani.com.br,biv@vetromani.com.br,' . $dados["email"];
            $destino = $emailenviar;
            $assunto = "BIV - Orçamento convertido em pedido.";

            // É necessário indicar que o formato do e-mail é html
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: BIV Vetromani <' . $email_from . '>';
            //$headers .= "Bcc: $EmailPadrao\r\n";

            $enviaremail = mail($destino, $assunto, $msg, $headers);
        }
    } else if ($DOC_ACAO == 'CONVERTER_REJEITAR') {

        $MOTIVO_REJEICAO = strtoupper($_POST['MOTIVO_REJEICAO']);

        $result = mysql_query("UPDATE md_vendas_pedidos SET
        orc_updated_at='$pedido_conv_date', pedido_conv_date='$pedido_conv_date', pedido_conv_user='$pedido_conv_user', pedido_num='$ID_PED', status='A' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento
        $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento, descricao) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Conversão Rejeitada','$MOTIVO_REJEICAO')") or die(mysql_error());

        //indenficando usuario que incluiu o orcamento para enviar email:
        $result0 = mysql_query("SELECT pv.id, usr.codigo, usr.nome_completo, usr.email FROM md_vendas_pedidos as pv
        LEFT JOIN sys_usuarios as usr ON pv.orc_created_user = usr.codigo
         where pv.id = $ID_ORC ") or die(mysql_error());
        $orc_email = $result0;
        $linhas = mysql_num_rows($result0);
        if ($linhas) {
            $dados = mysql_fetch_assoc($result0);
        }
        //enviando email informando a a rejeicao do pedido se perfil = V = vendedor externo
        if ($perfil = 1 and !$baseteste) {
            $data = date("d/m/Y");
            $nome = 'Sistema Biv - Vetromani';
            $email_from = 'biv@vetromani.com.br';

            $msg = '
                <h3>O orçamento  '.$ID_ORC.' não foi convertido em pedido:</h3>
                Motivo: '.$MOTIVO_REJEICAO.' <br />
                Data: '.$data.' <br />
                Usuario que incluiu o orcamento: '.$dados["nome_completo"].' <br />
                E-mail: ' . $dados["email"].'<br />
                <br /><br />
                ';

            // emails para quem será enviado o formulário
            $emailenviar = 'renato@vetromani.com.br,biv@vetromani.com.br,' . $dados["email"];
            $destino = $emailenviar;
            $assunto = "BIV - Orçamento não convertido (REJEITADO).";

            // É necessário indicar que o formato do e-mail é html
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: BIV Vetromani <' . $email_from . '>';
            //$headers .= "Bcc: $EmailPadrao\r\n";

            $enviaremail = mail($destino, $assunto, $msg, $headers);
        }
    } else if ($DOC_ACAO == 'CANCELAR') {
        $DESCRICAO = $MOTIVO_CANCELA . ' - ' . $MOTIVO_DESCRICAO;
        $result = mysql_query("UPDATE md_vendas_pedidos SET
        orc_updated_at='$pedido_conv_date',  status='C' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento
        $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento, descricao) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Cancelado', '$DESCRICAO')") or die(mysql_error());
    } else if ($DOC_ACAO == 'DESBLOQ') {
        $DESCRICAO = $MOTIVO_DESCRICAO;
        $result = mysql_query("UPDATE md_vendas_pedidos SET
        orc_updated_at='$pedido_conv_date', status='A' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento
        $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento, descricao) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Desbloqueado', '$DESCRICAO')") or die(mysql_error());
    }
}

echo $result;
