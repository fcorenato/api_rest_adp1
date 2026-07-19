<?php
ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);
date_default_timezone_set('America/Sao_Paulo');

// require('../../src/config/SUsuario.php');
include_once('../../sys_functions.php');

// ============== AJAX CARTEIRA ==============================================
// Arquivo para gerar a carteira e distribuir estoque. Gerando no final o status de cada pedido.

//VARIAVEIS DE CONTROLE
$atender_parcial_estoque = TRUE;
$atender_parcial_op = TRUE;
$atender_parcial_pc = TRUE;
$consulta_estoque = TRUE;
$consulta_op = FALSE;
$consulta_pc = TRUE;
$processar_carteira = TRUE;
$imprimi_item_carteira = TRUE;
$subtotal_por_pedido = TRUE;
$subtotal_por_pedido_ultimo = TRUE;
$pesquisa_por_pedido = '';
$pesquisa_por_produto  = '';
$pesquisa_por_cliente  = '';
$pesquisa_por_unidade  = '';
$imprimi_apenas_resumo_ref = FALSE;
$imprimi_valores = TRUE; //imprimir valores R$ na carteira


// ============== PROCESSAR CARTEIRA     ====================================
// FUNCAO PARA GERAR TIMESTAMP E CALCULCAR DIFERENCA ENTRE DATAS NO FORMATO DD/MM/AAAA
function geraTimestamp($data)
{
    $partes = explode('/', $data);
    return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
}
//variaveis de controle e de subtotais
$num_pedido_check = 'inicial';
$produto_check = 'inicial';
$total_pedido_qtde = 0;
$total_pedido_reservado = 0;
$total_pedido_pendente = 0;
$total_pedido_valor = 0;
$total_pedido_op = 0;
$total_pedido_pc = 0;


$total_geral_valor = 0;
$total_geral_qtde = 0;
$total_geral_reservado = 0;
$total_geral_pendente = 0;
$total_geral_est = 0;
$total_geral_op = 0;
$total_geral_pc = 0;

if ($processar_carteira) {
    //chamdada api pedidos get
    // include_once('../../src/api/bling_pedido_vendas_get.php');
    include_once('../../src/api/bv3_pv_get.php');
    //chamdada api prod_estoque get
    include_once('../../src/api/bling_prod_estoque_biv_get.php');
    //chamdada api Op get
    //include_once('../../src/api/bling_op_get.php');
    //chamdada api PC get
    // include_once('../../src/api/bling_pedido_compras_get.php');
    // include_once('../../src/api/bv3_pc_get.php');

    if ($api_qtde_pedido == 0) {
        $carteira_processada = '
        <table>
            <tbody>
                <tr class="bg-warning">
                    <td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png"> Ops! Dados não processados. Por favor, tente novamente.</td>
                </tr>
            </tbody>
        </table>
        ';
        echo $carteira_processada;
        exit;
    } else {
        require('../config/conexao.php');
        //criar array com dados do produto:
        echo 'Criar array com dados do produto:<br>';
        $query1 = "SELECT * FROM md_cad_produtos";
        $result_query1 = mysql_query($query1) or die(mysql_error());
        $qtde_query1 = mysql_num_rows($result_query1);
        echo "qtde_query1: " . $qtde_query1 . "<br>";
        if ($qtde_query1 > 0) {
            while ($campos = mysql_fetch_array($result_query1)) {
                $prod_array_ipi[trim($campos['referencia'])] = $campos['ipi'];
                $prod_array_peso[trim($campos['referencia'])] = $campos['peso'];
                $prod_array_qtdecx[trim($campos['referencia'])] = $campos['qtde_cx'];
            }
        }
        echo "print prod_array_qtdecx: ";
        print("<pre>" . print_r($prod_array_qtdecx, true) . "</pre>");

        //incluindo IPI no valor do item
        $item_valor_total =  $value['item_valor'] + ($value['item_valor'] * $prod_array_ipi[$value['item_ref']] / 100);


        //percorrendo array de pedidos para processar atendimento com estoque, OP e PC
        foreach ($pedido_vendas_array as $key_ped => $value_ped) {

            /*
            // se produto vetromani ou vivarte checar se entrear maior que 60 dias
            if (trim($campos['B1_FABRIC']) == 'VETROMANI' || trim($campos['B1_FABRIC']) == 'VIVARTE') {

                $time_inicial = geraTimestamp(date('d/m/Y'));
                $time_final = geraTimestamp($data_entrega);
                // Calcula a diferença de segundos entre as duas datas:
                $diferenca = $time_final - $time_inicial; // 19522800 segundos
                // Calcula a diferença de dias
                $dias_p_atender = (int)floor($diferenca / (60 * 60 * 24)); // 225 dias
                if ($dias_p_atender >= 60) {
                    $atender_pedidovenda = FALSE; // se maior que 60 nao atender
                } else {
                    $atender_pedidovenda = TRUE;
                }
            }
            */
            $atender_pedidovenda = TRUE;

            //=== Veirifando (1)Estoque Disponivel, (2)OP e (3)Ped de Compras para atender itens pendentes ====

            $qtde_atender = round($value_ped['item_qtde'], 2);
            $qtde_pendente_pv = $qtde_atender;
            $qtde_sugerida_estoque = 0;
            $destacar_estoque = '';
            $destacar_op = '';
            $destacar_pc = '';



            //  =============== (1) Verificando no estoque disponivel  ============


            if ($qtde_pendente_pv > 0 and $atender_pedidovenda) {
                $destacar_estoque = '';
                $saldo_disp_atu_item_corrent = 0;
                $dep_est_atende = '';

                if ($atender_parcial_estoque) {
                    foreach ($estoquedisp as $key_estoq => $value_estoq) {
                        if ($value_estoq['ref'] == $value_ped['item_ref'] and $qtde_pendente_pv > 0 and $value_estoq['saldo_disp_atu'] > 0) {
                            $saldo_disp_atu_item_corrent_calc1 = round($value_estoq['saldo_disp_atu'], 2) - $qtde_pendente_pv;
                            $qtde_sugerida_item_atual = 0;
                            //nao deixar saldo ficar negativo
                            if ($saldo_disp_atu_item_corrent_calc1 < 0) {
                                $saldo_disp_atu_item_corrent = 0;
                                $qtde_pendente_pv = $qtde_pendente_pv - round($value_estoq['saldo_disp_atu'], 2);
                                $qtde_sugerida_estoque += round($value_estoq['saldo_disp_atu'], 2);
                                $qtde_sugerida_item_atual = round($value_estoq['saldo_disp_atu'], 2);
                            } else {
                                $saldo_disp_atu_item_corrent = $saldo_disp_atu_item_corrent_calc1;
                                $qtde_sugerida_estoque += $qtde_pendente_pv;
                                $qtde_sugerida_item_atual += $qtde_pendente_pv;
                                $qtde_pendente_pv = 0;
                            }

                            $estoquedisp[$key_estoq]['saldo_disp_atu'] = $saldo_disp_atu_item_corrent;


                            $destacar_estoque = 'style="background-color: LightGreen;"';
                            $dep_est_atende .= $estoquedisp[$key_estoq]['deposito'] . ' (' . number_format($qtde_sugerida_item_atual,    2, ',', '.') . ') ';
                        }
                    }
                } else {
                    foreach ($estoquedisp as $key_estoq => $value_estoq) {
                        if ($value_estoq['ref'] == $value_ped['item_ref'] and $qtde_pendente_pv > 0 and $value_estoq['saldo_disp_atu'] >= $qtde_pendente_pv) {
                            $estoquedisp[$key_estoq]['saldo_disp_atu'] = round($value_estoq['saldo_disp_atu'], 2) - $qtde_pendente_pv;
                            $saldo_disp_atu_item_corrent = round($value_estoq['saldo_disp_atu'], 2) - $qtde_pendente_pv;
                            $qtde_sugerida_estoque = $qtde_pendente_pv;
                            $qtde_pendente_pv = 0;
                            $destacar_estoque = 'style="background-color: LightGreen;"';
                            $dep_est_atende = $estoquedisp[$key_estoq]['deposito'];
                        }
                    }
                }
            }

            $pedido_vendas_array[$key_ped]['est_sugest'] = $qtde_sugerida_estoque;
            // ======== fim do  1 - Verificando no estoque disponivel  =======



            //  =============== (3) Verificando Pedidos de Compra  ============
            $qtde_sugerida_pc = 0;
            $destacar_pc = '';
            $data_prev_pc = '';
            $item_doc_atend_pc = '';
            $item_data_prev_pc = '';

            if ($qtde_pendente_pv > 0 and $consulta_pc and $atender_pedidovenda) {
                if ($atender_parcial_pc) {
                    foreach ($pc_array as $key_pc => $value_pc) {
                        if ($value_pc['pc_ref'] == $value_ped['item_ref']  and $qtde_pendente_pv > 0 and $value_pc['pc_qtde_atu'] > 0) {

                            //se pedido de compra é complementar para pedido especifico
                            $ped_complementar = 0;
                            $atende_com_pc = TRUE;
                            $ped_complementar = strlen($value_pc['pc_ordemcompra']);

                            unset($pc_ordemcompra_array);
                            $pc_ordemcompra_array = explode(",", TRIM($value_pc['pc_ordemcompra']));

                            //print("<pre>" . print_r($pc_ordemcompra_array, true) . "</pre>");
                            //echo strlen($value_pc['pc_ordemcompra']);
                            if ($ped_complementar) {
                                if (in_array(TRIM($value_ped['ped_num']), $pc_ordemcompra_array)) {
                                    $atende_com_pc = TRUE;
                                } else {
                                    $atende_com_pc = FALSE;
                                }
                            } else {
                                $atende_com_pc = TRUE;
                            }


                            if ($atende_com_pc) {
                                $saldo_disp_atu_item_corrent_calc1 = round($value_pc['pc_qtde_atu'], 2) - $qtde_pendente_pv;
                                if ($saldo_disp_atu_item_corrent_calc1 < 0) {
                                    $saldo_disp_atu_item_corrent = 0;
                                    $qtde_pendente_pv = $qtde_pendente_pv - round($value_pc['pc_qtde_atu'], 2);
                                    $qtde_sugerida_pc += round($value_pc['pc_qtde_atu'], 2);
                                } else {
                                    $saldo_disp_atu_item_corrent = $saldo_disp_atu_item_corrent_calc1;
                                    $qtde_sugerida_pc += $qtde_pendente_pv;
                                    $qtde_pendente_pv = 0;
                                }

                                $pc_array[$key_pc]['pc_qtde_atu'] = $saldo_disp_atu_item_corrent;

                                $destacar_pc = 'style="background-color: LightGreen;"';
                                $item_data_prev_pc .= date("d/m/Y", strtotime(substr($value_pc['pc_previsao'], 0, 10))) . ' ';
                                $item_doc_atend_pc .= 'PC:' . $value_pc['pc_num'] . ' ';
                            }
                        }
                    }
                } else {
                    foreach ($pc_array as $key_pc => $value_pc) {
                        if ($value_pc['pc_ref'] == $value_ped['item_ref']  and $qtde_pendente_pv > 0 and $value_pc['pc_qtde_atu'] >= $qtde_pendente_pv) {
                            $pc_array[$key_pc]['pc_qtde_atu'] = round($value_pc['pc_qtde_atu'], 2) - $qtde_pendente_pv;
                            $saldo_disp_atu_item_corrent = round($value_pc['pc_qtde_atu'], 2) - $qtde_pendente_pv;
                            $qtde_sugerida_pc = $qtde_pendente_pv;
                            $qtde_pendente_pv = 0;
                            $destacar_pc = 'style="background-color: LightGreen;"';
                            $item_data_prev_pc = date("d/m/Y", strtotime(substr($value_pc['pc_previsao'], 0, 10)));
                            $item_doc_atend_pc = 'PC:' . $value_pc['pc_num'];
                        }
                    }
                }
            }

            $pedido_vendas_array[$key_ped]['pc_sugest'] = $qtde_sugerida_pc;



            // ======== fim do  3 - Verificando Pedidos de Compra ==========

            //verificando status do item
            $ped_item_status = '';
            $ped_item_status_color = 'style=""';

            if ($qtde_pendente_pv > 0 and $qtde_sugerida_op == 0 and $qtde_sugerida_PC == 0) {
                $ped_item_status = 'COMPRAR';
                $ped_item_status_color = 'style="background-color: orange;color:#ffff;"';
            } else if ($qtde_pendente_pv == 0 and $qtde_sugerida_op > 0) {
                $ped_item_status = 'PROGRAMADO';
                $ped_item_status_color = 'style=""';
            } else if ($qtde_pendente_pv == 0 and $qtde_sugerida_pc > 0) {
                $ped_item_status = 'PED COMPRAS';
                $ped_item_status_color = 'style=""';
            } else if ($qtde_pendente_pv > 0 and ($qtde_sugerida_pc > 0 or $qtde_sugerida_op > 0)) {
                $ped_item_status = 'PRODUZIR';
                $ped_item_status_color = 'style="background-color: orange;color:#ffff;"';
            } else {
                if (strpos($dep_est_atende, 'AG-PA')) {
                    $ped_item_status = 'ATENDIDO(AG)';
                    $ped_item_status_color = 'style=""';
                } else {
                    $ped_item_status = 'ATENDIDO';
                    $ped_item_status_color = 'style=""';
                }
            }


            //atualizando array pedido com dados calculados
            $pedido_vendas_array[$key_ped]['qtde_pend'] = $qtde_pendente_pv;
            $pedido_vendas_array[$key_ped]['situacao'] = $ped_item_status;
            $pedido_vendas_array[$key_ped]['situacao_color'] = $ped_item_status_color;
            $pedido_vendas_array[$key_ped]['data_prev'] = $item_data_prev_op . $item_data_prev_pc;
            $pedido_vendas_array[$key_ped]['doc'] = $dep_est_atende . $item_doc_atend_op . $item_doc_atend_pc;
            $pedido_vendas_array[$key_ped]['saldo_est'] = $saldo_disp_atu_item_corrent;
        } // fim do foreach pedidos

        //se imprime valores == false
        $titulo_col_valor = $imprimi_valores ? 'Valor R$ c/ ipi' : '-';

        $carteira_processada = '
            <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed tabela_carteira">
            <thead>
			<tr>
					<th>Ped Bling</th>
                    <th>Orc Biv</th>
                    <th>Nome Cliente</th>
                    <th>Emissão</th>
					<th>Entrega</th>
					<th>Cond. Pgto</th>
					<th>' . $titulo_col_valor . '</th>
					<th>Produto</th>
					<th>Qtde Pedido</th>
					<th>EST Sugest</th>
					<th>PC Sugest</th>
                    <th>Qtde Pend</th>
					<th>Situação</th>
					<th>Data Prev</th>
					<th>Doc</th>
                    <th>Saldo EST</th>
                    <th>Obs Pedido</th>
                    <th class="dados-extras d-none">UF</th>
                    <th class="dados-extras d-none">Cidade</th>
                    <th class="dados-extras d-none">Bairro</th>
                    <th class="dados-extras d-none">Volume</th>
                    <th class="dados-extras d-none">Peso Bruto</th>
                    <th class="dados-extras d-none">Frete</th>
                    <th class="dados-extras d-none" style="width: auto" >Msg Nota</th>
                    
            </tr>
            </thead>
            <tbody>
			';


        //print("<pre>" . print_r($pedido_vendas_array, true) . "</pre>");

        require('../config/conexao.php');

        //limpado dado de atualizacao dos itens na tabela carteira
        $limpa_tabela = mysql_query("UPDATE md_vendas_carteira SET updated_at= NULL WHERE situacao NOT IN ('FATURADO','CANCELADO')") or die(mysql_error());

        // percorrendo array pedidos ja processado
        foreach ($pedido_vendas_array as $key_ped => $value_ped) {

            // ================  Subtotal por Pedido   ============================
            if ($processar_carteira) { //se imprimi a carteira
                if ($subtotal_por_pedido and $imprimi_item_carteira) {   // se imprime subtotal do pedido
                    $qtde_pedido_cart += 1;
                    $status_pedido = '';
                    $status_pedido_color = '';
                    $status_pedido_icone = '';

                    // se todos itens atendidos exibir FATURAR
                    if ($total_pedido_pendente == 0 and $total_pedido_op == 0 and $total_pedido_pc == 0) {
                        if (strpos($total_dep_est_atende, 'AG-PA')) {
                            $status_pedido = '<a target="blank" style="color:#fff;" href="carteirapedidos.php?tipo_rel=total_por_pedido&referencia=&pedido=' . $num_pedido_check . '" >TRANSF AG</a>';
                            $status_pedido_color = 'style="background-color:#12a77b;color:#fff;text-align: left;"';
                        } else {
                            $status_pedido = '<a target="blank" style="color:#fff;" href="carteirapedidos.php?tipo_rel=total_por_pedido&referencia=&pedido=' . $num_pedido_check . '" >FATURAR</a>';
                            $status_pedido_color = 'style="background-color:green;color:#fff;text-align: left;"';
                        }
                    };

                    //verificar se a cond pgto do pedido necessita confirmacao e se foi confirmado
                    if ($cond_pgto_confpgto == 'CONF PGTO') {
                        $status_pedido = '<a target="blank" style="color:#fff;" href="carteirapedidos.php?tipo_rel=total_por_pedido&referencia=&pedido=' . $num_pedido_check . '" >CONF PGTO</a>';
                        $status_pedido_color = 'style="background-color:blue;color:#fff;text-align: left;"';
                    };



                    if ($value_ped['ped_num'] != $num_pedido_check and $num_pedido_check != "inicial") {



                        $carteira_processada .= '
                            <tr class="bg_subtotal_rel tr_result" >
                            <td>' . $num_pedido_check . '</td>
                            <td><a target="_blank" href="src/relpdf/orcamento.php?id=' . $total_ped_web_num . '&state=view" >' . $total_ped_web_num . '</a></td>
                            <td>' . $total_pedido_nomecli . '</td>
                            <td>' . $total_pedido_dataemissao . '</td>
                            <td >Vend: ' . $total_pedido_nomevendedor . '</td>
                            <td >' . $total_pedido_condpag . '</td>
                            <td align="right">' . number_format($total_pedido_valor,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_valor + $total_pedido_frete,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_qtde,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_est,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_pc,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_pendente,    2, ',', '.') . '</td>
                            <td align="right" ' . $status_pedido_color . '>' . $status_pedido . '</td>
                            <td align="right"></td>
                            <td align="right"></td>
                            <td align="right"></td>
                            <td>' . $total_pedido_obs_interna . '</td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_est . '</td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_mun . '</td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_bairro . '</td>
                            <td class="dados-extras d-none" align="right">' . number_format($total_pedido_volume,    2, ',', '.') . '</td>
                            <td class="dados-extras d-none" align="right">' . number_format($total_pedido_pesobruto,    2, ',', '.') . '</td>
                            <td class="dados-extras d-none" align="right">' . number_format($total_pedido_frete,    2, ',', '.') . '</td>
                            <td class="dados-extras d-none" align="right">' . $total_pedido_mennota . '</td>
                            
                            
                            ';

                        //zerando totais para proximo pedido
                        $total_pedido_qtde = 0;
                        $total_pedido_valor = 0;
                        $total_pedido_est = 0;
                        $total_pedido_op = 0;
                        $total_pedido_pc = 0;
                        $total_pedido_pendente = 0;
                        $total_pedido_volume = 0;
                        $total_pedido_pesobruto = 0;
                        $total_pedido_item_situacao = 0;
                        $total_dep_est_atende = '';
                    }
                }
                $num_pedido_check = $value_ped['ped_num'];
            }
            //========= Fim do se imprime subtotal do pedido =======

            //incluindo IPI no campo C6_VALOR
            $item_valor_cipi =  $value_ped['item_valor'] + ($value_ped['item_valor'] * $prod_array_ipi[$value_ped['item_ref']] / 100);
            $item_valor_cipi =  round($item_valor_cipi, 2);

            //verificando se previsa de entrega preenchido
            $ped_data_prev = $value_ped['ped_previsao'] == '' ? '' : date("d/m/y", strtotime($value_ped['ped_previsao']));

            $destacar_estoque = $value_ped['est_sugest'] > 0 ? 'style="background-color: LightGreen;"' : '';
            $destacar_op = $value_ped['op_sugest'] > 0 ? 'style="background-color: LightGreen;"' : '';
            $destacar_pc = $value_ped['pc_sugest'] > 0 ? 'style="background-color: LightGreen;"' : '';

            //filtro por produto
            $filtro_por_produto = $pesquisa_por_produto == '' ? $value_ped['item_ref'] : $pesquisa_por_produto;

            //filtro por pedido
            $filtro_por_pedido = $pesquisa_por_pedido == '' ? $value_ped['ped_num'] : $pesquisa_por_pedido;

            //filtro por orcamento biv
            $filtro_por_orcamento = $pesquisa_por_orcamento == '' ? $value_ped['ped_web_num'] : $pesquisa_por_orcamento;

            // ============== imprimir itens da carteira  ===========================
            //<td>' . date("d/m/y", strtotime($value_ped['ped_emissao'])) . '</td>

            //se nao imprimir valores = false
            $item_valor_cipi = $imprimi_valores ? $item_valor_cipi : 0;
            $total_pedido_frete = $total_pedido_frete ? $item_valor_cipi : 0;

            if ($imprimi_item_carteira and $filtro_por_produto == $value_ped['item_ref'] and $filtro_por_pedido == $value_ped['ped_num'] and $filtro_por_orcamento == $value_ped['ped_web_num']) {

            $volume_item = $prod_array_qtdecx[$value_ped['item_ref']] * $value_ped['item_qtde'];
            $peso_item = $prod_array_peso[$value_ped['item_ref']] * $value_ped['item_qtde'];

                //inserindo itens na tabela md_vendas_carteira
                $sql = "
                INSERT INTO md_vendas_carteira (
                    pv_id, item_pv_id, bling_emp, ud, ped_bling, orc_biv, nome_cliente, emissao, entrega, cond_pgto,
                    valor_rs_com_ipi, produto, qtde_pedido, est_sugest, op_sugest,
                    pc_sugest, qtde_pend, situacao, situacao_color, data_prev, doc,
                    saldo_est, tipo_cli, uf, cidade, bairro, volume, peso_bruto,
                    frete, msg_nota, obs_ped_cli, created_at, updated_at 
                ) VALUES (
                    '" . mysql_real_escape_string($value_ped['ped_id']) . "',
                    '" . mysql_real_escape_string($value_ped['item_pv_id']) . "',
                    '" . mysql_real_escape_string($value_ped['bling_emp']) . "',
                    '" . mysql_real_escape_string($ud_array[$value_ped['ped_ud']]) . "',
                    '" . mysql_real_escape_string($value_ped['ped_num']) . "',
                    '" . mysql_real_escape_string($value_ped['ped_web_num']) . "',
                    '" . mysql_real_escape_string($value_ped['cliente_nome']) . "',
                    '" . mysql_real_escape_string($value_ped['ped_emissao']) . "',
                    '" . mysql_real_escape_string($ped_data_prev) . "',
                    '" . mysql_real_escape_string($value_ped['cond_pgto']) . "',
                    '" . mysql_real_escape_string($item_valor_cipi) . "',
                    '" . mysql_real_escape_string($value_ped['item_ref']) . "',
                    '" . mysql_real_escape_string($value_ped['item_qtde']) . "',
                    '" . mysql_real_escape_string($value_ped['est_sugest']) . "',
                    '" . mysql_real_escape_string($value_ped['op_sugest']) . "',
                    '" . mysql_real_escape_string($value_ped['pc_sugest']) . "',
                    '" . mysql_real_escape_string($value_ped['qtde_pend']) . "',
                    '" . mysql_real_escape_string($value_ped['situacao']) . "',
                    '" . mysql_real_escape_string($value_ped['situacao_color']) . "',
                    '" . mysql_real_escape_string($value_ped['data_prev']) . "',
                    '" . mysql_real_escape_string($value_ped['doc']) . "',
                    '" . mysql_real_escape_string($value_ped['saldo_est']) . "',
                    '" . mysql_real_escape_string($value_ped['cliente_tipo']) . "',
                    '" . mysql_real_escape_string($value_ped['cliente_uf']) . "',
                    '" . mysql_real_escape_string($value_ped['cliente_cidade']) . "',
                    '" . mysql_real_escape_string($value_ped['cliente_bairro']) . "',
                    '" . mysql_real_escape_string($volume_item) . "',
                    '" . mysql_real_escape_string($peso_item) . "',
                    '0',
                    '',
                    '',
                    '" . date("Y-m-d H:i:s") . "',
                    '" . date("Y-m-d H:i:s")  . "'
                )
                ON DUPLICATE KEY UPDATE
                    ud                = VALUES(ud),
                    nome_cliente      = VALUES(nome_cliente),
                    emissao           = VALUES(emissao),
                    entrega           = VALUES(entrega),
                    cond_pgto         = VALUES(cond_pgto),
                    valor_rs_com_ipi  = VALUES(valor_rs_com_ipi),
                    produto           = VALUES(produto),
                    qtde_pedido       = VALUES(qtde_pedido),
                    est_sugest        = VALUES(est_sugest),
                    op_sugest         = VALUES(op_sugest),
                    pc_sugest         = VALUES(pc_sugest),
                    qtde_pend         = VALUES(qtde_pend),
                    situacao          = VALUES(situacao),
                    situacao_color    = VALUES(situacao_color),
                    data_prev         = VALUES(data_prev),
                    doc               = VALUES(doc),
                    saldo_est         = VALUES(saldo_est),
                    tipo_cli          = VALUES(tipo_cli),
                    uf                = VALUES(uf),
                    cidade            = VALUES(cidade),
                    bairro            = VALUES(bairro),
                    volume            = VALUES(volume),
                    peso_bruto        = VALUES(peso_bruto),
                    frete             = VALUES(frete),
                    msg_nota          = VALUES(msg_nota),
                    obs_ped_cli       = VALUES(obs_ped_cli),
                    updated_at        = VALUES(updated_at)
                ";

                echo '<hr>inserindou ou atualiznado md_vendas_carteira: <br>' . $sql . '<hr>';

                $result = mysql_query($sql);

                if (!$result) {
                    die('Erro no INSERT: ' . mysql_error());
                }

                // =========================================================================================


                $carteira_processada .= '
                    <tr class="tr_result ' . $value_ped['item_ref'] . '">
                    <td>' . $value_ped['ped_num'] . '</td>
                    <td><a target="_blank" href="src/relpdf/orcamento.php?id=' . $value_ped['ped_web_num'] . '&state=view" >' . $value_ped['ped_web_num'] . '</a></td>
                    <td>' . substr($value_ped['cliente_nome'], 0, 18) . '</td>
                    <td>' . date("d/m/y", strtotime($value_ped['ped_emissao'])) . '</td>
                    <td>' . $ped_data_prev . '</td>
                    <td>' . $value_ped['cond_pgto'] . '</td>
                    <td align="right">' . exibirValor($item_valor_cipi) . '</td>
                    <td><a target="_blank" href="md_vendas_rel_carteira.php?referencia=' . $value_ped['item_ref'] . '" >' . $value_ped['item_ref'] . '</a></td>
                    <td align="right">' . exibirValor($value_ped['item_qtde']) . '</td>
                    <td align="right" ' . $destacar_estoque . '>' . exibirValor($value_ped['est_sugest']) . '</td>
                    <td align="right" ' . $destacar_pc . '>' . exibirValor($value_ped['pc_sugest']) . '</td>
                    <td align="right">' . exibirValor($value_ped['qtde_pend']) . '</td>
                    <td ' . $value_ped['situacao_color'] . '>' . $value_ped['situacao'] . '</td>
                    <td>' . $value_ped['data_prev'] . '</td>  
                    <td>' . $value_ped['doc'] . '</td>  
                    <td align="right">' . exibirValor($value_ped['saldo_est']) . '</td>
                    <td>' . $value_ped['ped_obs_interna'] . '</td> 
                    <td class="dados-extras d-none">' . $value_ped['cliente_uf'] . '</td>
                    <td class="dados-extras d-none">' . $value_ped['cliente_cidade'] . '</td>
                    <td class="dados-extras d-none">' . $value_ped['cliente_bairro'] . '</td> 
                    <td class="dados-extras d-none"></td> 
                    <td align="right" class="dados-extras d-none">' . exibirValor($value_ped['item_pesototal']) . '</td> 
                    <td class="dados-extras d-none"></td> 
                    <td class="dados-extras d-none"></td> 
                    
                    </tr>
                    ';





                // totalizando pedidos

                $total_pedido_nomecli = substr($value_ped['cliente_nome'], 0, 18);
                $total_pedido_nomevendedor = substr($value_ped['vendedor_nome'], 0, 18);
                $total_pedido_condpag = $value_ped['cond_pgto'];
                $total_pedido_dataemissao =  date("d/m/y", strtotime($value_ped['ped_emissao']));
                $total_pedido_dataentrega =  date("d/m/y", strtotime($value_ped['ped_previsao']));
                $total_pedido_valor += $item_valor_cipi;
                $total_pedido_frete = $value_ped['ped_valorfrete'];
                $total_pedido_qtde += $value_ped['item_qtde'];
                $total_pedido_est += $value_ped['est_sugest'];
                $total_pedido_op += $value_ped['op_sugest'];
                $total_pedido_pc += $value_ped['pc_sugest'];
                $total_pedido_pendente += $value_ped['qtde_pend'];
                $total_pedido_cli_est = $value_ped['cliente_uf'];
                $total_pedido_cli_mun = $value_ped['cliente_cidade'];
                $total_pedido_cli_bairro = $value_ped['cliente_bairro'];
                $total_pedido_obs_interna = $value_ped['ped_obs_interna'];

                $total_pedido_pesobruto += $value_ped['item_pesototal'];

                $cond_pgto_confpgto = $value_ped['ped_situacao'] == 'Em aberto' ? 'CONF PGTO' : 'PAGO';
                $total_dep_est_atende .= $dep_est_atende;


                //Totalizando GERAL
                if ($processar_carteira) {
                    $total_geral_valor +=  $item_valor_cipi;
                    $total_geral_qtde += $value_ped['item_qtde'];

                    $total_geral_est +=  $value_ped['est_sugest'];
                    $total_geral_op += $value_ped['op_sugest'];
                    $total_geral_pc += $value_ped['pc_sugest'];
                    $total_geral_pendente += $value_ped['qtde_pend'];
                }
            } // fim do se imprimi_item_carteira
            $total_ped_web_num = $value_ped['ped_web_num'];
            $total_pedido_num = $value_ped['ped_num'];
        } // FIM DO percorrendo array pedidos ja processado

        // ================  Ultimo Subtotal por Pedido   ============================
        if ($processar_carteira) { //se imprimi a carteira
            if ($subtotal_por_pedido and $imprimi_item_carteira) {   // se imprime subtotal do pedido

                $status_pedido = '';
                $status_pedido_color = '';
                $status_pedido_icone = '';

                // se todos itens atendidos exibir FATURAR
                if ($total_pedido_pendente == 0 and $total_pedido_op == 0 and $total_pedido_pc == 0) {
                    $status_pedido = '<a target="blank" style="color:#fff;" href="carteirapedidos.php?tipo_rel=total_por_pedido&referencia=&pedido=' . $num_pedido_check . '" >FATURAR</a>';
                    $status_pedido_color = 'style="background-color:green;color:#fff;text-align: left;"';
                };

                //verificar se a cond pgto do pedido necessita confirmacao e se foi confirmado
                if ($cond_pgto_confpgto == 'CONF PGTO') {
                    $status_pedido = '<a target="blank" style="color:#fff;" href="carteirapedidos.php?tipo_rel=total_por_pedido&referencia=&pedido=' . $num_pedido_check . '" >CONF PGTO</a>';
                    $status_pedido_color = 'style="background-color:blue;color:#fff;text-align: left;"';
                };



                if ($num_pedido_check != "inicial") {



                    $carteira_processada .= '
                        <tr class="bg_subtotal_rel tr_result" >
                        <td>' . $num_pedido_check . '</td>
                        <td><a target="_blank" href="src/relpdf/orcamento.php?id=' . $total_ped_web_num . '&state=view" >' . $total_ped_web_num . '</a></td>
                        <td>' . $total_pedido_nomecli . '</td>
                        <td>' . $total_pedido_dataemissao . '</td>
                        <td >Vend: ' . $total_pedido_nomevendedor . '</td>
                        <td >' . $total_pedido_condpag . '</td>
                        <td align="right">' . number_format($total_pedido_valor,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_valor + $total_pedido_frete,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_qtde,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_est,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_pc,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_pendente,    2, ',', '.') . '</td>
                        <td align="right" ' . $status_pedido_color . '>' . $status_pedido . '</td>
                        <td align="right"></td>
                        <td align="right"></td>
                        <td align="right"></td>
                        <td>' . $total_pedido_obs_interna . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_est . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_mun . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_bairro . '</td>
                        <td class="dados-extras d-none" align="right">' . number_format($total_pedido_volume,    2, ',', '.') . '</td>
                        <td class="dados-extras d-none" align="right">' . number_format($total_pedido_pesobruto,    2, ',', '.') . '</td>
                        <td class="dados-extras d-none" align="right">' . number_format($total_pedido_frete,    2, ',', '.') . '</td>
                        <td class="dados-extras d-none" align="right">' . $total_pedido_mennota . '</td>
                        
                        ';

                    //zerando totais para proximo pedido
                    $total_pedido_qtde = 0;
                    $total_pedido_valor = 0;
                    $total_pedido_est = 0;
                    $total_pedido_op = 0;
                    $total_pedido_pc = 0;
                    $total_pedido_pendente = 0;
                    $total_pedido_volume = 0;
                    $total_pedido_pesobruto = 0;
                    $total_pedido_item_situacao = 0;
                }
            }
            $num_pedido_check = $total_pedido_num;
        }
        //========= Fim do Ultimo subtotal do pedido =======
        // ===============   Total GERAL===========  ================
        if ($processar_carteira) {
            $carteira_processada .=  '
                    <tr class="bg_subtotal_rel tr_result" >
                        <td colspan="6">TOTAL GERAL ' . $qtde_pedido_cart . ' Pedido(s)</td>
                        <td align="right">' . number_format($total_geral_valor,    2, ',', '.') . '</td>
                        <td align="right"></td>
                        <td align="right">' . number_format($total_geral_qtde,    2, ',', '.') . '</td>    
                        <td align="right">' . number_format($total_geral_est,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_geral_pc,    2, ',', '.') . '</td>
                        
                        <td align="right">' . number_format($total_geral_pendente,    2, ',', '.') . '</td>					
                        
                        <td align="right"></td>	
                        <td align="right"></td>	
                        <td align="right"></td>	
                        <td align="right"></td>
                        <td align="right"></td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_est . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_mun . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_bairro . '</td>
                        <td class="dados-extras d-none" align="right"></td>
                        <td class="dados-extras d-none" align="right"></td>
                        <td class="dados-extras d-none" align="right"></td>
                        <td class="dados-extras d-none" align="right"></td>
                        
                    </tr>';
        } // fim do Se imprimi a carteira

        //fechamento da tabela
        $carteira_processada .= '</tbody>
            </table>
            </div>';
    }
} // END carteira processada



echo '<hr> dados processados finalizados v2<hr>';
