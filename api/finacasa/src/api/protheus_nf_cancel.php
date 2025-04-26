<?php
/* NOTA FISCAL CANCELADA NO PROTHEUS */

$api_protheus_status = 'on';

if ($api_protheus_status == 'on') {
    // API - RECEBENDO DO BLING ESTOQUE DO PRODUTO DO ARMAZEM HORIZONTE 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require('../config/conexao.php');

        header("Access-Control-Allow-Origin: *");
        header('Cache-Control: no-cache, must-revalidate');
        header("Content-Type: text/plain; charset=UTF-8");
        header("HTTP/1.1 200 OK");

        //funcao para verificar se recebido json valido 
        function isValidJSON($str)
        {
            json_decode($str);
            return json_last_error() == JSON_ERROR_NONE;
        }

        //recebendo json via Post enviado pelo BLING
        $json_params = file_get_contents("php://input");

        if (strlen($json_params) > 0 && isValidJSON($json_params)) {
            echo '200';

            $conteudo = $json_params;
            $fp = fopen("log/nf-cancel-nf-$nf.txt","wb");
            fwrite($fp, $conteudo);
            fclose($fp);

        }
    } // fim do POST
}
