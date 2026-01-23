<?php
// ====================================== ACESSANDO VIVARTE ==================================================
// BUSCA NA TABELA MD_VENDAS_CARTEIRA OS PEDIDOS DE VENDA (PV) QUE NAO FORAM ATUALIZADOS
// E BUSCA NA API DO BLING OS DADOS ATUALIZADOS DOS PEDIDOS
// E ATUALIZA A TABELA MD_VENDAS_CARTEIRA COM OS NOVOS DADOS
// ============================================================================================================ 


require('../config/conexao.php');
$pedido_nao_atualizado = mysql_query("SELECT pv_id, bling_emp FROM md_vendas_carteira WHERE updated_at is null") or die(mysql_error());
echo 'Qtde pedidos nao atualizados: ' . mysql_num_rows($pedido_nao_atualizado) . '<br>';
$array_pvs = array();
while ($pvs = mysql_fetch_object($pedido_nao_atualizado)) {
    $array_pvs[$pvs->pv_id] = $pvs->bling_emp;
}

print("<pre>" . print_r($array_pvs, true) . "</pre>");

foreach ($array_pvs as $pv_id => $bling_emp) {

    echo '<hr>Buscando dados do PV ID: ' . $pv_id . ' - Empresa: ' . $bling_emp . '<br> ';

    if ($bling_emp ==  'VIVARTE') {
        include('bv3_get_token_vivarte.php');
    } else if ($bling_emp ==  'AGAS') {
        include('bv3_get_token_agas.php');
    }

    echo 'Usando token: ' . substr($token, 0, 20) . '...<br>';

    //buscar produtos do pedido de venda:
    //inicializando CURL =================================================================
    $url = "https://api.bling.com.br/Api/v3/pedidos/vendas/$pv_id";
    echo $url . '<hr>';

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
        $msg = 'Erro api bling v3 (bv3_pv_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);

        // echo $msg;
        // print("<pre>" . print_r($resultado2, true) . "</pre>");
    } else {
        /*
        codigo statua pedido no bling em (24/ago/21)
        6	Em aberto
        9	Atendido = pedido finalizado (emitido nf)
        12	Cancelado
        15	Em andamento = pedidos com pgto confirmados
        18	Venda Agenciada
        21	Em digitação
        24	Verificado
        10928	Amostras e Bonificações
        */

        $array_status_pedido = array();
        $array_status_pedido['6'] = 'VERIFICAR';
        $array_status_pedido['9'] = 'FATURADO';
        $array_status_pedido['12'] = 'CANCELADO';
        $array_status_pedido['15'] = 'VERIFICAR';
        $array_status_pedido['18'] = 'VERIFICAR';
        $array_status_pedido['21'] = 'VERIFICAR';
        $array_status_pedido['24'] = 'VERIFICAR';
        $array_status_pedido['10928'] = 'VERIFICAR';

        $pedido = $resultado2->data;
        $dados_pedido[] = array(
            'ped_ud' => $pedido->loja->id,
            'ped_id' => $pedido->id,
            'ped_num' => $pedido->numero,
            'ped_situacao' => $pedido->situacao->id,
            'ped_situacao_desc' => $array_status_pedido[$pedido->situacao->id]

        );
        foreach ($resultado2->data->itens as $item) {

            //calculando total item com desconto
            $total_item = $item->quantidade * $item->valor;
            $total_item  = $total_item * (1 - ($item->desconto / 100));
            $total_item = round($total_item, 2);

            //se cliente PF ou PJ
            $clitipo = '';
            $cliente_tipo = strlen($pedido->contato->tipoPessoa);
            if ($clidoc_tam == 'F') {
                $clitipo = 'PF';
            } else if ($clidoc_tam == 'J') {
                $clitipo = 'PJ';
            } else {
                $clitipo = '';
            }

            $ped_previsao = $pedido->dataPrevista != '' ? $pedido->dataPrevista : '';

            // === consultando dados do cliente no BIV  ====
            $cliente_tipo = '';
            $cpf_cnpj = '';
            $bairro = '';
            $cidade = '';
            $uf = '';
            $total_volumes = '';
            $total_peso = '';
            $msg_pedido = '';
            $pedido_prev_ent = '';
            $query2 = "SELECT cliente_tipo, cpf_cnpj, bairro, cidade, uf, total_volumes, total_peso, msg_pedido, msg_nota, pedido_prev_ent 
                                        FROM `md_vendas_pedidos` 
                                        WHERE id = $pedido->numeroPedidoCompra";
            // echo '<hr>' . $query2;               
            $result_query2 = mysql_query($query2);
            $qtde_query2 = mysql_num_rows($result_query2);
            if ($qtde_query2 > 0) {
                while ($campos = mysql_fetch_array($result_query2)) {

                    // =======  carrega o array com os dados do estoque ============
                    $cliente_tipo = $campos['cliente_tipo'];
                    $cpf_cnpj = $campos['cpf_cnpj'];
                    $bairro = $campos['bairro'];
                    $cidade = $campos['cidade'];
                    $uf = $campos['uf'];
                    $total_volumes = $campos['total_volumes'];
                    $total_peso = $campos['total_peso'];
                    $msg_pedido = $campos['msg_pedido'];
                    $pedido_prev_ent = $campos['pedido_prev_ent'];
                }
            }

            //print("<pre>" . print_r($estoquedisp, true) . "</pre>");//
            // if ($pedido->contato->nome == 'PRODUÇÃO ESTOQUE') {
            //     $pedido_prev_ent = '2050-12-31';
            // }

            $pedido_prev_ent = $pedido_prev_ent != '' ? $pedido_prev_ent : $pedido->dataPrevista;

            $dados_pedido_itens[] = array(

                'ped_ud' => $pedido->loja->id,
                'ped_num' => $pedido->numero,
                'ped_web_num' => $pedido->numeroPedidoCompra,
                'ped_situacao' => $pedido->situacao->id,
                'ped_emissao' => $pedido->data,
                'ped_previsao' => $pedido_prev_ent,
                'ped_valorfrete' => $pedido->transporte->frete,
                'cond_pgto' => $pedido->parcelas[0]->observacoes,
                'item_valor' => $total_item,
                'item_pv_id' => trim($item->id),
                'item_ref' => trim($item->codigo),
                'item_qtde' => $item->quantidade,
                'item_pesobruto' => $total_peso,
                'item_pesototal' => $total_peso,
                'item_volumetotal' => $total_volumes,
                'cliente_nome' => $pedido->contato->nome,
                'cliente_tipo' => $cliente_tipo,
                'cliente_uf' => $uf,
                'cliente_cidade' => $cidade,
                'cliente_bairro' => $bairro,
                'vendedor_nome'  => $pedido->vendedor->id,
                'est_sugest' => '',
                'op_sugest' => '',
                'pc_sugest' => '',
                'qtde_pend' => '',
                'situacao' => '',
                'situacao_color' => '',
                'data_prev' => '',
                'doc' => '',
                'saldo_est' => ''
            );
        }
    }
    usleep(200000);
}
echo '<hr>PEDIDO<br>';
print("<pre>" . print_r($dados_pedido, true) . "</pre>");
// echo '<hr>ITENS DOS PEDIDOS<br>';
// print("<pre>" . print_r($dados_pedido_itens, true) . "</pre>");

//atualizando tabela md_vendas_carteira
foreach ($dados_pedido as $pv) {
    require('../config/conexao.php');
    $sql = "
        UPDATE md_vendas_carteira 
        SET 
            situacao = '" . mysql_real_escape_string($pv['ped_situacao_desc']) . "',
            updated_at = NOW()
        WHERE 
            pv_id = '" . mysql_real_escape_string($pv['ped_id']) . "'
    ";
    echo '<hr>' . $sql;
    mysql_query($sql) or die(mysql_error());
    
}
