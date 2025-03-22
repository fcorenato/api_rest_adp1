<?php
// require('../config/SUsuario.php');
$api_bling_op == 'off';
if ($api_bling_op == 'on') {
    $apikey = '3f254d9c3055473dbec4679632239d9c470d6c25a0505c95ddd57bcdad3201af40007953';
    $outputType = "json";

    $api_pagina = 0;
    $bling_api_cod_erro = 0;

    while ($bling_api_cod_erro == 0) {
        $api_pagina++;


        //inicializando CURL =================================================================
        $url = 'https://bling.com.br/Api/v2/ordensproducao/page=' . $api_pagina . '/'  . $outputType;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . '/&apikey=' . $apikey,
            CURLOPT_RETURNTRANSFER => true,
        ));
        $retorno = curl_exec($curl);
        curl_close($curl);
        //finalizando CURL ====================================================================

        $resultado = json_decode($retorno);

        if ($resultado->retorno->erros) {
            $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
            $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
            //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
        } else {

            foreach ($resultado->retorno->ordensproducao as $ops) {
                $op_num = $ops->numero;
                $op_situacao = $ops->situacao;
                $deposito_dest = $ops->idDepositoDestino;
                //11919578899 = VH - PROD ACABADO  e  1462456848 = VC - PROD ACABADO
                if (($op_situacao != 'Finalizado' and $op_situacao != 'Finalizado parcial') and ($deposito_dest == '1462456848' or $deposito_dest == '11919578899')) {
                    foreach ($ops->itens as $it) {
                        $op_ref = trim($it->codigoProduto);
                        $op_qtde = $it->quantidade;
                        $op_qtde =  str_replace(",", ".", str_replace(".", "", $op_qtde));
                    }

                    $op_array[] = array(
                        'op_ref' => $op_ref,
                        'op_num' => $op_num,
                        'op_situacao' => $op_situacao,
                        'op_qtde' => $op_qtde,
                        'op_qtde_atu' => $op_qtde,
                        'op_previsaoFinal' => $ops->previsaoFinal,
                        'deposito_destino' => $deposito_dest
                    );
                }
            }
        }
        usleep(400000);
    }
    //print("<pre>" . print_r($op_array, true) . "</pre>");
}
