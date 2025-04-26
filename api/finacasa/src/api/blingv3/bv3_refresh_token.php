<?php
include('../../config/conexao.php');

//buscando token no bando de dados
$query1 = "SELECT bling_credenciais, bling_access_token, bling_refresh_token FROM `sys_unidades` WHERE codigo = 1";
$result_tk = mysql_query($query1) or die(mysql_error());
$linhas_result_tk = mysql_num_rows($result_tk);
if ($linhas_result_tk > 0) {
    while ($dados_tk = mysql_fetch_array($result_tk)) {
        $bling_credenciais = $dados_tk['bling_credenciais'];
        $token = $dados_tk['bling_access_token'];
        $refresh_token = $dados_tk['bling_refresh_token'];
    }
}

echo $token .' -- ' . $refresh_token . '<hr>';
// URL da API
$url = 'https://api.bling.com.br/Api/v3/oauth/token';

// Configuração dos cabeçalhos
$headers = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: 1.0',
    'Authorization: Basic '.$bling_credenciais
];

// Dados da requisição
$data = http_build_query([
    'grant_type' => 'refresh_token',
    'refresh_token' => "$refresh_token"
]);

// Inicializando o cURL
$curl = curl_init();

// Configurando o cURL
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Executando a requisição e capturando a resposta
$response = curl_exec($curl);
curl_close($curl);

$resultado = json_decode($response);
print("<pre>" . print_r($resultado, true) . "</pre>");

if ($resultado->error) {
    echo 'type: ' . $resultado->error->message . ' - ' . $resultado->error->description . '<hr>';
} else {
    echo 'token: ' . $resultado->access_token .' refresh: '. $resultado->refresh_token;
    //salvando token e refresh_token
    $tk = $resultado->access_token;
    $tk_refresh = $resultado->refresh_token;
    $time_update = date('Y-m-d H:i:s');
    $atu_pv_ibge = mysql_query("UPDATE sys_unidades SET bling_access_token='$tk', bling_refresh_token='$tk_refresh', bling_token_update = '$time_update'  WHERE codigo = 1");
}
