<?php
require('../config/SUsuario.php');
$apikey = $un_bling_apikey;
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

                $estoquedisp[] = array(
                    'ref' => $prod->produto->codigo,
                    'ref_desc' => $prod->produto->descricao,
                    'ref_um' => $prod->produto->unidade,
                    'saldo_disp' =>  $dep->deposito->saldo,
                    'saldo_disp_atu' =>  $dep->deposito->saldo,
                    'deposito' => $dep->deposito->nome
                );

                
                //echo 'dep'. $dep->deposito->nome.'<br>';

                
            }


            
        }
    }
    usleep( 400000 );
}
//print("<pre>" . print_r($estoquedisp, true) . "</pre>");
