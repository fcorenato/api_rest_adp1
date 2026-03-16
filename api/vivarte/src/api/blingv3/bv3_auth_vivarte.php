<?php
// URL da API
$url = 'https://api.bling.com.br/Api/v3/oauth/token';

// Configuração dos cabeçalhos
$headers = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: 1.0',
    'Authorization: Basic MmNhMWY5Yjg0MjFiODhhMzM2NjMzZmZkZGNkZjIwZWZjYjNiNGE4MjoxODYxMjRhYjMwMDQxY2YwMTE0MTc2ZTVjNGQ5OGQ0MzAwN2Q1ZTA3Y2ZkYTZjNTE5OGY3ZjhkYjY4MjI='
];

// Dados da requisição
$data = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => 'd09aebd1dc28e3a1dc24f3587f69e334b7031c30'
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
