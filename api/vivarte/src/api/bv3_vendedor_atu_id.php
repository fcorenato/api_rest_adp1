<?php
// listando vendedores do BIV
$qt = 0;
$vend_biv_vivarte_array = [];
$vend_biv_agas_array = [];
include('../config/conexao.php');
$query1 = "SELECT codigo, descricao, bling_vend_nome, bling_vend_nome2 FROM `sys_unidades` WHERE status = 'A'";
$result_vend = mysql_query($query1) or die(mysql_error());
$linhas_result_vend = mysql_num_rows($result_vend);
if ($linhas_result_vend > 0) {
    while ($dados_vend = mysql_fetch_array($result_vend)) {
        $qt++;
        $vend_codigo = $dados_vend['codigo'];
        $bling_vend_nome = $dados_vend['bling_vend_nome'];
        $bling_vend_nome2 = $dados_vend['bling_vend_nome2'];
        // echo "Nome: $descricao -  Bling nome1: $bling_vend_nome |  Bling nome2: $bling_vend_nome <br>";
        if ($bling_vend_nome != '') {
            $vend_biv_vivarte_array[$vend_codigo] = $bling_vend_nome;
        }
        if ($bling_vend_nome2 != '') {
            $vend_biv_agas_array[$vend_codigo] = $bling_vend_nome2;
        }
    }
}
// print("<pre>" . print_r($vend_biv_agas_array, true) . "</pre>");
echo "Qtde de produtos no biv = $qt <hr>";

// ====================================== ACESSANDO VIVARTE ==================================================
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');


$qt_erro = 0;
$qt_vend_api = 0;
$vend_array_vivarte = [];

$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    
    $url = "https://api.bling.com.br/Api/v3/vendedores?pagina=$api_pagina";

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
    print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pc_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
        $qt_erro++;
    } else {

        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $vend) {
                $qt_vend_api++;
                $vend_id = $vend->id;
                $vend_nome = $vend->contato->nome;
                // echo "vend id = $vend_id - Nome: $vend_nome <br>";

                $vend_array_vivarte[$vend_nome] = $vend_id;
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(400000);
}

// ====================================== ACESSANDO VIVARTE ==================================================
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_agas.php');


$qt_erro = 0;
$qt_vend_api = 0;
$vend_array_agas = [];

$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================

    $url = "https://api.bling.com.br/Api/v3/vendedores?pagina=$api_pagina";

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
        $qt_erro++;
    } else {

        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $vend) {
                $qt_vend_api++;
                $vend_id = $vend->id;
                $vend_nome = $vend->contato->nome;
                // echo "vend id = $vend_id - Nome: $vend_nome <br>";

                $vend_array_agas[$vend_nome] = $vend_id;
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(400000);
}


// ===============================  ATUALIZANDO IDS NO BIV================================

echo "Vivarte - Qtde produtos retornado da API = $qt_vend_api <hr>";
// print("<pre>" . print_r($vend_array_vivarte, true) . "</pre>");
foreach ($vend_biv_vivarte_array as $codigo => $nome) {
    // echo "Nome: $nome | Id: $vend_array_vivarte[$nome]<br>";
    $id_v = 0;
    $id_v = $vend_array_vivarte[$nome];
    if ($id_v > 1) {
        $query_upd = "UPDATE `sys_unidades` SET `bling_vend_id` = $id_v WHERE codigo = '$codigo'";
        $result = mysql_query($query_upd) or die(mysql_error());
        // echo $query_upd.'<br>';
    }
}

echo "AGAS - Qtde produtos retornado da API = $qt_vend_api <hr>";
// print("<pre>" . print_r($vend_array_agas, true) . "</pre>");
foreach ($vend_biv_agas_array as $codigo => $nome) {
    // echo "Nome: $nome | Id: $vend_array_agas[$nome]<br>";
    $id_v = 0;
    $id_v = $vend_array_agas[$nome];
    if ($id_v > 1) {
        $query_upd = "UPDATE `sys_unidades` SET `bling_vend_id2` = $id_v WHERE codigo = '$codigo'";
        $result = mysql_query($query_upd) or die(mysql_error());
        // echo $query_upd.'<br>';
    }
}
