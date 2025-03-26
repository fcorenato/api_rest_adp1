<?php
// ====================================== ACESSANDO VIVARTE ==================================================

$apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
$outputType = "json";

$api_pagina = 0;
$bling_api_cod_erro = 0;

//filtros
//produtos ativos e exibir estoque
$filtro_pesq = 'filters=situacao[A]/&estoque=S';

while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/produtos/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url . '&apikey=' . $apikey,
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

        foreach ($resultado->retorno->produtos as $prod) {
            foreach ($prod->produto->depositos as $dep) {

                if ($dep->deposito->nome == "VH - PROD ACABADO" AND $dep->deposito->saldo > 0) {
                    
                    $estoquedisp[] = array(
                        'ref' => $prod->produto->codigo,
                        'ref_desc' => $prod->produto->descricao,
                        'ref_um' => $prod->produto->unidade,
                        'saldo_disp' =>  $dep->deposito->saldo,
                        'saldo_disp_atu' =>  $dep->deposito->saldo,
                        'deposito' => 'VH-PA'
                    );

                }
                if ($dep->deposito->nome == "VC - PROD ACABADO" AND $dep->deposito->saldo > 0) {
                   
                    $estoquedisp[] = array(
                        'ref' => $prod->produto->codigo,
                        'ref_desc' => $prod->produto->descricao,
                        'ref_um' => $prod->produto->unidade,
                        'saldo_disp' =>  $dep->deposito->saldo,
                        'saldo_disp_atu' =>  $dep->deposito->saldo,
                        'deposito' => 'VC-PA'
                    );
                }

                if ($dep->deposito->nome == "VM - PROD ACABADO" AND $dep->deposito->saldo > 0) {
                   
                    $estoquedisp[] = array(
                        'ref' => $prod->produto->codigo,
                        'ref_desc' => $prod->produto->descricao,
                        'ref_um' => $prod->produto->unidade,
                        'saldo_disp' =>  $dep->deposito->saldo,
                        'saldo_disp_atu' =>  $dep->deposito->saldo,
                        'deposito' => 'VM-PA'
                    );
                }
                //echo 'dep'. $dep->deposito->nome.'<br>';

                
            }


            
        }
    }
    usleep( 400000 );
}

// ====================================== ACESSANDO AGAS ==================================================

$apikey = "7d0dc1fce7ece5e83815bcd73e97122777c6941f9238e72457d7a633bf7e82d03726ad86";
$outputType = "json";

$api_pagina = 0;
$bling_api_cod_erro = 0;

//filtros
//produtos ativos e exibir estoque
$filtro_pesq = 'filters=situacao[A]/&estoque=S';

while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/produtos/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url . '&apikey=' . $apikey,
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

        foreach ($resultado->retorno->produtos as $prod) {
            foreach ($prod->produto->depositos as $dep) {

                //deposito id: 14886856259 = "AG - PROD ACABADO"
                if ($dep->deposito->id == "14886856259" AND $dep->deposito->saldo > 0) {
                    
                    $estoquedisp[] = array(
                        'ref' => $prod->produto->codigo,
                        'ref_desc' => $prod->produto->descricao,
                        'ref_um' => $prod->produto->unidade,
                        'saldo_disp' =>  $dep->deposito->saldo,
                        'saldo_disp_atu' =>  $dep->deposito->saldo,
                        'deposito' => 'AG-PA'
                    );

                }
                //echo 'dep'. $dep->deposito->nome.'<br>';
                
            }
        }
    }
    usleep( 400000 );
}

//print("<pre>" . print_r($estoquedisp, true) . "</pre>");
