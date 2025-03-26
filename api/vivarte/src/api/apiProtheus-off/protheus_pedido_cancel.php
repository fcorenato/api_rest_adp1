<?php
/* PEDIDO CANCELADO NO PROTHEUS (ELIMINADO RESIDUO) */

$api_protheus_status = 'on';

if ($api_protheus_status == 'on') {
    // API - RECEBENDO DO BLING ESTOQUE DO PRODUTO DO ARMAZEM HORIZONTE 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require('../config/conexao.php');

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
            $cod_orc = trim($dados->orcamento);
            $cod_ped = $dados->pedido;
            $filial = $dados->filial;
            $data_hoje = date("Y-m-d H:i:s");
            //echo '<pre>' . print_r(json_decode($json_params), true) . '</pre>';

            //cancelado orçameto
            $upd = mysql_query("UPDATE md_vendas_pedidos SET status='C' WHERE id = $cod_orc");

            //commit
            if ($upd) {
                //registrando evento
                $evento_ped_insert = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento, descricao) VALUES ('$cod_orc', '$data_hoje', 0, 'Cancelado-P', 'Orcamento e Pedido cancelado no Protheus')") or die(mysql_error());

                //salvando arquivo txt no log api
                $conteudo = $json_params;
                $fp = fopen("log/orc-cancelado-$cod_orc.txt","wb");
                fwrite($fp, $conteudo);
                fclose($fp);

                echo '200';
            } else {
                echo '400';
            }
        }
    } // fim do POST
}
