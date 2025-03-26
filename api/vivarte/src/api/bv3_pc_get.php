<?php

//ATENCAO: Como o endpoint nao permite pesquisar mais de uma situação ao mesmo tempo é necessário pesquisar duas vezes. Um pelos pedidos em aberto, depois pelos pedidos em andamanento

//inicio do cronometro
$inicio_pc_get = microtime(true);


// ====================================== ACESSANDO VIVARTE ==================================================
//PEDIDOS EM ABERTO
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
$qte_pedidod = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 28 = em aberto | 31 = atendido | 34 = cancelado | 37 = em andamento

    $url = "https://api.bling.com.br/Api/v3/pedidos/compras?pagina=$api_pagina&idSituacao=28";

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
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pc_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $pcs) {
                $pc_id = $pcs->id;
                $pc_num = $pcs->numero;
                // echo $op_num . '<br>';
                $qte_pedidod++;


                //buscar produtos do pedido de compra:
                //inicializando CURL =================================================================
                $url = "https://api.bling.com.br/Api/v3/pedidos/compras/$pc_id";
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
                // print("<pre>" . print_r($resultado2, true) . "</pre>");

                if ($resultado2->error) {
                    $msg = 'Erro api bling v3 (bv3_pc_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                    botc_enviar($msg);

                    // echo $msg;
                } else {

                    foreach ($resultado2->data->itens as $item) {
                        $pc_array_api[] = array(
                            'pc_ref' => trim($item->produto->codigo),
                            'pc_ref_um' => trim($item->unidade),
                            'pc_num' =>  'VI-' . $resultado2->data->numero,
                            'pc_qtde' => $item->quantidade,
                            'pc_qtde_atu' => $item->quantidade,
                            'pc_emissao' => $resultado2->data->data,
                            'pc_previsao' => date("d/m/Y", strtotime($resultado2->data->dataPrevista)),
                            'pc_fornecedor' => $resultado2->data->fornecedor->id,
                            'pc_ordemcompra' => $resultado2->data->ordemCompra

                        );
                    }
                }
                usleep(200000);
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(200000);
}

// ====================================== ACESSANDO VIVARTE ==================================================
//PEDIDOS EM ANDAMENTO
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 28 = em aberto | 31 = atendido | 34 = cancelado | 37 = em andamento

    $url = "https://api.bling.com.br/Api/v3/pedidos/compras?pagina=$api_pagina&idSituacao=37";

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
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pc_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $pcs) {
                $pc_id = $pcs->id;
                $pc_num = $pcs->numero;
                // echo $op_num . '<br>';
                $qte_pedidod++;

                //buscar produtos do pedido de compra:
                //inicializando CURL =================================================================
                $url = "https://api.bling.com.br/Api/v3/pedidos/compras/$pc_id";
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
                // print("<pre>" . print_r($resultado2, true) . "</pre>");

                if ($resultado2->error) {
                    $msg = 'Erro api bling v3 (bv3_pc_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                    botc_enviar($msg);

                    // echo $msg;
                } else {

                    foreach ($resultado2->data->itens as $item) {
                        $pc_array_api[] = array(
                            'pc_ref' => trim($item->produto->codigo),
                            'pc_ref_um' => trim($item->unidade),
                            'pc_num' =>  'VI-' . $resultado2->data->numero,
                            'pc_qtde' => $item->quantidade,
                            'pc_qtde_atu' => $item->quantidade,
                            'pc_emissao' => $resultado2->data->data,
                            'pc_previsao' => date("d/m/Y", strtotime($resultado2->data->dataPrevista)),
                            'pc_fornecedor' => $resultado2->data->fornecedor->id,
                            'pc_ordemcompra' => $resultado2->data->ordemCompra

                        );
                    }
                }
                usleep(200000);
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(200000);
}

// ====================================== ACESSANDO AGAS ==================================================
//PEDIDOS EM ABERTO
include('bv3_get_token_agas.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 28 = em aberto | 31 = atendido | 34 = cancelado | 37 = em andamento

    $url = "https://api.bling.com.br/Api/v3/pedidos/compras?pagina=$api_pagina&idSituacao=28";

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
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pc_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $pcs) {
                $pc_id = $pcs->id;
                $pc_num = $pcs->numero;
                // echo $op_num . '<br>';
                $qte_pedidod++;

                //buscar produtos do pedido de compra:
                //inicializando CURL =================================================================
                $url = "https://api.bling.com.br/Api/v3/pedidos/compras/$pc_id";
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
                // print("<pre>" . print_r($resultado2, true) . "</pre>");

                if ($resultado2->error) {
                    $msg = 'Erro api bling v3 (bv3_pc_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                    botc_enviar($msg);

                    // echo $msg;
                } else {

                    foreach ($resultado2->data->itens as $item) {
                        $pc_array_api[] = array(
                            'pc_ref' => trim($item->produto->codigo),
                            'pc_ref_um' => trim($item->unidade),
                            'pc_num' =>  'AG-' . $resultado2->data->numero,
                            'pc_qtde' => $item->quantidade,
                            'pc_qtde_atu' => $item->quantidade,
                            'pc_emissao' => $resultado2->data->data,
                            'pc_previsao' => date("d/m/Y", strtotime($resultado2->data->dataPrevista)),
                            'pc_fornecedor' => $resultado2->data->fornecedor->id,
                            'pc_ordemcompra' => $resultado2->data->ordemCompra

                        );
                    }
                }
                usleep(200000);
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(200000);
}

// ====================================== ACESSANDO AGAS ==================================================
//PEDIDOS EM ANDAMENTO
include('bv3_get_token_agas.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 28 = em aberto | 31 = atendido | 34 = cancelado | 37 = em andamento

    $url = "https://api.bling.com.br/Api/v3/pedidos/compras?pagina=$api_pagina&idSituacao=37";

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
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pc_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $pcs) {
                $pc_id = $pcs->id;
                $pc_num = $pcs->numero;
                // echo $op_num . '<br>';
                $qte_pedidod++;

                //buscar produtos do pedido de compra:
                //inicializando CURL =================================================================
                $url = "https://api.bling.com.br/Api/v3/pedidos/compras/$pc_id";
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
                // print("<pre>" . print_r($resultado2, true) . "</pre>");

                if ($resultado2->error) {
                    $msg = 'Erro api bling v3 (bv3_pc_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                    botc_enviar($msg);

                    // echo $msg;
                } else {

                    foreach ($resultado2->data->itens as $item) {
                        $pc_array_api[] = array(
                            'pc_ref' => trim($item->produto->codigo),
                            'pc_ref_um' => trim($item->unidade),
                            'pc_num' =>  'AG-' . $resultado2->data->numero,
                            'pc_qtde' => $item->quantidade,
                            'pc_qtde_atu' => $item->quantidade,
                            'pc_emissao' => $resultado2->data->data,
                            'pc_previsao' => date("d/m/Y", strtotime($resultado2->data->dataPrevista)),
                            'pc_fornecedor' => $resultado2->data->fornecedor->id,
                            'pc_ordemcompra' => $resultado2->data->ordemCompra

                        );
                    }
                }
                usleep(200000);
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(200000);
}


function ordenarArrayPorPrevisao($array)
{
    usort($array, function ($a, $b) {
        $dataA = DateTime::createFromFormat('d/m/Y', $a['pc_previsao']);
        $dataB = DateTime::createFromFormat('d/m/Y', $b['pc_previsao']);

        if ($dataA == $dataB) {
            return 0;
        }

        return ($dataA < $dataB) ? -1 : 1;
    });

    return $array;
}
// echo "<hr>Qtde pedidod = $qte_pedidod <hr>";
$pc_array = ordenarArrayPorPrevisao($pc_array_api);
    // print("<pre>" . print_r($pc_array, true) . "</pre>");

//fim do cronometro
$fim_pc_get = microtime(true);
$tempoExecucao_pc_get = $fim_pc_get - $inicio_pc_get;
printf("<hr>O script PC_GET levou %f segundos para finalizar.\n", $tempoExecucao_pc_get);
