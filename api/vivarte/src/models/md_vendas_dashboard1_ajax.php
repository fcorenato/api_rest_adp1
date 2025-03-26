<?php
date_default_timezone_set('America/Sao_Paulo');
//if ($_SERVER["REQUEST_METHOD"] == "POST") {
if ($s = 1) {

    require('../config/conexao.php');
    //se vendas até a data de hoje
    $vendas_ate_hoje = $_POST['ofday'];
    //$vendas_ate_hoje = true;
    //data hoje
    $data_atual = date("Ymd");
    //$data_atual = '20230710';

    //calculando filtro sql faixa data 1 mes e 6mes pela data atual
    $fx_start = date('Ym01', strtotime($data_atual));
    $fx_end  = date('Ymt', strtotime($data_atual));
    $filtro_fx_1mes .=  "AND cast(pedido_conv_date as date) between '$fx_start' and '$fx_end'";

    $fx_start = date('Ym01', strtotime('-5 month', strtotime($data_atual)));
    $fx_end  = date('Ymt', strtotime($data_atual));
    $filtro_fx_6mes .=  "AND cast(pedido_conv_date as date) between '$fx_start' and '$fx_end'";

    //filtro para tabela de taxa de conversao
    $filtro_orc_created_at_6mes = " Cast(orc_created_at AS date) BETWEEN '$fx_start' AND '$fx_end'";
    $filtro_pedido_conv_date_6mes = " Cast(pedido_conv_date AS date) BETWEEN '$fx_start' AND '$fx_end'";



    $filtro_fx_sql = $vendas_ate_hoje ? $filtro_fx_1mes : $filtro_fx_6mes;

    $data_atual = $vendas_ate_hoje ? $data_atual : date('Ymt', strtotime($data_atual));

    $n_mes_atual = date('m', strtotime($data_atual));
    $dt = date('Ym15', strtotime($data_atual));
    //echo '<hr> numero mes atual = ' . $n_mes_atual . ' data = ' . $data_atual . '<hr>';
    $n_mes_atual = 5;
    $n_fx = 1;
    $OR = '';
    $label_mes = [];
    $anoarray = [];
    $mesarray1 = [];
    $mesarray2 = [];
    while ($n_mes_atual >= 0) {
        //echo '<br>faixa ' . $n_fx;
        //echo ' -- data ref = ' . date('Ym15', strtotime('-' . $n_mes_atual . ' month', strtotime($dt)));
        //echo '  -- mesano ref = ' . date('Y-n', strtotime('-' . $n_mes_atual . ' month', strtotime($dt)));

        // ==== FAIXA DA DATA DO ANO ATUAL  ===
        $fx_dt_incio = date('Ym01', strtotime('-' . $n_mes_atual . ' month', strtotime($dt)));
        //echo '<br>fx inicio = ' . $fx_dt_incio;


        //calculando dia final 
        $dt_fim_mes = date('Ymt', strtotime('-' . $n_mes_atual . ' month', strtotime($dt)));
        $dt_atual_mes = date('Ymd', strtotime('-' . $n_mes_atual . ' month', strtotime($data_atual)));
        if (!$vendas_ate_hoje) {
            //echo '   fx fim = ' . $dt_fim_mes;
            $fx_dt_fim =  $dt_fim_mes;
        } else if ($dt_atual_mes > $dt_fim_mes) {
            //echo '        fx fim = ' . $dt_fim_mes;
            $fx_dt_fim =  $dt_fim_mes;
        } else {
            //echo '        fx fim = ' . $dt_atual_mes;
            $fx_dt_fim = $dt_atual_mes;
        }

        // ==== FAIXA DA DATA DO ANO PASSADO  ===
        $fx_dt_incio_anopass = date('Ym01', strtotime('-1 year', strtotime($fx_dt_incio)));
        //echo '<br>fx inicio = ' . $fx_dt_incio_anopass;

        //calculando dia final do ano passado
        $dt_fim_mes_pass = date('Ymt', strtotime($fx_dt_incio_anopass));
        $dt_atual_mes_pass = date('Ymd', strtotime('-1 year', strtotime($dt_atual_mes)));
        if (!$vendas_ate_hoje) {
            //echo '        fx fim t=a ' .  $dt_fim_mes_pass;
            $fx_dt_fim_anopass =  $dt_fim_mes_pass;
        } else  if ($dt_atual_mes_pass > $dt_fim_mes_pass) {
            //echo '        fx fim t=b ' . $dt_fim_mes_pass;
            $fx_dt_fim_anopass =  $dt_fim_mes_pass;
        } else {
            //echo '        fx fim f=c ' . $dt_atual_mes_pass;
            $fx_dt_fim_anopass =  $dt_atual_mes_pass;
        }
        ////echo '<br> ??? data pass dt_fim_mes_pass='.$dt_fim_mes_pass.'. e dt_atual_mes_pass='.$dt_atual_mes_pass.'<br>';
        $filtro_fx_data_emissao_1 .=  "$OR Cast(orc_data_emissao AS date) BETWEEN '$fx_dt_incio_anopass' AND '$fx_dt_fim_anopass' ";
        $filtro_fx_data_emissao_2 .=  "$OR Cast(orc_data_emissao AS date) BETWEEN '$fx_dt_incio' AND '$fx_dt_fim' ";
        
        $filtro_fx_data_1 .=  "$OR Cast(pedido_conv_date AS date) BETWEEN '$fx_dt_incio_anopass' AND '$fx_dt_fim_anopass' ";
        $filtro_fx_data_2 .=  "OR Cast(pedido_conv_date AS date) BETWEEN '$fx_dt_incio' AND '$fx_dt_fim' ";

        //array com ano e meses da pesquisa
        array_push($label_mes,  date('n', strtotime($fx_dt_incio_anopass)));
        $anoarray[date('Y', strtotime($fx_dt_incio_anopass))] = 1;
        $anoarray[date('Y', strtotime($fx_dt_incio))] = 1;
        $mesarray1[date('Y-n', strtotime($fx_dt_incio_anopass))] = 0;
        $mesarray2[date('Y-n', strtotime($fx_dt_incio))] = 0;


        //echo '<hr>';
        $n_mes_atual--;
        $n_fx++;
        $OR = ' OR ';
    }
    //filtros de data para query
    $filtro_fx_data_emissao = $filtro_fx_data_emissao_2;

    $filtro_fx_data = $filtro_fx_data_1 . ' ' . $filtro_fx_data_2;
    
    $query1 = "SELECT Year(pedido_conv_date)  AS ano,
        Month(pedido_conv_date) AS mes,
        CONCAT(Year(pedido_conv_date), '-', Month(pedido_conv_date)) as mesano,
        sum( if ( orc_split_pgto = 'S',total_cimp *0.75, 0 )) as split,
        sum( if ( (orc_split_pgto != 'S' OR orc_split_pgto IS NULL),total_cimp, 0 )) as normal
        FROM   `md_vendas_pedidos`
        WHERE  status = 'P'
        AND ( $filtro_fx_data )
        GROUP  BY ano, mes, CONCAT(Year(pedido_conv_date), '-', Month(pedido_conv_date))
        ORDER  BY orc_data_emissao
    ";
    //echo '<hr>query 1<br><br>' . $query1.'<hr>';

    // =========== VENDAS COMPARATIVO  ===========

    //gerando relatório
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);


    if ($qtde_query1 == 0) {
        $resultado_rel = '<hr>';
    } else {
        $labels = [];
        $data1 = [];
        $data2 = [];

        $total_6meses1 = 0;


        while ($campos = mysql_fetch_array($result_query1)) {
            $total_mes2_anterior = $total_mes2;
            $total_c_imposto_e_frete = ($campos['split'] + $campos['normal']);
            $total_6meses1 += $total_c_imposto_e_frete;
            $total_mes1 = $total_c_imposto_e_frete;
            $total_mes2 = $total_c_imposto_e_frete;
            foreach ($mesarray1 as $key => $value) {
                if ($campos['mesano'] == $key) {
                    $mesarray1[$key] = $total_c_imposto_e_frete;
                }
            }

            foreach ($mesarray2 as $key => $value) {
                if ($campos['mesano'] == $key) {
                    $mesarray2[$key] = $total_c_imposto_e_frete;
                }
            }
        } // fim do while

    } // fim do if qtde_result1

    //percorrendo array preenchendo os data para enviar no json
    foreach ($anoarray as $key => $value) {
        //echo '<br> ano array 1 '.$key;
    }
    foreach ($mesarray1 as $key => $value) {
        //echo '<br> array 1 '.$key.' = '.$value;
        array_push($data1,  $value);
    }
    foreach ($mesarray2 as $key => $value) {
        //echo '<br> array 1 '.$key.' = '.$value;
        array_push($data2,  $value);
    }

    //percentual referete mes anteior
    $total_mes1 = end($mesarray1);
    if ($total_mes1 == 0) {
        $compara_mes = 100;
    } else {
        //$compara_mes = (($total_mes2 - $total_mes2_anterior) / $total_mes2_anterior) * 100;
        $compara_mes = (($total_mes2 / $total_mes2_anterior) - 1) * 100;
    }
    $compara_mes_posneg = $compara_mes < 0 ? "neg" : "pos";
    $compara_mes = round($compara_mes, 2);


    //array meses
    $meses = array(1 => "Jan", 2 => "Fev", 3 => "Mar", 4 => "Abr", 5 => "Mai", 6 => "Jun", 7 => "Jul", 8 => "Ago", 9 => "Set", 10 => "Out", 11 => "Nov", 12 => "Dez");


    $res_vendas = array(
        "labels" => [$meses[$label_mes[0]], $meses[$label_mes[1]], $meses[$label_mes[2]], $meses[$label_mes[3]], $meses[$label_mes[4]], $meses[$label_mes[5]]],
        "data1" => $data1,
        "data2" => $data2,
        "total_mes2" => number_format($total_mes2,    2, ',', '.'),
        "compara_mes" => number_format($compara_mes,    2, ',', '.'),
        "compara_mes_posneg" => $compara_mes_posneg

    );

    // =========== TAXA CONVERSAO ORCAMENTO ===========
    //QUERY ORCADOS
    $query_orcados = "SELECT Year(orc_created_at)  AS ano,
            Month(orc_created_at) AS mes,
            CONCAT(Year(orc_created_at), '-', Month(orc_created_at)) as mesano,
            sum( if ( orc_split_pgto = 'S',total_cimp *0.75, 0 )) as split,
            sum( if ( (orc_split_pgto != 'S' OR orc_split_pgto IS NULL),total_cimp, 0 )) as normal
            FROM   `md_vendas_pedidos`
            WHERE ($filtro_fx_data_emissao)
            GROUP  BY ano, mes, CONCAT(Year(orc_created_at), '-', Month(orc_created_at))
            ORDER  BY orc_data_emissao
        ";
    // echo $query_orcados;

    //gerando relatório
    $result_query_orcados = mysql_query($query_orcados);
    $qtde_query_orcados = mysql_num_rows($result_query_orcados);

    if ($qtde_query_orcados == 0) {
        $resultado_rel = '<hr>';
    } else {
        while ($dados = mysql_fetch_array($result_query_orcados)) {
            $orcados = $dados["split"] + $dados["normal"];
            $txconv_orcados[] = [
                'mesano' => $dados["mesano"],
                'orcados' => $orcados
            ];
        } // fim do while

    } // fim do if qtde_result orcados

    //QUERY CONVERTIDOS
    $query_convertidos = "SELECT Year(pedido_conv_date)  AS ano,
                Month(pedido_conv_date) AS mes,
                CONCAT(Year(pedido_conv_date), '-', Month(pedido_conv_date)) as mesano,
                sum( if ( orc_split_pgto = 'S',total_cimp *0.75, 0 )) as split,
                sum( if ( (orc_split_pgto != 'S' OR orc_split_pgto IS NULL),total_cimp, 0 )) as normal
                FROM   `md_vendas_pedidos`
                WHERE  status = 'P'
                AND ( $filtro_fx_data )
                GROUP  BY ano, mes, CONCAT(Year(pedido_conv_date), '-', Month(pedido_conv_date))
                ORDER  BY orc_data_emissao
        ";
    // echo $query_convertidos;

    //gerando relatório
    $result_query_convertidos = mysql_query($query_convertidos);
    $qtde_query_convertidos = mysql_num_rows($result_query_convertidos);

    if ($qtde_query_convertidos == 0) {
        $resultado_rel = '<hr>';
    } else {
        while ($dados = mysql_fetch_array($result_query_convertidos)) {
            $convertidos = $dados["split"] + $dados["normal"];
            $txconv_convertidos[$dados["mesano"]] = [
                'mesano' => $dados["mesano"],
                'convertidos' => $convertidos
            ];
        } // fim do while

    } // fim do if qtde_result orcados

    //montando tabela com dados
    $taxa_conv_calc_total = 0;
    foreach ($txconv_orcados as $key => $value) {
        $convertidos = $txconv_convertidos[$value['mesano']]['convertidos'];
        $taxa_conv_calc = round($convertidos / $value['orcados'] * 100, 2);
        $taxa_conv_calc_total +=  $taxa_conv_calc;
        $taxa_ult_mes = $taxa_conv_calc;
        $orcados_ult_mes = $value['orcados'];
        $res_taxa_convercao .= '
        <tr>
            <td>' . $value['mesano'] . '</td>
            <td class="text-right">R$ ' . number_format($value['orcados'],    2, ',', '.') . '</td>
            <td class="text-right">R$ ' . number_format($convertidos,    2, ',', '.') . '</td>
            <td class="text-right">' . number_format($taxa_conv_calc,    2, ',', '.') . '</td>
        </tr>';
    }
    //total tabela
    $tx_media = round(($taxa_conv_calc_total - $taxa_ult_mes) / 5, 2);
    $projecao_venda = $orcados_ult_mes * $tx_media / 100;
    $res_taxa_convercao .= '
        <tr>
            <td class="font-weight-bold">Taxa Média</td>
            <td>' . number_format($tx_media,    2, ',', '.') . ' %</td>
            <td class="text-right font-weight-bold">Projeção Venda c/ taxa média</td>
            <td class="text-right ">R$ ' . number_format($projecao_venda,    2, ',', '.') . '</td>
        </tr>';





    // =========== RANKING PRODUTOS QTDE ===========

    $query2 = "SELECT pvi.codigo, pvi.descricao, pvi.unidade, sum(pvi.qtde) as qtd,
            sum(if ( pv.orc_split_pgto = 'S',(((pvi.prc_tab - pvi.desconto) * pvi.qtde)*(1+ pvi.ipi / 100) )*0.75,(((pvi.prc_tab - pvi.desconto) * pvi.qtde)*(1+ pvi.ipi / 100)))) as valor_total
            
            FROM   `md_vendas_pedidos` pv
            left join md_vendas_pedidos_itens AS pvi on (pv.id = pvi.pedido_id)
            
            WHERE pv.status = 'P'
            AND pvi.status = 'A'
            $filtro_fx_sql

            GROUP  BY pvi.codigo, pvi.descricao, pvi.unidade
            ORDER  BY qtd desc
        ";


    //gerando relatório
    $result_query2 = mysql_query($query2);
    $qtde_query2 = mysql_num_rows($result_query2);

    if ($qtde_query2 == 0) {
        $resultado_rel = '<hr>';
    } else {
        while ($dadosprod = mysql_fetch_array($result_query2)) {
            $total_c_imposto = ($dadosprod['valor_total']);
            $total_geral_qtde += $dadosprod['qtd'];
            $total_geral_valor += $total_c_imposto;
            $res_prod_qtde .= '
            <tr>
                <td>' . $dadosprod['codigo'] . '</td>
                <td>' . $dadosprod['descricao'] . '</td>
                <td>' . $dadosprod['unidade'] . '</td>
                <td class="text-right">' . number_format($dadosprod['qtd'],    2, ',', '.') . '</td>
                <td class="text-right">' . number_format($total_c_imposto,    2, ',', '.') . '</td>
            </tr>';
        } // fim do while
        $res_prod_qtde .= '
        <tr>
            <td class="font-weight-bold">Total Geral</td>
            <td></td>
            <td></td>
            <td class="text-right font-weight-bold">' . $total_geral_qtde . '</td>
            <td class="text-right font-weight-bold">R$ ' . number_format($total_geral_valor,    2, ',', '.') . '</td>
        </tr>';
    } // fim do if qtde_result1


    // =========== RANKING unidade venda===========

    $query3 = "SELECT un.descricao, count(pv.id) as numped,
        sum(if ( pv.orc_split_pgto = 'S',pv.total_cimp *0.75,pv.total_cimp)) as totalvalor
        
        FROM   `md_vendas_pedidos` pv
        left join sys_unidades AS un on (un.codigo = pv.unidade_codigo)
        
        WHERE pv.status = 'P'
        $filtro_fx_sql

        GROUP  BY un.descricao
        ORDER  BY totalvalor desc

        ";

    //echo $query3.'<hr>';

    //gerando relatório
    $result_query3 = mysql_query($query3);
    $qtde_query3 = mysql_num_rows($result_query3);
    $count_unidades = 0;
    if ($qtde_query3 == 0) {
        $resultado_rel = '<hr>';
    } else {
        while ($dadosprod = mysql_fetch_array($result_query3)) {
            //$total_c_imposto_e_frete = ($dadosprod['split'] + $dadosprod['normal'] + $dadosprod['frete']);
            $total_c_imposto_e_frete = $dadosprod['totalvalor'];
            $total_geral_unidade_pedidos += $dadosprod['numped'];
            $total_geral_unidade += $total_c_imposto_e_frete;
            $res_unidvenda_val .= '
            <tr>
                <td>' . $dadosprod['descricao'] . '</td>
                <td class="text-right">' . $dadosprod['numped'] . '</td>
                <td class="text-right">R$ ' . number_format($total_c_imposto_e_frete,    2, ',', '.') . '</td>
            </tr>';
            $count_unidades++;
        } // fim do while
        $res_unidvenda_val .= '
        <tr>
            <td class="font-weight-bold">Total Positivadas:  ' . $count_unidades . '</td>
            <td class="text-right font-weight-bold">' . $total_geral_unidade_pedidos . '</td>
            <td class="text-right font-weight-bold">R$ ' . number_format($total_geral_unidade,    2, ',', '.') . '</td>
        </tr>';
    } // fim do if qtde_result1


    // =========== RANKING VENDEDOR ===========

    $query4 = "SELECT vendc.nome_completo as vend_interno,
        sum(if ( pv.orc_split_pgto = 'S',pv.total_cimp *0.75,pv.total_cimp)) as totalvalor
        
        FROM   `md_vendas_pedidos` pv
        left join sys_usuarios AS vendc on (vendc.codigo = pv.pedido_conv_user)
        
        WHERE pv.status = 'P'
        $filtro_fx_sql

        GROUP  BY vendc.nome_completo
        ORDER  BY totalvalor desc    
        ";

    /*somatorio separado na query
        sum(pv.frete_valor) as frete,
        sum( if ( pv.orc_split_pgto = 'S',pv.total_cimp *0.75, 0 )) as split,
        sum( if ( (pv.orc_split_pgto != 'S' OR pv.orc_split_pgto IS NULL),pv.total_cimp, 0 )) as normal
    */
    //echo $query4.'<hr>';

    //gerando relatório
    $result_query4 = mysql_query($query4);
    $qtde_query4 = mysql_num_rows($result_query4);
    //echo $query4.'<hr>';
    if ($qtde_query4 == 0) {
        $resultado_rel = '<hr>';
    } else {
        while ($dadosprod = mysql_fetch_array($result_query4)) {
            //$total_c_imposto_e_frete = ($dadosprod['split'] + $dadosprod['normal'] + $dadosprod['frete']);
            $total_c_imposto_e_frete = $dadosprod['totalvalor'];
            $total_geral_vendedor += $total_c_imposto_e_frete;
            $res_vendedor_val .= '
            <tr>
                <td>' . $dadosprod['vend_interno'] . '</td>
                <td class="text-right">R$ ' . number_format($total_c_imposto_e_frete,    2, ',', '.') . '</td>
            </tr>';
        } // fim do while
        $res_vendedor_val .= '
            <tr>
                <td class="font-weight-bold">Total Geral</td>
                <td class="text-right font-weight-bold">R$ ' . number_format($total_geral_vendedor,    2, ',', '.') . '</td>
            </tr>';
    } // fim do if qtde_result1


    //TODOS OS DADOS 

    $data_dash = array(
        "vendas" => $res_vendas,
        "rkvendedor" => $res_vendedor_val,
        "rkprodqtde" => $res_prod_qtde,
        "rkprodval" => $res_unidvenda_val,
        "taxaconv" => $res_taxa_convercao
    );


    // Encoding array in JSON format
    echo json_encode($data_dash);
} // fim do POST