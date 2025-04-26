<?php
date_default_timezone_set('America/Sao_Paulo');
//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf_cnpj_pesq = $_POST['codcli'];

    //chamdada api pedidos get
    include_once('../../src/api/bling_cliente_get.php');

    $qtde_query1 = count($cliente_array);
    if ($qtde_query1 == 0) {
        $resultado_dados_cli = 'erro' . $codigo_cli;
    } else {
        foreach ($cliente_array as $key_cli => $value_cli) {

            $resultado_dados_cli = '
                <div id="resultado_dados_cli" style="display:none">
                    <input name="result_A1_COD" id="result_A1_COD" type="hidden" value="' . $value_cli["id"] . '">
                    <input name="result_A1_LOJA" id="result_A1_LOJA" type="hidden" value="01">
                    <input name="result_A1_NOME" id="result_A1_NOME" type="hidden" value="' . $value_cli["nome"] . '">
                    <input name="result_A1_NREDUZ" id="result_A1_NREDUZ" type="hidden" value="' . $value_cli["fantasia"] . '">
                    <input name="result_A1_FILIAL" id="result_A1_FILIAL" type="hidden" value="1">
                    <input name="result_A1_CGC" id="result_A1_CGC" type="hidden" value="' . $value_cli["cnpj"] . '">
                    <input name="result_A1_PESSOA" id="result_A1_PESSOA" type="hidden" value="' . $value_cli["tipo"] . '">
                    <input name="result_A1_CEP" id="result_A1_CEP" type="hidden" value="' . $value_cli["cep"] . '">
                    <input name="result_A1_END" id="result_A1_END" type="hidden" value="' . $value_cli["endereco"] . '">
                    <input name="result_A1_NUM" id="result_A1_NUM" type="hidden" value="' . $value_cli["numero"] . '">
                    <input name="result_A1_EST" id="result_A1_EST" type="hidden" value="' . $value_cli["uf"] . '">
                    <input name="result_A1_MUN" id="result_A1_MUN" type="hidden" value="' . $value_cli["cidade"] . '">
                    <input name="result_A1_BAIRRO" id="result_A1_BAIRRO" type="hidden" value="' . $value_cli["bairro"] . '">
                    <input name="result_A1_TEL" id="result_A1_TEL" type="hidden" value="' . $value_cli["fone"] . '">
                    <input name="result_A1_EMAIL" id="result_A1_EMAIL" type="hidden" value="' . $value_cli["email"] . '">
                    <input name="result_A1_EMAIL" id="result_A1_INSCR" type="hidden" value="' . $value_cli["ie_rg"] . '">
                    <input name="result_A1_EMAIL" id="result_A1_SIMPLES" type="hidden" value="">
                    <input name="result_A1_EMAIL" id="result_A1_CONTRIB" type="hidden" value="' . $value_cli["contribuinte"] . '">
                </div>';
        } // fim do foreach
    } // fim do if qtde_result1

} // fim do POST

echo $resultado_dados_cli;
