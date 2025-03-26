<?php

function botc_enviar($msg)
{
    $id_botc = '545238154'; //id Renato Botconversa Vivarte
    $text_msg = $msg;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://backend.botconversa.com.br/api/v1/webhook/subscriber/' . $id_botc . '/send_message/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{ \"type\": \"text\", \"value\": \"$text_msg\"}");

    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Api-Key: 99755c4c-f1bc-4324-9bed-22cf1c2b051d';
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    // if (curl_errno($ch)) {
    //     $erro =  '    | ERRO! = ' . curl_error($ch);
    // } else {
    //     $erro = '';
    // }
    curl_close($ch);
}

