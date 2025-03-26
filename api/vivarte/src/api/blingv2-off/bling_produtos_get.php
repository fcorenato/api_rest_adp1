<?php
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
            $qtd_reg++;
            $prod_saldo_it = 0;
            $vh_pa_saldo = 0;
            $vc_pa_saldo = 0;
            
            foreach ($prod->produto->depositos as $dep) {

                if ($dep->deposito->nome == "VH - PROD ACABADO") {
                    
                    $prod_saldo_it += $dep->deposito->saldo;
                    $vh_pa_saldo = $dep->deposito->saldo;

                }
                if ($dep->deposito->nome == "VC - PROD ACABADO") {
                   
                    $prod_saldo_it += $dep->deposito->saldo;
                    $vc_pa_saldo = $dep->deposito->saldo;
                }
               
                if ($prod->produto->spedTipoItem == '04') {
                    $produtos_array[$prod->produto->codigo] = array(
                        'ref' => $prod->produto->codigo,
                        'ref_desc' => $prod->produto->descricao,
                        'ref_um' => $prod->produto->unidade,
                        'saldo_disp' =>  $prod_saldo_it,
                        'saldo_disp_atu' =>  $prod_saldo_it,
                        'vh_pa' => $vh_pa_saldo,
                        'vc_pa' => $vc_pa_saldo,
                        'tipo_sped' => $prod->produto->spedTipoItem
                    );
                }
            }
        }
    }
    usleep( 400000 );
}
//echo 'Qtde Registros= '.$qtd_reg;
print("<pre>" . print_r($produtos_array, true) . "</pre>");
