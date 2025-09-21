<?php
//inicio do cronometro
$inicio2 = microtime(true);

date_default_timezone_set('America/Sao_Paulo');
require('../config/conexao.php');
include_once('../../sys_functions.php');

// ============== AJAX CARTEIRA ==============================================
// Arquivo para gerar a carteira e distribuir estoque. Gerando no final o status de cada pedido.

//VARIAVEIS DE CONTROLE
$atender_parcial_estoque = TRUE;
$atender_parcial_op = TRUE;
$atender_parcial_pc = TRUE;
$consulta_estoque = TRUE;
$consulta_op = TRUE;
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

//recebendo POST 
if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if (isset($_POST['pedido']) and $_POST['pedido'] != '') {
        $num_pedidos = str_replace(' ', '', $_POST['pedido']);
        $pesquisa_por_pedido = explode(",", $num_pedidos);
        //$subtotal_por_pedido_ultimo = FALSE;
    } else {
        $pesquisa_por_pedido = 0;
    }

    if (isset($_POST['cliente']) and $_POST['cliente'] != '') {
        $pesquisa_por_cliente = strtoupper($_POST['cliente']);
        //$subtotal_por_pedido_ultimo = FALSE;
    }

    if (isset($_POST['unidade'])) {
        $pesquisa_por_unidade = strtoupper($_POST['unidade']);
        //$subtotal_por_pedido_ultimo = FALSE;
    }


    if (isset($_POST['referencia'])) {
        $referencia = str_replace(" ", "", $_POST['referencia']); //eliminar espaços 
        $produto = trim(strtoupper($referencia));
        if ($produto != "") {
            $pesquisa_por_produto = $produto;
            $subtotal_por_pedido = FALSE;
        } else {
            $pesquisa_por_produto = "";
        };
    }

    //SE CONSULTA VINDO DA TELA DE CONSULTA DE PRODUTO
    if (isset($_POST['ref'])) {
        $referencia = str_replace(" ", "", $_POST['ref']); //eliminar espaços 
        $produto = strtoupper($referencia);
        if ($produto != "") {
            $pesquisa_por_produto = $produto;
            $subtotal_por_pedido = FALSE;
            $imprimi_apenas_resumo_ref = TRUE;
        } else {
            $pesquisa_por_produto = "";
        };
    }
} // fim do POST
// ============== (1.1)  CONSULTA ESTOQUE VETROMANI    ====================================
/*
if ($consulta_estoque) {
    $query2 = "SELECT referencia, deposito, sum(saldo) as saldo, sum(saldo_disp) as saldo_disp 
                FROM `md_estoque_bling` 
                WHERE deposito != 'Manual'
                GROUP BY referencia, deposito
                ORDER BY referencia";
    $result_query2 = mysql_query($query2);
    $qtde_query2 = mysql_num_rows($result_query2);
    if ($qtde_query2 > 0) {
        while ($campos = mysql_fetch_array($result_query2)) {

            // =======  carrega o array com os dados do estoque ============
            $saldoreal_ref = $campos['saldo']; // SALDOREAL = SALDO ATUAL - EMPENHO (Campo calculado na query)
            settype($saldoreal_ref, "float");
            $estoquedisp[] = array(
                'ref' => trim($campos['referencia']),
                'saldo_disp' => $saldoreal_ref,
                'saldo_disp_atu' => $saldoreal_ref,
                'deposito' => $campos['deposito']
            );
        }
    } else {
        echo 'OPs! Não foi possivel consultar estoque';
    }

    //print("<pre>" . print_r($estoquedisp, true) . "</pre>");//

}
*/
// ==================   fim do consulta (1.1) estoque  ========================= 

// ============== (4) PROCESSAR CARTEIRA     ====================================
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

//array com a identificação da unidade onde o pedido está.
$ud_array['204261108'] = 'VH/VC';
$ud_array['204264525'] = 'VH/VC';
$ud_array['204264613'] = 'VH/VC';
$ud_array['204415556'] = 'AG';
$ud_array['204581986'] = 'VM';


if ($processar_carteira) {
    //chamdada api pedidos get
    // include_once('../../src/api/bling_pedido_vendas_get.php');
    // //chamdada api prod_estoque get
    // include_once('../../src/api/bling_prod_estoque_get.php');
    // //chamdada api Op get
    // include_once('../../src/api/bling_op_get.php');
    // //chamdada api PC get
    // include_once('../../src/api/bling_pedido_compras_get.php');

    //chamdada api bling v3
    include_once('../../src/api/bv3_pv_get.php');
    include_once('../../src/api/bv3_prod_estoque_get.php');
    include_once('../../src/api/bv3_op_get.php');
    include_once('../../src/api/bv3_pc_get.php');

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
        // echo $carteira_processada;
        exit;
    } else {
        //criar array com dados do produto:
        $query1 = "SELECT * FROM md_cad_produtos";
        $result_query1 = mysql_query($query1);
        $qtde_query1 = mysql_num_rows($result_query1);
        if ($qtde_query1 > 0) {
            while ($campos = mysql_fetch_array($result_query1)) {
                $prod_array_ipi[trim($campos['referencia'])] = $campos['ipi'];
                $prod_array_peso[trim($campos['referencia'])] = $campos['peso'];
                $produtos_array[trim($campos['referencia'])] = $campos['unidade'];
                $produtos_desc_array[trim($campos['referencia'])] = $campos['descricao'];
            }
        }
        //incluindo IPI no valor do item
        $item_valor_total =  $value['item_valor'] + ($value['item_valor'] * $prod_array_ipi[$value['item_ref']] / 100);

        //=== historico de vendas 6 meses por produto para relatorio programacao
        $retro = 6;
        $data_inicio = date('Ym', strtotime('-' . $retro . ' months')) . '01'; // 1 dia do mes
        $data_final = date('Ymt', strtotime('-1 months'));  // ultimo dia do mes
        $query1 = "SELECT YEAR(p.pedido_conv_date) as ano, MONTH(p.pedido_conv_date) AS mes,i.codigo, SUM(i.qtde) as qtd  FROM `md_vendas_pedidos` as p 
        LEFT JOIN md_vendas_pedidos_itens as i ON i.pedido_id = p.id
        WHERE p.status = 'P'
        AND i.status = 'A'
        AND cast(p.pedido_conv_date as date) between '$data_inicio' and '$data_final'
        GROUP BY ano, MES, i.codigo
            ";

        $result_query1 = mysql_query($query1);
        $qtde_query1 = mysql_num_rows($result_query1);
        // echo 'registro historico ='. $qtde_query1 .'<hr>';

        if ($qtde_query1 > 0) {
            while ($campos = mysql_fetch_array($result_query1)) {

                $prod_mes_ano = trim($campos['codigo']) . '-' . $campos['mes'] . '/' . $campos['ano'];
                // =======  carrega o array com os dados de venda por produto ============
                //$venda_prod_array_mesano[$prod_mes_ano] = round($campos['QTDV'], 2);
                $venda_prod_mesames_array[$prod_mes_ano] += round($campos['qtd'], 2);

                $venda_prod_array[trim($campos['codigo'])] += round($campos['qtd'], 2);
            }
        }
        // print("<pre>" . print_r($venda_prod_array, true) . "</pre>");


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
                        if ($value_estoq['ref'] == $value_ped['item_ref'] and $qtde_pendente_pv > 0 and $value_estoq['saldo_disp_atu'] > 0 and ($value_estoq['deposito'] != 'VH-OUTLET' and $value_estoq['deposito'] != 'AG-OUTLET')) {
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
                        if ($value_estoq['ref'] == $value_ped['item_ref'] and $qtde_pendente_pv > 0 and $value_estoq['saldo_disp_atu'] >= $qtde_pendente_pv and ($value_estoq['deposito'] != 'VH-OUTLET' and $value_estoq['deposito'] != 'AG-OUTLET')) {
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

            //  =============== (2) Verificando OPs  ============

            $qtde_sugerida_op = 0;
            $destacar_op = '';
            $data_prev_op = '';
            $item_doc_atend_op = '';
            $item_data_prev_op = '';

            if ($qtde_pendente_pv > 0 and $consulta_op and $atender_pedidovenda) {
                if ($atender_parcial_op) {
                    foreach ($op_array as $key_op => $value_op) {
                        if ($value_op['op_ref'] == $value_ped['item_ref']  and $qtde_pendente_pv > 0 and $value_op['op_qtde_atu'] > 0) {
                            $qtde_sugerida_op_item_atual = 0;
                            $saldo_disp_atu_item_corrent_calc1 = round($value_op['op_qtde_atu'], 2) - $qtde_pendente_pv;
                            if ($saldo_disp_atu_item_corrent_calc1 < 0) {
                                $saldo_disp_atu_item_corrent = 0;
                                $qtde_pendente_pv = $qtde_pendente_pv - round($value_op['op_qtde_atu'], 2);
                                $qtde_sugerida_op += round($value_op['op_qtde_atu'], 2);
                                $qtde_sugerida_op_item_atual = round($value_op['op_qtde_atu'], 2);
                            } else {
                                $saldo_disp_atu_item_corrent = $saldo_disp_atu_item_corrent_calc1;
                                $qtde_sugerida_op += $qtde_pendente_pv;
                                $qtde_sugerida_op_item_atual = $qtde_pendente_pv;
                                $qtde_pendente_pv = 0;
                            }

                            $op_array[$key_op]['op_qtde_atu'] = $saldo_disp_atu_item_corrent;

                            $destacar_op = 'style="background-color: LightGreen;"';
                            $item_data_prev_op .= substr($value_op['op_previsaoFinal'], 0, 10) . ' ';
                            $item_doc_atend_op .= 'OP:' . $value_op['op_num'] . ' (' . number_format($qtde_sugerida_op_item_atual,    2, ',', '.') . ') ';
                        }
                    }
                } else {
                    foreach ($op_array as $key_op => $value_op) {
                        if ($value_op['op_ref'] == $value_ped['item_ref']  and $qtde_pendente_pv > 0 and $value_op['op_qtde_atu'] >= $qtde_pendente_pv) {
                            $op_array[$key_op]['op_qtde_atu'] = round($value_op['op_qtde_atu'], 2) - $qtde_pendente_pv;
                            $saldo_disp_atu_item_corrent = round($value_op['op_qtde_atu'], 2) - $qtde_pendente_pv;
                            $qtde_sugerida_op = $qtde_pendente_pv;
                            $qtde_pendente_pv = 0;
                            $destacar_op = 'style="background-color: LightGreen;"';
                            $item_data_prev_op = substr($value_op['op_previsaoFinal'], 0, 10);
                            $item_doc_atend_op = 'OP:' . $value_op['op_num'];
                        }
                    }
                }
            }
            $pedido_vendas_array[$key_ped]['op_sugest'] = $qtde_sugerida_op;

            // ======== fim do  2 - Verificando Ops  ==========

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
                                $item_data_prev_pc .= $value_pc['pc_previsao'] . ' ';
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
                            $item_data_prev_pc = $value_pc['pc_previsao'] . ' ';
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
                $ped_item_status = 'PRODUZIR';
                $ped_item_status_color = 'style="background-color: orange;color:#ffff;"';
            } else if ($qtde_pendente_pv == 0 and $qtde_sugerida_op > 0) {
                $ped_item_status = 'PROGRAMADO';
                $ped_item_status_color = 'style=""';
            } else if ($qtde_pendente_pv == 0 and $qtde_sugerida_pc > 0) {
                $ped_item_status = 'PROGRAMADO AGAS';
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

        $carteira_processada = '
            <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed tabela_carteira">
            <thead>
			<tr>    
                    <th>UD</th>
					<th>Ped Bling</th>
                    <th>Orc Biv</th>
                    <th>Nome Cliente</th>
                    <th>Emissão</th>
					<th>Entrega</th>
					<th>Cond. Pgto</th>
					<th>Valor R$ c/ ipi</th>
					<th>Produto</th>
					<th>Qtde Pedido</th>
					<th>EST Sugest</th>
					<th>OP Sugest</th>
					<th>PC Sugest</th>
                    <th>Qtde Pend</th>
					<th>Situação</th>
					<th>Data Prev</th>
					<th>Doc</th>
                    <th>Saldo EST</th>
                    <th class="dados-extras d-none">Tipo Cli</th>
                    <th class="dados-extras d-none">UF</th>
                    <th class="dados-extras d-none">Cidade</th>
                    <th class="dados-extras d-none">Bairro</th>
                    <th class="dados-extras d-none">Volume</th>
                    <th class="dados-extras d-none">Peso Bruto</th>
                    <th class="dados-extras d-none">Frete</th>
                    <th class="dados-extras d-none" style="width: auto" >Msg Nota</th>
                    <th class="dados-extras d-none" style="width: 200px" >Obs Ped Cli</th>
            </tr>
            </thead>
            <tbody>
			';


        //print("<pre>" . print_r($pedido_vendas_array, true) . "</pre>");

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
                            <td></td>
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
                            <td align="right">' . number_format($total_pedido_op,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_pc,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_pedido_pendente,    2, ',', '.') . '</td>
                            <td align="right" ' . $status_pedido_color . '>' . $status_pedido . '</td>
                            <td align="right"></td>
                            <td align="right"></td>
                            <td align="right"></td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_tipo . '</td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_est . '</td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_mun . '</td>
                            <td class="dados-extras d-none">' . $total_pedido_cli_bairro . '</td>
                            <td class="dados-extras d-none" align="right">' . number_format($total_pedido_volume,    2, ',', '.') . '</td>
                            <td class="dados-extras d-none" align="right">' . number_format($total_pedido_pesobruto,    2, ',', '.') . '</td>
                            <td class="dados-extras d-none" align="right">' . number_format($total_pedido_frete,    2, ',', '.') . '</td>
                            <td class="dados-extras d-none" align="right">' . $total_pedido_mennota . '</td>
                            <td class="dados-extras d-none" align="right">' . $total_pedido_yobsped . '</td>
                            
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
            if ($value_ped['ped_previsao'] == '' or $value_ped['ped_previsao'] == '0000-00-00') {
                $ped_data_prev = '';
            } else {
                $ped_data_prev = date("d/m/y", strtotime($value_ped['ped_previsao']));
            }


            $destacar_estoque = $value_ped['est_sugest'] > 0 ? 'style="background-color: LightGreen;"' : '';
            $destacar_op = $value_ped['op_sugest'] > 0 ? 'style="background-color: LightGreen;"' : '';
            $destacar_pc = $value_ped['pc_sugest'] > 0 ? 'style="background-color: LightGreen;"' : '';

            //filtro por produto
            $filtro_por_produto = $pesquisa_por_produto == '' ? $value_ped['item_ref'] : $pesquisa_por_produto;
            // ============== imprimir itens da carteira  ===========================
            //<td>' . date("d/m/y", strtotime($value_ped['ped_emissao'])) . '</td>



            if ($imprimi_item_carteira and $filtro_por_produto == $value_ped['item_ref']) {

                $peso_item = $prod_array_peso[$value_ped['item_ref']] * $value_ped['item_qtde'];

                $carteira_processada .= '
                    <tr class="tr_result ' . $value_ped['item_ref'] . '">
                    <td>' . $ud_array[$value_ped['ped_ud']] . '</td>
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
                    <td align="right" ' . $destacar_op . '>' . exibirValor($value_ped['op_sugest']) . '</td>
                    <td align="right" ' . $destacar_pc . '>' . exibirValor($value_ped['pc_sugest']) . '</td>
                    <td align="right">' . exibirValor($value_ped['qtde_pend']) . '</td>
                    <td ' . $value_ped['situacao_color'] . '>' . $value_ped['situacao'] . '</td>
                    <td>' . $value_ped['data_prev'] . '</td>  
                    <td>' . $value_ped['doc'] . '</td>  
                    <td align="right">' . exibirValor($value_ped['saldo_est']) . '</td>
                    <td class="dados-extras d-none">' . $value_ped['cliente_tipo'] . '</td>
                    <td class="dados-extras d-none">' . $value_ped['cliente_uf'] . '</td>
                    <td class="dados-extras d-none">' . $value_ped['cliente_cidade'] . '</td>
                    <td class="dados-extras d-none">' . $value_ped['cliente_bairro'] . '</td> 
                    <td class="dados-extras d-none">' . $value_ped['item_volume'] . '</td> 
                    <td align="right" class="dados-extras d-none">' . exibirValor($peso_item) . '</td> 
                    <td class="dados-extras d-none"></td> 
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

                $total_pedido_pesobruto += $peso_item;
                $total_pedido_volume = $value_ped['ped_volume'];
                $total_pedido_cli_tipo = $value_ped['cliente_tipo'];
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
                        <td></td>
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
                        <td align="right">' . number_format($total_pedido_op,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_pc,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_pendente,    2, ',', '.') . '</td>
                        <td align="right" ' . $status_pedido_color . '>' . $status_pedido . '</td>
                        <td align="right"></td>
                        <td align="right"></td>
                        <td align="right"></td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_est . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_mun . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_bairro . '</td>
                        <td class="dados-extras d-none">' . $total_pedido_cli_tipo . '</td>
                        <td class="dados-extras d-none" align="right">' . number_format($total_pedido_volume,    2, ',', '.') . '</td>
                        <td class="dados-extras d-none" align="right">' . number_format($total_pedido_pesobruto,    2, ',', '.') . '</td>
                        <td class="dados-extras d-none" align="right">' . number_format($total_pedido_frete,    2, ',', '.') . '</td>
                        <td class="dados-extras d-none" align="right">' . $total_pedido_mennota . '</td>
                        <td class="dados-extras d-none" align="right">' . $total_pedido_yobsped . '</td>
                        
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
                        <td colspan="7">TOTAL GERAL ' . $qtde_pedido_cart . ' Pedido(s)</td>
                        <td align="right">' . number_format($total_geral_valor,    2, ',', '.') . '</td>
                        <td align="right"></td>
                        <td align="right">' . number_format($total_geral_qtde,    2, ',', '.') . '</td>    
                        <td align="right">' . number_format($total_geral_est,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_geral_op,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_geral_pc,    2, ',', '.') . '</td>
                        
                        <td align="right">' . number_format($total_geral_pendente,    2, ',', '.') . '</td>					
                        
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
                        <td class="dados-extras d-none" align="right"></td>
                    </tr>';
        } // fim do Se imprimi a carteira

        //fechamento da tabela
        $carteira_processada .= '</tbody>
            </table>
            </div>';
    }
} // END carteira processada

//  ==========  TABELA COM SALDOS ESTOQUE, PC E PV DO ITEM  ==================================================

//IF imprimir apenas resumo, limpa variavel de retorno para ficar apenas a tabela de resumo do produto
if ($imprimi_apenas_resumo_ref) {
    $carteira_processada = '';
}
// ================= Consulta de Disponibilidade de Esoque  ============================
if ($pesquisa_por_produto <> '') {

    //se perfil vendedor externo nao exibe colunas 
    if ($perfil == 'V') {  // SE PERFIL FOR VENDEDOR EXIBIR APENAS OS DISPONIVEIS
        // ============ ESTOQUE DISPONIVEL  ============
        $carteira_processada .= '
        <div class="col">
        <strong> <i class="fas fa-cubes"></i> Disponibilidade do Produto: ' . $produto . '</strong><br><hr>
        <strong> Pronta Entrega:</strong>
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
                <th>Armazém</th>
				<th>UN</th>
			
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

        //percorrer array estoque
        $total_geral_disp_saldo = 0;
        $total_geral_disp_empenho = 0;
        $total_geral_disp_disponivel = 0;

        $total_disp_estoque_saldo = 0;
        $total_disp_estoque_empenho = 0;
        $total_disp_estoque_diponivel = 0;


        foreach ($estoquedisp as $key_estoq => $value_estoq) {
            $estoque_empenho = 0;
            $estoque_menos_total_pedidos = 0;
            //print("<pre>" . print_r($estoquedisp, true) . "</pre>");

            if ($value_estoq['ref'] == $produto and $value_estoq['saldo_disp'] > 0) {
                $estoque_empenho = $value_estoq['saldo_disp'] - $value_estoq['saldo_disp_atu'];
                $unidade_medida_produto_pesquisado = $value_estoq['ref_um'];
                $amz = $value_estoq['deposito'];

                $carteira_processada .= '
                <tr>
                    <td>' . $amz . '</td>
                    <td>' . $unidade_medida_produto_pesquisado . '</td>
             
                    <td align="right">' . number_format($value_estoq['saldo_disp_atu'],    2, ',', '.') . '</td>
    
                </tr>
                ';

                $total_disp_estoque_saldo += $value_estoq['saldo_disp'];
                $total_disp_estoque_empenho += $estoque_empenho;
                $total_disp_estoque_diponivel += $value_estoq['saldo_disp_atu'];
            }
        } // fim do foreach

        //total geral
        $total_geral_disp_saldo += $total_disp_estoque_saldo;
        $total_geral_disp_empenho += $total_disp_estoque_empenho;
        $total_geral_disp_disponivel += $total_disp_estoque_diponivel;

        $carteira_processada .= '
		<tr class="bg_subtotal_rel" >
			<td>TOTAL </td>
            <td></td>

			<td align="right" class="bg-success">' . number_format($total_disp_estoque_diponivel,    2, ',', '.') . '</td>
			
        </tr>
        </tbody>
		</table>
	';

        // ============ PREVISAO DE DISPONIBILIDADE  ============

        $carteira_processada .= '<strong> Previsão de Disponibilidade:</strong>';
        $carteira_processada .= '
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
				<th >OP</th>
                <th>Data Prev</th>
				<th >UM</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

        //percorrer array ordems producao
        $total_disp_op_saldo = 0;
        $total_disp_op_empenho = 0;
        $total_disp_op_diponivel = 0;
        $item_data_prev = '';
        foreach ($op_array as $key_op => $value_op) {
            $op_empenho = 0;
            if ($value_op['op_ref'] == $produto and $value_op['op_qtde'] > 0) {
                $op_empenho = $value_op['op_qtde'] - $value_op['op_qtde_atu'];
                $item_data_prev = substr($value_op['op_previsaoFinal'], 0, 10);
                if ($value_op['op_qtde_atu'] > 0) {
                    $carteira_processada .= '
                    <tr>
                        <td>' . $value_op['op_num'] . '</td>
                        <td>' . $item_data_prev . '</td>
                        <td>' . $unidade_medida_produto_pesquisado . '</td>
                            <td align="right">' . number_format($value_op['op_qtde_atu'],    2, ',', '.') . '</td>
    
                    </tr>
                    ';
                }


                $total_disp_op_saldo += $value_op['op_qtde'];
                $total_disp_op_empenho += $op_empenho;
                $total_disp_op_diponivel += $value_op['op_qtde_atu'];
            }
        } // fim do foreach

        //total geral
        $total_geral_disp_saldo += $total_disp_op_saldo;
        $total_geral_disp_empenho += $total_disp_op_empenho;
        $total_geral_disp_disponivel += $total_disp_op_diponivel;

        // ============ PCs DISPONIVEL  ============



        //percorrer array pedido compras
        $total_disp_pc_saldo = 0;
        $total_disp_pc_empenho = 0;
        $total_disp_pc_diponivel = 0;
        $item_data_prev = '';
        foreach ($pc_array as $key_pc => $value_pc) {
            $pc_empenho = 0;
            if ($value_pc['pc_ref'] == $produto and $value_pc['pc_qtde'] > 0) {
                $pc_empenho = $value_pc['pc_qtde'] - $value_pc['pc_qtde_atu'];
                $item_data_prev = $value_pc['pc_previsao'];
                if ($value_pc['pc_qtde_atu'] > 0) {
                    $carteira_processada .= '
                    <tr>
                        <td>' . $value_pc['pc_num'] . '</td>
                        <td>' . $item_data_prev . '</td>
                        <td>' . $unidade_medida_produto_pesquisado . '</td>
            
                        <td align="right">' . number_format($value_pc['pc_qtde_atu'],    2, ',', '.') . '</td>

                    </tr>
                    ';
                }


                $total_disp_pc_saldo += $value_pc['pc_qtde'];
                $total_disp_pc_empenho += $pc_empenho;
                $total_disp_pc_diponivel += $value_pc['pc_qtde_atu'];
            }
        } // fim do foreach

        //total geral
        $total_geral_disp_saldo += $total_disp_pc_saldo;
        $total_geral_disp_empenho += $total_disp_pc_empenho;
        $total_geral_disp_disponivel += $total_disp_pc_diponivel;

        $carteira_processada .= '
		<tr class="bg_subtotal_rel" >
			<td colspan="3" > TOTAL </td>
			<td align="right" class="bg-success">' . number_format($total_disp_op_diponivel + $total_disp_pc_diponivel,    2, ',', '.') . '</td>
        </tr>
        </tbody>
		</table>
	';



        // ============ TOTAL GERAL DISPONIVEL  ============

        $carteira_processada .= '
    
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
                <th >TOTAL GERAL</th>
				<th style="width: 160px;" class="bg-success text-right" align="right">' . number_format($total_geral_disp_disponivel,    2, ',', '.') . '</th>
		
        </tr>
        </thead>
 
		</table>
        </div>
		';
    } else {  // SE NAO FOR PERFIL VENDEDOR EXIBIR DETALHAMENTO

        // ============ ESTOQUE DISPONIVEL  ============
        $carteira_processada .= '
        <div class="col">
        <strong> <i class="fas fa-cubes"></i> Disponibilidade do Produto: ' . $produto . '</strong><br><hr>
        <strong> Estoque:</strong>
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
                <th>Armazém</th>
				<th>UN</th>
				<th style="width: 160px;">Quantidade</th>
				<th style="width: 160px;">Empenho</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

        //percorrer array estoque
        $total_geral_disp_saldo = 0;
        $total_geral_disp_empenho = 0;
        $total_geral_disp_disponivel = 0;

        $total_disp_estoque_saldo = 0;
        $total_disp_estoque_empenho = 0;
        $total_disp_estoque_diponivel = 0;


        foreach ($estoquedisp as $key_estoq => $value_estoq) {
            $estoque_empenho = 0;
            $estoque_menos_total_pedidos = 0;
            //print("<pre>" . print_r($estoquedisp, true) . "</pre>");

            if ($value_estoq['ref'] == $produto and $value_estoq['saldo_disp'] > 0) {
                $estoque_empenho = $value_estoq['saldo_disp'] - $value_estoq['saldo_disp_atu'];
                $unidade_medida_produto_pesquisado = $value_estoq['ref_um'];
                $amz = $value_estoq['deposito'];

                $carteira_processada .= '
                <tr>
                    <td>' . $amz . '</td>
                    <td>' . $unidade_medida_produto_pesquisado . '</td>
                    <td align="right">' . number_format($value_estoq['saldo_disp'],    2, ',', '.') . '</td>
                    <td align="right">' . number_format($estoque_empenho,    2, ',', '.') . '</td>
                    <td align="right">' . number_format($value_estoq['saldo_disp_atu'],    2, ',', '.') . '</td>
    
                </tr>
                ';

                $total_disp_estoque_saldo += $value_estoq['saldo_disp'];
                $total_disp_estoque_empenho += $estoque_empenho;
                $total_disp_estoque_diponivel += $value_estoq['saldo_disp_atu'];
            }
        } // fim do foreach

        //total geral
        $total_geral_disp_saldo += $total_disp_estoque_saldo;
        $total_geral_disp_empenho += $total_disp_estoque_empenho;
        $total_geral_disp_disponivel += $total_disp_estoque_diponivel;

        $carteira_processada .= '
		<tr class="bg_subtotal_rel" >
			<td>TOTAL </td>
            <td></td>
			<td align="right">' . number_format($total_disp_estoque_saldo,    2, ',', '.') . '</td>
			<td align="right">' . number_format($total_disp_estoque_empenho,    2, ',', '.') . '</td>
			<td align="right" class="bg-success">' . number_format($total_disp_estoque_diponivel,    2, ',', '.') . '</td>
			
        </tr>
        </tbody>
		</table>
	';

        // ============ OPs DISPONIVEL  ============

        $carteira_processada .= '<strong> OP:</strong>';
        $carteira_processada .= '
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
				<th >OP</th>
                <th>Data Prev</th>
				<th>UM</th>
				<th     style="width: 160px;">Quantidade</th>
				<th style="width: 160px;">Empenho</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

        //percorrer array ordems producao
        $total_disp_op_saldo = 0;
        $total_disp_op_empenho = 0;
        $total_disp_op_diponivel = 0;
        $item_data_prev = '';
        foreach ($op_array as $key_op => $value_op) {
            $op_empenho = 0;
            if ($value_op['op_ref'] == $produto and $value_op['op_qtde'] > 0) {
                $op_empenho = $value_op['op_qtde'] - $value_op['op_qtde_atu'];
                $item_data_prev = substr($value_op['op_previsaoFinal'], 0, 10);
                $carteira_processada .= '
	    	<tr>
				<td>' . $value_op['op_num'] . '</td>
                <td>' . $item_data_prev . '</td>
				<td>' . $unidade_medida_produto_pesquisado . '</td>
				<td align="right">' . number_format($value_op['op_qtde'],    2, ',', '.') . '</td>
				<td align="right">' . number_format($op_empenho,    2, ',', '.') . '</td>
				<td align="right">' . number_format($value_op['op_qtde_atu'],    2, ',', '.') . '</td>

			</tr>
	    	';

                $total_disp_op_saldo += $value_op['op_qtde'];
                $total_disp_op_empenho += $op_empenho;
                $total_disp_op_diponivel += $value_op['op_qtde_atu'];
            }
        } // fim do foreach

        //total geral
        $total_geral_disp_saldo += $total_disp_op_saldo;
        $total_geral_disp_empenho += $total_disp_op_empenho;
        $total_geral_disp_disponivel += $total_disp_op_diponivel;

        $carteira_processada .= '
		<tr class="bg_subtotal_rel" >
			<td colspan="3" > TOTAL </td>
			<td align="right">' . number_format($total_disp_op_saldo,    2, ',', '.') . '</td>
			<td align="right">' . number_format($total_disp_op_empenho,    2, ',', '.') . '</td>
			<td align="right" class="bg-success">' . number_format($total_disp_op_diponivel,    2, ',', '.') . '</td>
        </tr>
        </tbody>
		</table>
	';

        // ============ PCs DISPONIVEL  ============

        $carteira_processada .= '<strong> Ped. Compras:</strong>';
        $carteira_processada .= '
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
				<th>PC</th>
                <th>Data Prev</th>
				<th>UM</th>
				<th style="width: 160px;">Quantidade</th>
				<th style="width: 160px;">Empenho</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

        //percorrer array pedido compras
        $total_disp_pc_saldo = 0;
        $total_disp_pc_empenho = 0;
        $total_disp_pc_diponivel = 0;
        $item_data_prev = '';
        foreach ($pc_array as $key_pc => $value_pc) {
            $pc_empenho = 0;
            if ($value_pc['pc_ref'] == $produto and $value_pc['pc_qtde'] > 0) {
                $pc_empenho = $value_pc['pc_qtde'] - $value_pc['pc_qtde_atu'];
                $item_data_prev = $value_pc['pc_previsao'];
                $carteira_processada .= '
	    	<tr>
				<td>' . $value_pc['pc_num'] . '</td>
                <td>' . $item_data_prev . '</td>
				<td>' . $unidade_medida_produto_pesquisado . '</td>
				<td  align="right">' . number_format($value_pc['pc_qtde'],    2, ',', '.') . '</td>
				<td  align="right">' . number_format($pc_empenho,    2, ',', '.') . '</td>
				<td align="right">' . number_format($value_pc['pc_qtde_atu'],    2, ',', '.') . '</td>

			</tr>
	    	';

                $total_disp_pc_saldo += $value_pc['pc_qtde'];
                $total_disp_pc_empenho += $pc_empenho;
                $total_disp_pc_diponivel += $value_pc['pc_qtde_atu'];
            }
        } // fim do foreach

        //total geral
        $total_geral_disp_saldo += $total_disp_pc_saldo;
        $total_geral_disp_empenho += $total_disp_pc_empenho;
        $total_geral_disp_disponivel += $total_disp_pc_diponivel;

        $carteira_processada .= '
		<tr class="bg_subtotal_rel" >
			<td colspan="3" > TOTAL </td>
			<td  align="right">' . number_format($total_disp_pc_saldo,    2, ',', '.') . '</td>
			<td  align="right">' . number_format($total_disp_pc_empenho,    2, ',', '.') . '</td>
			<td align="right" class="bg-success">' . number_format($total_disp_pc_diponivel,    2, ',', '.') . '</td>
        </tr>
        </tbody>
		</table>
	';



        // ============ TOTAL GERAL DISPONIVEL  ============

        $carteira_processada .= '
    
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
                <th>GERAL</th>

				<th style="width: 160px;">Quantidade</th>
				<th style="width: 160px;">Empenho</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
		
        </tr>
        </thead>
        <tbody>
		<tr class="bg_subtotal_rel">
            <td >TOTAL </td>
			<td align="right">' . number_format($total_geral_disp_saldo,    2, ',', '.') . '</td>
			<td align="right">' . number_format($total_geral_disp_empenho,    2, ',', '.') . '</td>
			<td align="right" class="bg-success">' . number_format($total_geral_disp_disponivel,    2, ',', '.') . '</td>
        </tr>
        </tbody>
		</table>
        </div>
		';
    }
} //  fim do IF de consulta de estoque ===================================================
// echo $carteira_processada;
$carteira_processada = str_replace("'", "", $carteira_processada);
$update_at = date("Y-m-d H:i:s");
// salvar relatorio processado ===================================================

$limpa_tabela = mysql_query("TRUNCATE md_vendas_carteira_rel") or die(mysql_error());
$query_rel_cart = "INSERT INTO `md_vendas_carteira_rel` (`id`, `conteudo`, `create_at`) VALUES ('1', '$carteira_processada', '$update_at');";
// echo '<hr>' . $query_rel_cart . '<hr>';
$result = mysql_query($query_rel_cart) or die(mysql_error());

// se atualizar estoque no biv ===================================================
$atualiza_estoque_biv = TRUE;
if ($atualiza_estoque_biv) {

    include('../config/conexao.php');
    $limpa_tabela = mysql_query("TRUNCATE md_estoque_disponivel_detail;") or die(mysql_error());

    //ESTOQUE
    foreach ($estoquedisp as $key_estoq => $value_estoq) {
        $ref_prod = '';
        $saldo_prod = 0;
        $estoque_empenho = 0;
        $saldo_disp = 0;
        $unidade_medida_produto_pesquisado = '';
        $local = '';
        $tipo_estq = '';
        $data_prev = '';
        $estoque_menos_total_pedidos = 0;

        if ($value_estoq['saldo_disp'] > 0) {
            $ref_prod = $value_estoq['ref'];
            $saldo_prod = $value_estoq['saldo_disp'];
            $estoque_empenho = $value_estoq['saldo_disp'] - $value_estoq['saldo_disp_atu'];
            $saldo_disp = $value_estoq['saldo_disp_atu'];
            $unidade_medida_produto_pesquisado = $value_estoq['ref_um'];
            $local = $value_estoq['deposito'];
            $tipo_estq = "ESTQ";
            $data_prev = ' ';

            $result = mysql_query("INSERT INTO md_estoque_disponivel_detail (referencia, unid_medida, tipo_estq, local, data_prev, saldo, empenho, saldo_disp, update_at) VALUES ('$ref_prod', '$unidade_medida_produto_pesquisado', '$tipo_estq', '$local', '$data_prev', '$saldo_prod', ' $estoque_empenho', ' $saldo_disp', '$update_at')") or die(mysql_error());
            // echo 'incluindo estoque  = '. $result.'<br>';

        }
    } // fim do foreach


    //ORDEM PRODUCAO
    foreach ($op_array as $key_op => $value_op) {
        $ref_prod = '';
        $saldo_prod = 0;
        $estoque_empenho = 0;
        $saldo_disp = 0;
        //$unidade_medida_produto_pesquisado = '';
        $local = '';
        $tipo_estq = '';
        $data_prev = '';
        $estoque_menos_total_pedidos = 0;

        if ($value_op['op_qtde'] > 0) {
            $ref_prod = $value_op['op_ref'];
            $saldo_prod = $value_op['op_qtde'];
            $estoque_empenho = $value_op['op_qtde'] - $value_op['op_qtde_atu'];
            $saldo_disp = $value_op['op_qtde_atu'];
            $local = $value_op['op_num'];
            $tipo_estq = "OP";
            $data_prev = substr($value_op['op_previsaoFinal'], 0, 10);

            $result = mysql_query("INSERT INTO md_estoque_disponivel_detail (referencia, unid_medida, tipo_estq, local, data_prev, saldo, empenho, saldo_disp, update_at) VALUES ('$ref_prod', '$unidade_medida_produto_pesquisado', '$tipo_estq', '$local', '$data_prev', '$saldo_prod', ' $estoque_empenho', ' $saldo_disp', '$update_at')") or die(mysql_error());
        }
    } // fim do foreach


    //PEDIDO DE COMPRA
    foreach ($pc_array as $key_pc => $value_pc) {
        $ref_prod = '';
        $saldo_prod = 0;
        $estoque_empenho = 0;
        $saldo_disp = 0;
        $unidade_medida_produto_pesquisado = '';
        $local = '';
        $tipo_estq = '';
        $data_prev = '';
        $estoque_menos_total_pedidos = 0;

        if ($value_pc['pc_qtde'] > 0) {
            $ref_prod = $value_pc['pc_ref'];
            $saldo_prod = $value_pc['pc_qtde'];
            $estoque_empenho = $value_pc['pc_qtde'] - $value_pc['pc_qtde_atu'];
            $saldo_disp = $value_pc['pc_qtde_atu'];
            $local = $value_pc['pc_num'];
            $tipo_estq = "PC";
            $data_prev = substr($value_pc['pc_previsao'], 0, 10);
            $unidade_medida_produto_pesquisado = $value_pc['pc_ref_um'];

            $result = mysql_query("INSERT INTO md_estoque_disponivel_detail (referencia, unid_medida, tipo_estq, local, data_prev, saldo, empenho, saldo_disp, update_at) VALUES ('$ref_prod', '$unidade_medida_produto_pesquisado', '$tipo_estq', '$local', '$data_prev', '$saldo_prod', ' $estoque_empenho', ' $saldo_disp', '$update_at')") or die(mysql_error());
        }
    } // fim do foreach


} //  fim do IF  atualizar estoque no BIV ===================================================

echo '<hr> ====calculando resumo para programação===== <br>';
// print("<pre>" . print_r($estoquedisp, true) . "</pre>");
foreach ($estoquedisp as $key_estoq => $value_estoq) {
    if ($value_estoq['saldo_disp'] > 0 and ($value_estoq['deposito'] != 'VH-OUTLET' and $value_estoq['deposito'] != 'AG-OUTLET')) {
        //dados para rel programacao ESTOQUE TOTAL
        $prog_estoque[$value_estoq['ref']] += $value_estoq['saldo_disp'];
    }
}

foreach ($op_array as $key_op => $value_op) {
    if ($value_op['op_qtde'] > 0) {
        //dados para rel programacao OP
        $prog_op[$value_op['op_ref']] += $value_op['op_qtde'];
    }
}

foreach ($pc_array as $key_pc => $value_pc) {
    if ($value_pc['pc_qtde'] > 0) {
        //dados para rel programacao PC
        $prog_pc[$value_pc['pc_ref']] += $value_pc['pc_qtde'];
    }
}

foreach ($pedido_vendas_array as $key_ped => $value_ped) {
    //dados para rel programacao PV
    $prog_pv[$value_ped['item_ref']] += $value_ped['item_qtde'];
}



$relatorio_prog .= '<table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed tabela_carteira" border="1">';
$relatorio_prog .= '
        <thead>
        <tr>
            <th>REFs</th>
            <th>DESCRIÇÃO</th>
            <th>UM</th>
            <th>ESTQ TOTAL</th>
            <th>OP</th>
            <th>PC</th>
            <th>PV</th>
            <th class="bg-success" data-toggle="tooltip" title="SALDO PROD = ESTQ + PED. COMP. + ORD. PROD. - PED. VENDA">SALDO DISP</th>
            <th>VEND (6m)</th>
            <th>GIRO (6m)</th>
            <th data-toggle="tooltip" title="Prev de duração do estoque em meses">DISP MÊS</th>
       ';

foreach ($produtos_array as $key_ref => $prod_un) {
    //estoque do item

    $ref_it = trim($key_ref);
    $saldo_disp_item = $prog_estoque[$ref_it] + $prog_op[$ref_it] + $prog_pc[$ref_it] - $prog_pv[$ref_it];

    //imprimir apenas item que tem dados
    if ($prog_estoque[$ref_it] > 0 OR $prog_op[$ref_it] > 0 OR $prog_pc[$ref_it] > 0 OR $prog_pv[$ref_it] > 0) {
        $imprimi_item = TRUE;
    } else {
        $imprimi_item = FALSE;
    }

    
    if ($imprimi_item) {
        $relatorio_prog .= '
        <tr class="' . $bg_total . ' tr_result" >
            <td>' . $key_ref . '</td>
            <td>'. $produtos_desc_array[$ref_it] .'</td>
            <td>' . $prod_un . '</td>
            <td align="right">' . number_format($prog_estoque[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($prog_op[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($prog_pc[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($prog_pv[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($saldo_disp_item,    2, ',', '.') . '</td>
            <td align="right">' . number_format($venda_prod_array[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($venda_prod_array[$ref_it] / 6,    2, ',', '.') . '</td>
            <td align="right">' . number_format($saldo_disp_item / ($venda_prod_array[$ref_it] / 6),    2, ',', '.') . '</td>
        ';

        $relatorio_prog .= '                       
                    </tr>';
    }
} //fim do imprimir_consulta_apos_atendimentos

$relatorio_prog .= '
    </tbody>
    </table>';


echo '<br>';
// echo $relatorio_prog;

$query_rel_cart2 = "INSERT INTO `md_vendas_carteira_rel` (`id`, `conteudo`, `create_at`) VALUES ('2', '$relatorio_prog', '$update_at');";
// echo '<hr>' . $query_rel_cart . '<hr>';
$result2 = mysql_query($query_rel_cart2) or die(mysql_error());

echo 'fim';
//fim do cronometro
$fim2 = microtime(true);
$tempoExecucao2 = $fim2 - $inicio2;
printf("<hr>O script levou %f segundos para finalizar.\n", $tempoExecucao2);
