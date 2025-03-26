<?php
// listando produtos do BIV
$qt = 0;
$qt_erro = 0;
$qt_prod_api = 0;
$prod_array = [];

include('../config/conexao.php');
$query1 = "SELECT * FROM `md_cad_produtos`";
$result_pd = mysql_query($query1) or die(mysql_error());
$linhas_result_pd = mysql_num_rows($result_pd);
if ($linhas_result_pd > 0) {
    while ($dados_pd = mysql_fetch_array($result_pd)) {
        $qt++;
        $ref = $dados_pd['referencia'];
        // echo 'Ref = '. $ref . '<br>';
        $refs_pesq .= '&codigos[]=' . $ref;
    }
}
echo "Qtde de produtos no biv = $qt <hr>";

// ====================================== ACESSANDO VIVARTE ==================================================
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 18158 = Não iniciado | 18159 = Em produção | 18160 = Pausado

    $url = "https://api.bling.com.br/Api/v3/produtos?pagina=$api_pagina$refs_pesq'";

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

        echo $msg;
        $qt_erro++;
    } else {

        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $pds) {
                $pd_id = $pds->id;
                $pd_codigo = $pds->codigo;

                // echo "Prod: $pd_codigo - ID = $pd_id <br>";
                $prod_array[$pd_codigo] = $pd_id;
                $qt_prod_api++;
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(400000);
}

echo "VIVARTE - Qtde de erros ocorrido = $qt_erro <hr>";
echo "VIVARTE - Qtde produtos retornado da API = $qt_prod_api <hr>";

// ====================================== ACESSANDO AGAS ==================================================
$qt_erro = 0;
$qt_prod_api = 0;
$prod_array_agas = [];
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_agas.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;


    //inicializando CURL =================================================================
    //id situacoes: 18158 = Não iniciado | 18159 = Em produção | 18160 = Pausado

    $url = "https://api.bling.com.br/Api/v3/produtos?pagina=$api_pagina$refs_pesq'";

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

        echo $msg;
        $qt_erro++;
    } else {

        if ($resultado->data[0]->id > 0) {

            foreach ($resultado->data as $pds) {
                $pd_id = $pds->id;
                $pd_codigo = $pds->codigo;

                // echo "Prod: $pd_codigo - ID = $pd_id <br>";
                $prod_array_agas[$pd_codigo] = $pd_id;
                $qt_prod_api++;
            }
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(400000);
}

echo "AGAS - Qtde de erros ocorrido = $qt_erro <hr>";
echo "AGAS - Qtde produtos retornado da API = $qt_prod_api <hr>";
// print("<pre>" . print_r($prod_array, true) . "</pre>");
foreach ($prod_array as $cod => $id) {
    $id_agas = 0;
    $id_agas = $prod_array_agas[$cod];
    $id_agas = $id_agas > 0 ? $id_agas : 0;

    $query_upd = "UPDATE `md_cad_produtos` SET `id_bling_prod` = $id, `id_bling_prod2` = $id_agas WHERE referencia = '$cod'";
    // echo $query_upd.'<br>';
    $result = mysql_query($query_upd) or die(mysql_error());
}
