<?php
require('../config/SUsuario.php');
$apikey = $un_bling_apikey;
$outputType = "json";
$bling_api_cod_erro = 0;

//filtros
//produtos ativos e exibir estoque
$filtro_pesq = 'filters=situacao[A]/&estoque=S';
//$codigo_prod = '12.0001'; //$codigo_prod_pesq;
$codigo_prod = $ref_pesq;


//inicializando CURL =================================================================
$url = 'https://bling.com.br/Api/v2/produto/' . $codigo_prod . '/' . $outputType. '/&' . $filtro_pesq;
echo $url.'<hr>';
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $url . '?apikey=' . $apikey,
    CURLOPT_RETURNTRANSFER => true,
));
$retorno = curl_exec($curl);
curl_close($curl);
//finalizando CURL ====================================================================

$resultado = json_decode($retorno);
//print("<pre>" . print_r($resultado, true) . "</pre>");

if ($resultado->retorno->erros) {
    $bling_api_cod_erro = $resultado->retorno->erros->erro->cod;
    $bling_api_cod_erro_msg = $resultado->retorno->erros->erro->msg;
    echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
} else {

    foreach ($resultado->retorno->produtos as $prod) {
            $qtd_reg++;
            $permitePalletAberto_val = $prod->produto->camposCustomizados->permitePalletAberto;

            $ref_desc = str_replace(array("'", '//'), "", $prod->produto->descricao);
                $prod_array[$prod->produto->codigo] = array(
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
                    'qtdePallet' => $prod->produto->camposCustomizados->qtdePallet,
                    'taxaPalletAbertoR' => $prod->produto->camposCustomizados->taxaPalletAbertoR,
                    'taxaPalletAberto' => $prod->produto->camposCustomizados->taxaPalletAberto,
                    'precoProdPalletAbertoR' => $prod->produto->camposCustomizados->precoProdPalletAbertoR
                );
        
    }
}


echo 'Qtde Registros= '.$qtd_reg;
print("<pre>" . print_r($prod_array, true) . "</pre>");
unset($prod_array);
