<?php
// ====================================== ACESSANDO VIVARTE ==================================================

//token vivarte
include('bv3_get_token_vivarte.php');
// include('bv3_get_token_agas.php');
$pesquisa_agas = false;

//tratando valor da pesquisa
$cpf_cnpj_pesq = str_replace(array(' ', '.', '-', '/'), "", $cpf_cnpj_pesq);
// $cpf_cnpj_pesq = '02686656301';

//inicializando CURL =================================================================
$url = "https://api.bling.com.br/Api/v3/contatos?pagina=1&numeroDocumento=$cpf_cnpj_pesq";
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

    echo $msg;
} else {


    if ($resultado->data[0]->id > 0) {
        $id_cli = $resultado->data[0]->id;

        //inicializando CURL =================================================================
        $url = "https://api.bling.com.br/Api/v3/contatos/$id_cli";
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
        //dados do cliente:

        if ($resultado->error) {
            $msg = 'Erro api bling v3 (bv3_op_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
            botc_enviar($msg);

            echo $msg;
        } else {
            $cli = $resultado->data;

            $cliente_array[] = array(
                'id' => $cli->id,
                'nome' => $cli->nome,
                'fantasia' => $cli->fantasia,
                'tipo' => $cli->tipo,
                'cnpj' => $cli->numeroDocumento,
                'ie_rg' => $cli->ie,
                'endereco' => $cli->endereco->geral->endereco,
                'numero' => $cli->endereco->geral->numero,
                'bairro' => $cli->endereco->geral->bairro,
                'cep' => $cli->endereco->geral->cep,
                'cidade' => $cli->endereco->geral->municipio,
                'complemento' => $cli->endereco->geral->complemento,
                'uf' => $cli->endereco->geral->uf,
                'fone' => $cli->telefone,
                'email' => $cli->email,
                'situacao' => $cli->situacao,
                'contribuinte' => $cli->indicadorIe,
                'celular' => $cli->celular,
                'tipo_cliente' => $cli->tipo
            );
            // print("<pre>" . print_r($cliente_array, true) . "</pre>");
            // echo 'qtde clientes = '.count($cliente_array);
        }
    } else {
        // echo 'clienta NÂO encontrado!';
        //SE CLIENTE NAO ENCONTRADO NA VIVARTE PESQUISA NA AGAS

        $pesquisa_agas = true;
    }
}

if ($pesquisa_agas) {
    //token vivarte
    include('bv3_get_token_agas.php');

    //tratando valor da pesquisa
    $cpf_cnpj_pesq = str_replace(array(' ', '.', '-', '/'), "", $cpf_cnpj_pesq);
    // $cpf_cnpj_pesq = '02686656301';

    //inicializando CURL =================================================================
    $url = "https://api.bling.com.br/Api/v3/contatos?pagina=1&numeroDocumento=$cpf_cnpj_pesq";
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
        $msg = 'Erro api bling v3 (bv3_op_get.php BA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        echo $msg;
    } else {


        if ($resultado->data[0]->id > 0) {
            $id_cli = $resultado->data[0]->id;

            //pesquisando dados do cliente
            //inicializando CURL =================================================================
            $url = "https://api.bling.com.br/Api/v3/contatos/$id_cli";
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
            //dados do cliente:

            if ($resultado->error) {
                $msg = 'Erro api bling v3 (bv3_op_get.php BB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                botc_enviar($msg);

                echo $msg;
            } else {
                $cli = $resultado->data;

                $cliente_array[] = array(
                    'id' => $cli->id,
                    'nome' => $cli->nome,
                    'fantasia' => $cli->fantasia,
                    'tipo' => $cli->tipo,
                    'cnpj' => $cli->numeroDocumento,
                    'ie_rg' => $cli->ie,
                    'endereco' => $cli->endereco->geral->endereco,
                    'numero' => $cli->endereco->geral->numero,
                    'bairro' => $cli->endereco->geral->bairro,
                    'cep' => $cli->endereco->geral->cep,
                    'cidade' => $cli->endereco->geral->municipio,
                    'complemento' => $cli->endereco->geral->complemento,
                    'uf' => $cli->endereco->geral->uf,
                    'fone' => $cli->telefone,
                    'email' => $cli->email,
                    'situacao' => $cli->situacao,
                    'contribuinte' => $cli->indicadorIe,
                    'celular' => $cli->celular,
                    'tipo_cliente' => $cli->tipo
                );
                // print("<pre>" . print_r($cliente_array, true) . "</pre>");
                // echo 'qtde clientes = '.count($cliente_array);
            }
        } else {
            // echo 'clienta NÂO encontrado!';
            //SE CLIENTE NAO ENCONTRADO NA VIVARTE PESQUISA NA AGAS

        }
    }
}
