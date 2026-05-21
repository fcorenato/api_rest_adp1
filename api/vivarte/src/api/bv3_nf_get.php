<?php
//inicio do cronometro
$inicio_op_get = microtime(true);
// ====================================== ACESSANDO VIVARTE ==================================================
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;

// $idnfe = '25853239268';

//inicializando CURL =================================================================
//id situacoes: 18158 = Não iniciado | 18159 = Em produção | 18160 = Pausado
$url = "https://api.bling.com.br/Api/v3/nfe/$idnfe";

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
    $msg = 'Erro api bling v3 (bv3_op_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
    botc_enviar($msg);

    // echo $msg;
} else {
    
    if ($resultado->data->id > 0) {
        $nf = $resultado->data;
        $nf_id = $nf->id;
        $nf_num = $nf->numero;
        $nf_situacao = $nf->situacao;
        $itens_nf = array();
        echo "NF ID: $nf_id - NF NUM: $nf_num - SITUACAO: $nf_situacao <hr>";
        foreach ($resultado->data->itens as $item) {
            $itens_nf[$item->codigo] += $item->quantidade;
        }
        echo "ITENS NF: <hr>";
        print("<pre>" . print_r($itens_nf, true) . "</pre>");    
        usleep(200000);
    } else {
        $bling_api_cod_erro = 1;
    }
    usleep(200000);
}


// ====================================== ACESSANDO AGAS ==================================================
include('bv3_get_token_agas.php');

print("<pre>" . print_r(json_encode($op_array), true) . "</pre>");
//fim do cronometro
$fim_op_get = microtime(true);
$tempoExecucao_op_get = $fim_op_get - $inicio_op_get;
printf("<hr>O script OP_GET levou %f segundos para finalizar.\n", $tempoExecucao_op_get);
