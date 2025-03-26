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
    $DOC_ACAO = strtoupper($_POST['DOC_ACAO']);
    $ID_ORC = strtoupper($_POST['ID_ORC']);
    $ID_OPT = strtoupper($_POST['ID_OPT']);
    $ID_PED = strtoupper($_POST['ID_PED']);
    $PED_PREV_ENT = ($_POST['C5_YDTPREVENT']);
    $C5_YDTPREVENT_ATU = ($_POST['C5_YDTPREVENT_ATU']);
    
    $MOTIVO_CANCELA = strtoupper($_POST['MOTIVO_CANCELA']);
    $MOTIVO_DESCRICAO = strtoupper($_POST['MOTIVO_DESCRICAO']);


    $pedido_conv_date = date("Y-m-d H:i:s");
    $pedido_conv_user = $usuario_codigo;

    require('../config/conexao.php');

    if ($DOC_ACAO == 'CONVERTER') {
        //upload foto
        $foramatos_permitidos = array("jpg", "JPG", "jpeg", "JPEG", "png", "PNG", "pdf", "PDF", "docx", "doc", "bmp");

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

        //chamada API para integração bling
        // include('../api/bling_pedido_post.php');
        include('../api/bv3_pv_post.php');

        $ID_PED = $bling_api_pedidovenda;
        if ($integrou) {
            $result = mysql_query("UPDATE md_vendas_pedidos SET
            orc_updated_at='$pedido_conv_date', pedido_conv_date='$pedido_conv_date', pedido_conv_user='$pedido_conv_user', pedido_num='$ID_PED', pedido_prev_ent = '$PED_PREV_ENT', status='P' WHERE id = $ID_ORC") or die(mysql_error());

            //registrando evento
            $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Convertido Pedido Bling = $bling_api_pedidovenda')") or die(mysql_error());

            //indenficando usuario que incluiu o orcamento para enviar email:
            $result0 = mysql_query("SELECT pv.id, usr.codigo, usr.nome_completo, usr.email FROM md_vendas_pedidos as pv
            LEFT JOIN sys_usuarios as usr ON pv.orc_created_user = usr.codigo
            where pv.id = $ID_ORC ") or die(mysql_error());
            $orc_email = $result0;
            $linhas = mysql_num_rows($result0);
            if ($linhas) {
                $dados = mysql_fetch_assoc($result0);
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
        } else {
            $result = 'Bling Erro! Codigo 2: '.$bling_api_cod_erro.' - '.$bling_api_cod_erro_msg.'atemp:'.$atemp;
            echo $result;
            die();
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
                <h3>O orçamento  ' . $ID_ORC . ' não foi convertido em pedido:</h3>
                Motivo: ' . $MOTIVO_REJEICAO . ' <br />
                Data: ' . $data . ' <br />
                Usuario que incluiu o orcamento: ' . $dados["nome_completo"] . ' <br />
                E-mail: ' . $dados["email"] . '<br />
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
    } else if ($DOC_ACAO == 'DATAENTATU') {
        $result = mysql_query("UPDATE md_vendas_pedidos SET
            pedido_prev_ent = '$C5_YDTPREVENT_ATU', status='P' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento
        $datebr = date('d/m/Y', strtotime($C5_YDTPREVENT_ATU));
        $result_evento = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Data de Entrega alterado para $datebr')") or die(mysql_error());
    } else if ($DOC_ACAO == 'DESVINCULAROPT') {
        $result = mysql_query("UPDATE md_vendas_pedidos SET
        oportunidade_id = '0' WHERE id = $ID_ORC") or die(mysql_error());

        //registrando evento no orçamento
        $result_evento = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$pedido_conv_date', '$pedido_conv_user', 'Orçamento desvinculado da oportunidade $ID_OPT')") or die(mysql_error());

        //alterando valor da oportunidade para valor total dos orcamentos associados:
        $query_pesq_orc_opt = "SELECT SUM(total_final) as totalped FROM md_vendas_pedidos as p WHERE p.oportunidade_id = '$ID_OPT'";
        $pesquisa_orc_opt = mysql_query($query_pesq_orc_opt)  or die(mysql_error());
        $linhas_orc_opt = mysql_num_rows($pesquisa_orc_opt);
        if ($linhas_orc_opt > 0) {
               $dados_orc_opt = mysql_fetch_array($pesquisa_orc_opt);
        }
        $total_orc_valor = $dados_orc_opt['totalped'];
        $total_orc_valor = $total_orc_valor > 0 ? $total_orc_valor : 0;
        $query_update_orc_opt = "UPDATE md_crm_oportunidade SET valor = $total_orc_valor WHERE id = '$ID_OPT'";
        $update_orc_opt = mysql_query($query_update_orc_opt)  or die(mysql_error());

        //registrand evento alteração de total da oportunidade
        $oportunidade_id = $ID_OPT;
        $desc_evento .= '<i class="fas fa-caret-right"></i> Orçamento '.$ID_ORC.' desvinculado da oportunidade<br>';
        $desc_evento .= '<i class="fas fa-caret-right"></i> Valor total da oportunidade atualizado R$ '.number_format($total_orc_valor, 2, ',', '.').'<br>';

        $query_evento_opt = "INSERT INTO md_crm_eventos 
        (oportunidade_id, created_at, etapa_id, usuario_id, descricao, acao, acao_data, acao_status, status) VALUES ('$oportunidade_id', '$pedido_conv_date', '$etapa_atual', '$usuario_codigo', '$desc_evento', '', '', '', 'A')";
        $result_evento_opt = mysql_query("$query_evento_opt") or die(mysql_error());
        
    }
}

echo $result;
