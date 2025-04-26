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
          select un.descricao as un_nome, pv.vend1, vend.codigo as codven,vend.nome_completo, pv.cliente_razao, pv.tabela_preco, pv.bolstd_prim_parc, pv.bolsdt_qtd_parc, pv.id as ped_num, pv.orc_data_emissao, pg.descricao as dec_pgto, pv.desc3, pv.total_cimp, pv.frete_valor

          from md_vendas_pedidos AS pv
          left join md_vendas_cpgto AS pg on (pg.codigo = pv.cond_pgto)
          left join sys_usuarios AS vend on (vend.codigo = pv.vend1)
          left join sys_unidades as un on (un.codigo = vend.unidade_codigo)
  
          WHERE cast(pedido_conv_date as date) between '$data_rel_inicial' and '$data_rel_final' 
          AND pv.status = 'P'
          
          order by pv.vend1, pv.orc_data_emissao
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
                      <th>Desc Com.</th>
                      <th class="text-center" >Valor R$ <br>c/ ipi</th>
                      <th class="text-center">Frete R$</th>
                      <th class="text-center" >Valor R$ <br>c/ ipi+Frete</th>
                      <th class="text-center">Venda<br>Qualif R$</th>
                      <th class="text-center" >Comissão %</th>
                      <th class="text-center" >Comissão R$</th>


              </tr>
              </thead>
              <tbody>

              ';
        $total_geral_valor = 0;
        $subtotal_rel = 'inicial';
        $subtotal2_rel = 'inicial';
        while ($row = mysql_fetch_array($result_query1)) {
            $dados_result[] = $row;

            $nome_unidade = strtoupper(substr($campos['un_nome'], 0, 14));
            $total_vendedor[$row['codven']] += $row['total_cimp'];
        }

        foreach ($dados_result as $key => $campos) {

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

            // ============================   Subtotal  ======================================

            if ($nome_vendedor1 != $subtotal_rel and $subtotal_rel != "inicial") {
                $resultado_rel .= '
          <tr class="' . $bg_subtotal . ' tr_result">
              <td colspan="3">TOTAL ' . $subtotal_rel . '</td>
              <td>' . $subtotal_cont_ped . '</td>
              <td></td>
              <td></td>
              <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_frete,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_valor_cfrete,  2, ',', '.') . '</td>
              <td align="right">' . number_format($subtotal_rel_venda_qualif,  2, ',', '.') . '</td>
              <td></td>
              <td align="right">' . number_format($subtotal_rel_comissao,  2, ',', '.') . '</td>
          </tr>
          ';

                //zerando totais para proximo pedido
                $subtotal_rel_qtde = 0;
                $subtotal_rel_frete = 0;
                $subtotal_rel_valor = 0;
                $subtotal_rel_valor_cfrete = 0;
                $subtotal_cont_ped = 0;

                $subtotal_rel_venda_qualif = 0;
                $subtotal_rel_comissao = 0;

                $subtotal2_rel_qtde = 0;
                $subtotal2_rel_valor = 0;
            }

            $subtotal_rel = $nome_vendedor1;


            //========= Fim do  subtotal ========================================================

            // ============== imprimir itens se analitico================================================
            //tratando pgto split
            if ($campos['orc_split_pgto'] == 'S' and $desconta_split == 'S') {
                $total_pv = $campos['total_cimp'] * 0.75;
            } else {
                $total_pv = $campos['total_cimp'];
            }

            //aplicar em 1 dez 2023
            //tem que ter zero Desc C.Pgto % Desc Faixa % Desc Comercial %


            //venda qulificada
            // regra 1: Quando desconto comercial (desc3) for maior que 0.5  = NÃO Qualificada
            // if ($campos['desc1'] < 0.5 AND $campos['desc2'] < 0.5 AND $campos['desc3'] < 0.5) {
            if ($campos['desc3'] < 0.5) {
                $venda_qualif = ($total_pv * 1 / 100); //1% venda qualifcada
            } else {
                $venda_qualif = 0;
            }
            //regra 2: Quando usar tabela de preço não for 1-finacasa =  NÃO Qualificada
            if ($campos['tabela_preco'] != 1) {
                $venda_qualif = 0;
            }

            //regra 3: Regras boleto Santander: 
                // se 30 and parc > 6 = não qualif
                // se 60 and parc > 4 = não qualif
                // se 90 and parc > 2 = não qualif
            if ($campos['bolstd_prim_parc'] == 30 AND $campos['bolsdt_qtd_parc'] == 6) {
                $venda_qualif = 0;
            }
            if ($campos['bolstd_prim_parc'] == 60 AND $campos['bolsdt_qtd_parc'] == 4) {
                $venda_qualif = 0;
            }
            if ($campos['bolstd_prim_parc'] == 90 AND $campos['bolsdt_qtd_parc'] == 2) {
                $venda_qualif = 0;
            }
            


            //comissão.
            // aplicar em 1/dez/2023
            // se desconto comercial >= 10 comissao  = 1%
            // Faixa de comissao:
            // Ate: 99.999,99 = 1,5%
            // Entre 100 ate 149.999,99 = 2 %
            // Entre 150 ate 199.999,99 = 2,5 %
            // Maior ou igual de 200 = 3%

            $total_venda_vendedor = $total_vendedor[$campos['codven']];
            if ($total_venda_vendedor <= 99999.99) {
                $comissao_percent = 1.5;
            } else if ($total_venda_vendedor < 149999.99) {
                $comissao_percent = 2;
            } else if ($total_venda_vendedor < 199999.99) {
                $comissao_percent = 2.5;
            } else {
                $comissao_percent = 3;
            }

            //se desconto comercial acima ou igual de 10
            // if ($campos['desc3'] >= 10) {
            //     $comissao_percent = 1;
            // }

            $comissão = ($total_pv * $comissao_percent / 100) + $venda_qualif; //comissão 5%

            if ($tipo_rel == 'analitico') {
                $resultado_rel .= '<tr class="tr_result">
                <td>' . $nome_unidade . '</td>
                <td>' . $nome_vendedor1 . '</td>
                <td>' . substr($campos['cliente_razao'], 0, 18) . '</td>
                <td>' . $campos['ped_num'] . '</td>
                <td>' . date("d/m/Y", strtotime($campos['orc_data_emissao'])) . '</td>
                <td align="center">' . number_format($campos['desc3'],  2, ',', '.') . '%</td>
                <td align="right">' . number_format($total_pv,  2, ',', '.') . '</td>
                <td align="right">' . number_format($campos['frete_valor'],  2, ',', '.') . '</td>
                <td align="right">' . number_format($total_pv + $campos['frete_valor'],  2, ',', '.') . '</td>
                <td align="right">' . number_format($venda_qualif,  2, ',', '.') . '</td>
                <td align="center">' . number_format($comissao_percent,  2, ',', '.') . '%</td>
                <td align="right">' . number_format($comissão,  2, ',', '.') . '</td>
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
            $subtotal_rel_venda_qualif += $venda_qualif;
            $subtotal_rel_comissao += $comissão;


            //Totalizando GERAL
            $total_geral_frete += $campos['frete_valor'];
            $total_geral_valor +=  $total_pv;
            $total_geral_valor_cfrete += $total_pv + $campos['frete_valor'];
            $subtotal_geral_venda_qualif += $venda_qualif;
            $subtotal_geral_comissao += $comissão;
        } // fim do while

        // ultimo subtotal
        $resultado_rel .= '
            <tr class="' . $bg_subtotal . ' tr_result">
                <td colspan="3">' . $subtotal_rel . '</td>
                <td>' . $subtotal_cont_ped . '</td>
                <td></td>
                <td></td>
                <td align="right">' . number_format($subtotal_rel_valor,  2, ',', '.') . '</td>
                <td align="right">' . number_format($subtotal_rel_frete,  2, ',', '.') . '</td>
                <td align="right">' . number_format($subtotal_rel_valor_cfrete,  2, ',', '.') . '</td>
                <td align="right">' . number_format($subtotal_rel_venda_qualif,  2, ',', '.') . '</td>
                <td></td>
                <td align="right">' . number_format($subtotal_rel_comissao,  2, ',', '.') . '</td>
            </tr>
            ';

        //Total Geral
        $resultado_rel .= '
                <tr class="bg_subtotal3_rel tr_result" >
                    <td colspan="6">TOTAL GERAL </td>
                    <td align="right">' . number_format($total_geral_valor,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_frete,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($total_geral_valor_cfrete,  2, ',', '.') . '</td>
                    <td align="right">' . number_format($subtotal_geral_venda_qualif,  2, ',', '.') . '</td>
                    <td></td>
                    <td align="right">' . number_format($subtotal_geral_comissao,  2, ',', '.') . '</td>
                </tr>';


        $resultado_rel .= '
            </tbody>
            </table>
            </div>';
    } // fim do if qtde_result1

} // fim do POST

echo $resultado_rel;
