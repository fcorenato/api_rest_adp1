<?php

//tratando valor da pesquisa
// $cpf_cnpj_pesq = '84728108368';
// $cpf_cnpj_pesq = '897.862.250-07';
$cpf_cnpj_pesq = str_replace(array(' ', '.', '-', '/'), "", $cpf_cnpj_pesq);

//dados do cliente
// $dados_cli = '{
//     "nome": "TESTE API",
//     "tipo": "F",
//     "situacao": "A",
//     "numeroDocumento": "' . $cpf_cnpj_pesq . '",
//     "indicadorIe": 9,
//     "ie": "",
//     "email": "renato@vivarte.com.br",
//     "telefone": "85991890803",
//     "celular": "85991890803",
//     "endereco": {
//         "geral": {
//             "endereco": "rua Manoel Conrado",
//             "cep": "62882070",
//             "bairro": "Zumbi",
//             "municipio": "HORIZONTE",
//             "uf": "CE",
//             "numero": "1444",
//             "complemento": "prox ali"
//             }
//         }
//     }';

//ACESSAR API FINACASA SE CLIENTE EXISTE ATUALIZA E PEGA ID, SENÃO, CRIA E PEGA ID.    
// ====================================== ACESSANDO FINACASA ==================================================
//token 
include('bv3_get_token.php');

//inicializando CURL =================================================================
$url = "https://api.bling.com.br/Api/v3/contatos?pagina=1&numeroDocumento=$cpf_cnpj_pesq";
// echo $url . '<hr>';
// echo 'token = ' .$token . '<hr>';

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
     echo $msg;
} else {

    //se cliente existe faz PUT senao POST
    if ($resultado->data[0]->id > 0) {
        // echo 'editar cliente:<br>';
        $id_cli = $resultado->data[0]->id;
        $id_cli_bling_vivarte = $id_cli;
        // echo 'Cliente Editado = ' . $id_cli_bling;

        $url = "https://api.bling.com.br/Api/v3/contatos/$id_cli";

        //inicializando CURL =================================================================
        $curl = curl_init();

        // Define as opções do cURL individualmente
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dados_cli);


        // Define os cabeçalhos HTTP
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token . '',
            'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
        ));

        $response = curl_exec($curl);
        // var_dump($response);
        curl_close($curl);

        //finalizando CURL ====================================================================
        $resultado = json_decode($response);
        // print("<pre>" . print_r($resultado, true) . "</pre>");


        if ($resultado->error) {
            foreach ($resultado->error->fields as $key => $field) {
                $campos .= " code: $field->code | msg: $field->msg | element: $field->element | namespece: $field->namespace";
            }

            echo $msg;

        } else {
            // print("<pre>" . print_r($resultado, true) . "</pre>");
        }
    } else {
        // SE CLENTE NÃO ENCONTRADO CRIAR CLINTE
        // echo 'criar cliente:<br>';

        $url = "https://api.bling.com.br/Api/v3/contatos";

        //inicializando CURL =================================================================
        $curl = curl_init();

        // Define as opções do cURL individualmente
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dados_cli);


        // Define os cabeçalhos HTTP
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token . '',
            'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
        ));

        $response = curl_exec($curl);
        // var_dump($response);
        curl_close($curl);

        //finalizando CURL ====================================================================
        $resultado = json_decode($response);
        // print("<pre>" . print_r($resultado, true) . "</pre>");


        if ($resultado->error) {
            foreach ($resultado->error->fields as $key => $field) {
                $campos .= " code: $field->code | msg: $field->msg | element: $field->element | namespece: $field->namespace";
            }

            echo $msg;

        } else {
            // print("<pre>" . print_r($resultado, true) . "</pre>");
            $id_cli_bling_vivarte = $resultado->data->id;
            // echo 'Cliente cadastrado = ' . $id_cli_bling .'<br>';
        }
    }
}





