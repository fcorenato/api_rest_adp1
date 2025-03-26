<?php
// ====================================== ACESSANDO VIVARTE ==================================================
//inicio do cronometro
$inicio_pv_get = microtime(true);
require('../config/conexao.php');
include_once('biv_botconversa_enviar_dev.php');
include('bv3_get_token_vivarte.php');

$api_pagina = 0;
$totalpaginas = 0;
$totalpedidos = 0;
$bling_api_cod_erro = 0;
$api_qtde_pedido = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;
    $totalpaginas++;

    //inicializando CURL =================================================================
    //filtros
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


    $url = "https://api.bling.com.br/Api/v3/pedidos/vendas?pagina=$api_pagina&idsSituacoes[]=6&idsSituacoes[]=15&idsSituacoes[]=10928";

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
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pv_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);
        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {
            $api_qtde_pedido++;
            foreach ($resultado->data as $pvs) {
                $pv_id = $pvs->id;
                $pv_num = $pvs->numero;
                $totalpedidos++;
                // echo $pv_id . '<br>';
                // echo $op_num . '<br>';

                //buscar produtos do pedido de venda:
                //inicializando CURL =================================================================
                $url = "https://api.bling.com.br/Api/v3/pedidos/vendas/$pv_id";
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
                    $msg = 'Erro api bling v3 (bv3_pv_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                    botc_enviar($msg);

                    // echo $msg;
                    // print("<pre>" . print_r($resultado2, true) . "</pre>");
                } else {
                    $pedido = $resultado2->data;
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


                        $pedido_vendas_array_api[] = array(

                            'ped_ud' => $pedido->loja->id,
                            'ped_num' => $pedido->numero,
                            'ped_web_num' => $pedido->numeroPedidoCompra,
                            'ped_situacao' => $pedido->situacao->id,
                            'ped_emissao' => $pedido->data,
                            'ped_previsao' => $pedido_prev_ent,
                            'ped_valorfrete' => $pedido->transporte->frete,
                            'cond_pgto' => $pedido->parcelas[0]->observacoes,
                            'item_valor' => $total_item,
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
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(200000);
}

// ====================================== ACESSANDO AGAS ==================================================
include('bv3_get_token_agas.php');
$api_pagina = 0;
$bling_api_cod_erro = 0;
while ($bling_api_cod_erro == 0) {
    $api_pagina++;
    $totalpaginas++;

    //inicializando CURL =================================================================
    //filtros
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


    $url = "https://api.bling.com.br/Api/v3/pedidos/vendas?pagina=$api_pagina&idsSituacoes[]=6&idsSituacoes[]=15&idsSituacoes[]=10928";

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
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        $msg = 'Erro api bling v3 (bv3_pv_get.php AA) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
        botc_enviar($msg);
        // echo $msg;
    } else {
        if ($resultado->data[0]->id > 0) {
            $api_qtde_pedido++;
            foreach ($resultado->data as $pvs) {
                $pv_id = $pvs->id;
                $pv_num = $pvs->numero;
                $totalpedidos++;
                // echo $pv_id . '<br>';
                // echo $op_num . '<br>';

                //buscar produtos do pedido de venda:
                //inicializando CURL =================================================================
                $url = "https://api.bling.com.br/Api/v3/pedidos/vendas/$pv_id";
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
                    $msg = 'Erro api bling v3 (bv3_pv_get.php AB) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description;
                    botc_enviar($msg);

                    // echo $msg;
                    // print("<pre>" . print_r($resultado2, true) . "</pre>");
                } else {
                    $pedido = $resultado2->data;
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

                        $pedido_vendas_array_api[] = array(

                            'ped_ud' => $pedido->loja->id,
                            'ped_num' => $pedido->numero,
                            'ped_web_num' => $pedido->numeroPedidoCompra,
                            'ped_situacao' => $pedido->situacao->id,
                            'ped_emissao' => $pedido->data,
                            'ped_previsao' => $pedido_prev_ent,
                            'ped_valorfrete' => $pedido->transporte->frete,
                            'cond_pgto' => $pedido->parcelas[0]->observacoes,
                            'item_valor' => $total_item,
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
        } else {
            $bling_api_cod_erro = 1;
        }
    }
    usleep(200000);
}


// ORDENANDO POR DATA DE PREVISAO(ENTREGA) E NUM PEDIDO PARA FICAR OS ITENS DO PEIDO JUNTOS
function sortArray($array)
{
    usort($array, function ($a, $b) {
        if ($a['ped_previsao'] == $b['ped_previsao']) {
            return ($a['ped_num'] < $b['ped_num']) ? -1 : 1;
        }
        return ($a['ped_previsao'] < $b['ped_previsao']) ? -1 : 1;
    });
    return $array;
}


$pedido_vendas_array = sortArray($pedido_vendas_array_api);
// print("<pre>" . print_r($pedido_vendas_array, true) . "</pre>");

//fim do cronometro
$fim_pv_get = microtime(true);
$tempoExecucao_pv_get = $fim_pv_get - $inicio_pv_get;
printf("<hr>O script PV_GET levou %f segundos para finalizar.\n", $tempoExecucao_pv_get);
// echo '<hr> total de paginas: ' . $totalpaginas . ' e total pedidos: ' . $totalpedidos;
