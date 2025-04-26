<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ref_pesq = $_POST['ref_pesq'];

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
    $url = 'https://bling.com.br/Api/v2/produto/' . $codigo_prod . '/' . $outputType . '/&' . $filtro_pesq;
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
        require('../config/conexao.php');
        foreach ($resultado->retorno->produtos as $prod) {
            echo $prod->produto->codigo;
            $ref =  $prod->produto->codigo;
            $ref_desc = str_replace(array("'", '//'), "", $prod->produto->descricao);
            $ref_um = $prod->produto->unidade;
            $preco = $prod->produto->preco;
            $qtde_cx = $prod->produto->itensPorCaixa;
            $marca = $prod->produto->marca;
            $tipo_sped = $prod->produto->spedTipoItem;
            $pesoBruto =  $prod->produto->pesoBruto;
            $situacao =  $prod->produto->situacao;
            $permitePalletAberto = $prod->produto->camposCustomizados->permitePalletAberto;
            $qtdePallet = $prod->produto->camposCustomizados->qtdePallet;
            $taxaPalletAberto = $prod->produto->camposCustomizados->taxaPalletAberto;
            $taxaPalletAbertoR = $prod->produto->camposCustomizados->taxaPalletAbertoR;
            $precoProdPalletAbertoR =  $prod->produto->camposCustomizados->precoProdPalletAbertoR;
            $update_at = date("d/m/Y H:i:s");
            
            $query1 = "INSERT INTO md_cad_produtos(referencia, descricao, unidade, preco, qtde_cx, fraciona_cx, dias_prod, marca, tipo, ipi, peso, permitePalletAberto, qtdePallet, taxaPalletAbertoR, taxaPalletAberto, precoProdPalletAbertoR, status, updated_at) VALUES ('$ref', '$ref_desc', '$ref_um', '$preco', '$qtde_cx', 'S','0','$marca' ,'$tipo_sped','0','$pesoBruto','$permitePalletAberto','$qtdePallet','$taxaPalletAberto','$taxaPalletAbertoR','$precoProdPalletAbertoR','A','$update_at')";
            
            $result = mysql_query($query1) or die(mysql_error());
        }
    }


    unset($prod_array);
} //fim do post