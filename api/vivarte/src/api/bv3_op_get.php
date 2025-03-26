<?php
//inicio do cronometro
$inicio_op_get = microtime(true);
// ====================================== ACESSANDO VIVARTE ==================================================
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 18158 = Não iniciado | 18159 = Em produção | 18160 = Pausado
    $url = "https://api.bling.com.br/Api/v3/ordens-producao?pagina=$api_pagina&idsSituacoes[]=18158&idsSituacoes[]=18159&idsSituacoes[]=18160";

    // echo $url . '<hr>';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer ' . $token . '',
            'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
        ),
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_op_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $ops) {
                $op_id = $ops->id;
                $op_num = $ops->numero;
                // echo $op_num . '<br>';
                $op_situacao = $ops->situacao->nome;
                $dataPrevisaoFinal = $ops->dataPrevisaoFinal;
                $deposito_dest = $ops->deposito->idDestino;

                //11919578899 = VH - PROD ACABADO  e  1462456848 = VC - PROD ACABADO
                if (($op_situacao != 'Finalizado' and $op_situacao != 'Finalizado parcial' and $op_situacao != 'Cancelado') and ($deposito_dest == '1462456848' or $deposito_dest == '11919578899')) {

                    //buscar produtos da ordem:
                    //inicializando CURL =================================================================
                    $url = "https://api.bling.com.br/Api/v3/ordens-producao/$op_id";
                    // echo $url . '<hr>';

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => array(
                            'Accept: application/json',
                            'Authorization: Bearer ' . $token . '',
                            'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
                        ),
                    ));
                    $retorno2 = curl_exec($curl);
                    curl_close($curl);
                    //finalizando CURL ====================================================================

                    $resultado2 = json_decode($retorno2);

                    if ($resultado2->error) {
                        $msg = 'Erro api bling v3 (bv3_op_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                        botc_enviar($msg);

                        // echo $msg;
                    } else {
                        foreach ($resultado2->data->itens as $it) {
                            $op_ref = trim($it->produto->codigo);
                            $op_qtde = $it->quantidade;
                            // $op_qtde =  str_replace(",", ".", str_replace(".", "", $op_qtde));
                        }

                        

                        $op_array_api['VI-' . $op_num] = array(
                            'op_ref' => $op_ref,
                            'op_num' => 'VI-' . $op_num,
                            'op_situacao' => $op_situacao,
                            'op_qtde' => $op_qtde,
                            'op_qtde_atu' => $op_qtde,
                            'op_previsaoFinal' => date("d/m/Y", strtotime($dataPrevisaoFinal)),
                            'deposito_destino' => $deposito_dest
                        );
                    }
                }
            }
        } else {
            $bling_api_cod_erro = 1;
        }
        usleep(200000);
    }
}

// ====================================== ACESSANDO AGAS ==================================================
include('bv3_get_token_agas.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 18158 = Não iniciado | 18159 = Em produção | 18160 = Pausado
    $url = "https://api.bling.com.br/Api/v3/ordens-producao?pagina=$api_pagina&idsSituacoes[]=18158&idsSituacoes[]=18159&idsSituacoes[]=18160";

    // echo $url . '<hr>';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer ' . $token . '',
            'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
        ),
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_op_get.php BA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $ops) {
                $op_id = $ops->id;
                $op_num = $ops->numero;
                // echo $op_num . '<br>';
                $op_situacao = $ops->situacao->nome;
                $dataPrevisaoFinal = $ops->dataPrevisaoFinal;
                $deposito_dest = $ops->deposito->idDestino;

                //11919578899 = VH - PROD ACABADO  e  1462456848 = VC - PROD ACABADO
                if (($op_situacao != 'Finalizado' and $op_situacao != 'Finalizado parcial' and $op_situacao != 'Cancelado') and ($deposito_dest == '14886856259')) {

                    //buscar produtos da ordem:
                    //inicializando CURL =================================================================
                    $url = "https://api.bling.com.br/Api/v3/ordens-producao/$op_id";
                    // echo $url . '<hr>';

                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => array(
                            'Accept: application/json',
                            'Authorization: Bearer ' . $token . '',
                            'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
                        ),
                    ));
                    $retorno2 = curl_exec($curl);
                    curl_close($curl);
                    //finalizando CURL ====================================================================

                    $resultado2 = json_decode($retorno2);

                    if ($resultado2->error) {
                        $msg = 'Erro api bling v3 (bv3_op_get.php BB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                        botc_enviar($msg);

                        // echo $msg;
                    } else {
                        foreach ($resultado2->data->itens as $it) {
                            $op_ref = trim($it->produto->codigo);
                            $op_qtde = $it->quantidade;
                            // $op_qtde =  str_replace(",", ".", str_replace(".", "", $op_qtde));
                        }

                        $op_array_api['AG-' . $op_num] = array(
                            'op_ref' => $op_ref,
                            'op_num' => 'AG-' . $op_num,
                            'op_situacao' => $op_situacao,
                            'op_qtde' => $op_qtde,
                            'op_qtde_atu' => $op_qtde,
                            'op_previsaoFinal' => date("d/m/Y", strtotime($dataPrevisaoFinal)),
                            'deposito_destino' => $deposito_dest
                        );
                    }
                }
            }
        } else {
            $bling_api_cod_erro = 1;
        }
        usleep(200000);
    }
}


// function ordenarArrayPorPrevisaoFinal($array)
// {
//     usort($array, function ($a, $b) {
//         $dataA = DateTime::createFromFormat('d/m/Y H:i:s', $a['op_previsaoFinal']);
//         $dataB = DateTime::createFromFormat('d/m/Y H:i:s', $b['op_previsaoFinal']);

//         if ($dataA == $dataB) {
//             return 0;
//         }

//         return ($dataA > $dataB) ? -1 : 1;
//     });

//     return $array;
// }
// $op_array = ordenarArrayPorPrevisaoFinal($op_array_api);

// Função para comparar duas datas no formato DD/MM/YYYY
function ordenarDataCrescente($a, $b)
{
    $dataA = strtotime(str_replace('/', '-', $a['op_previsaoFinal']));
    $dataB = strtotime(str_replace('/', '-', $b['op_previsaoFinal']));
    return $dataA - $dataB;
}
// Ordena o array usando a função compararDatas
usort($op_array_api, 'ordenarDataCrescente');
$op_array = $op_array_api;

// print("<pre>" . print_r($op_array, true) . "</pre>");
//fim do cronometro
$fim_op_get = microtime(true);
$tempoExecucao_op_get = $fim_op_get - $inicio_op_get;
printf("<hr>O script OP_GET levou %f segundos para finalizar.\n", $tempoExecucao_op_get);

