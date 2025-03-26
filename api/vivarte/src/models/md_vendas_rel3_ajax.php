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

  if ($ordem == 'vendedor') {
    $col1 = 'A3_NOME';
    $col1_nome = 'Vendedor';
    $col2 = 'B1_FABRIC';
    $col2_nome = 'Fabricante';
    $col3 = 'B1_COD';
    $col3_nome = 'Produto';
  } else if ($ordem == 'fabricante') {
    $col3 = 'A3_NOME';
    $col3_nome = 'Vendedor';
    $col1 = 'B1_FABRIC';
    $col1_nome = 'Fabricante';
    $col2 = 'B1_COD';
    $col2_nome = 'Produto';
  }

  if ($tipo_rel == 'analitico') {
    $tipo_rel_orderby = " ORDER BY $col1, $col2, TOTAL DESC";
  } else {
    $tipo_rel_orderby = " ORDER BY $col1, TOTAL DESC";
  }

  $data_rel_inicial_array = explode('-', $data_inicial);
  $data_rel_inicial = $data_rel_inicial_array[0] . $data_rel_inicial_array[1] . $data_rel_inicial_array[2];

  $data_rel_final_array = explode('-', $data_final);
  $data_rel_final = $data_rel_final_array[0] . $data_rel_final_array[1] . $data_rel_final_array[2];

  $periodo_rel =  "Período: de " . $data_rel_inicial_array[2] . "/" . $data_rel_inicial_array[1] . "/" . $data_rel_inicial_array[0] . " até " . $data_rel_final_array[2] . "/" . $data_rel_final_array[1] . "/" . $data_rel_final_array[0];

  $incio_mes = $data_inicial;
  $data_hoje = $data_final;




  //gerando relatório
  $query1 = "
          select $col1, A3_UNIDAD, $col2, $col3, B1_DESC, B1_UM, BM_DESC, SUM(SC6.C6_QTDVEN) AS QTDV,  
          SUM(SC6.C6_QTDVEN * (SC6.C6_PRCVEN + (SC6.C6_PRCVEN * SB1.B1_IPI /100) )) AS TOTAL  

          from SC6010 AS SC6
          left join SC5010 AS SC5 on (C6_NUM = C5_NUM AND C6_FILIAL = C5_FILIAL)
          left join SA1010 AS SA1 on (C6_CLI = A1_COD	AND C6_LOJA = A1_LOJA)
          left join SA3010 AS SA3 on (C5_VEND1 = A3_COD)
          left join ACY010 AS ACY on (A1_GRPVEN = ACY_GRPVEN)
          left join SB1010 AS SB1 on C6_PRODUTO = B1_COD
          left join SBM010 AS SBM on B1_GRUPO = BM_GRUPO
          left join SE4010 AS SE4 on C5_CONDPAG = E4_CODIGO
          left join SF4010 AS SF4 on (C6_TES = F4_CODIGO AND C6_FILIAL = F4_FILIAL)

          WHERE C5_EMISSAO between '$data_rel_inicial' and '$data_rel_final'
          $filtro_unidade
          AND C6_BLQ <> 'R'
          AND F4_DUPLIC = 'S'
          AND SC6.D_E_L_E_T_ = ''
          AND SC5.D_E_L_E_T_ = ''
          AND SA1.D_E_L_E_T_ = ''
          AND SB1.D_E_L_E_T_ = ''
          AND SE4.D_E_L_E_T_ = ''
          AND SF4.D_E_L_E_T_ = ''
          GROUP BY $col1, A3_UNIDAD, $col2, $col3, B1_DESC, B1_UM, BM_DESC 
          $tipo_rel_orderby
          ";

  $result_query1 = mysql_query($query1);
  $qtde_query1 = mysql_num_rows($result_query1);

  if ($qtde_query1 == 0) {
    $resultado_rel = '<hr>';
  } else {

    $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed text-nowrap">
              <thead>
              <tr class="table-success">
                      <th>' . $col1_nome . '</th>
                      <th>' . $col2_nome . '</th>
                      <th>' . $col3_nome . '</th>
                      <th>Descricao</th>
                      <th>Grupo Prod</th>
                      <th>UN</th>
                      <th class="text-right" >Qtde</th>
                      <th class="text-right" >Valor R$ c/ ipi</th>

              </tr>
              </thead>
              <tbody>

              ';
    $total_geral_valor = 0;
    $subtotal_rel = 'inicial';
    $subtotal2_rel = 'inicial';
    while ($campos = mysql_fetch_array($result_query1)) {


      $con[] = $campos;
      // ===============  tratando os dados e formatando ===========

      //incluindo IPI no campo C6_VALOR
      $saldo_pedido = $campos['C6_QTDVEN'];
      $saldo_pedido_valor = $saldo_pedido * $campos['C6_PRCVEN'];
      $campos['C6_VALOR'] = $saldo_pedido_valor; // + ($saldo_pedido_valor*$campos['B1_IPI']/100);

      //Produto com 11 digitos. Retirando qualquer valor depois da referencia. ex: VT0388C4317. para VT0388C4317
      $campos['C6_PRODUTO'] = substr($campos['C6_PRODUTO'], 0, 11);

      // ============================   Subtotal 2  ======================================
      $subtotal2_rel_on = 1;
      if (($campos[$col1] != $subtotal_rel and $subtotal_rel != "inicial") OR ($campos[$col2] != $subtotal2_rel and $subtotal2_rel != "inicial" and $subtotal2_rel_on == true)) {
        $resultado_rel .= '
            <tr class="'.$bg_subtotal2.'">
              <td>' . $subtotal_rel . '</td>
              <td colspan="5">' . $subtotal2_rel . '</td>
              <td align="right">' . number_format($subtotal2_rel_qtde,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal2_rel_valor,  2, ',', '.') . '</td>
            </tr>
            ';

        //zerando totais para proximo pedido
        $subtotal2_rel_qtde = 0;
        $subtotal2_rel_valor = 0;
      }

      $subtotal2_rel = $campos[$col2];

      //========= Fim do  subtotal 2 ========================================================

      // ============================   Subtotal  ======================================

      if ($campos[$col1] != $subtotal_rel and $subtotal_rel != "inicial") {
        $resultado_rel .= '
          <tr class="'.$bg_subtotal.'">
              <td colspan="6">TOTAL ' . $subtotal_rel . '</td>
              <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
          </tr>
          ';

        //zerando totais para proximo pedido
        $subtotal_rel_qtde = 0;
        $subtotal_rel_valor = 0;

        $subtotal2_rel_qtde = 0;
        $subtotal2_rel_valor = 0;
      }

      $subtotal_rel = $campos[$col1];

      //========= Fim do  subtotal ========================================================



      // ============== imprimir itens se analitico================================================
      if ($tipo_rel == 'analitico') {
        $resultado_rel .= '<tr>
            <td>' . $campos[$col1] . '</td>
            <td>' . $campos[$col2] . '</td>
            <td>' . $campos[$col3] . '</td>
            <td>' . substr($campos['B1_DESC'], 0, 22) . '</td>
            <td>' . substr($campos['BM_DESC'], 0, 22) . '</td>
            <td>' . $campos['B1_UM'] . '</td>
            <td align="right">' . number_format($campos['QTDV'],  2, ',', '.') . '</td>
            <td align="right">' . number_format($campos['TOTAL'],  2, ',', '.') . '</td>
            </tr>';
        $bg_subtotal = 'bg_subtotal_rel';
        $bg_subtotal2 = 'bg_subtotal2_rel';
      } else {
        $bg_subtotal = 'bg_subtotal2_rel';
        $bg_subtotal2 = '';
      } //fim se analitico

      // totalizando pedidos
      $subtotal_rel_qtde  += $campos['QTDV'];
      $subtotal_rel_valor += $campos['TOTAL'];

      $subtotal2_rel_qtde  += $campos['QTDV'];
      $subtotal2_rel_valor += $campos['TOTAL'];

      //Totalizando GERAL
      $total_geral_qtde  += $campos['QTDV'];
      $total_geral_valor += $campos['TOTAL'];
    } // fim do while

    // ultimo subtotal
    $resultado_rel .= '
            <tr class="'.$bg_subtotal2.'">
              <td>' . $subtotal_rel . '</td>
              <td colspan="5">' . $subtotal2_rel . '</td>
              <td align="right">' . number_format($subtotal2_rel_qtde,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal2_rel_valor,  2, ',', '.') . '</td>
            </tr>
            <tr class="'.$bg_subtotal.'">
                <td colspan="6">' . $subtotal_rel . '</td>
                <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
                <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
            </tr>
            ';

    //Total Geral
    $resultado_rel .= '
                <tr class="bg_subtotal_rel" >
                    <td colspan="6">TOTAL GERAL </td>
                    <td align="right">' . number_format($total_geral_qtde,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_valor,  2, ',', '.') . '</td>
                </tr>';


    $resultado_rel .= '
            </tbody>
            </table>
            </div>';
  } // fim do if qtde_result1

} // fim do POST

echo $resultado_rel;
