<?php
// ====================================== ACESSANDO VIVARTE ==================================================

$apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
$outputType = "json";
$bling_api_cod_erro = 0;

$cpf_cnpj_pesq = str_replace(array(' ', '.', '-', '/'), "", $cpf_cnpj_pesq);
$cpf_cnpj_cli = $cpf_cnpj_pesq;



//inicializando CURL =================================================================
$url = 'https://bling.com.br/Api/v2/contato/' . $cpf_cnpj_cli . '/' . $outputType;

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
    $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
    $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
    //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
} else {

    foreach ($resultado->retorno->contatos[0] as $cli) {
        foreach ($cli->tiposContato as $cli_tipo) {
            if ($cli_tipo->tipoContato->descricao == 'Cliente') {
                $tipo_cliente = 'S';
            }
        }
        $cliente_array[] = array(
            'id' => $cli->codigo,
            'nome' => $cli->nome,
            'fantasia' => $cli->fantasia,
            'tipo' => $cli->tipo,
            'cnpj' => $cli->cnpj,
            'ie_rg' => $cli->ie_rg,
            'endereco' => $cli->endereco,
            'numero' => $cli->numero,
            'bairro' => $cli->bairro,
            'cep' => $cli->cep,
            'cidade' => $cli->cidade,
            'complemento' => $cli->complemento,
            'uf' => $cli->uf,
            'fone' => $cli->fone,
            'email' => $cli->email,
            'situacao' => $cli->situacao,
            'contribuinte' => $cli->contribuinte,
            'celular' => $cli->celular,
            'tipo_cliente' => $tipo_cliente
        );
    }
}
//print("<pre>" . print_r($cliente_array, true) . "</pre>");


// ====================================== ACESSANDO AGAS ==================================================
$qtde_cli = count($cliente_array);

if ($qtde_cli == 0) {
    $apikey = "7d0dc1fce7ece5e83815bcd73e97122777c6941f9238e72457d7a633bf7e82d03726ad86";
    $outputType = "json";
    $bling_api_cod_erro = 0;

    $cpf_cnpj_pesq = str_replace(array(' ', '.', '-', '/'), "", $cpf_cnpj_pesq);
    $cpf_cnpj_cli = $cpf_cnpj_pesq;



    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/contato/' . $cpf_cnpj_cli . '/' . $outputType;

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
        $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
        $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
        //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
    } else {

        foreach ($resultado->retorno->contatos[0] as $cli) {
            foreach ($cli->tiposContato as $cli_tipo) {
                if ($cli_tipo->tipoContato->descricao == 'Cliente') {
                    $tipo_cliente = 'S';
                }
            }
            $cliente_array[] = array(
                'id' => $cli->codigo,
                'nome' => $cli->nome,
                'fantasia' => $cli->fantasia,
                'tipo' => $cli->tipo,
                'cnpj' => $cli->cnpj,
                'ie_rg' => $cli->ie_rg,
                'endereco' => $cli->endereco,
                'numero' => $cli->numero,
                'bairro' => $cli->bairro,
                'cep' => $cli->cep,
                'cidade' => $cli->cidade,
                'complemento' => $cli->complemento,
                'uf' => $cli->uf,
                'fone' => $cli->fone,
                'email' => $cli->email,
                'situacao' => $cli->situacao,
                'contribuinte' => $cli->contribuinte,
                'celular' => $cli->celular,
                'tipo_cliente' => $tipo_cliente
            );
        }
    }
    //print("<pre>" . print_r($cliente_array, true) . "</pre>");

}
