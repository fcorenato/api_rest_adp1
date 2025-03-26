<?php
$apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
$outputType = "json";

//filtros
/*
    codigo statua pedido no bling em (24/ago/21)
    0   aberto
    1	Atendido
    3   Andamento
*/

// Filtros pedidos em andamento

$status_pedido = 'situacao[3]';
$filtro_pesq = 'filters=' . $status_pedido;

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0 or $api_pagina > 10) {
    $api_pagina++;
    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/pedidoscompra/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;
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
        $api_qtde_pc = 0;
        $api_qtde_itens_pc = 0;
        foreach ($resultado->retorno->pedidoscompra[0] as $pc) {
            $api_qtde_pc++;
     
            foreach ($pc->pedidocompra->itens as $item) {
                $api_qtde_itens_pc++;
                //if ($pc->pedidocompra->fornecedor->cpfcnpj == "48.061.210/0001-16") {
                    $pc_array_api[] = array(
                        'pc_ref' => trim($item->item->codigo),
                        'pc_ref_um' => trim($item->item->un),
                        'pc_num' =>  'VI-'.$pc->pedidocompra->numeropedido,
                        'pc_qtde' => $item->item->qtde,
                        'pc_qtde_atu' => $item->item->qtde,
                        'pc_emissao' => $pc->pedidocompra->datacompra,
                        'pc_previsao' => date("d/m/Y", strtotime($pc->pedidocompra->dataprevista)),
                        'pc_fornecedor' => $pc->pedidocompra->fornecedor->nome,
                        'pc_ordemcompra' => $pc->pedidocompra->ordemcompra
               
                    );
                //}
            }

        }
        
    }
    usleep( 400000 );
}

// Filtros pedidos em aberto  pois a api nao permite filtrar os dois status num unico comando

$status_pedido = 'situacao[0]';
$filtro_pesq = 'filters=' . $status_pedido;

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0 or $api_pagina > 10) {
    $api_pagina++;
    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/pedidoscompra/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;
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
        $api_qtde_pc = 0;
        $api_qtde_itens_pc = 0;
        foreach ($resultado->retorno->pedidoscompra[0] as $pc) {
            $api_qtde_pc++;
     
            foreach ($pc->pedidocompra->itens as $item) {
                $api_qtde_itens_pc++;
                //if ($pc->pedidocompra->fornecedor->cpfcnpj == "48.061.210/0001-16") {
                    $pc_array_api[] = array(
                        'pc_ref' => trim($item->item->codigo),
                        'pc_ref_um' => trim($item->item->un),
                        'pc_num' =>  'VI-'.$pc->pedidocompra->numeropedido,
                        'pc_qtde' => $item->item->qtde,
                        'pc_qtde_atu' => $item->item->qtde,
                        'pc_emissao' => $pc->pedidocompra->datacompra,
                        'pc_previsao' => date("d/m/Y", strtotime($pc->pedidocompra->dataprevista)),
                        'pc_fornecedor' => $pc->pedidocompra->fornecedor->nome,
                        'pc_ordemcompra' => $pc->pedidocompra->ordemcompra
               
                    );
                //}
            }

        }
        
    }
    usleep( 400000 );
}

// ====================================== ACESSANDO AGAS ==================================================

$apikey = "7d0dc1fce7ece5e83815bcd73e97122777c6941f9238e72457d7a633bf7e82d03726ad86";
$outputType = "json";


//filtros
/*
    codigo statua pedido no bling em (24/ago/21)
    0   aberto
    1	Atendido
    3   Andamento
*/

// Filtros pedidos em andamento

$status_pedido = 'situacao[3]';
$filtro_pesq = 'filters=' . $status_pedido;

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0 or $api_pagina > 10) {
    $api_pagina++;
    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/pedidoscompra/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;
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
        $api_qtde_pc = 0;
        $api_qtde_itens_pc = 0;
        foreach ($resultado->retorno->pedidoscompra[0] as $pc) {
            $api_qtde_pc++;
     
            foreach ($pc->pedidocompra->itens as $item) {
                $api_qtde_itens_pc++;
                //if ($pc->pedidocompra->fornecedor->cpfcnpj == "48.061.210/0001-16") {
                    $pc_array_api[] = array(
                        'pc_ref' => trim($item->item->codigo),
                        'pc_ref_um' => trim($item->item->un),
                        'pc_num' =>  'AG-'.$pc->pedidocompra->numeropedido,
                        'pc_qtde' => $item->item->qtde,
                        'pc_qtde_atu' => $item->item->qtde,
                        'pc_emissao' => $pc->pedidocompra->datacompra,
                        'pc_previsao' => date("d/m/Y", strtotime($pc->pedidocompra->dataprevista)),
                        'pc_fornecedor' => $pc->pedidocompra->fornecedor->nome,
                        'pc_ordemcompra' => $pc->pedidocompra->ordemcompra
               
                    );
                //}
            }

        }
        
    }
    usleep( 400000 );
}

// Filtros pedidos em aberto  pois a api nao permite filtrar os dois status num unico comando

$status_pedido = 'situacao[0]';
$filtro_pesq = 'filters=' . $status_pedido;

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0 or $api_pagina > 10) {
    $api_pagina++;
    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/pedidoscompra/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;
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
        $api_qtde_pc = 0;
        $api_qtde_itens_pc = 0;
        foreach ($resultado->retorno->pedidoscompra[0] as $pc) {
            $api_qtde_pc++;
     
            foreach ($pc->pedidocompra->itens as $item) {
                $api_qtde_itens_pc++;
                //if ($pc->pedidocompra->fornecedor->cpfcnpj == "48.061.210/0001-16") {
                    $pc_array_api[] = array(
                        'pc_ref' => trim($item->item->codigo),
                        'pc_ref_um' => trim($item->item->un),
                        'pc_num' =>  'AG-'.$pc->pedidocompra->numeropedido,
                        'pc_qtde' => $item->item->qtde,
                        'pc_qtde_atu' => $item->item->qtde,
                        'pc_emissao' => $pc->pedidocompra->datacompra,
                        'pc_previsao' => date("d/m/Y", strtotime($pc->pedidocompra->dataprevista)),
                        'pc_fornecedor' => $pc->pedidocompra->fornecedor->nome,
                        'pc_ordemcompra' => $pc->pedidocompra->ordemcompra
               
                    );
                //}
            }

        }
        
    }
    usleep( 400000 );
}


function ordenarArrayPorPrevisao($array) {
    usort($array, function($a, $b) {
        $dataA = DateTime::createFromFormat('d/m/Y', $a['pc_previsao']);
        $dataB = DateTime::createFromFormat('d/m/Y', $b['pc_previsao']);
        
        if ($dataA == $dataB) {
            return 0;
        }
        
        return ($dataA < $dataB) ? -1 : 1;
    });
    
    return $array;
}

$pc_array = ordenarArrayPorPrevisao($pc_array_api);
//print("<pre>" . print_r($pc_array, true) . "</pre>");
?>
