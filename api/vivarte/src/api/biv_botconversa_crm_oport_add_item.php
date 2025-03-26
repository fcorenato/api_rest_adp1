<?php
/* RECEBENDO NOVA OPORTUNIDADE VINDO DO BOTCONVERSA */

$api_protheus_status = 'on';

if ($api_protheus_status == 'on') {
    // API - RECEBENDO DADOS DO BOTCONVERSA 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        header("Access-Control-Allow-Origin: *");
        header('Cache-Control: no-cache, must-revalidate');
        header("Content-Type: text/plain; charset=UTF-8");
        header("HTTP/1.1 200 OK");

        //funcao para verificar se recebido json valido 
        function isValidJSON($str)
        {
            json_decode($str);
            return json_last_error() == JSON_ERROR_NONE;
        }

        //recebendo json via Post enviado pelo BLING
        $json_params = file_get_contents("php://input");

        if (strlen($json_params) > 0 && isValidJSON($json_params)) {
            $dados = json_decode($json_params);

            // echo '{
            //     "status": "ok",
            //       }';

            //pesquisand referencia recebida
            $ref_recebida = $dados->ref;
            if ($ref_recebida != '' and $dados->idbotconversa) {
                //conectando banco
                require('../config/conexao.php');

                //pesquisando o codigo da oportunidade
                if ($dados->idbotconversa > 0) {
                    //pesquisando revendsa
                    $query1 = "SELECT id, etapa_atual_id FROM `md_crm_oportunidade` WHERE `botconversa_id` =  '$dados->idbotconversa' and status = 'A' ";

                    $result_query1 = mysql_query($query1)  or die(mysql_error());
                    $qtde_query1 = mysql_num_rows($result_query1);

                    if ($qtde_query1 == 0) {
                       echo 'Oportunidade não encontrada';
                    } else {
                        while ($campos = mysql_fetch_array($result_query1)) {
                            $oportunidade_id = $campos['id'];
                            $etapa_atual = $campos['etapa_atual_id'];
                        }
                    }
                    $desc_evento .= '<i class="fas fa-caret-right"></i> Oportunidade encaminhado para revenda ' . $revenda . ' - ' . $desc_revenda . '<br>';
                }

                //Pesquisando dados e preço do produto
                $sql1 = "SELECT pd.*, pi.preco_venda FROM `md_cad_produtos` as pd
                        LEFT JOIN md_vendas_tabpreco_itens as pi ON pi.referencia = pd.referencia
                        WHERE pd.referencia = '$ref_recebida'
                        AND pi.codigo_tabela = '236'";
                $pesquisa = mysql_query($sql1)  or die(mysql_error());
                $linhas = mysql_num_rows($pesquisa);

                if ($linhas > 0) {
                    $dados = mysql_fetch_array($pesquisa);

                    // $oportunidade_id = 1533;
                    $d_ref = $dados["referencia"];
                    $d_desc = $dados["descricao"];
                    $d_unidade = $dados["unidade"];
                    $d_qtde_cx = $dados["qtde_cx"];
                    $d_qtde = 1;
                    $qtde_frac = 'N';
                    $d_ptc_tab = $dados["preco_venda"];
                    $d_desconto = 0;
                    $d_ipi = $dados["ipi"];
                    $d_peso = $dados["peso"];
                    $d_ipi = $dados["ipi"];
                    $d_ipi = $dados["ipi"];

                    $created_at = date("Y-m-d H:i:s");

                    $sql_inserir_itens = "INSERT INTO md_crm_oportunidade_itens (created_at, updated_at, oportunidade_id, codigo, descricao, unidade, qtde_cx, qtde_frac, qtde, prc_tab, desconto, ipi, peso, data_prev_fatura, obs,  status ) VALUES ('$created_at', '$created_at', '$oportunidade_id', '$d_ref', '$d_desc', '$d_unidade', '$d_qtde_cx', '$qtde_frac', '$d_qtde', '$d_ptc_tab', '$d_desconto', '$d_ipi', '$d_peso', '', '', 'A')";
                    $result_item = mysql_query($sql_inserir_itens) or die(mysql_error());

                    if ($sql_inserir_itens) {
                        //Registrando evento ================================
                        $usuario_nome = "BotConversa";
                        $usuario_codigo = 0;

                        $desc_evento = '<i class="fas fa-caret-right"></i> Item Incluido: ' . $d_ref . ' Qtde: 1<br>';

                        $query_evento = "INSERT INTO md_crm_eventos 
                        (oportunidade_id, created_at, etapa_id, usuario_id, descricao, acao, acao_data, acao_status, status) VALUES ('$oportunidade_id', '$created_at', '$etapa_atual', '$usuario_codigo', '$desc_evento', '', '', '', 'A')";

                        $result_evento = mysql_query("$query_evento") or die(mysql_error());
                    }
                    if ($d_ipi > 0) {
                        $d_ptc_tab = $d_ptc_tab + ($d_ptc_tab * $d_ipi /100);
                    }
                    $resp_preco = number_format($d_ptc_tab, 2, ',', '.');
                    $resp_texto = "O produto $d_ref - $d_desc custa a partir de R$ $resp_preco ($d_unidade)";

                    //retorno para API
                    echo '{
                            "status" : "ok",
                            "resp_texto": "' . $resp_texto . '"
                            }';
                  
                } else {

                    echo $ref_recebida . ' nao encontrado na base de dados e tabela 236';
                }
            } else {

                echo 'Referencia não enviada pelo Botconversa';
            }

            //Enviando email: Oportunindade via BOTConversa ================
            $data = date("d/m/Y");
            $nome = 'Sistema Biv - BOT Conversa';
            $email_from = 'biv@vetromani.com.br';

            $msg = "
                    <h3>Bot Conversa: Item adicionado a oportunidade</h3>
                    Nome: $dados->nome <br />
                    telefone: $dados->telefone <br />
                    idbotconversa: $dados->idbotconversa <br />
                    ref: $dados->ref <br />
                    ";

            $emailenviar = "renato@vetromani.com.br,fco.renatogomes@gmail.com";
            $destino = $emailenviar;
            $assunto = "BIV - BotConversa: Item adicionado a Oportunidade.";

            // É necessário indicar que o formato do e-mail é html
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: BIV Vetromani <' . $email_from . '>';
            //$headers .= "Bcc: $EmailPadrao\r\n";

            $enviaremail = mail($destino, $assunto, $msg, $headers);
            //enviando email: Oportunindade via BOTConversa ================


        } else {
            echo '{
                "status": "err"
                }';
        }
    } // fim do POST
}
