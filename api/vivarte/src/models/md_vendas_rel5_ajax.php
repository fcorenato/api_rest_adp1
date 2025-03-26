<?php
date_default_timezone_set('America/Sao_Paulo');
//require('../config/conexaosql.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    $unidade = $_POST['unidade'];
    $tipo_rel = $_POST['tipo_rel'];
    $ordem = $_POST['ordem'];

    if ($unidade != 'TODAS') {
        if ($unidade == 'FABRICA') {
            $filtro_unidade = "AND (A3_UNIDAD = '' OR A3_UNIDAD = '000001' OR A3_UNIDAD = '000002' OR A3_UNIDAD = '000003')";
        } else {
            $filtro_unidade = "AND A3_UNIDAD = '$unidade'";
        }
    } else {
        $filtro_unidade = " ";
    }


    if (isset($_POST['parceiro'])) {
        $parceiro_cod = strtoupper($_POST['parceiro']);
        if ($parceiro_cod != "") {
            $pesquisa_por_parceiro = " AND pd.vend2 = '$parceiro_cod' ";
        }
    }

    if ($ordem == 'cliente') {
        $col1 = 'A1_NOME';
        $col1_nome = 'Cliente';
        $col2 = 'B1_FABRIC';
        $col2_nome = 'Fabricante';
        $col3 = 'A3_NOME';
        $col3_nome = 'Vendedor';
        $col4 = 'B1_COD';
        $col4_nome = 'Produto';
    } else if ($ordem == 'fabricante') {
        $col2 = 'A1_NOME';
        $col2_nome = 'Cliente';
        $col1 = 'B1_FABRIC';
        $col1_nome = 'Fabricante';
        $col3 = 'A3_NOME';
        $col3_nome = 'Vendedor';
        $col4 = 'B1_COD';
        $col4_nome = 'Produto';
    }

    if ($tipo_rel == 'analitico') {
        $tipo_rel_orderby = " ORDER BY $col1, $col2, TOTAL DESC";
    } else {
        $tipo_rel_orderby = " ORDER BY $col1, TOTAL DESC";
    }

    //filtro status
    if (isset($_POST['status'])) {
        $status_cod = strtoupper($_POST['status']);
        if ($status_cod != "") {
            $pesquisa_por_status = " AND pd.status = '$status_cod' ";
        }

        if ($tipo_data == 'CONVERSAO') {
            $pesquisa_por_status = " AND pd.status = 'P' ";
        }
    }

    $data_rel_inicial_array = explode('-', $data_inicial);
    $data_rel_inicial = $data_rel_inicial_array[0] . $data_rel_inicial_array[1] . $data_rel_inicial_array[2];

    $data_rel_final_array = explode('-', $data_final);
    $data_rel_final = $data_rel_final_array[0] . $data_rel_final_array[1] . $data_rel_final_array[2];

    $periodo_rel =  "Período: de " . $data_rel_inicial_array[2] . "/" . $data_rel_inicial_array[1] . "/" . $data_rel_inicial_array[0] . " até " . $data_rel_final_array[2] . "/" . $data_rel_final_array[1] . "/" . $data_rel_final_array[0];

    $incio_mes = $data_inicial;
    $data_hoje = $data_final;
    $pesquisa_por_data = " AND cast(pedido_conv_date as date) between '$data_inicial' and '$data_final' ";

    require('../config/conexao.php');
    //pesquisando vendedores no biv
    $query3 = "
            SELECT codigo, nome_completo, perfil FROM sys_usuarios AS us
            ORDER BY nome_completo
          ";

    $result_query3 = mysql_query($query3);
    $qtde_query3 = mysql_num_rows($result_query3);

    if ($qtde_query3 == 0) {
        $vendedores .= '';
    } else {
        while ($campos = mysql_fetch_array($result_query3)) {
            $vendedor_array[$campos['codigo']] = substr($campos['nome_completo'], 0, 16);
        }
    }

    $vendedor_subtotal = 'inicial';
    //gerando relatório
    $query1 = "

            select pd.id,  pd.empresa,  pd.pedido_num,  pd.orc_created_user, pd.pedido_conv_date,  pd.pedido_conv_user,  pd.cliente_codigo,  pd.cliente_razao,  pd.uf,  pd.unidade_codigo,  pd.vend1,  pd.vend2,  pd.orc_data_emissao,  pd.orc_data_valid, pd.pedido_conv_date,  pd.cond_pgto, pd.orc_split_pgto, pd.tabela_preco,  pd.frete_valor,  pd.total_desc,  pd.total_cimp,  pd.total_final,  pd.status as pv_status, pi.id as pi_id, pi.pedido_id, pi.codigo, pi.descricao, pi.unidade, pi.qtde, pi.prc_tab, pi.desconto, pi.ipi, pi.status as pi_status, un.descricao as loja, cp.descricao as cp_desc

            from md_vendas_pedidos AS pd
            left join md_vendas_pedidos_itens AS pi on (pi.	pedido_id = pd.id)
            left join sys_usuarios AS us on (us.codigo = pd.orc_created_user)
            left join sys_unidades AS un on (un.codigo = us.unidade_codigo)
            left join md_vendas_cpgto as cp on (pd.cond_pgto = cp.codigo)

            where pi.status = 'A'
            and pd.vend2 != '' 
            $pesquisa_por_parceiro
            $pesquisa_por_data
            $pesquisa_por_status
            order by pd.vend2,pd.id

          ";


    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    if ($qtde_query1 == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro atende o filtro selecionado.</td></tr></table>';
    } else {

        $resultado_rel = '
            <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed text-nowrap">
              <thead>
              <tr class="table-success">
                <th>Parceiro</th>
                <th>Vendedor</th>
                <th>Cliente</th>
                <th>Orçamento</th>
                <th>Status</th>
                <th>Pedido</th>
                <th>Emissao</th>
                <th>Convertido</th>
                <th>Cod Prod</th>
                <th>Descricao</th>
                <th>UN</th>
                <th>FAB</th>
                <th>Qtde</th>
                <th>Valor R$ c/ ipi</th>

              </tr>
              </thead>
              <tbody>

              ';
        while ($campos = mysql_fetch_array($result_query1)) {
            // ===============  tratando os dados e formatando ===========

            //incluindo IPI no campo CK_VALOR
            $prod_prc_unit =  ($campos['prc_tab'] - $campos['desconto'])  + (($campos['prc_tab'] - $campos['desconto']) * $campos['ipi'] / 100);
            $prod_total_item = $prod_prc_unit * $campos['qtde'];


            // ============================   Subtotal por vendedor   ======================================

            if ($vendedor_array[$campos['vend2']] != $vendedor_subtotal and $vendedor_subtotal != "inicial" and $imprimi_subtotal_vendedor = true) {
                $resultado_rel .= '
                    <tr class="' . $bg_subtotal2 . ' tr_result" >
                        <td colspan="10">Total do Vendedor: ' . $vendedor_subtotal . '</td>
                        <td align="right">' . number_format($total_pedido_qtde,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_valor,    2, ',', '.') . '</td>
                    </tr>
                    ';


                //zerando totais para proximo pedido
                $total_pedido_qtde  = 0;
                $total_pedido_valor = 0;
            }

            $vendedor_subtotal = $vendedor_array[$campos['vend2']];

            //========= Fim do  subtotal do vendedor  ====================================================


            // ============== imprimir itens  =====================================================

            // Status do orçamento
            $status_orc = '';
            if ($campos['pv_status'] == 'A') {
                $status_orc = 'Aberto';
            } else  if ($campos['pv_status'] == 'P') {
                $status_orc = 'Convertido';
            } else  if ($campos['pv_status'] == 'B') {
                $status_orc = 'Bloqueado';
            } else  if ($campos['pv_status'] == 'C') {
                $status_orc = 'Cancelado';
            } else  if ($campos['pv_status'] == 'V') {
                $status_orc = 'Vencido';
            } else {
                $status_orc = '';
            }

            //data convertido
            if ($campos['pedido_conv_date'] != '0') {
                $pedido_conv_date = date("d/m/Y", strtotime($campos['pedido_conv_date']));
            } else {$pedido_conv_date = ' ';}

            $item_status = $campos['C6_FILIAL'] . '-' . $campos['C6_NUM'] . '-' . $campos['C6_ITEM'] . '-' . $campos['C6_PRODUTO'];
            $resultado_rel .=  '<tr class="tr_result">
                    <td>' . $vendedor_array[$campos['vend2']] . '</td>
                    <td>' . $vendedor_array[$campos['vend1']] . '</td>
                    <td>' . $campos['cliente_razao'] . '</td>
                    <td>' . $campos['id'] . '</td>
                    <td>' . $status_orc . '</td>
                    <td>' . $campos['pedido_num'] . '</td>
                    <td>' . date("d/m/Y", strtotime($campos['orc_data_emissao'])) . '</td>
                    <td>' . $pedido_conv_date . '</td>
                    <td>' . $campos['codigo'] . '</td>
                    <td>' . substr($campos['descricao'], 0, 18) . '</td>
                    <td>' . $campos['unidade'] . '</td>
                    <td>' . $campos['B1_FABRIC'] . '</td>
                    <td align="right">' . number_format($campos['qtde'],    2, ',', '.') . '</td>
                    <td align="right">' . number_format($prod_total_item,    2, ',', '.') . '</td>
        
                    </tr>';

            // totalizando pedidos
            $total_pedido_qtde  += $campos['QTDV'];
            $total_pedido_valor += $prod_total_item;
            //Totalizando GERAL
            $total_geral_qtde  += $campos['QTDV'];
            $total_geral_valor += $prod_total_item;
            $bg_subtotal = 'bg_subtotal_rel';
            $bg_subtotal2 = 'bg_subtotal2_rel';
        } // fim do while

        // ultimo subtotal
        $resultado_rel .= '
                    <tr class="' . $bg_subtotal2 . ' tr_result" >
                        <td colspan="10">Total do Vendedor: ' . $vendedor_subtotal . '</td>
                        <td align="right">' . number_format($total_pedido_qtde,    2, ',', '.') . '</td>
                        <td align="right">' . number_format($total_pedido_valor,    2, ',', '.') . '</td>
        
                    </tr>
                    ';


        //total geral
        $resultado_rel .=  '
                        <tr class="bg_subtotal_rel tr_result" >
                            <td colspan="10">TOTAL GERAL </td>
                            <td align="right">' . number_format($total_geral_qtde,    2, ',', '.') . '</td>
                            <td align="right">' . number_format($total_geral_valor,    2, ',', '.') . '</td>
                        </tr>
                        ';

        $resultado_rel .= '
                        
        
                        </tbody>
                        </table>
                        </div>';
    } // fim do if qtde_result1

} // fim do POST

echo $resultado_rel;
