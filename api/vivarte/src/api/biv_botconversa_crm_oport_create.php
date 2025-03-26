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

            echo '{
                "status": "ok",
                "nome": "' . $dados->nome . '",
                "telefone": "' . $dados->telefone . '",
                "campanha": "' . $dados->campanha . '",
                "ddd": "' . $dados->ddd . '"
                "atendente": "' . $dados->atendente . '"
                }';

            //INCLUINDO OPORTUNIDADE NO BIV
            $incluiOptBiv = 1;
            if ($incluiOptBiv) {
                function protect(&$str)
                {
                    /*** Função para retornar uma string/Array protegidos contra SQL/Blind/XSS Injection*/
                    if (!is_array($str)) {
                        $str = preg_replace('/(from|select|insert|delete|where|drop|union|order|update|database)/i', '', $str);
                        $str = preg_replace('/(&lt;|<)?script(\/?(&gt;|>(.*))?)/i', '', $str);
                        $tbl = get_html_translation_table(HTML_ENTITIES);
                        $tbl = array_flip($tbl);
                        $str = addslashes($str);
                        $str = strip_tags($str);
                        return strtr($str, $tbl);
                    } else {
                        return array_filter($str, "protect");
                    }
                }
                $ddds = array(
                    "68" => "AC",
                    "82" => "AL",
                    "96" => "AP",
                    "92" => "AM",
                    "97" => "AM",
                    "71" => "BA",
                    "73" => "BA",
                    "74" => "BA",
                    "75" => "BA",
                    "77" => "BA",
                    "85" => "CE",
                    "88" => "CE",
                    "61" => "DF",
                    "27" => "ES",
                    "28" => "ES",
                    "62" => "GO",
                    "64" => "GO",
                    "98" => "MA",
                    "99" => "MA",
                    "65" => "MT",
                    "66" => "MT",
                    "67" => "MT",
                    "31" => "MG",
                    "32" => "MG",
                    "33" => "MG",
                    "34" => "MG",
                    "35" => "MG",
                    "37" => "MG",
                    "38" => "MG",
                    "91" => "PA",
                    "93" => "PA",
                    "94" => "PA",
                    "83" => "PB",
                    "41" => "PR",
                    "42" => "PR",
                    "43" => "PR",
                    "44" => "PR",
                    "45" => "PR",
                    "46" => "PR",
                    "81" => "PE",
                    "87" => "PE",
                    "86" => "PI",
                    "89" => "PI",
                    "21" => "RJ",
                    "22" => "RJ",
                    "24" => "RJ",
                    "84" => "RN",
                    "51" => "RS",
                    "53" => "RS",
                    "54" => "RS",
                    "69" => "RO",
                    "95" => "RR",
                    "47" => "SC",
                    "48" => "SC",
                    "11" => "SP",
                    "12" => "SP",
                    "13" => "SP",
                    "14" => "SP",
                    "15" => "SP",
                    "16" => "SP",
                    "17" => "SP",
                    "18" => "SP",
                    "19" => "SP",
                    "79" => "SE",
                    "63" => "TO"
                );

                //definindo sem campanha fabrica ou showroom
                if ($dados->campanha > 0) {
                    $campanha = $dados->campanha;
                } else if($dados->unidade == 'SHOWROOM') {
                    $campanha = 30;
                } else {
                    $campanha = 1;
                }
    
    
                $vendedor = $dados->atendente; //se 1 = marcelo 2=cecilia
                $tipo = 1;
                $funil = 1;
                $etapa_incial = 1;
                $data_inicio = date("Y-m-d");
                $valor = 1;
                $nome = strtoupper(protect($dados->nome));
                $email = '';
                $telefone = strtoupper(protect($dados->telefone));
                $uf = $ddds[$dados->ddd];
                $fonte = 1;
                $etapa_atual = 1;
                $tabela = 0;
                $botconversa_id = $dados->idbotconversa;
                $created_at = date("Y-m-d H:i:s");
                $updated_at = date("Y-m-d H:i:s");
                $status_oportunidade = 'A';
                $motivo_perda = 0;
                $revenda = 0;
    
                // //salvando
                require('../config/conexao.php');
    
    
                $query1 = "INSERT INTO `md_crm_oportunidade` 
                    (`id`, `vendedor_id`, `tipo_id`, `funil_id`, `etapa_inicial_id`, `fonte_id`, `campanha_id`, `data_inicio`, `valor`, `nome`, `email`, `telefone`, `uf`, `etapa_atual_id`, `create_at`, `update_at`, `motivo_perda_id`, `revenda_id`, `botconversa_id`,  `status`) VALUES 
                    (NULL, '$vendedor', '$tipo', ' $funil', '$etapa_incial', '$fonte', '$campanha ', '$data_inicio', ' $valor', '$nome', '$email', '$telefone', '$uf', '$etapa_atual', '  $created_at', '$updated_at', '$motivo_perda', '$revenda', '$botconversa_id', '$status_oportunidade')";
    
                $result = mysql_query($query1) or die(mysql_error());
    
                //pegando codigo id inserido
                $oportunidade_id = mysql_insert_id();
                

                //Registrando evento ================================
                $usuario_nome = "BotConversa";
                $usuario_codigo = 0;
                $desc_evento = '<i class="fas fa-caret-right"></i> Oportunidade cadastrada por ' . $usuario_nome . '<br>';
                
                $query_evento = "INSERT INTO md_crm_eventos 
                (oportunidade_id, created_at, etapa_id, usuario_id, descricao, acao, acao_data, acao_status, status) VALUES ('$oportunidade_id', '$created_at', '$etapa_atual', '$usuario_codigo', '$desc_evento', '', '', '', 'A')";
    
                $result_evento = mysql_query("$query_evento") or die(mysql_error());
    
            }
            



            //Enviando email: Oportunindade via BOTConversa ================
            $data = date("d/m/Y");
            $nome = 'Sistema Biv - BOT Conversa';
            $email_from = 'biv@vetromani.com.br';

            $msg = "
                    <h3>Bot Conversa:</h3>
                    Nome: $dados->nome <br />
                    telefone: $dados->telefone <br />
                    Campanha: $dados->campanha <br />
                    ddd: $dados->ddd <br />
                    atendente: $dados->atendente <br />
                    idbotconversa: $dados->idbotconversa <br />
                    ";

            $emailenviar = "renato@vetromani.com.br,fco.renatogomes@gmail.com";
            $destino = $emailenviar;
            $assunto = "BIV - BotConversa: Nova Oportunidade.";

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
