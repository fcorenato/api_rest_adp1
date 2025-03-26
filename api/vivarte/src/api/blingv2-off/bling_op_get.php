<?php
// ====================================== ACESSANDO VIVARTE ==================================================

$apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
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

                $op_array_api['VI-' . $op_num] = array(
                    'op_ref' => $op_ref,
                    'op_num' => 'VI-' . $op_num,
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

// ====================================== ACESSANDO AGAS ==================================================

$apikey = "7d0dc1fce7ece5e83815bcd73e97122777c6941f9238e72457d7a633bf7e82d03726ad86";
$outputType = "json";

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/ordensproducao/page=' . $api_pagina . '/'  . $outputType;
    // echo $url;
    // echo '<hr>';
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url . '/&apikey=' . $apikey,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);
    // print("<pre>" . print_r($resultado, true) . "</pre>");

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
            if (($op_situacao != 'Finalizado' and $op_situacao != 'Finalizado parcial') and ($deposito_dest == '14886856259')) {
                foreach ($ops->itens as $it) {
                    $op_ref = trim($it->codigoProduto);
                    $op_qtde = $it->quantidade;
                    $op_qtde =  str_replace(",", ".", str_replace(".", "", $op_qtde));
                }

                $op_array_api['AG-' . $op_num] = array(
                    'op_ref' => $op_ref,
                    'op_num' => 'AG-' . $op_num,
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

function ordenarArrayPorPrevisaoFinal($array)
{
    usort($array, function ($a, $b) {
        $dataA = DateTime::createFromFormat('d/m/Y H:i:s', $a['op_previsaoFinal']);
        $dataB = DateTime::createFromFormat('d/m/Y H:i:s', $b['op_previsaoFinal']);

        if ($dataA == $dataB) {
            return 0;
        }

        return ($dataA < $dataB) ? -1 : 1;
    });

    return $array;
}
$op_array = ordenarArrayPorPrevisaoFinal($op_array_api);

//print("<pre>" . print_r($op_array, true) . "</pre>");
