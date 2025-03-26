<?php
date_default_timezone_set('America/Sao_Paulo');
//require('../config/conexaosql.php');
require('../config/SUsuario.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  //data do dia para verificar orcamentos vencidos
  $data_hoje = date("Ymd"); //data atual formado 20201205

  //Incializar controle de exibir subtotal
  $exibe_subtotal = TRUE;
  $exibe_subtotal2 = FALSE;

  //recebendo filtros

  if (isset($_POST['unidade'])) {
    $unidade = $_POST['unidade'];
    if ($unidade != 'ALL') {
      if ($unidade == 'FABRICA') {
        $filtro_unidade = "AND (A3_UNIDAD = '' OR A3_UNIDAD = '000001' OR A3_UNIDAD = '000002' OR A3_UNIDAD = '000003')";
      } else {
        $filtro_unidade = "AND A3_UNIDAD = '$unidade'";
      }

      if ($un_codigo > 1) {
        $pesquisa_por_unidade = "AND un.codigo = '$un_codigo'";
      }
    } else {
      $filtro_unidade = " ";
    }
  }


  if ($un_codigo > 1) {
    $pesquisa_por_unidade = "AND un.codigo = '$un_codigo'";
  }
  if (isset($_POST['tipo_rel'])) {
    $tipo_rel = $_POST['tipo_rel'];
  }

  if (isset($_POST['data_inicial']) and isset($_POST['data_final'])) {
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    if ($data_inicial != "" and $data_final != "") {
      //tratando as datas para a consulta ao banco
      $data_rel_inicial_array = explode('-', $data_inicial);
      $data_rel_inicial = $data_rel_inicial_array[0] . $data_rel_inicial_array[1] . $data_rel_inicial_array[2];

      $data_rel_final_array = explode('-', $data_final);
      $data_rel_final = $data_rel_final_array[0] . $data_rel_final_array[1] . $data_rel_final_array[2];

      $periodo_rel =  "Período: de " . $data_rel_inicial_array[2] . "/" . $data_rel_inicial_array[1] . "/" . $data_rel_inicial_array[0] . " até " . $data_rel_final_array[2] . "/" . $data_rel_final_array[1] . "/" . $data_rel_final_array[0];

      $incio_mes = $data_inicial;

      $pesquisa_por_data = " AND CJ_EMISSAO between '$data_rel_inicial' and '$data_rel_final' ";
    }
  }

  if (isset($_POST['status'])) {
    $status_cod = strtoupper($_POST['status']);
    if ($status_cod != "") {
      if ($status_cod == "V") {
        $pesquisa_por_status = " AND SCJ.CJ_STATUS = 'A' AND CJ_VALIDA < '$data_hoje' ";
      } else {
        $pesquisa_por_status = " AND SCJ.CJ_STATUS = '$status_cod' ";
      }
    }
  }

  if (isset($_POST['referencia'])) {
    $referencia_cod = strtoupper($_POST['referencia']);
    if ($referencia_cod != '') {
      $pesquisa_por_ref = " and (B1_COD LIKE '%$referencia_cod%' OR B1_DESC LIKE '%$referencia_cod%')";
    }
  }

  if (isset($_POST['vendedor'])) {
    $vendedor_cod = strtoupper($_POST['vendedor']);
    if ($vendedor_cod != "") {
      $pesquisa_por_vendedor = " AND SCJ.CJ_YVEND1 = '$vendedor_cod' ";
    }
  }

  if (isset($_POST['parceiro'])) {
    $parceiro_cod = strtoupper($_POST['parceiro']);
    if ($parceiro_cod != "") {
      $pesquisa_por_parceiro = " AND SCJ.CJ_YVEND2 = '$parceiro_cod' ";
    }
  }

  if (isset($_POST['grupo_prod'])) {
    $grupo_prod_cod = strtoupper($_POST['grupo_prod']);
    if ($grupo_prod_cod != 'ALL') {
      $pesquisa_por_grupo_prod = " AND B1_GRUPO = '$grupo_prod_cod' ";
    }
  }

  if (isset($_POST['fabricante'])) {
    $fabricante = strtoupper($_POST['fabricante']);
    if ($fabricante != 'ALL') {
      $pesquisa_por_fabricante = " AND B1_FABRIC = '$fabricante' ";
    }
  }


  if (isset($_POST['ordem'])) {
    $ordem = strtoupper($_POST['ordem']);
    if ($ordem == 'FILIAL_ORC') {
      $ordenar_consulta = ' ORDER BY CK_FILIAL,CK_NUM ';
    } else if ($ordem == 'VENDEDOR') {
      $ordenar_consulta = ' ORDER BY CJ_YVEND1, CK_FILIAL,CK_NUM ';
      $col1 = 'A3_NREDUZ';
      $exibe_subtotal2 = TRUE;
    } else if ($ordem == 'GRUPO_PROD') {
      $ordenar_consulta = ' ORDER BY BM_DESC, CK_FILIAL,CK_NUM ';
      $col1 = 'BM_DESC';
      $exibe_subtotal2 = TRUE;
    } else if ($ordem == 'FABRICANTE') {
      $ordenar_consulta = ' ORDER BY B1_FABRIC, CK_FILIAL,CK_NUM ';
      $col1 = 'B1_FABRIC';
      $exibe_subtotal2 = TRUE;
    }
  }


  //pesquisando vendedores
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



  $query1 = "
			select SCK.CK_FILIAL, SCK.CK_ITEM, SCK.CK_PRODUTO, SCK.CK_QTDVEN, SCK.CK_PRCVEN, SCK.CK_VALOR, SCK.CK_ENTREG, SCK.CK_CLIENTE, SCK.CK_LOJA, SCK.CK_NUM,SCK.CK_LOCAL, SA1.A1_COD, SA1.A1_LOJA, SA1.A1_NOME, SUS.US_NOME, SCJ.CJ_CLIENTE, SCJ.CJ_EMISSAO, SCJ.CJ_VALIDA, SCJ.CJ_STATUS, SCJ.CJ_CONDPAG, SCJ.CJ_YVEND1, CJ_YVEND2, SA3.A3_COD, SA3.A3_NREDUZ, SA3.A3_UNIDAD, SE4.E4_DESCRI, SB1.B1_COD, SB1.B1_DESC, SB1.B1_FABRIC, SB1.B1_IPI, SB1.B1_ORIGEM, SB1.B1_GRTRIB, BM_DESC 

			from SCK010 AS SCK
			left join SCJ010 AS SCJ on (CK_NUM = CJ_NUM AND CK_FILIAL = CJ_FILIAL)
			left join SA3010 AS SA3 on CJ_YVEND1 = A3_COD
      left join SA1010 AS SA1 on (CK_CLIENTE = A1_COD	AND CK_LOJA = A1_LOJA)
      left join SUS010 AS SUS on (CJ_PROSPE = US_COD	AND CJ_LOJPRO = US_LOJA)
      left join SB1010 AS SB1 on CK_PRODUTO = B1_COD
      left join SBM010 AS SBM on (B1_GRUPO = BM_GRUPO)
			left join SE4010 AS SE4 on CJ_CONDPAG = E4_CODIGO
			left join SF4010 AS SF4 on (CK_TES = F4_CODIGO AND CK_FILIAL = F4_FILIAL)
      WHERE F4_ESTOQUE = 'S'
      $pesquisa_por_data
      $filtro_unidade
      $pesquisa_por_status
      $pesquisa_por_ref
      $pesquisa_por_vendedor
      $pesquisa_por_parceiro
      $pesquisa_por_grupo_prod
      $pesquisa_por_fabricante
			AND SCK.D_E_L_E_T_ = ''
			AND SCJ.D_E_L_E_T_ = ''
      AND SA1.D_E_L_E_T_ = ''
      AND SUS.D_E_L_E_T_ = ''
			AND SB1.D_E_L_E_T_ = ''
			AND SE4.D_E_L_E_T_ = ''
			AND SF4.D_E_L_E_T_ = ''
			$ordenar_consulta 
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
                  <th>Filial</th>
                  <th>Num Orc</th>
                  <th>Nome Cliente</th>
                  <th>Emissão</th>
                  <th>Validade</th>
                  <th>Cond. Pgto</th>
                  <th>Vendedor</th>
                  <th>Parceiro</th>
                  <th>Grp Produto</th>
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


      $con[] = $campos;
      // ===============  tratando os dados e formatando ===========

      //tratando datas
      $data_emissao = substr($campos['CJ_EMISSAO'], 6, 2) . '/' . substr($campos['CJ_EMISSAO'], 4, 2) . '/' . substr($campos['CJ_EMISSAO'], 0, 4);
      $data_validade = substr($campos['CJ_VALIDA'], 6, 2) . '/' . substr($campos['CJ_VALIDA'], 4, 2) . '/' . substr($campos['CJ_VALIDA'], 0, 4);

      //incluindo IPI no campo CK_VALOR
      $campos['CK_VALOR'] =  $campos['CK_VALOR'] + ($campos['CK_VALOR'] * $campos['B1_IPI'] / 100);

      // ============================   Subtotal  ======================================

      if ($campos['CK_NUM'] != $subtotal_rel and $subtotal_rel != "inicial" and $exibe_subtotal == TRUE) {
        $resultado_rel .= '
                <tr class="' . $bg_subtotal . '">
                    <td colspan="10">TOTAL ' . $subtotal_rel . '</td>
                    <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
                    <td></td>
                    <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
                    <td>' . $subtotal_rel_status . '</td>
                </tr>
                ';

        //zerando totais para proximo pedido
        $subtotal_rel_qtde = 0;
        $subtotal_rel_valor = 0;
      }

      $subtotal_rel = $campos['CK_NUM'];

      //========= Fim do  subtotal ========================================================

      // ============================   Subtotal 2  ======================================

      if ($campos[$col1] != $subtotal2_rel and $subtotal2_rel != "inicial" and $exibe_subtotal2 == TRUE) {
        $resultado_rel .= '
                <tr class="' . $bg_subtotal2 . '">
                    <td colspan="10">TOTAL ' . $subtotal2_rel . '</td>
                    <td align="right">' . number_format($subtotal2_rel_qtde,  2, ',', '.') . '</td>
                    <td></td>
                    <td align="right">' . number_format($subtotal2_rel_valor,  2, ',', '.') . '</td>
                    <td></td>
                </tr>
                ';

        //zerando totais para proximo pedido
        $subtotal2_rel_qtde = 0;
        $subtotal2_rel_valor = 0;
      }

      $subtotal2_rel = $campos[$col1];

      //========= Fim do  subtotal 2 ========================================================

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

      //verificando nome do cliente no orçamento
      if ($campos['CJ_CLIENTE'] == '003931' or ($campos['CJ_CLIENTE'] >= '003984' and $campos['CJ_CLIENTE'] <= '004010')) {
        $cliente_nome = $campos['US_NOME'];
      } else {
        $cliente_nome = $campos['A1_NOME'];
      }

      // ============== imprimir itens se analitico================================================
      if ($tipo_rel == 'analitico') {
        $resultado_rel .= '<tr>
            <td>' . $campos['CK_FILIAL'] . '</td>
            <td>' . $campos['CK_NUM'] . '</td>
            <td>' .  $cliente_nome . '</td>
            <td>' . $data_emissao . '</td>
            <td>' . $data_validade . '</td>
            <td>' . $campos['E4_DESCRI'] . '</td>
            <td>' . $campos['A3_NREDUZ'] . '</td>
            <td>' . $vendedor_array[$campos['CJ_YVEND2']] . '</td>
            <td>' . $campos['BM_DESC'] . '</td>
            <td data-toggle="tooltip" title="' . $campos['B1_DESC'] . '"><a href="#" class="consulta_estoque" prod="' . $campos['B1_COD'] . '" >' . $campos['B1_COD'] . '</a></td>
            <td align="right">' . number_format($campos['CK_QTDVEN'],  2, ',', '.') . '</td>
            <td align="right">' . number_format($campos['CK_PRCVEN'],  2, ',', '.') . '</td>
            <td align="right">' . number_format($campos['CK_VALOR'],  2, ',', '.') . '</td>
            <td>' . $status_orcamento . '</td>
            </tr>';
        $bg_subtotal = 'bg_subtotal_rel';
        $bg_subtotal2 = 'bg_subtotal2_rel';
      } else {
        $bg_subtotal = '';
        $bg_subtotal2 = 'bg_subtotal2_rel';

        if ($ordem != 'FILIAL_ORC') {
          $exibe_subtotal = FALSE;
        }
      } //fim se analitico

      // totalizando subtotal 
      $subtotal_rel_qtde += $campos['CK_QTDVEN'];
      $subtotal_rel_valor += $campos['CK_VALOR'];
      $subtotal_rel_status = $status_orcamento;

      // totalizando subtotal 2 
      $subtotal2_rel_qtde += $campos['CK_QTDVEN'];
      $subtotal2_rel_valor += $campos['CK_VALOR'];

      //Totalizando GERAL
      $total_geral_qtde += $campos['CK_QTDVEN'];
      $total_geral_valor += $campos['CK_VALOR'];
    } // fim do while

    // ultimo subtotal
    if ($exibe_subtotal == TRUE) {
      $resultado_rel .= '
      <tr class="' . $bg_subtotal . '">
          <td colspan="10">TOTAL ' . $subtotal_rel . '</td>
          <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
          <td></td>
          <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
          <td>' . $subtotal_rel_status . '</td>
      </tr>
      ';
    }

    // ultimo subtotal 2
    if ($exibe_subtotal2 == TRUE) {
      $resultado_rel .= '
        <tr class="' . $bg_subtotal2 . '">
            <td colspan="10">TOTAL ' . $subtotal2_rel . '</td>
            <td align="right">' . number_format($subtotal2_rel_qtde,  2, ',', '.') . '</td>
            <td></td>
            <td align="right">' . number_format($subtotal2_rel_valor,  2, ',', '.') . '</td>
            <td></td>
        </tr>
        ';
    }

    // TOTAL GERAL
    $resultado_rel .= '
      <tr class="bg_subtotal_rel">
          <td colspan="10">TOTAL GERAL</td>
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
