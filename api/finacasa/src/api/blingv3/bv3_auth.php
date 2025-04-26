<?php
// URL da API
$url = 'https://api.bling.com.br/Api/v3/oauth/token';

// Configuração dos cabeçalhos
$headers = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: 1.0',
    'Authorization: Basic ZmI0NWMwYzBhMWYyZjI1YzVjNjQ2YWQ3MjFhMmUyMzI2ZmQxNDJkMjpjYWYyOWZmNWZhYjg2ZDc0NWYzMmExZGRiNzk0YjdhMjJhM2MyNGE5NDY2OTU0ZWU3ZTA3ZDNkNmRhNjc='
];

// Dados da requisição
$data = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => '5e8ad5a0c9c44531476874d2749438ddbe2df18b'
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
