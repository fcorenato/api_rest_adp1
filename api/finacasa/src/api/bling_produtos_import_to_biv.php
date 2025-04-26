<?php
ini_set('max_execution_time', 12300);
require('../config/SUsuario.php');
$apikey = $un_bling_apikey;
$outputType = "json";

$api_pagina = 0;
$bling_api_cod_erro = 0;

//filtros
//produtos ativos e exibir estoque
$filtro_pesq = 'filters=situacao[A]';

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
    //print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->retorno->erros) {
        $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
        $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
        //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
    } else {

        foreach ($resultado->retorno->produtos as $prod) {
            //if (($prod->produto->spedTipoItem == '00' || $prod->produto->spedTipoItem == '04') and $prod->produto->situacao == 'Ativo') {
            $qtd_reg++;
            $ref_pesq = $prod->produto->codigo;
            //include('bling_produto_porref_get.php');

            $ref_desc = str_replace(array("'", '//'), "", $prod->produto->descricao);

            $produtos_array[$prod->produto->codigo] = array(
                'ref' => $prod->produto->codigo,
                'ref_desc' => $ref_desc,
                'ref_um' => $prod->produto->unidade,
                'preco' => $prod->produto->preco,
                'qtde_cx' => $prod->produto->itensPorCaixa,
                'marca' => $prod->produto->marca,
                'tipo_sped' => $prod->produto->spedTipoItem,
                'pesoBruto' =>  $prod->produto->pesoBruto,
                'situacao' =>  $prod->produto->situacao,
                'permitePalletAberto' => $prod->produto->camposCustomizados->permitePalletAberto,
                'precoProdPalletAbertoR' => $prod->produto->camposCustomizados->precoProdPalletAbertoR,
                'qtdePallet' => $prod->produto->camposCustomizados->qtdePallet,
                'taxaPalletAberto' => $prod->produto->camposCustomizados->taxaPalletAberto,
                'taxaPalletAbertoR' => $prod->produto->camposCustomizados->taxaPalletAbertoR

            );

            //}
        }
    }
    usleep(400000);
}

$resultado_rel = '
    <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-striped table-head-fixed">
    <thead>
    <tr>
    <th data-field="id">Item</th>
    <th data-field="id">Codigo</th>
    <th data-field="id">Descrição</th>
    <th data-field="id">status</th>
    </tr>
    </thead>
    <tbody>

    ';
    $i=1;
    foreach ($produtos_array as $key_prod => $value_prod) {
        $ref_pesq =$key_prod;
        $resultado_rel .= '
                <tr class="tr_result">
                <td>' . $i . '</td>
                <td class="cod_prod">' . $key_prod . '</td>
                <td>' . $value_prod['ref_desc'] . '</td>
                <td><small class="badge badge-secondary float-right">Pendende</small></td>
                </tr>
                ';
        
        $i++;
    }

    // rodape da tabela resultado
    $resultado_rel .= '
    </tbody>
    </table>
    </div>';

    echo $resultado_rel;