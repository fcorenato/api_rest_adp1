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
    $filtro_fx_1mes_conv .=  "AND cast(pedido_conv_date as date) between '$fx_start' and '$fx_end'";
    $filtro_fx_1mes_create .=  " cast(orc_created_at as date) between '$fx_start' and '$fx_end'";

    $fx_start = date('Ym01', strtotime('-5 month', strtotime($data_atual)));
    $fx_end  = date('Ymt', strtotime($data_atual));
    $filtro_fx_6mes .=  "AND cast(pedido_conv_date as date) between '$fx_start' and '$fx_end'";

    //filtro para tabela de taxa de conversao
    $filtro_orc_created_at_6mes = " Cast(orc_created_at AS date) BETWEEN '$fx_start' AND '$fx_end'";
    $filtro_pedido_conv_date_6mes = " Cast(pedido_conv_date AS date) BETWEEN '$fx_start' AND '$fx_end'";



    $filtro_fx_sql = $vendas_ate_hoje ? $filtro_fx_1mes : $filtro_fx_6mes;
    $filtro_fx_sql_create = $vendas_ate_hoje ? $filtro_fx_1mes_create : $filtro_orc_created_at_6mes;

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
        $filtro_fx_data_1 .=  "$OR Cast(pedido_conv_date AS date) BETWEEN '$fx_dt_incio_anopass' AND '$fx_dt_fim_anopass' ";
        $filtro_fx_data_2 .=  "OR Cast(pedido_conv_date AS date) BETWEEN '$fx_dt_incio' AND '$fx_dt_fim' ";
        $filtro_fx_data_ano_atual =  " Cast(pedido_conv_date AS date) BETWEEN '$fx_dt_incio' AND '$fx_dt_fim' ";

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
        $compara_mes = (( $total_mes2_anterior / $total_mes2) - 1) * 100;
    }
    $compara_mes_posneg = $compara_mes < 0 ? "neg" : "pos";
    $compara_mes = round($compara_mes, 2);


    //array meses
    $meses = array(1 => "Jan", 2 => "Fev", 3 => "Mar", 4 => "Abr", 5 => "Mai", 6 => "Jun", 7 => "Jul", 8 => "Ago", 9 => "Set", 10 => "Out", 11 => "Nov", 12 => "Dez");


    $res_vendas = array(
        "labels" => [$meses[$label_mes[0]], $meses[$label_mes[1]], $meses[$label_mes[2]], $meses[$label_mes[3]], $meses[$label_mes[4]], $meses[$label_mes[5]]],
        "data1" => $data1,
        "data2" => $data2,
        "total_mes2" => number_format($total_mes2_anterior,    2, ',', '.'),
        "compara_mes" => number_format($compara_mes,    2, ',', '.'),
        "compara_mes_posneg" => $compara_mes_posneg

    );

    // =========== GRAFICO DE PEDIDOS POR STATUS ===========

    //1 - pedidos criado no periodo
    $query1 = "SELECT count(id) as ped, sum(total_cimp)  FROM `md_vendas_pedidos` 
             WHERE 1
            AND $filtro_fx_sql_create
            ";
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    echo $$query1;
    if ($qtde_query1 == 0) {
        $ped_status_create = 0;
    } else {
        while ($campos = mysql_fetch_array($result_query1)) {
            $ped_status_create = $campos['ped'];
        }
    }

    //1.1 - pedidos aberto no periodo
    $query1 = "SELECT count(id) as ped, sum(total_cimp)  FROM `md_vendas_pedidos` 
            WHERE ( status = 'A' OR  status = 'G' OR  status = 'N' OR  status = 'B')
            AND $filtro_fx_sql_create
            ";
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    // echo $query1;

    if ($qtde_query1 == 0) {
        $ped_status_aberto = 0;
    } else {
        while ($campos = mysql_fetch_array($result_query1)) {
            $ped_status_aberto = $campos['ped'];
        }
    }

    //2 - pedidos convertidos no periodo
    $query1 = "SELECT count(id) as ped, sum(total_cimp)  FROM `md_vendas_pedidos` 
            WHERE status = 'P'
            $filtro_fx_sql
            ";
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);


    if ($qtde_query1 == 0) {
        $ped_status_conv = 0;
    } else {
        while ($campos = mysql_fetch_array($result_query1)) {
            $ped_status_conv = $campos['ped'];
        }
    }

    //3 - pedidos cancelados no periodo
    $query1 = "SELECT count(pv.id) as ped, sum(total_cimp)  FROM `md_vendas_pedidos` as pv
        LEFT JOIN md_vendas_pedidos_eventos as pe on pv.id = pe.pedido_id
        WHERE pv.status = 'C'
        AND pe.evento = 'Cancelado'
        AND $filtro_fx_sql_create
            ";
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);


    if ($qtde_query1 == 0) {
        $ped_status_cancel = 0;
    } else {
        while ($campos = mysql_fetch_array($result_query1)) {
            $ped_status_cancel = $campos['ped'];
        }
    }

    //4 - pedidos vencidos no periodo
    $query1 = "SELECT count(id) as ped, sum(total_cimp)  FROM `md_vendas_pedidos` 
        WHERE status = 'V'
        AND $filtro_fx_sql_create
            ";
    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);


    if ($qtde_query1 == 0) {
        $ped_status_vencido = 0;
    } else {
        while ($campos = mysql_fetch_array($result_query1)) {
            $ped_status_vencido = $campos['ped'];
        }
    }


    //montando array para enviar valores
    $res_pedidos = array(
        "labels" => ['Aberto(' . $ped_status_aberto . ')', 'Convertido(' . $ped_status_conv . ')', 'Cancelado(' . $ped_status_cancel . ')', 'Vencido(' . $ped_status_vencido . ')', 'Criado(' . $ped_status_create . ')'],
        "data" => [$ped_status_aberto, $ped_status_conv, $ped_status_cancel, $ped_status_vencido,0],
    );

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
    // echo $query2;
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
        left join sys_usuarios AS vendc on (vendc.codigo = pv.vend1)
        
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
    


    // =========== HISTORICO VENDEDOR ===========

    $query5 = "SELECT vendc.nome_completo as vend_interno,
		Concat(Month(pedido_conv_date), '-', Year(pedido_conv_date)) AS mesano,
        sum(if ( pv.orc_split_pgto = 'S',pv.total_cimp *0.75,pv.total_cimp)) as totalvalor
        
        FROM   `md_vendas_pedidos` pv
        left join sys_usuarios AS vendc on (vendc.codigo = pv.vend1)
        
        WHERE pv.status = 'P'
        AND $filtro_pedido_conv_date_6mes

        GROUP  BY vendc.nome_completo, mesano
        ORDER  BY vendc.nome_completo, orc_data_emissao;    
        ";

    //gerando relatório
    $result_query5 = mysql_query($query5);
    $qtde_query5 = mysql_num_rows($result_query5);
    //echo $query5.'<hr>';
    if ($qtde_query5 == 0) {
        $resultado_rel = '<hr>';
    } else {
        while ($dadoshv = mysql_fetch_array($result_query5)) {
            $htv[$dadoshv['vend_interno']][$dadoshv['mesano']] = $dadoshv['totalvalor'];
        }
    } // fim do if qtde_result1

    $hv_id = 1;
  
    foreach ($htv as $vendedor => $vendasv) {
        $label_hv_meses = '';
        $label_hv_valor = '';
        foreach ($vendasv as $mesano => $value) {
            // echo $vendedor .' mes= '.$mesano .' --> '.$value.' || ';
            $label_hv_meses .= "'" . $mesano . "',";
            $label_hv_valor .= "'" . $value . "',"; 
        }

        $label_hv_meses = substr($label_hv_meses, 0, -1);
        $label_hv_valor = substr($label_hv_valor, 0, -1);

        $res_htvendedor_val .= '&nbsp;&nbsp;'. $vendedor . '<canvas id="grafico-'.$hv_id.'" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; box-sizing: border-box; width: 398px;" width="398" height="250"></canvas>';

        $res_htvendedor_val .= "
        <script>
        $(function() {
            var barChartData = {
                labels: [$label_hv_meses],
                datasets: [{
                    label: '',
                    data: [$label_hv_valor],
                    backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
                    borderWidth: 2,
                }],
            }
            var barChartCanvas = $('#grafico-$hv_id').get(0).getContext('2d')

            var barChartOptions = {
                title: {
                    display: true,
                    text: ''
                },
                legend: {
                    display: false,
                },
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    xAxes: [{

                            gridLines: {
                                offsetGridLines: true // à rajouter
                            }
                        },

                    ],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
                plugins: {
                    labels: {
                        render: 'value',
                        position: 'outside',
                        // textMargin: -20,
                        // fontColor: 'white', //OR fontColor: ['green', 'white', 'red'],
                    }
                },



            }

            var barChart = new Chart(barChartCanvas, {
                type: 'bar',
                data: barChartData,
                options: barChartOptions
            })



        })
    </script>";

    $hv_id++;
    }

    

    //TODOS OS DADOS 

    $data_dash = array(
        "vendas" => $res_vendas,
        "pedidos" => $res_pedidos,
        "rkvendedor" => $res_vendedor_val,
        "htvendedor" => $res_htvendedor_val,
        "rkprodqtde" => $res_prod_qtde,
        "rkprodval" => $res_unidvenda_val
    );


    // Encoding array in JSON format
    echo json_encode($data_dash);
} // fim do POST