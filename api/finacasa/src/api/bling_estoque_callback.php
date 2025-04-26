<?php

$api_bling_status = 'on';

if ($api_bling_status == 'on') {
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

        //id do deposito a ser importado
        //$id_desposito_de_consulta = '5768190547';

        //recebendo json via Post enviado pelo BLING
        $json_params = file_get_contents("php://input");
        //removendo texto "data=" que esta vindo no incio do arquivo tornando invalido o layout do json
        $json_params = str_replace("data=", "", $json_params);

        //salvando o json recebido pra analise
        //removendo ref com saldo antigo, caso exista.
        $remove_ref_saldo_antigo = mysql_query("DELETE FROM `md_estoque_bling` WHERE deposito = 'manual'") or die(mysql_error());


        $json_receive_at = date("Y-m-d H:i:s");
        //$result = mysql_query("INSERT INTO md_estoque_bling (referencia, descricao, un, deposito, saldo, json) VALUES ('api', 'json recebido - $json_receive_at', 'm2', 'Manual','0', '$json_params')") or die(mysql_error());

        if (strlen($json_params) > 0 && isValidJSON($json_params)) {
            $dados = json_decode($json_params);
            // referencia recebid
            $ref_prod = $dados->retorno->estoques[0]->estoque->codigo;
            $ref_desc = $dados->retorno->estoques[0]->estoque->nome;

            //removendo ref com saldo antigo, caso exista.
            $remove_ref_saldo_antigo = mysql_query("DELETE FROM `md_estoque_bling` WHERE referencia = '$ref_prod'") or die(mysql_error());


            //percorrendo os depositos para indenticar saldo do id_desposito_de_consulta
            foreach ($dados->retorno->estoques[0]->estoque->depositos as $dep) {
                $deposito_id = $dep->deposito->id;
                $deposito_nome = $dep->deposito->nome;

                //->saldo = saldo sem atender pedidos na carteira bling
                //->saldoVirtual = saldo com pedidos atendindos no bling
                $saldo = $dep->deposito->saldo;
                $saldo_disp = $dep->deposito->saldoVirtual;

                //incluindo novo saldo deposito VH - PROD ACABADO
                //if ($deposito_id == '11919578899') {
                $result = mysql_query("INSERT INTO md_estoque_bling (referencia, descricao, un, deposito_id, deposito, saldo, saldo_disp, update_at) VALUES ('$ref_prod', '$ref_desc', ' ', '$deposito_id', '$deposito_nome', '$saldo','$saldo_disp','$json_receive_at')") or die(mysql_error());
                //}

            }
        } else {
            $dados = file_get_contents("php://input");
            $result = mysql_query("INSERT INTO md_estoque_bling (referencia, descricao, un, deposito, saldo, json) VALUES ('api', 'ERRO! - json recebido - $json_receive_at', 'm2', 'Manual','0', '$json_params')") or die(mysql_error());
        }
    } // fim do POST
}
