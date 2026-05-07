<?php
// ====================================== ACESSANDO VIVARTE ==================================================
// BUSCA NA TABELA md_prog_op OPS QUE NAO FORAM ATUALIZADOS
// E BUSCA NA API DO BLING OS DADOS ATUALIZADOS DOS PEDIDOS
// E ATUALIZA A TABELA md_prog_op COM OS NOVOS DADOS
// ============================================================================================================ 

echo '<h1>Atualizando status das OPs que nao foram atualizadas - API Bling V3</h1>';
include('../config/conexao.php');
$ops_nao_atualizado = mysql_query("SELECT op_id, op_num FROM md_prog_op WHERE updated_at is null and op_situacao NOT IN ('Cancelado','Finalizado','Finalizado parcial')") or die(mysql_error());
echo 'Qtde OPs nao atualizados: ' . mysql_num_rows($ops_nao_atualizado) . '<br>';
$array_ops = array();
while ($ops = mysql_fetch_object($ops_nao_atualizado)) {
    $array_ops[$ops->op_id] = $ops->op_num;
}

print("<pre>" . print_r($array_ops, true) . "</pre>");

foreach ($array_ops as $op_id => $op_num) {

    $op_emp = explode('-', $op_num)[0];
    echo '<hr>Buscando dados da OP ID: ' . $op_id . ' - Empresa: ' . $op_emp . '<br> ';

    if ($op_emp ==  'VI') {
        include('bv3_get_token_vivarte.php');
    } else if ($op_num ==  'AGAS') {
        include('bv3_get_token_agas.php');
    }

    $url = "https://api.bling.com.br/Api/v3/ordens-producao/$op_id";
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
    $retorno2 = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado2 = json_decode($retorno2);
    // print("<pre>" . print_r($resultado2, true) . "</pre>");

    if ($resultado2->error) {
        $msg = 'Erro api bling v3 (bv3_op_get.php BB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
    } else {
        foreach ($resultado2->data->itens as $it) {
            $op_ref = trim($it->produto->codigo);
            $op_qtde = $it->quantidade;
            // $op_qtde =  str_replace(",", ".", str_replace(".", "", $op_qtde));
        }
        $op_situacao_nome = $resultado2->data->situacao->nome;
        $deposito_orig = $resultado2->data->deposito->idOrigem;
        $deposito_dest = $resultado2->data->deposito->idDestino;
        
        $dataPrevisaoInicio = $resultado2->data->dataPrevisaoInicio;
        $dataPrevisaoFinal = $resultado2->data->dataPrevisaoFinal;


        include('../config/conexao.php');
        $sql = "UPDATE md_prog_op SET op_situacao = '$op_situacao_nome', deposito_origem = '$deposito_orig', deposito_destino = '$deposito_dest', op_previsaoInicio = '$dataPrevisaoInicio', op_previsaoFinal = '$dataPrevisaoFinal', updated_at = NOW() WHERE op_id = '$op_id';";
        echo '<hr>' . $sql;
        mysql_query($sql) or die(mysql_error());

        $op_array_api_atualizado['VI-' . $op_num] = array(
            'op_id' => $op_id,
            'op_ref' => $op_ref,
            'op_num' =>  $op_num,
            'op_situacao' => $op_situacao_nome,
            'op_qtde' => $op_qtde,
            'op_qtde_atu' => $op_qtde,
            'op_previsaoInicio' => (new DateTime($dataPrevisaoInicio))->format('d/m/Y'),
            'op_previsaoFinal' => (new DateTime($dataPrevisaoFinal))->format('d/m/Y'),
            'deposito_origem' => $deposito_orig,
            'deposito_destino' => $deposito_dest,
            'observacoes' => $resultado2->data->observacoes
        );
    }



    usleep(200000);

    // include('../config/conexao.php');
    // //atualizando status na tabela md_prog_op
    // echo 'Atualizando OP ID: ' . $op_id . ' - Numero OP: ' . $op_num . ' - Situação OP: ' . $op_situacao . '<br>';
    // $sql = "UPDATE md_prog_op SET op_situacao = '$op_situacao', updated_at = NOW() WHERE op_id = '$op_id';";
    // echo '<hr>' . $sql;
    // mysql_query($sql) or die(mysql_error());
}
echo '<hr>Qtde OPs atualizadas: ' . mysql_num_rows($ops_nao_atualizado) . '<br>';
print("<pre>" . print_r($op_array_api_atualizado, true) . "</pre>");
