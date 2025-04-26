<?php
date_default_timezone_set('America/Sao_Paulo');
//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //data do dia para verificar orcamentos vencidos
    $data_hoje = date("Ymd"); //data atual formado 20201205

    //Incializar controle de exibir subtotal
    $exibe_subtotal = TRUE;
    $exibe_subtotal2 = FALSE;

    //recebendo filtros

    if (isset($_POST['empresa'])) {
        $empresa = $_POST['empresa'];
        if ($empresa != 'ALL') {
            $filtro_unidade = "AND empresa = '$empresa'";
        } else {
            $filtro_unidade = " ";
        }
    }

    if (isset($_POST['loja'])) {
        $loja = $_POST['loja'];
        if ($loja != 'ALL') {
            $filtro_loja = "AND us.unidade_codigo = '$loja'";
        } else {
            $filtro_loja = " ";
        }
    }

    if (isset($_POST['tipo_rel'])) {
        $tipo_rel = $_POST['tipo_rel'];
    }

    //filtro tipo data (emissao ou coversao)
    if (isset($_POST['tipo_data'])) {
        $tipo_data = $_POST['tipo_data'];

        if ($tipo_data == 'EMISSAO') {
            //filtro por EMISSAO
            if (isset($_POST['data_inicial']) and isset($_POST['data_final'])) {
                $data_inicial = $_POST['data_inicial'];
                $data_final = $_POST['data_final'];
                if ($data_inicial != "" and $data_final != "") {
                    $pesquisa_por_data = " AND cast(orc_data_emissao as date) between '$data_inicial' and '$data_final' ";
                }
            }
        } else if ($tipo_data == 'CONVERSAO') {
            //filtro por CONVERSAO
            if (isset($_POST['data_inicial']) and isset($_POST['data_final'])) {
                $data_inicial = $_POST['data_inicial'];
                $data_final = $_POST['data_final'];
                if ($data_inicial != "" and $data_final != "") {
                    $pesquisa_por_data = " AND cast(pedido_conv_date as date) between '$data_inicial' and '$data_final' ";
                }
            }
        }
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

    //filtro referencia
    if (isset($_POST['referencia'])) {
        $referencia_cod = trim(strtoupper($_POST['referencia']));
        if ($referencia_cod != '') {
            $pesquisa_por_ref = " and (codigo LIKE '%$referencia_cod%')";
        }
    }

    //filtro vendedor
    if (isset($_POST['vendedor'])) {
        $vendedor_cod = strtoupper($_POST['vendedor']);
        if ($vendedor_cod != "") {
            $pesquisa_por_vendedor = " AND pd.vend1 = '$vendedor_cod' ";
        }
    }
    //filtro parceiro
    if (isset($_POST['parceiro'])) {
        $parceiro_cod = strtoupper($_POST['parceiro']);
        if ($parceiro_cod != "") {
            $pesquisa_por_parceiro = " AND pd.vend2 = '$parceiro_cod' ";
        }
    }

    //filtro grupo produto
    if (isset($_POST['grupo_prod'])) {
        $grupo_prod_cod = strtoupper($_POST['grupo_prod']);
        if ($grupo_prod_cod != 'ALL') {
            $pesquisa_por_grupo_prod = $grupo_prod_cod;
        }
    }

    if (isset($_POST['fabricante'])) {
        $fabricante = strtoupper($_POST['fabricante']);
        if ($fabricante != 'ALL') {
            $pesquisa_por_fabricante = $fabricante;
        }
    }



    require('../config/conexaosql.php');
    //pesquisando vendedores no protheus
    $query3 = "
  SELECT A3_COD, A3_NREDUZ, A3_TIPO FROM SA3010 AS SA3
  WHERE A3_MSBLQL = 2
  AND SA3.D_E_L_E_T_ = ''
  ORDER BY A3_NREDUZ
  ";

    $result_query3 = mysql_query($query3);
    $qtde_query3 = mysql_num_rows($result_query3);

    if ($qtde_query3 > 0) {
        while ($campos = mysql_fetch_array($result_query3)) {
            $vendedor_array[$campos['A3_COD']] = $campos['A3_NREDUZ'];
        }
    }

    //pesquisando condicao de pagto no protheus
    $query2 = "
        SELECT E4_CODIGO, E4_TIPO, E4_YDESCCP, E4_DESCRI FROM SE4010 AS SE4
        WHERE SE4.D_E_L_E_T_ = ''
          ";

    $result_query2 = mysql_query($query2);
    $qtde_query2 = mysql_num_rows($result_query2);

    if ($qtde_query2 > 0) {
        while ($campos = mysql_fetch_array($result_query2)) {
            $cpag_array[$campos['E4_CODIGO']] = $campos['E4_DESCRI'];
        }
    }


    //pesquisando referencias no protheus
    $query4 = "
        SELECT B1_COD, B1_GRUPO, B1_FABRIC FROM SB1010 AS SB1
        WHERE SB1.D_E_L_E_T_ = ''
          ";

    $result_query4 = mysql_query($query4);
    $qtde_query4 = mysql_num_rows($result_query4);

    if ($qtde_query4 > 0) {
        while ($campos = mysql_fetch_array($result_query4)) {
            $prod_grupo_array[trim($campos['B1_COD'])] = $campos['B1_GRUPO'];
            $prod_fabric_array[trim($campos['B1_COD'])] = $campos['B1_FABRIC'];
        }
    }




    //PESQUISANDO NO BANCO DE DADOS MYSQL
    require('../config/conexao.php');
    $query1 = "
			select pd.id,  pd.empresa,  pd.pedido_num,  pd.orc_created_user, pd.pedido_conv_date,  pd.pedido_conv_user,  pd.cliente_codigo,  pd.cliente_razao,  pd.uf,  pd.unidade_codigo,  pd.vend1,  pd.vend2,  pd.orc_data_emissao,  pd.orc_data_valid,  pd.cond_pgto,  pd.tabela_preco,  pd.frete_valor,  pd.total_desc,  pd.total_cimp,  pd.total_final,  pd.status, pi.id as pi_id, pi.pedido_id, pi.codigo, pi.descricao, pi.unidade, pi.qtde, pi.prc_tab, pi.desconto, pi.ipi, pi.status as pi_status, un.descricao as loja

      from md_vendas_pedidos AS pd
      left join md_vendas_pedidos_itens AS pi on (pi.	pedido_id = pd.id)
      left join sys_usuarios AS us on (us.codigo = pd.orc_created_user)
      left join sys_unidades AS un on (un.codigo = us.unidade_codigo)

      where pi.status = 'A'
      $filtro_unidade
      $filtro_loja
      $pesquisa_por_ref
      $pesquisa_por_vendedor
      $pesquisa_por_parceiro
      $pesquisa_por_data
      $pesquisa_por_status
      order by unidade_codigo, vend1

     
    ";


    //gerando relatório
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    if ($qtde_query1 == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro atende o filtro selecionado.</td></tr></table>';
    } else {

        $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed tabela_carteira">
              <thead>
                <tr>
                  <th>Loja</th>  
                  <th>Vendedor</th>
                  <th>Empresa</th>
                  <th>Num Orc</th>
                  <th>Nome Cliente</th>
                  <th>Emissão</th>
                  <th>Convertido</th>
                  <th>Cond. Pgto</th>
                  <th>Parceiro</th>
                  <th>Fabric</th>
                  <th>Produto</th>
                  <th>Qtde</th>
                  <th>Preço</th>
                  <th>Valor R$ c/ ipi</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>

              ';
        $total_geral_valor = 0;

        //inciando controle de subtotal
        $subtotal_rel = 'inicial';
        $subtotal2_rel = 'inicial';
        while ($campos = mysql_fetch_array($result_query1)) {

            // ===============  tratando os dados e formatando ===========

            //incluindo IPI no campo CK_VALOR
            $prod_prc_unit =  ($campos['prc_tab'] - $campos['desconto'])  + (($campos['prc_tab'] - $campos['desconto']) * $campos['ipi'] / 100);
            $prod_total_item = $prod_prc_unit * $campos['qtde'];

            //data convesao se tiver
            if ($campos["pedido_conv_date"] != 0) {
                $data_conversao = date("d/m/Y", strtotime($campos["pedido_conv_date"]));
            } else {
                $data_conversao = '';
            }

            //status do pedido
            $status_orc = '';
            if ($campos['status'] == 'A') {
                $status_orc = 'Aberto';
            } else  if ($campos['status'] == 'P') {
                $status_orc = 'Convertido';
            } else  if ($campos['status'] == 'B') {
                $status_orc = 'Bloqueado';
            } else  if ($campos['status'] == 'C') {
                $status_orc = 'Cancelado';
            } else {
                $status_orc = '';
            }

            // ============================   Subtotal 2  ======================================
            $subtotal2_rel_on = 1;
            if (($campos['loja'] != $subtotal_rel and $subtotal_rel != "inicial") OR ($vendedor_array[$campos['vend1']] != $subtotal2_rel and $subtotal2_rel != "inicial" and $subtotal2_rel_on == true) and $subtotal_rel_valor != 0) {
                $resultado_rel .= '
                    
                    <tr class="'.$bg_subtotal2.' tr_result">
                    <td>' . $subtotal_rel_loja . '</td>
                    <td>' . $subtotal2_rel . '</td>
                    <td colspan="9"></td>
                    <td align="right">' . number_format($subtotal2_rel_qtde,  2, ',', '.') . '</td>
                    <td></td>
                    <td align="right">' . number_format($subtotal2_rel_valor,  2, ',', '.') . '</td>
                    <td></td>
                    </tr>
                    ';

                //zerando totais para proximo pedido
                $subtotal2_rel_frete = 0;
                $subtotal2_rel_qtde = 0;
                $subtotal2_rel_valor = 0;
            }

            $subtotal2_rel = $vendedor_array[$campos['vend1']];

            //========= Fim do  subtotal 2 ========================================================

            // ============================   Subtotal  ======================================

            if ($campos['loja'] != $subtotal_rel and $subtotal_rel != "inicial" and $exibe_subtotal == TRUE and $subtotal_rel_valor != 0) {
                $resultado_rel .= '
                <tr class="' . $bg_subtotal . '">
                    <td>TOTAL ' . $subtotal_rel_loja . '</td>
                    <td colspan="10"></td>
                    <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
                    <td></td>
                    <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
                    <td></td>
                </tr>
                ';

                //zerando totais para proximo pedido
                $subtotal_rel_qtde = 0;
                $subtotal_rel_valor = 0;
                $subtotal_rel_qtde_itens = 0;
                $subtotal_rel_qtde_itens_totalped = 0;
            }

            if ($campos['loja'] != $subtotal_rel and $subtotal_rel != "inicial" and $exibe_subtotal == TRUE and $subtotal_rel_valor == 0) {
                $subtotal_rel_qtde_itens_totalped = 0;
            }


            $subtotal_rel = $campos['loja'];

            //========= Fim do  subtotal ========================================================


            // Status do orçamento
            if ($campos['CJ_STATUS'] == 'A') {
                if ($campos['CJ_VALIDA'] < $data_hoje) {
                    $status_orcamento = '<span class="badge bg-success p-2">Aberto <i class="fas fa-clock text-warning"></i></span>';
                } else {
                    $status_orcamento = '<span class="badge bg-success p-2">Aberto</span>';
                }
            } else if ($campos['CJ_STATUS'] == 'B') {
                $status_orcamento = '<span class="badge bg-info p-2">Convertido</span>';
            } else  if ($campos['CJ_STATUS'] == 'C') {
                $status_orcamento = '<span class="badge bg-danger p-2">Cancelado</span>';
            } else  if ($campos['CJ_STATUS'] == 'D') {
                $status_orcamento = '<span class="badge bg-warning p-2">Incompleto</span>';
            }



            // ============== imprimir itens se analitico================================================
            // filtros ===============
            $imprime_item = TRUE;
            if (trim($pesquisa_por_fabricante) != '' and trim($prod_fabric_array[$campos['codigo']]) != trim($pesquisa_por_fabricante)) {
                $imprime_item = FALSE;
            }

            if (trim($pesquisa_por_grupo_prod) != '' and trim($prod_grupo_array[$campos['codigo']]) != trim($pesquisa_por_grupo_prod)) {
                $imprime_item = FALSE;
            }


            if ($tipo_rel == 'analitico' and $imprime_item) {
                $resultado_rel .= '<tr>
            <td>' . $campos['loja'] . '</td>
            <td>' . $vendedor_array[$campos['vend1']] . '</td>
            <td>' . $campos['empresa'] . '</td>
            <td>' . $campos['id'] . '</td>
            <td>' .  $campos['cliente_razao'] . '</td>
            <td>' . date("d/m/Y", strtotime($campos["orc_data_emissao"])) . '</td>
            <td>' . $data_conversao . '</td>
            <td>' . $cpag_array[$campos['cond_pgto']] . '</td>
            <td>' . $vendedor_array[$campos['vend2']] . '</td>
            <td>' . $prod_fabric_array[$campos['codigo']] . '</td>
            <td>' . $campos['codigo'] . '</td>
            <td align="right">' . number_format($campos['qtde'],  2, ',', '.') . '</td>
            <td align="right">' . number_format($prod_prc_unit,  2, ',', '.') . '</td>
            <td align="right">' . number_format($prod_total_item,  2, ',', '.') . '</td>
            <td>' .  $status_orc . '</td>
            </tr>';


                // totalizando subtotal 1  
                $subtotal_rel_empresa = $campos['empresa'];
                $subtotal_rel_loja = $campos['loja'];
                $subtotal_rel_cliente = $campos['cliente_razao'];
                $subtotal_rel_dt_emissao = date("d/m/Y", strtotime($campos["orc_data_emissao"]));
                $subtotal_rel_dt_conv = $data_conversao;
                $subtotal_rel_cpga = $cpag_array[$campos['cond_pgto']];
                $subtotal_rel_vendedor = $vendedor_array[$campos['vend1']];
                $subtotal_rel_parceiro = $vendedor_array[$campos['vend2']];
                $subtotal_rel_qtde_itens++;
                $subtotal_rel_qtde += $campos['qtde'];
                $subtotal_rel_valor += $prod_total_item;
                $subtotal_rel_frete = $campos['frete_valor'];
                $subtotal_rel_status = $status_orc;

                // totalizando subtotal 2 
                $subtotal2_rel_vendedor = $vendedor_array[$campos['vend1']];
                $subtotal2_rel_qtde_itens++;
                $subtotal2_rel_qtde += $campos['qtde'];
                $subtotal2_rel_valor += $prod_total_item;
                $subtotal2_rel_frete += $campos['frete_valor'];

                //Totalizando GERAL
                $total_geral_qtde += $campos['qtde'];
                $total_geral_valor += $prod_total_item;
                $total_geral_frete += $campos['frete_valor'];
                $total_geral_valor_cfrete += $prod_total_item + $campos['frete_valor'];

                $bg_subtotal = 'bg_subtotal_rel';
                $bg_subtotal2 = 'bg_subtotal2_rel'; //fim se analitico
            } else if ($tipo_rel == 'sintetico') {

                // totalizando subtotal 
                $subtotal_rel_empresa = $campos['empresa'];
                $subtotal_rel_loja = $campos['loja'];
                $subtotal_rel_cliente = $campos['cliente_razao'];
                $subtotal_rel_dt_emissao = date("d/m/Y", strtotime($campos["orc_data_emissao"]));
                $subtotal_rel_dt_conv = $data_conversao;
                $subtotal_rel_cpga = $cpag_array[$campos['cond_pgto']];
                $subtotal_rel_vendedor = $vendedor_array[$campos['vend1']];
                $subtotal_rel_parceiro = $vendedor_array[$campos['vend2']];
                $subtotal_rel_qtde_itens++;
                $subtotal_rel_qtde += $campos['qtde'];
                $subtotal_rel_valor += $prod_total_item;
                $subtotal_rel_frete = $campos['frete_valor'];
                $subtotal_rel_status = $status_orc;


                //Totalizando GERAL
                $total_geral_qtde += $campos['qtde'];
                $total_geral_valor += $prod_total_item;

                $bg_subtotal = ' ';
                $bg_subtotal2 = 'bg_subtotal2_rel';
            } // fum se relatorio sintetico

            $subtotal_rel_qtde_itens_totalped++;
        } // fim do while

        // ultimo subtotal 1 e subtotal 2
        if ($exibe_subtotal == TRUE and $subtotal_rel_valor != 0) {
            $resultado_rel .= '
            <tr class="'.$bg_subtotal2.' tr_result">
                <td></td>
                <td>' . $subtotal2_rel . '</td>
                <td colspan="9"></td>
                <td align="right">' . number_format($subtotal2_rel_qtde,  2, ',', '.') . '</td>
                <td></td>
                <td align="right">' . number_format($subtotal2_rel_valor,  2, ',', '.') . '</td>
                <td></td>
            </tr>
            
            <tr class="' . $bg_subtotal . '">
                    <td>TOTAL ' . $subtotal_rel_loja . '</td>
                    <td colspan="10"></td>
                    
                    <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
                    <td></td>
                    <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
                    <td></td>
                </tr>
      ';
        }



        // TOTAL GERAL
        $resultado_rel .= '
      <tr class="bg_subtotal_rel">
          <td colspan="10">TOTAL GERAL</td>
          <td></td>
          <td align="right">' . number_format($total_geral_qtde,  2, ',', '.') . '</td>
          <td></td>
          <td align="right">' . number_format($total_geral_valor,  2, ',', '.') . '</td>
          <td></td>
      </tr>
      ';

        $resultado_rel .= '
            </tbody>
            </table>
            </div>';
    } // fim do if qtde_result1

} // fim do POST

echo $resultado_rel;
