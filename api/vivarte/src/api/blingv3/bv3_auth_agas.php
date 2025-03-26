<?php
// URL da API
$url = 'https://api.bling.com.br/Api/v3/oauth/token';

// Configuração dos cabeçalhos
$headers = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: 1.0',
    'Authorization: Basic OWE5YTIwMzc0NDk5MTQ5ZThlODk3ZWNjN2ZkNWNhNTUyMzRkNTE4YjoyMDkzYjMzYzZiNDFlNTVkMTU0YTlhOTA1YWE2OTBhMDE3MjlhMGM3YWJlYTdmYzQ1YTZhNzE1NTBhYzE='
];

// Dados da requisição
$data = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => '77330cfcee5cae0e8921ba27614f99d2ce05017b'
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

// Verificando erros
if (curl_errno($curl)) {
    echo 'Erro no cURL: ' . curl_error($curl);
} else {
    echo 'Resposta da API: ' . $response;
}

// Fechando a conexão cURL
curl_close($curl);
