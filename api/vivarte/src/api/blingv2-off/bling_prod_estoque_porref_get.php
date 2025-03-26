<?php
$apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
$outputType = "json";
$bling_api_cod_erro = 0;

//filtros
//produtos ativos e exibir estoque
$filtro_pesq = 'filters=situacao[A]/&estoque=S';
$codigo_prod_pesq = $key; //$codigo_prod_pesq;



//inicializando CURL =================================================================
$url = 'https://bling.com.br/Api/v2/produto/' . $codigo_prod_pesq . '/' . $outputType . '/&' . $filtro_pesq;

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
    //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
} else {

    foreach ($resultado->retorno->produtos as $prod) {
        if (($prod->produto->spedTipoItem == '00' || $prod->produto->spedTipoItem == '04') and $prod->produto->situacao == 'Ativo') {
            $qtd_reg++;
            foreach ($prod->produto->depositos as $dep) {

                $estoquedisp[] = array(
                    'ref' => $prod->produto->codigo,
                    'ref_desc' => $prod->produto->descricao,
                    'ref_um' => $prod->produto->unidade,
                    'saldo_disp' =>  $dep->deposito->saldo,
                    'saldo_disp_atu' =>  $dep->deposito->saldo,
                    'deposito' => $dep->deposito->nome
                );
            }
        }
    }
}
//echo 'Qtde Registros= ' . $qtd_reg;
//print("<pre>" . print_r($estoquedisp, true) . "</pre>");
