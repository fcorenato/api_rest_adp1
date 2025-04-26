<?php
date_default_timezone_set('America/Sao_Paulo');
require('../config/conexao.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $data_inicial = $_POST['data_inicial'];
  $data_final = $_POST['data_final'];
  $ref = $_POST['ref'];
  $unidade = $_POST['unidade'];
  $tipo_rel = $_POST['tipo_rel'];
  $vendedor = $_POST['vendedor'];
  $margem = $_POST['margem'];

  $imprime_item = true;

  if ($ref) {
    $filtro_ref = " AND pvi.codigo = '$ref' ";
  }

  if ($vendedor) {
    $filtro_vendedor = " AND vend.codigo = '$vendedor' ";
  }


  $data_rel_inicial_array = explode('-', $data_inicial);
  $data_rel_inicial = $data_rel_inicial_array[0] . $data_rel_inicial_array[1] . $data_rel_inicial_array[2];

  $data_rel_final_array = explode('-', $data_final);
  $data_rel_final = $data_rel_final_array[0] . $data_rel_final_array[1] . $data_rel_final_array[2];

  $periodo_rel =  "Período: de " . $data_rel_inicial_array[2] . "/" . $data_rel_inicial_array[1] . "/" . $data_rel_inicial_array[0] . " até " . $data_rel_final_array[2] . "/" . $data_rel_final_array[1] . "/" . $data_rel_final_array[0];

  $incio_mes = $data_inicial;
  $data_hoje = $data_final;




  //gerando relatório
  $query1 = "select un.descricao as un_nome,pv.vend1, vend.nome_completo, pv.cliente_razao, pvi.codigo, pvi.descricao, pvi.unidade, pvi.ipi, pv.frete_valor, pv.orc_split_pgto, pv.id as pednum, pvi.qtde, pvi.prc_tab, pvi.desconto, pv.total_cimp, pv.pedido_conv_user, pv.desc4, pv.desc5, pv.transp_nome, pv.pedido_conv_date, vendc.nome_completo as vend_interno, pg.descricao as dec_pgto, pg.fator_margem, prod.custo_produto,  prod.custoPrecoProdPalletAberto, pvi.app_tx_pal, pvi.taxaPalletAbertoR, pvi.taxaPalletAberto, pvi.precoProdPalletAbertoR

          from md_vendas_pedidos_itens AS pvi
          left join md_vendas_pedidos AS pv on (pv.id = pvi.pedido_id)
          left join sys_usuarios AS vend on (vend.codigo = pv.vend1)
          left join sys_unidades AS un on (un.codigo = vend.unidade_codigo)
          left join sys_usuarios AS vendc on (vendc.codigo = pv.pedido_conv_user)
          left join md_vendas_cpgto AS pg on (pg.codigo = pv.cond_pgto)
          left join md_cad_produtos AS prod on (prod.referencia = pvi.codigo)
  
          WHERE cast(pedido_conv_date as date) between '$data_rel_inicial' and '$data_rel_final' 
          AND pv.status = 'P'
          AND pvi.status = 'A'
          $filtro_ref 
          $filtro_vendedor
          
          order by pvi.codigo,pv.pedido_conv_date
         
          ";
  // echo $query1;
  $result_query1 = mysql_query($query1);
  $qtde_query1 = mysql_num_rows($result_query1);

  if ($qtde_query1 == 0) {
    $resultado_rel = '<hr>';
  } else {

    $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed text-nowrap">
              <thead>
              <tr class="table-success">
                      <th>Unidade</th>
                      <th>Vendedor</th>
                      <th>Cliente</th>
                      <th>Pedido</th>
                      <th>Dt. Conversão</th>
                      <th>C.Pgto</th>
                      <th>C.Pgto Fator %</th>
                      <th>Ref</th>
                      <th>Descricao</th>
                      <th>UN</th>
                      <th>Custo Unit R$</th>
                      <th class="text-right" >Qtde</th>
                      <th class="text-right" >Valor R$ c/ ipi</th>
                      <th class="text-right" >Margem R$</th>
                      <th class="text-right" >Margem %</th>

              </tr>
              </thead>
              <tbody>

              ';
    $total_geral_valor = 0;
    $subtotal_rel = 'inicial';
    $subtotal2_rel = 'inicial';
    while ($campos = mysql_fetch_array($result_query1)) {
      // ===============  tratando os dados e formatando ===========

      $codref = $campos['codigo'];
      $nome_unidade = strtoupper(substr($campos['un_nome'], 0, 14));
      $nome_vendedor1 = strtoupper(substr($campos['nome_completo'], 0, 14));
      $nome_vend_interno = strtoupper(substr($campos['vend_interno'], 0, 14));

      $subtotal_chave = $nome_unidade;
      // ============================   Subtotal  ======================================

      if ($subtotal_chave != $subtotal_rel and $subtotal_rel != "inicial") {
        foreach ($subtotal_rel_und as $key => $value) {
          $subtotal_rel_por_und .= $key . ' = ' . $value . ' | ';
        }
        $resultado_rel .= '
          <tr class="' . $bg_subtotal . ' tr_result">
              <td >TOTAL ' . $subtotal_rel . '</td>
              <td></td>
              <td></td>
              <td colspan="8">TOTAL/UN MEDIDA: |' . $subtotal_rel_por_und . '</td>
              <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_margem,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_margem / $subtotal_rel_valor * 100,  2, ',', '.') . '%</td>
          </tr>
          ';

        //zerando totais para proximo pedido
        $subtotal_rel_qtde = 0;
        $subtotal_rel_valor = 0;
        unset($subtotal_rel_und);
        $subtotal_rel_por_und = '';
      }

      $subtotal_rel = $subtotal_chave;

      //========= Fim do  subtotal ========================================================

      
      // ============== imprimir itens se analitico================================================
      //incluindo IPI no campo CK_VALOR
      //verificando se aplica taxa pallet aberto
      $taxa_item = 0;
      if ($campos["app_tx_pal"] == 'S') {
        if ($campos["taxaPalletAbertoR"] > 0) {
          //se taxa valor R$
          $taxa_item = round($campos["taxaPalletAbertoR"] / $campos["qtde"], 2);
        } else if ($campos["taxaPalletAberto"] > 0) {
          //se taxa %
          $campos["prc_tab"] = $campos["prc_tab"] + ($campos["prc_tab"] * $campos["taxaPalletAberto"] / 100);
        } else if ($campos["precoProdPalletAbertoR"] > 0) {
          $campos["prc_tab"] = $campos["precoProdPalletAbertoR"];
        }
      }

      $prod_prc_unit =  ($campos['prc_tab'] - $campos['desconto'] + $taxa_item);
      $prod_total_item = round($prod_prc_unit * $campos['qtde'], 2);

      //tratando pgto split
      if ($campos['orc_split_pgto'] == 'S' and $desconta_split == 'S') {
        $total_pv = $prod_total_item * 0.75;
      } else {
        $total_pv = $prod_total_item;
      }

      //frete po item
      $frete_item = round(($prod_total_item / $campos['total_cimp']) * $campos['frete_valor'], 2);

      //margem produto
      $campos['fator_margem'] = $campos['fator_margem'] - $campos['desc4']; //margem - desconto parceiro
      $campos['fator_margem'] = $campos['fator_margem'] - $campos['desc5']; //margem - desconto referente a cashback
      if ($campos['transp_nome'] == 'RETIRAR NA LOJA') {
        $campos['fator_margem'] = $campos['fator_margem'] - 3; //margem - desconto por frete = RETIRAR NA LOJA
      }

      // ===== custo unitario do produto ====
      $custo_unit_prod = $campos['custo_produto'];
      
      //se  preco por pallet aberto e custoPrecoProdPalletAberto estiver preenchido
      if ($campos["precoProdPalletAbertoR"] > 0 AND $campos['custoPrecoProdPalletAberto'] > 0) {
        $custo_unit_prod = $campos['custoPrecoProdPalletAberto'];
      }
      //se taxa %, adicionar ao custo unit
      if ($campos["taxaPalletAberto"] > 0) {
        $custo_unit_prod = $custo_unit_prod + ($custo_unit_prod * $campos["taxaPalletAberto"] / 100);
      }


      $margem_produto = $total_pv - ($total_pv * $campos['fator_margem'] / 100);
      $margem_produto = $margem_produto - ($campos['qtde'] * $custo_unit_prod);
      $margem_produto_perc = $margem_produto / $total_pv * 100;
      $alert_margem = $margem_produto_perc < 20 ? ' style="background-color: red; color: #ffffff" ' : '';

      //filtro por maragem
      if ($margem) {
        $imprime_item = false;
        if ($margem == 'menor20' and $margem_produto_perc < 20) {
          $imprime_item = true;
        } else if ($margem == 'igualmaior20' and $margem_produto_perc >= 20) {
          $imprime_item = true;
        } else if ($margem == 'all') {
          $imprime_item = true;
        }
      }

      if ($tipo_rel == 'analitico' and $imprime_item) {
        $resultado_rel .= '<tr class="tr_result">
            <td>' . $nome_unidade . '</td>
            <td>' . $nome_vendedor1 . '</td>
            <td>' . substr($campos['cliente_razao'], 0, 22) . '</td>
            <td>' . $campos['pednum'] . '</td>
            <td>' .  date("d/m/Y", strtotime($campos['pedido_conv_date'])) . '</td>
            <td>' . $campos['dec_pgto'] . '</td>
            <td align="right">' . number_format($campos['fator_margem'],  2, ',', '.') . '</td>
            <td>' . $campos['codigo'] . '</td>
            <td>' . substr($campos['descricao'], 0, 22) . '</td>
            <td>' . substr($campos['unidade'], 0, 22) . '</td>
            <td align="right">' . number_format($custo_unit_prod,  2, ',', '.') . '</td>
            <td align="right">' . number_format($campos['qtde'],  2, ',', '.') . '</td>
            <td align="right">' . number_format($total_pv,  2, ',', '.') . '</td>
            <td align="right">' . number_format($margem_produto,  2, ',', '.') . '</td>
            <td align="right" ' . $alert_margem . '>' . number_format($margem_produto_perc,  2, ',', '.') . '%</td>
            </tr>';
        $bg_subtotal = 'bg_subtotal_rel';
        $bg_subtotal2 = 'bg_subtotal2_rel2';

        // totalizando pedidos
        $subtotal_rel_qtde  += $campos['qtde'];
        $subtotal_rel_valor += $total_pv;
        $subtotal_rel_margem += $margem_produto;
        $subtotal_rel_margem_p += $margem_produto_perc;
        $subtotal_rel_und[$campos['unidade']] += $campos['qtde'];
        $subtotal_pednum = $campos['pednum'];

        //Totalizando GERAL
        $total_geral_qtde  += $campos['qtde'];
        $total_geral_valor += $total_pv;
        $total_geral_margem += $margem_produto;
        $total_geral_margem_p += $margem_produto_perc;
        $subtotal_geral_und[$campos['unidade']] += $campos['qtde'];
        $total_geral_frete += $frete_item;
      } else {
        $bg_subtotal = 'bg_subtotal2_rel';
        $bg_subtotal2 = '';
      } //fim se analitico


    } // fim do while

    // ultimo subtotal
    foreach ($subtotal_rel_und as $key => $value) {
      $subtotal_rel_por_und .= $key . ' = ' . $value . ' | ';
    }
    $resultado_rel .= '
            <tr class="' . $bg_subtotal . ' tr_result">
              <td >TOTAL ' . $subtotal_rel . '</td>
              <td></td>
              <td></td>
              <td colspan="8">TOTAL/UN MEDIDA: |' . $subtotal_rel_por_und . '</td>
              <td align="right">' . number_format($subtotal_rel_qtde,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_margem,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_margem / $subtotal_rel_valor * 100,  2, ',', '.') . '%</td>
            </tr>
            ';


    //Total Geral
    foreach ($subtotal_geral_und as $key => $value) {
      $subtotal_geral_por_und .= $key . ' = ' . $value . ' | ';
    }
    $resultado_rel .= '
                <tr class="bg_subtotal2_rel tr_result" >
                    <td align="right" colspan="11" >TOTAL GERAL </td>
                    <td align="right">' . number_format($total_geral_qtde,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_valor,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_margem,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_margem / $total_geral_valor * 100,  2, ',', '.') . '%</td>
                </tr>
                <tr class="bg_subtotal2_rel tr_result" >
                    <td align="right" colspan="11">TOTAL FRETE </td>
                    <td></td>
                    <td align="right">' . number_format($total_geral_frete,  2, ',', '.') . '</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="bg_subtotal3_rel tr_result" >
                    <td align="right" colspan="11">TOTAL GERAL C/ FRETE </td>
                    <td align="right">' . number_format($total_geral_qtde,  2, ',', '.') . '</td>
                    <td align="right" style="font-weight: 600;">' . number_format($total_geral_valor + $total_geral_frete,  2, ',', '.') . '</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr class="bg_subtotal2_rel tr_result" >
                    <td align="right" colspan="13">TOTAL GERAL POR UND MEDIDA: ' . $subtotal_geral_por_und . ' </td>
                    <td></td>
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
