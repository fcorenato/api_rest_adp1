<?php
//inicio do cronometro
$inicio_est_get = microtime(true);

include_once('biv_botconversa_enviar_dev.php');
// ====================================== ACESSANDO VIVARTE ==================================================
include('bv3_get_token_vivarte.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
$qtd_prods = 0;
$qtd_prods_estoque = 0;
$refs_estoque = [];
$resultado2_count = 0;

while ($bling_api_cod_erro == 0) {
    //inicializando CURL =================================================================
    $api_pagina++;
    $url = "https://api.bling.com.br/Api/v3/produtos?pagina=$api_pagina";
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
        $msg = 'Erro api bling v3 (bv3_prod_estoque_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
        $bling_api_cod_erro = 1;
    } else {

        if (isset($resultado->data[0]) or  $resultado->data[0]->id > 0) {

            foreach ($resultado->data as $prod) {
                $qtd_prods = $qtd_prods + 1;
                $prod_id = $prod->id;
                $prod_codigo = $prod->codigo;
                $prod_nome = $prod->nome;
                $prod_estoque = $prod->estoque->saldoVirtualTotal;
                $prod_sit = $prod->situacao;
                if ($prod_codigo <> "" and $prod_estoque > 0 and $prod_sit == 'A') {
                    $qtd_prods_estoque++;
                    // echo $prod_id .' - '. $prod_codigo . ';saldo: ' . $prod_estoque . ';Situação: ' . $prod_sit . '<br>';
                    $refs_estoque[] = $prod_id;
                }
            }
        } else {
            $bling_api_cod_erro = 1;
            // echo 'erro bling = ' . $bling_api_cod_erro . 'na pagina = ' . $api_pagina;
        }
    }

    usleep(200000);
}

// echo 'total paginas = ' . $api_pagina . ' e produtos = ' . $qtd_prods . ' e produto com estoque = ' . $qtd_prods_estoque . '<br>';


// print("<pre>" . print_r($refs_estoque, true) . "</pre>");
$i = 0;
foreach ($refs_estoque as $key => $value) {
    $idp = $value;
    $ids_pesq .= '&idsProdutos[]=' . $idp;

    // $i++;
    // if ($i >= 10) {
    //     break;
    // }
}
$url = "https://api.bling.com.br/Api/v3/estoques/saldos?idsProdutos[]=0$ids_pesq";
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

$dep_arry['1462456848'] = 'VC-PA';
$dep_arry['11919578899'] = 'VH-PA';
$dep_arry['14887297853'] = 'VM-PA';
$dep_arry['14886856259'] = 'AG-PA';

$dep_arry['14888219392'] = 'VH-OUTLET';
$dep_arry['14888219394'] = 'AG-OUTLET';

if ($resultado2->error) {
    $msg = 'Erro api bling v3 (bv3_pv_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
    botc_enviar($msg);

    // echo $msg;
    // print("<pre>" . print_r($resultado2, true) . "</pre>");
} else {
    foreach ($resultado2->data as $data) {
        $resultado2_count++;
        $prod_codigo = $data->produto->codigo;
        foreach ($data->depositos as  $dep) {
            if (($dep->id == '1462456848' or $dep->id == '11919578899' or $dep->id == '14887297853' or $dep->id == '14886856259' or $dep->id == '14888219392'or $dep->id == '14888219394' ) and $dep->saldoFisico > 0) {
                // echo $dep->id .' dep = ' . $dep_arry[$dep->id] . ' prod = '. $prod_codigo .' qtde = ' . $dep->saldoFisico . '<br>';
                $estoquedisp[] = array(
                    'ref' => $prod_codigo,
                    'ref_desc' => $prod_nome,
                    'ref_um' => '',
                    'saldo_disp' =>  $dep->saldoFisico,
                    'saldo_disp_atu' =>  $dep->saldoFisico,
                    'deposito' => $dep_arry["$dep->id"]
                );
            }
        }
    }
}

//checando se qtde itens na consulta de estoque é igual a itens com saldo. caso na enviar elerta:
if ($resultado2_count != $qtd_prods_estoque) {
    $msg = 'Erro api bling v3 (bv3_prod_estoque_get.php CA) = Qtde de itens retornado na consulta de estoque por deposito diferente da quantidade de produtos com estoque. ';
    botc_enviar($msg);
}

// ====================================== ACESSANDO AGAS ==================================================
include('bv3_get_token_agas.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
$qtd_prods = 0;
$qtd_prods_estoque = 0;
$refs_estoque = [];
$resultado2_count = 0;

while ($bling_api_cod_erro == 0) {
    //inicializando CURL =================================================================
    $api_pagina++;
    $url = "https://api.bling.com.br/Api/v3/produtos?pagina=$api_pagina";
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
        $msg = 'Erro api bling v3 (bv3_prod_estoque_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
        $bling_api_cod_erro = 1;
    } else {

        if (isset($resultado->data[0]) or  $resultado->data[0]->id > 0) {

            foreach ($resultado->data as $prod) {
                $qtd_prods = $qtd_prods + 1;
                $prod_id = $prod->id;
                $prod_codigo = $prod->codigo;
                $prod_nome = $prod->nome;
                $prod_estoque = $prod->estoque->saldoVirtualTotal;
                $prod_sit = $prod->situacao;
                if ($prod_codigo <> "" and $prod_estoque > 0 and $prod_sit == 'A') {
                    $qtd_prods_estoque++;
                    // echo $prod_codigo . ';saldo: ' . $prod_estoque . ';Situação: ' . $prod_sit . '<br>';
                    $refs_estoque[] = $prod_id;
                }
            }
        } else {
            $bling_api_cod_erro = 1;
            // echo 'erro bling = ' . $bling_api_cod_erro . 'na pagina = ' . $api_pagina;
        }
    }

    usleep(200000);
}

// echo 'total paginas = ' . $api_pagina . ' e produtos = ' . $qtd_prods . ' e produto com estoque = ' . $qtd_prods_estoque . '<br>';


// print("<pre>" . print_r($refs_estoque, true) . "</pre>");
$i = 0;
foreach ($refs_estoque as $key => $value) {
    $idp = $value;
    $ids_pesq .= '&idsProdutos[]=' . $idp;

    // $i++;
    // if ($i >= 10) {
    //     break;
    // }
}
$url = "https://api.bling.com.br/Api/v3/estoques/saldos?idsProdutos[]=0$ids_pesq";
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

$dep_arry['1462456848'] = 'VC-PA';
$dep_arry['11919578899'] = 'VH-PA';
$dep_arry['14887297853'] = 'VM-PA';
$dep_arry['14886856259'] = 'AG-PA';

$dep_arry['14888219392'] = 'VH-OUTLET';
$dep_arry['14888219394'] = 'AG-OUTLET';

if ($resultado2->error) {
    $msg = 'Erro api bling v3 (bv3_pv_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
    botc_enviar($msg);

    // echo $msg;
    // print("<pre>" . print_r($resultado2, true) . "</pre>");
} else {
    foreach ($resultado2->data as $data) {
        $resultado2_count++;
        $prod_codigo = $data->produto->codigo;
        foreach ($data->depositos as  $dep) {
            if (($dep->id == '1462456848' or $dep->id == '11919578899' or $dep->id == '14887297853' or $dep->id == '14886856259' or $dep->id == '14888219392'or $dep->id == '14888219394') and $dep->saldoFisico > 0) {
                // echo 'dep = ' . $dep_arry[$dep->id] . ' qtde = ' . $dep->saldoFisico . '<br>';
                // echo $dep->id .' dep = ' . $dep_arry[$dep->id] . ' prod = '. $prod_codigo .' qtde = ' . $dep->saldoFisico . '<br>';
                $estoquedisp[] = array(
                    'ref' => $prod_codigo,
                    'ref_desc' => $prod_nome,
                    'ref_um' => '',
                    'saldo_disp' =>  $dep->saldoFisico,
                    'saldo_disp_atu' =>  $dep->saldoFisico,
                    'deposito' => $dep_arry["$dep->id"]
                );
            }
        }
    }
}

//checando se qtde itens na consulta de estoque é igual a itens com saldo. caso na enviar elerta:
if ($resultado2_count != $qtd_prods_estoque) {
    $msg = 'Erro api bling v3 (bv3_prod_estoque_get.php CA) = Qtde de itens retornado na consulta de estoque por deposito diferente da quantidade de produtos com estoque. ';
    botc_enviar($msg);
}

//fim do cronometro
$fim_est_get = microtime(true);
$tempoExecucao_est_get = $fim_est_get - $inicio_est_get;
printf("<hr>O script ESTOQUE_GET levou %f segundos para finalizar.\n", $tempoExecucao_est_get);

// print("<pre>" . print_r($estoquedisp, true) . "</pre>");

