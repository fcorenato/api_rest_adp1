<?php
date_default_timezone_set('America/Sao_Paulo');
require('../config/conexao.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $data_inicial = $_POST['data_inicial'];
  $data_final = $_POST['data_final'];
  $unidade = $_POST['unidade'];
  $tipo_rel = $_POST['tipo_rel'];
  $desconta_split = $_POST['split'];

  //tratando as datas para a consulta ao banco
  $data_rel_inicial_array = explode('-', $data_inicial);
  $data_rel_inicial = $data_rel_inicial_array[0] . $data_rel_inicial_array[1] . $data_rel_inicial_array[2];

  $data_rel_final_array = explode('-', $data_final);
  $data_rel_final = $data_rel_final_array[0] . $data_rel_final_array[1] . $data_rel_final_array[2];

  $periodo_rel =  "Período: de " . $data_rel_inicial_array[2] . "/" . $data_rel_inicial_array[1] . "/" . $data_rel_inicial_array[0] . " até " . $data_rel_final_array[2] . "/" . $data_rel_final_array[1] . "/" . $data_rel_final_array[0];

  $incio_mes = $data_inicial;
  $data_hoje = $data_final;

  $query1 = "
        select un.descricao as un_nome, pv.vend1, vend.nome_completo, pv.cliente_razao, pv.id as ped_num, pv.orc_data_emissao, pg.descricao as dec_pgto, ev.descricao as mt_cancela, pv.total_cimp, pv.frete_valor

        from md_vendas_pedidos AS pv
        left join md_vendas_cpgto AS pg on (pg.codigo = pv.cond_pgto)
        left join sys_usuarios AS vend on (vend.codigo = pv.vend1)
        left join sys_unidades as un on (un.codigo = vend.unidade_codigo)
        left join md_vendas_pedidos_eventos as ev on (ev.pedido_id = pv.id)

        WHERE cast(orc_data_emissao as date) between '$data_rel_inicial' and '$data_rel_final' 
        AND pv.status = 'C'
        AND ev.evento = 'Cancelado'
        order by ev.descricao, pv.orc_data_emissao
    ";


  //gerando relatório
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
                      <th>Emissão</th>
                      <th>Cond. Pgto</th>
                      <th>Motivo Cancelamento</th>
                      <th style="width: 16.66%;text-size:0.5rem">Desc. Cancelamento</th>
                      <th class="text-center" >Valor R$ <br>c/ ipi</th>
                      <th class="text-center">Frete R$</th>
                      <th class="text-center" >Valor R$ <br>c/ ipi+Frete</th>
                      

              </tr>
              </thead>
              <tbody>

              ';
    $total_geral_valor = 0;
    $subtotal_rel = 'inicial';
    $subtotal2_rel = 'inicial';
    while ($campos = mysql_fetch_array($result_query1)) {
      // ===============  tratando os dados e formatando ===========
      $nome_unidade = strtoupper(substr($campos['un_nome'], 0, 14));
      $nome_vendedor1 = strtoupper(substr($campos['nome_completo'], 0, 14));

      //incluindo IPI no campo C6_VALOR
      $saldo_pedido = $campos['C6_QTDVEN'];
      $saldo_pedido_valor = $saldo_pedido * $campos['C6_PRCVEN'];
      $campos['C6_VALOR'] = $saldo_pedido_valor; // + ($saldo_pedido_valor*$campos['B1_IPI']/100);

      //Produto com 11 digitos. Retirando qualquer valor depois da referencia. ex: VT0388C4317. para VT0388C4317
      $campos['C6_PRODUTO'] = substr($campos['C6_PRODUTO'], 0, 11);

      //reduzir tamanho do conteudo dos campos
      $campos[$col1] = substr($campos[$col1], 0, 18);
      $campos[$col2] = substr($campos[$col2], 0, 18);

      //motivo cancelado
      $mt_cancela_dados = explode('-',$campos['mt_cancela']);
      $motivo_cancela = $mt_cancela_dados[0];
      $desc_cancela =  $mt_cancela_dados[1];
      

      // ============================   Subtotal  ======================================
      $subtotal1_cond = $motivo_cancela;

      if ($subtotal1_cond != $subtotal_rel and $subtotal_rel != "inicial") {
        $resultado_rel .= '
          <tr class="' . $bg_subtotal . ' tr_result">
              <td colspan="5">TOTAL ' . $subtotal_rel . '</td>
              <td>' . $subtotal_cont_ped . '</td>
              <td></td>
              <td></td>
              <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_frete,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_valor_cfrete,  2, ',', '.') . '</td>
          </tr>
          ';

        //zerando totais para proximo pedido
        $subtotal_rel_qtde = 0;
        $subtotal_rel_frete = 0;
        $subtotal_rel_valor = 0;
        $subtotal_rel_valor_cfrete = 0;
        $subtotal_cont_ped = 0;

        $subtotal2_rel_qtde = 0;
        $subtotal2_rel_valor = 0;
      }

      $subtotal_rel = $subtotal1_cond;

      //========= Fim do  subtotal ========================================================

      // ============== imprimir itens se analitico================================================

      //tratando pgto split
      if ($campos['orc_split_pgto'] == 'S' and $desconta_split == 'S') {
        $total_pv = $campos['total_cimp'] * 0.75;
      } else {
        $total_pv = $campos['total_cimp'];
      }

      
      if ($tipo_rel == 'analitico') {
        $resultado_rel .= '<tr class="tr_result">
            <td>' . $nome_unidade . '</td>
            <td>' . $nome_vendedor1 . '</td>
            <td>' . substr($campos['cliente_razao'], 0, 18) . '</td>
            <td>' . $campos['ped_num'] . '</td>
            <td>' . date("d/m/Y", strtotime($campos['orc_data_emissao'])) . '</td>
            <td>' . $campos['dec_pgto'] . '</td>
            <td>' . $motivo_cancela . '</td>
            <td style="width: 16.66%;font-size:0.8rem">' . $desc_cancela . '</td>
            <td align="right">' . number_format($total_pv,  2, ',', '.') . '</td>
            <td align="right">' . number_format($campos['frete_valor'],  2, ',', '.') . '</td>
            <td align="right">' . number_format($total_pv + $campos['frete_valor'],  2, ',', '.') . '</td>
            </tr>';
        $bg_subtotal = 'bg_subtotal_rel';
        $bg_subtotal2 = 'bg_subtotal2_rel';
      } else {
        $bg_subtotal = '';
        $bg_subtotal2 = '';
      } //fim se analitico

      // totalizando pedidos
      $subtotal_rel_frete += $campos['frete_valor'];
      $subtotal_rel_valor += $total_pv;
      $subtotal_rel_valor_cfrete += $total_pv + $campos['frete_valor'];
      $subtotal_cont_ped += 1;


      //Totalizando GERAL
      $total_geral_frete += $campos['frete_valor'];
      $total_geral_valor +=  $total_pv;
      $total_geral_valor_cfrete += $total_pv + $campos['frete_valor'];
    } // fim do while

    // ultimo subtotal
    $resultado_rel .= '
            <tr class="' . $bg_subtotal . ' tr_result">
                <td colspan="5">' . $subtotal_rel . '</td>
                <td>' . $subtotal_cont_ped . '</td>
                <td></td>
                <td></td>
                <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
                <td align="right">' . number_format($subtotal_rel_frete,  2, ',', '.') . '</td>
                <td align="right">' . number_format($subtotal_rel_valor_cfrete,  2, ',', '.') . '</td>
                
            </tr>
            ';

    //Total Geral
    $resultado_rel .= '
                <tr class="bg_subtotal3_rel tr_result" >
                    <td colspan="8">TOTAL GERAL </td>
                    <td align="right">' . number_format($total_geral_valor,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_frete,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_valor_cfrete,  2, ',', '.') . '</td>
                </tr>';


    $resultado_rel .= '
            </tbody>
            </table>
            </div>';
  } // fim do if qtde_result1

} // fim do POST

echo $resultado_rel;
