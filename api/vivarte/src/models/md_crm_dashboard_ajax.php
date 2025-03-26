<?php
date_default_timezone_set('America/Sao_Paulo');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require('../../src/config/SUsuario.php');
    require('../config/conexao.php');
    //data do dia para calculos
    $data_hoje = date("Y-m-d"); //data atual formado 20201205
    //array meses
    $array_meses = array(1 => "Jan", 2 => "Fev", 3 => "Mar", 4 => "Abr", 5 => "Mai", 6 => "Jun", 7 => "Jul", 8 => "Ago", 9 => "Set", 10 => "Out", 11 => "Nov", 12 => "Dez");

    //filtro por periodo
    if (isset($_POST['data_inicial']) and isset($_POST['data_final'])) {
        $tipo_data = $_POST['tipo_data'];
        $data_inicial = $_POST['data_inicial'];
        $data_final = $_POST['data_final'];
        if ($data_inicial != "" and $data_final != "") {
            if ($tipo_data == 'CONVERSAO') {
                $pesquisa_por_data_op_data_inicio = " AND STR_TO_DATE(op.data_inicio, '%Y-%m-%d') BETWEEN '$data_inicial' AND '$data_final' ";
            } else {
                $pesquisa_por_data_op_data_inicio = " AND op.data_inicio between '$data_inicial' and '$data_final' ";
            }
        }
    }

    //card 1
    $sql1 = "SELECT status, etapa_atual_id, count(id) AS qtde, sum(valor) as valor FROM `md_crm_oportunidade` as op 
    WHERE 1 
    AND op.status != 'I'
    $pesquisa_por_data_op_data_inicio
    GROUP BY status,etapa_atual_id 
    ORDER BY status";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {

            $oport_array_qtde[$dados["status"]] += $dados["qtde"];
            $oport_array_val[$dados["status"]] += $dados["valor"];

            if ($dados["status"] != 'V' and $dados["status"] != 'C' and $dados["status"] != 'P') {
                $oport_etapa_array_qtde[$dados["etapa_atual_id"]] += $dados["qtde"];
                $oport_etapa_array_val[$dados["etapa_atual_id"]] += $dados["valor"];
            }
        }

        // print("<pre>" . print_r($oport_array_qtde, true) . "</pre>");
    }


    $array_opt_status = array('A' => 'EM ANDAMENTO', 'P' => 'PAUSADO', 'V' => 'VENDIDO', 'C' => 'PERDIDO');

    //Grafico 1 Oportunidade por Status 
    $sql1 = "SELECT status, count(id) AS qtde, sum(valor) as valor FROM `md_crm_oportunidade` as op 
    WHERE 1 
    AND op.status != 'I'
    $pesquisa_por_data_op_data_inicio
    GROUP BY status ORDER BY status";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {

            $labelg1 .= "'" . $array_opt_status[$dados["status"]] . " (" . $dados["qtde"] . ")',";
            $datag1 .= $dados["qtde"] . ",";
            $total_g1 += $dados["qtde"];
            $total_g1_valor += $dados["valor"];
        }

        $labelg1 = substr($labelg1, 0, -1);
        $datag1 = substr($datag1, 0, -1);
    }


    //Grafico 2 Contagem por Fonte / status
    $sql1 = "SELECT ft.descricao, count(op.id) as total, count(if(op.status = 'A', op.status, NULL)) AS andamento, count(if(op.status = 'V', op.status, NULL)) AS vendido, count(if(op.status = 'C', op.status, NULL)) AS cancelado FROM `md_crm_oportunidade` AS op
    LEFT JOIN md_crm_fonte AS ft ON ft.id = op.fonte_id
    WHERE 1 
    AND op.status != 'I'
    $pesquisa_por_data_op_data_inicio
    GROUP BY ft.descricao
    ORDER BY total DESC
    ";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg2 .= "'" . $dados["descricao"] . "',";
            $datag2_total .= $dados["total"] . ",";
            $datag2_a .= $dados["andamento"] . ",";
            $datag2_v .= $dados["vendido"] . ",";
            $datag2_c .= $dados["cancelado"] . ",";
            $total_g2 += $dados["total"];
        }


        $labelg2 = substr($labelg2, 0, -1);
        $datag2_total = substr($datag2_total, 0, -1);
        $datag2_a = substr($datag2_a, 0, -1);
        $datag2_v = substr($datag2_v, 0, -1);
        $datag2_c = substr($datag2_c, 0, -1);
    }

    //Grafico 3 Contagem por campanha
    $sql1 = "SELECT cp.descricao as campanha, count(op.id) as qtde FROM `md_crm_oportunidade` AS op
    LEFT JOIN md_crm_campanha AS cp ON cp.id =  op.campanha_id
    WHERE 1
    AND op.status != 'I'
    $pesquisa_por_data_op_data_inicio
    GROUP BY cp.descricao
    ORDER BY qtde DESC";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg3 .= "'" . $dados["campanha"] . "',";
            $datag3 .= $dados["qtde"] . ",";
            $total_g3 += $dados["qtde"];
        }

        $labelg3 = substr($labelg3, 0, -1);
        $datag3 = substr($datag3, 0, -1);
    }


    //Grafico 4 Contagem Vendedor / Status 
    $sql1 = "SELECT us.usuario, count(op.id) as total, count(if(op.status = 'A', op.status, NULL)) AS andamento, count(if(op.status = 'V', op.status, NULL)) AS vendido, count(if(op.status = 'C', op.status, NULL)) AS cancelado, count(if(op.status = 'P', op.status, NULL)) AS pausado FROM `md_crm_oportunidade` AS op
    LEFT JOIN sys_usuarios AS us ON us.codigo = op.vendedor_id
    WHERE 1 
    AND op.status != 'I'
    $pesquisa_por_data_op_data_inicio
    GROUP BY us.usuario
    ORDER BY total DESC
    ";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg4 .= "'" . $dados["usuario"] . "',";
            $datag4_total .= $dados["total"] . ",";
            $datag4_a .= $dados["andamento"] . ",";
            $datag4_v .= $dados["vendido"] . ",";
            $datag4_c .= $dados["cancelado"] . ",";
            $total_g4 += $dados["total"];

            $labelg4_pie[] = [
                'usuario' => $dados["usuario"],
                'total' => $dados["total"],
                'andamento' => $dados["andamento"],
                'vendido' => $dados["vendido"],
                'pausado' => $dados["pausado"],
                'cancelado' => $dados["cancelado"],
                'total' => $dados["total"]
            ];
        }

        $labelg4 = substr($labelg4, 0, -1);
        $datag4_total = substr($datag4_total, 0, -1);
        $datag4_a = substr($datag4_a, 0, -1);
        $datag4_v = substr($datag4_v, 0, -1);
        $datag4_c = substr($datag4_c, 0, -1);
    }

    // Grafico 5 Contagem Campanha / Status 
    // CONSULTA ACESSO CAMPANHA NO SITE
    // inicializando CURL =================================================================
    $url = 'https://vivarterevestimentos.com.br/api_data_campanha_get.php/?dt1=' . $data_inicial . '&dt2=' . $data_final . '';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================
    $resultado = json_decode($retorno);
    // echo $url;
    // print("<pre>".print_r($resultado,true)."</pre>");

    foreach ($resultado as $it) {
        // echo $it->campanha_id.'<br>'; 
        $array_acesso_site[$it->campanha_id] = $it->qtde;
    }

    //zerando acessos dos sem campanhas
    $array_acesso_site[1] = 0;
    $array_acesso_site[30] = 0;

    $sql1 = "SELECT cp.id AS campanha_id,
    cp.descricao,
    (SELECT Count(op.id) FROM md_crm_oportunidade AS op WHERE op.campanha_id = cp.id $pesquisa_por_data_op_data_inicio AND op.status != 'I') AS total,
    (SELECT Count(IF(op.status = 'A', op.status, NULL)) FROM md_crm_oportunidade AS op WHERE op.campanha_id = cp.id $pesquisa_por_data_op_data_inicio AND op.status != 'I') AS andamento,
    (SELECT Count(IF(op.status = 'V', op.status, NULL)) FROM md_crm_oportunidade AS op WHERE op.campanha_id = cp.id $pesquisa_por_data_op_data_inicio AND op.status != 'I') AS vendido,
    (SELECT Count(IF(op.status = 'C', op.status, NULL)) FROM md_crm_oportunidade AS op WHERE op.campanha_id = cp.id $pesquisa_por_data_op_data_inicio AND op.status != 'I') AS cancelado
    FROM   `md_crm_campanha` AS cp
    WHERE cp.status = 'A'
    ORDER  BY total DESC;
    ";

    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg5 .= "'"  . $dados["descricao"] . "',";
            $datag5_acessos .= $array_acesso_site[$dados["campanha_id"]] . ",";
            $datag5_total .= $dados["total"] . ",";
            $datag5_a .= $dados["andamento"] . ",";
            $datag5_v .= $dados["vendido"] . ",";
            $datag5_c .= $dados["cancelado"] . ",";
            $total_g5 += $dados["total"];

            $total_acessos += $array_acesso_site[$dados["campanha_id"]];
            $total_opt += $dados["total"];
            $total_andamento += $dados["andamento"];
            $total_vendido += $dados["vendido"];
            $total_perdido += $dados["cancelado"];

            $table_acessoxsite_itens .= '
            <tr>
                <td>(' . $dados["campanha_id"].') '. $dados["descricao"] . '</td>
                <td>' . $array_acesso_site[$dados["campanha_id"]] . '</td>
                <td>' . $dados["total"] . '</td>
                <td class="text-center">' . number_format($dados["total"] / $array_acesso_site[$dados["campanha_id"]] * 100,    2, ',', '.') . '</td>
                <td class="text-center">' . $dados["andamento"] . '</td>
                <td class="text-center">' . $dados["vendido"] . '</td>
                <td class="text-center">' . number_format($dados["vendido"] / $dados["total"] * 100,    2, ',', '.') . '</td>
                <td class="text-center">' . $dados["cancelado"] . '</td>
                
            </tr>';
        }

        $table_acessoxsite_itens .= '
            <tr style="background-color:#ccc;font-weight: bold;">
                <td >Total</td>
                <td>' . $total_acessos . '</td>
                <td>' . $total_opt . '</td>
                <td class="text-center">' . number_format($total_opt / $total_acessos * 100,    2, ',', '.') . '</td>
                <td class="text-center">' . $total_andamento . '</td>
                <td class="text-center">' . $total_vendido . '</td>
                <td class="text-center">' . number_format($total_vendido / $total_opt * 100,    2, ',', '.') . '</td>
                <td class="text-center">' . $total_perdido . '</td>
                
            </tr>';

        $labelg5 = substr($labelg5, 0, -1);
        $datag5_acessos = substr($datag5_acessos, 0, -1);
        $datag5_total = substr($datag5_total, 0, -1);
        $datag5_a = substr($datag5_a, 0, -1);
        $datag5_v = substr($datag5_v, 0, -1);
        $datag5_c = substr($datag5_c, 0, -1);
    }

    //Grafico 9 acessos por dia
    // Grafico 5 Contagem Campanha / Status 
    // CONSULTA ACESSO CAMPANHA NO SITE
    //inicializando CURL =================================================================
    $url = 'https://vivarterevestimentos.com.br/api_data_acessos_get.php/?dt1=' . $data_inicial . '&dt2=' . $data_final . '';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================
    $resultado = json_decode($retorno);
    // echo $url;
    // print("<pre>".print_r($resultado,true)."</pre>");


    $array_opt_campanha = array('1' => 'SEM CAMPANHA', '2' => 'GOOGLE PESQ TERMO', '3' => 'GOOGLE PESQ CONCORRENTE',  '4' => 'GOOGLE REMARKETING BLACKFRIDAY',  '5' => 'META GERAÇÃO LEAD',  '6' => 'META BLACKFRIDAY',  '7' => '7-REMARKETING WHATSAPP-EMAIL',  '8' => 'INSTAGRAM ORGANICO',  '9' => 'MES CONSUMIDOR',  '10' => 'CASOCA');

    foreach ($resultado as $it) {
        $diames = $it->dia . '/' . $it->mes;
        $cpid = $it->campanha_id;
        $qt = $it->qtde;

        $datag9day[$diames][$cpid] = $qt;
    }

    foreach ($datag9day as $key => $value) {
        $qtot = $value["2"] + $value["3"] + $value["4"] + $value["5"] + $value["6"] + $value["7"] + $value["8"] + $value["9"] + $value["10"];
        $labelg9 .= "'" . $key . "',";
        $datag9 .= $qtot . ",";
        $datag9c2 .= $value["2"] . ",";
        $datag9c3 .= $value["3"] . ",";
        $datag9c4 .= $value["4"] . ",";
        $datag9c5 .= $value["5"] . ",";
        $datag9c6 .= $value["6"] . ",";
        $datag9c7 .= $value["7"] . ",";
        $datag9c8 .= $value["8"] . ",";
        $datag9c9 .= $value["9"] . ",";
        $datag9c10 .= $value["10"] . ",";
    }

    // print("<pre>".print_r($datag9day,true)."</pre>");

    $labelg9 = substr($labelg9, 0, -1);
    $datag9 = substr($datag9, 0, -1);
    $datag9c2 = substr($datag9c2, 0, -1);
    $datag9c3 = substr($datag9c3, 0, -1);
    $datag9c4 = substr($datag9c4, 0, -1);
    $datag9c5 = substr($datag9c5, 0, -1);
    $datag9c6 = substr($datag9c6, 0, -1);
    $datag9c7 = substr($datag9c7, 0, -1);
    $datag9c8 = substr($datag9c8, 0, -1);
    $datag9c9 = substr($datag9c9, 0, -1);
    $datag9c10 = substr($datag9c10, 0, -1);

    //Grafico 6 motivo de perda
    $sql1 = "SELECT pd.descricao, count(op.id) as qtde, sum(op.valor) as valor, (count(op.id) / (SELECT COUNT(id) FROM md_crm_oportunidade AS op WHERE op.status = 'C' $pesquisa_por_data_op_data_inicio)) * 100 as percent 
    FROM `md_crm_oportunidade` AS op
    LEFT JOIN md_crm_motivo_perda AS pd ON pd.id = op.motivo_perda_id
    WHERE op.status = 'C'
    $pesquisa_por_data_op_data_inicio
    GROUP BY pd.descricao
    ORDER BY qtde Desc";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg6 .= "'" . $dados["descricao"] . " (" . $dados["qtde"] . ")',";
            $datag6 .= $dados["qtde"] . ",";
            $total_g6 += $dados["qtde"];
            $total_g6_valor += $dados["valor"];

            $table_motivoperda_itens .= '
            <tr>
                <td>' . $dados["descricao"] . '</td>
                <td align="center">' . $dados["qtde"] . '</td>
                <td align="center">' . number_format($dados["valor"],    2, ',', '.') . '</td>
                <td align="center">' . number_format($dados["percent"],    2, ',', '.') . '</td>
            </tr>';
        }

        $table_motivoperda_itens .= '
            <tr style="background-color:#ccc;font-weight: bold;">
                <td >Total</td>
                <td align="center">' . $total_g6 . '</td>
                <td align="center">' . number_format($total_g6_valor,    2, ',', '.') . '</td>
                <td align="center">100</td>
            </tr>';

        $labelg6 = substr($labelg6, 0, -1);
        $datag6 = substr($datag6, 0, -1);
    }

    //Grafico 6B perda por etapa
    $sql1 = "SELECT et.descricao, count(op.id) as qtde, sum(op.valor) as valor, (count(op.id) / (SELECT COUNT(id) FROM md_crm_oportunidade AS op WHERE op.status = 'C' $pesquisa_por_data_op_data_inicio )) * 100 as percent 
	FROM `md_crm_oportunidade` AS op
    LEFT JOIN md_crm_etapa as et ON op.etapa_atual_id = et.id
    WHERE op.status = 'C'
    $pesquisa_por_data_op_data_inicio
    GROUP BY et.descricao
    ORDER BY qtde Desc";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg6 .= "'" . $dados["descricao"] . " (" . $dados["qtde"] . ")',";
            $datag6 .= $dados["qtde"] . ",";
            $total_g6b += $dados["qtde"];
            $total_g6b_valor += $dados["valor"];

            $table_perda_etapa_itens .= '
            <tr>
                <td>' . $dados["descricao"] . '</td>
                <td align="center">' . $dados["qtde"] . '</td>
                <td align="center">' . number_format($dados["valor"],    2, ',', '.') . '</td>
                <td align="center">' . number_format($dados["percent"],    2, ',', '.') . '</td>
            </tr>';
        }

        $table_perda_etapa_itens .= '
            <tr style="background-color:#ccc;font-weight: bold;">
                <td >Total</td>
                <td align="center">' . $total_g6b . '</td>
                <td align="center">' . number_format($total_g6b_valor,    2, ',', '.') . '</td>
                <td align="center">100</td>
            </tr>';

        $labelg6 = substr($labelg6, 0, -1);
        $datag6 = substr($datag6, 0, -1);
    }

    //Grafico 7 Contagem Estado / Status 
    $sql1 = "SELECT op.uf, count(op.id) as total, count(if(op.status = 'A', op.status, NULL)) AS andamento, count(if(op.status = 'V', op.status, NULL)) AS vendido, count(if(op.status = 'C', op.status, NULL)) AS cancelado FROM `md_crm_oportunidade` AS op
    WHERE 1 
    AND op.status != 'I'
    $pesquisa_por_data_op_data_inicio
    GROUP BY op.uf
    ORDER BY total DESC
    ";
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        while ($dados = mysql_fetch_array($pesquisa)) {


            $labelg7 .= "'" . $dados["uf"] . "',";
            $datag7_total .= $dados["total"] . ",";
            $datag7_a .= $dados["andamento"] . ",";
            $datag7_v .= $dados["vendido"] . ",";
            $datag7_c .= $dados["cancelado"] . ",";
            $total_g7 += $dados["total"];
        }

        $labelg7 = substr($labelg7, 0, -1);
        $datag7_total = substr($datag7_total, 0, -1);
        $datag7_a = substr($datag7_a, 0, -1);
        $datag7_v = substr($datag7_v, 0, -1);
        $datag7_c = substr($datag7_c, 0, -1);
    }

    //Grafico 10 mes a mes
    $sql1 = "SELECT Date_format(op.data_inicio, '%y') AS ano,
                Month(op.data_inicio)             AS mes,
                cp.descricao						 AS campanha,
                Count(*)                          AS qtde
            FROM   md_crm_oportunidade AS op
            LEFT JOIN md_crm_campanha AS cp ON op.campanha_id = cp.id
            WHERE  1
            AND op.status != 'I'
                $pesquisa_por_data_op_data_inicio
            GROUP  BY ano, mes, campanha
            ORDER  BY ano, mes, qtde DESC  
        ";
    // echo $sql1;
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        $mes_ano = '';
        $mes_ano_camp = '';

        while ($dados = mysql_fetch_array($pesquisa)) {

            $mes_ano[$array_meses[$dados["mes"]] . "/" . $dados["ano"]] += $dados["qtde"];
            $mes_ano_camp[$array_meses[$dados["mes"]] . "/" . $dados["ano"]][$dados["campanha"]] += $dados["qtde"];
        }
        // print("<pre>" . print_r($mes_ano, true) . "</pre>");
        // print("<pre>" . print_r($mes_ano_camp, true) . "</pre><hr>");
        $grafico10_charts = '';
        $i = 1;

        $grafico10_chart_geral .= '
             <div class="col-md-12 border">
                 <canvas id="grafico10-geral" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>';
        foreach ($mes_ano as $key => $value) {

            // echo 'total ' . $key . ' = ' . $value . '<br>';

            $labelg10_geral .= "'" . $key . "',";
            $datag10_geral_qt .= $value . ",";

            $grafico10_charts .= '
            <div class="col-md-4 border">
                <canvas id="grafico10-' . $i . '" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>';

            $labelg10x = '';
            $datag10xqt = '';
            foreach ($mes_ano_camp[$key] as $key2 => $value2) {

                // echo $key . '---' . $key2 . ' - ' . $value2 . '<br>';

                $labelg10x .= "'" . $key2 . "',";
                $datag10xqt .= $value2 . ",";
            } //end foreach 2

            //removendo ultima virgula da string
            $labelg10x = substr($labelg10x, 0, -1);
            $datag10xqt = substr($datag10xqt, 0, -1);



            $grafico10_charts .= "
                <script>
                            $(function() {
                                var barChartData = {
                                    labels: [$labelg10x],
                                    datasets: [{
                                        label: '',
                                        data: [$datag10xqt],
                                        backgroundColor: ['#00a65a', '#00c0ef', '#f39c12', '#d2d6de'],
                                        borderWidth: 2,
                                    }],
                                }
                                var barChartCanvas = $('#grafico10-$i').get(0).getContext('2d')

                                var barChartOptions = {
                                    title: {
                                        display: true,
                                        text: '$key - $value'
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
                                        }
                                    },



                                }

                                var barChart = new Chart(barChartCanvas, {
                                    type: 'bar',
                                    data: barChartData,
                                    options: barChartOptions
                                })



                            })
                        </script>
                ";

            $i++;
            $total_g10 += $value;
            $grafico10_charts .= '</div>';
        } //end foreach 1

        //removendo ultima virgula da string
        $labelg10_geral = substr($labelg10_geral, 0, -1);
        $datag10_geral_qt = substr($datag10_geral_qt, 0, -1);

        $grafico10_chart_geral .= "
                <script>
                            $(function() {
                                var barChartData = {
                                    labels: [$labelg10_geral],
                                    datasets: [{
                                        label: '',
                                        data: [$datag10_geral_qt],
                                        backgroundColor: ['#00a65a', '#00c0ef', '#f39c12', '#d2d6de','#00a65a', '#00c0ef', '#f39c12', '#d2d6de','#00a65a', '#00c0ef', '#f39c12', '#d2d6de'],
                                        borderWidth: 2,
                                    }],
                                }
                                var barChartCanvas = $('#grafico10-geral').get(0).getContext('2d')

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
                                        }
                                    },



                                }

                                var barChart = new Chart(barChartCanvas, {
                                    type: 'bar',
                                    data: barChartData,
                                    options: barChartOptions
                                })



                            })
                        </script>
                ";
        $grafico10_chart_geral .= '</div>';
    } // end grafico 10

    //Grafico 11 oportunidades vendidas mes a mes
    $sql1 = "SELECT Date_format(op.data_inicio, '%y') AS ano,
                Month(op.data_inicio)             AS mes,
                cp.descricao                      AS campanha,
                Count(*)                          AS qtde
            FROM   md_crm_oportunidade AS op
                LEFT JOIN md_crm_campanha AS cp ON op.campanha_id = cp.id
                LEFT JOIN md_crm_eventos AS ev ON ev.oportunidade_id = op.id
            WHERE  1
                $pesquisa_por_data_op_data_inicio
                AND ev.descricao LIKE '%para VENDIDO%'
                AND op.status = 'V'
            GROUP  BY ano,mes, campanha
            ORDER  BY ano, mes, qtde DESC
    ";
    // echo $sql1;
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        $mes_ano = '';
        $mes_ano_camp = '';
        while ($dados = mysql_fetch_array($pesquisa)) {

            $mes_ano[$array_meses[$dados["mes"]] . "/" . $dados["ano"]] += $dados["qtde"];
            $mes_ano_camp[$array_meses[$dados["mes"]] . "/" . $dados["ano"]][$dados["campanha"]] += $dados["qtde"];
        }
        // print("<pre>" . print_r($mes_ano, true) . "</pre>");
        // print("<pre>" . print_r($mes_ano_camp, true) . "</pre><hr>");
        $grafico11_charts = '';
        $i = 1;

        $grafico11_chart_geral .= '
        <div class="col-md-12 border">
        <canvas id="grafico11-geral" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>';
        foreach ($mes_ano as $key => $value) {

            // echo 'total ' . $key . ' = ' . $value . '<br>';

            $labelg11_geral .= "'" . $key . "',";
            $datag11_geral_qt .= $value . ",";

            $grafico11_charts .= '
            <div class="col-md-4 border">
            <canvas id="grafico11-' . $i . '" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>';

            $labelg11x = '';
            $datag11xqt = '';
            foreach ($mes_ano_camp[$key] as $key2 => $value2) {

                // echo $key . '---' . $key2 . ' - ' . $value2 . '<br>';

                $labelg11x .= "'" . $key2 . "',";
                $datag11xqt .= $value2 . ",";
            } //end foreach 2

            //removendo ultima virgula da string
            $labelg11x = substr($labelg11x, 0, -1);
            $datag11xqt = substr($datag11xqt, 0, -1);



            $grafico11_charts .= "
            <script>
            $(function() {
                var barChartData = {
                    labels: [$labelg11x],
                    datasets: [{
                        label: '',
                        data: [$datag11xqt],
                        backgroundColor: ['#00a65a', '#00c0ef', '#f39c12', '#d2d6de'],
                        borderWidth: 2,
                    }],
                }
                var barChartCanvas = $('#grafico11-$i').get(0).getContext('2d')

                var barChartOptions = {
                    title: {
                        display: true,
                        text: '$key - $value'
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
                        }
                    },



                }

                var barChart = new Chart(barChartCanvas, {
                    type: 'bar',
                    data: barChartData,
                    options: barChartOptions
                })



            })
        </script>
        ";

            $i++;
            $total_g11 += $value;
            $grafico11_charts .= '</div>';
        } //end foreach 1

        //removendo ultima virgula da string
        $labelg11_geral = substr($labelg11_geral, 0, -1);
        $datag11_geral_qt = substr($datag11_geral_qt, 0, -1);

        $grafico11_chart_geral .= "
        <script>
            $(function() {
                var barChartData = {
                    labels: [$labelg11_geral],
                    datasets: [{
                        label: '',
                        data: [$datag11_geral_qt],
                        backgroundColor: ['#00a65a', '#00c0ef', '#f39c12', '#d2d6de','#00a65a', '#00c0ef', '#f39c12', '#d2d6de','#00a65a', '#00c0ef', '#f39c12', '#d2d6de'],
                        borderWidth: 2,
                    }],
                }
                var barChartCanvas = $('#grafico11-geral').get(0).getContext('2d')

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
                        }
                    },



                }

                var barChart = new Chart(barChartCanvas, {
                    type: 'bar',
                    data: barChartData,
                    options: barChartOptions
                })



            })
        </script>
        ";
        $grafico11_chart_geral .= '</div>';
    } // end grafico 11

    //Grafico 12 oportunidades perdidas mes a mes
    $sql1 = "SELECT Date_format(op.data_inicio, '%y') AS ano,
    Month(op.data_inicio)             AS mes,
    cp.descricao                      AS campanha,
    Count(*)                          AS qtde
    FROM   md_crm_oportunidade AS op
    LEFT JOIN md_crm_campanha AS cp ON op.campanha_id = cp.id
    LEFT JOIN md_crm_eventos AS ev ON ev.oportunidade_id = op.id
    WHERE  1
    $pesquisa_por_data_op_data_inicio
    AND ev.descricao LIKE '%para PERDIDO%'
    AND op.status = 'C'
    GROUP  BY ano,mes, campanha
    ORDER  BY ano, mes, qtde DESC
    ";
    // echo $sql1;
    $pesquisa = mysql_query($sql1);
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {

        $mes_ano = '';
        $mes_ano_camp = '';
        while ($dados = mysql_fetch_array($pesquisa)) {

            $mes_ano[$array_meses[$dados["mes"]] . "/" . $dados["ano"]] += $dados["qtde"];
            $mes_ano_camp[$array_meses[$dados["mes"]] . "/" . $dados["ano"]][$dados["campanha"]] += $dados["qtde"];
        }
        // print("<pre>" . print_r($mes_ano, true) . "</pre>");
        // print("<pre>" . print_r($mes_ano_camp, true) . "</pre><hr>");
        $grafico12_charts = '';
        $i = 1;

        $grafico12_chart_geral .= '
        <div class="col-md-12 border">
        <canvas id="grafico12-geral" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>';
        foreach ($mes_ano as $key => $value) {

            // echo 'total ' . $key . ' = ' . $value . '<br>';

            $labelg12_geral .= "'" . $key . "',";
            $datag12_geral_qt .= $value . ",";

            $grafico12_charts .= '
            <div class="col-md-4 border">
            <canvas id="grafico12-' . $i . '" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>';

            $labelg12x = '';
            $datag12xqt = '';
            foreach ($mes_ano_camp[$key] as $key2 => $value2) {

                // echo $key . '---' . $key2 . ' - ' . $value2 . '<br>';

                $labelg12x .= "'" . $key2 . "',";
                $datag12xqt .= $value2 . ",";
            } //end foreach 2

            //removendo ultima virgula da string
            $labelg12x = substr($labelg12x, 0, -1);
            $datag12xqt = substr($datag12xqt, 0, -1);



            $grafico12_charts .= "
                <script>
                $(function() {
                var barChartData = {
                    labels: [$labelg12x],
                    datasets: [{
                        label: '',
                        data: [$datag12xqt],
                        backgroundColor: ['#00a65a', '#00c0ef', '#f39c12', '#d2d6de'],
                        borderWidth: 2,
                    }],
                }
                var barChartCanvas = $('#grafico12-$i').get(0).getContext('2d')

                var barChartOptions = {
                    title: {
                        display: true,
                        text: '$key - $value'
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
                        }
                    },



                }

                var barChart = new Chart(barChartCanvas, {
                    type: 'bar',
                    data: barChartData,
                    options: barChartOptions
                })



                })
                </script>
                ";

            $i++;
            $total_g12 += $value;
            $grafico12_charts .= '</div>';
        } //end foreach 1

        //removendo ultima virgula da string
        $labelg12_geral = substr($labelg12_geral, 0, -1);
        $datag12_geral_qt = substr($datag12_geral_qt, 0, -1);

        $grafico12_chart_geral .= "
            <script>
            $(function() {
            var barChartData = {
                labels: [$labelg12_geral],
                datasets: [{
                    label: '',
                    data: [$datag12_geral_qt],
                    backgroundColor: ['#00a65a', '#00c0ef', '#f39c12', '#d2d6de','#00a65a', '#00c0ef', '#f39c12', '#d2d6de','#00a65a', '#00c0ef', '#f39c12', '#d2d6de'],
                    borderWidth: 2,
                }],
            }
            var barChartCanvas = $('#grafico12-geral').get(0).getContext('2d')

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
                    }
                },



            }

            var barChart = new Chart(barChartCanvas, {
                type: 'bar',
                data: barChartData,
                options: barChartOptions
            })



            })
            </script>
            ";
        $grafico12_chart_geral .= '</div>';
    } // end grafico 12



} // fim do POST



// echo $resultado_rel;

?>
<!-- chartJS -->
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/chart.js/Chart.min.js" class=""></script>
<script src="plugins/chart.js/plugins/chartjs-plugin-labels.min.js" class=""></script>


<!-- CARDS 1 -->
<div class="row pl-2 pr-2">
    <div class="col-12 col-sm">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-eye"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Contato Inicial (<?= $oport_etapa_array_qtde['1'] ?>)</span>
                <span class="info-box-number">
                    R$ <?= number_format($oport_etapa_array_val['1'],    2, ',', '.') ?>
                </span>
            </div>

        </div>

    </div>



    <div class="clearfix hidden-md-up"></div>
    <div class="col-12 col-sm">
        <div class="info-box mb-3">
            <span class="info-box-icon elevation-1" style="background-color:#00c0ef!important;"><i class="fas fa-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Orçamento (<?= $oport_etapa_array_qtde['2'] ?>)</span>
                <span class="info-box-number">R$ <?= number_format($oport_etapa_array_val['2'],    2, ',', '.') ?></span>
            </div>

        </div>

    </div>

    <div class="col-12 col-sm">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-handshake"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Negociação (<?= $oport_etapa_array_qtde['3'] ?>)</span>
                <span class="info-box-number">R$ <?= number_format($oport_etapa_array_val['3'],    2, ',', '.') ?></span>
            </div>

        </div>

    </div>



</div>
<div class="row pl-2 pr-2">
    <div class="col-12 col-sm">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-pause"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pausado (<?= $oport_array_qtde['P'] ?>)</span>
                <span class="info-box-number">R$ <?= number_format($oport_array_val['P'],    2, ',', '.') ?></span>
            </div>

        </div>

    </div>

    <div class="col-12 col-sm">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-success elevation-1 text-white"><i class="fas fa-thumbs-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Vendido (<?= $oport_array_qtde['V'] ?>)</span>
                <span class="info-box-number">R$ <?= number_format($oport_array_val['V'],    2, ',', '.') ?></span>
            </div>

        </div>

    </div>

    <div class="col-12 col-sm">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-thumbs-down"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Perdido (<?= $oport_array_qtde['C'] ?>)</span>
                <span class="info-box-number">R$ <?= number_format($oport_array_val['C'],    2, ',', '.') ?></span>
            </div>

        </div>

    </div>

</div>

<!-- GRAFICO 10 MES A MES -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-eye"></i> Oportunidades Criadas por mês - Total = <?= $total_g10 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">

                    <?= $grafico10_chart_geral ?>
                </div>
                <div class="row">
                    <div class="col-md-12 mt-4 mb-2">
                        <h5>Detalhado Mês a Mês</h5>
                    </div>

                    <?= $grafico10_charts ?>

                </div>
            </div>
        </div><!-- /.card -->
    </div>

</div>

<!-- GRAFICO 11 OPORTUNIDADES CONVETIDO MES A MES -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-success collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-thumbs-up"></i> Oportunidades Vendidas por mês - Total = <?= $total_g11 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">

                    <?= $grafico11_chart_geral ?>
                </div>
                <div class="row">
                    <div class="col-md-12 mt-4 mb-2">
                        <h5>Detalhado Mês a Mês</h5>
                    </div>

                    <?= $grafico11_charts ?>

                </div>
            </div>
        </div><!-- /.card -->
    </div>

</div>

<!-- GRAFICO 12 OPORTUNIDADES PERDIDAS MES A MES -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-danger collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-thumbs-down"></i> Oportunidades Perdidas por mês - Total = <?= $total_g12 -1 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">

                    <?= $grafico12_chart_geral ?>
                </div>
                <div class="row">
                    <div class="col-md-12 mt-4 mb-2">
                        <h5>Detalhado Mês a Mês</h5>
                    </div>

                    <?= $grafico12_charts ?>

                </div>
            </div>
        </div><!-- /.card -->
    </div>

</div>

<!-- GRAFICO 1 -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Oportunidade por Status - Qtde = <?= $total_g1 ?>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp; Valor R$ <?= number_format($total_g1_valor,    2, ',', '.') ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="grafico1" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div><!-- /.card -->
    </div><!-- /.col -->
</div><!-- /.row -->
<script>
    $(function() {
        var oilCanvas = document.getElementById("grafico1");

        var data = {
            labels: [<?= $labelg1 ?>],
            datasets: [{
                data: [<?= $datag1   ?>],
                backgroundColor: ["#00c0ef", "#f56954", "#e5e5e5", "#00a65a", ]
            }]
        };

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                labels: {
                    render: 'percentage',
                    fontColor: ['white', 'white', 'white'],
                    precision: 2
                }
            },
        }

        var pieChart = new Chart(oilCanvas, {
            type: 'pie',
            data: data,
            options: options
        });



    })
</script>

<!-- GRAFICO 6 -->
<div class="row pl-2 pr-2">
    <div class="col-md-6" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Motivo de Perda - Qtde = <?= $total_g6 ?>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp; Valor R$ <?= number_format($total_g6_valor,    2, ',', '.') ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <!-- <canvas id="grafico6" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas> -->
                <table class="table table-sm table-striped table-valign-middle">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th class="text-center">Qtde</th>
                            <th class="text-center">Valor</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $table_motivoperda_itens ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /.card -->
    </div>

    <!-- <script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg6 ?>],
            datasets: [{
                label: "",
                data: [<?= $datag6 ?>],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
                borderWidth: 2,
            }],
        }
        var barChartCanvas = $('#grafico6').get(0).getContext('2d')

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
</script>
 -->

    <!-- GRAFICO 6B -->
    <div class="col-md-6" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Perda por etapa - Qtde = <?= $total_g6 ?>&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp; Valor R$ <?= number_format($total_g6b_valor,    2, ',', '.') ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <!-- <canvas id="grafico6" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas> -->
                <table class="table table-sm table-striped table-valign-middle">
                    <thead>
                        <tr>
                            <th>Etapa</th>
                            <th class="text-center">Qtde</th>
                            <th class="text-center">Valor</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $table_perda_etapa_itens ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->
<!-- <script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg6 ?>],
            datasets: [{
                label: "",
                data: [<?= $datag6 ?>],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
                borderWidth: 2,
            }],
        }
        var barChartCanvas = $('#grafico6').get(0).getContext('2d')

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
</script>
 -->



<!-- GRAFICO 2 -->
<div class="row pl-2 pr-2">
    <div class="col-md-6" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Contagem por Fonte / status - Total = <?= $total_g2 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="grafico2" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div><!-- /.card -->
    </div>
    <script>
        $(function() {
            var barChartData = {
                labels: [<?= $labelg2 ?>],
                datasets: [{
                        label: "Total",
                        data: [<?= $datag2_total ?>],
                        backgroundColor: '#3c8dbc',
                        borderWidth: 2,
                    },
                    {
                        label: "Em Andamento",
                        data: [<?= $datag2_a ?>],
                        backgroundColor: '#00a65a',
                        borderWidth: 2,
                    },
                    {
                        label: "Vendidos",
                        data: [<?= $datag2_v ?>],
                        backgroundColor: '#00c0ef',
                        borderWidth: 2,
                    },
                    {
                        label: "Perdidos",
                        data: [<?= $datag2_c ?>],
                        backgroundColor: '#f56954',
                        borderWidth: 2,
                    }
                ],
            }

            var barChartCanvas = $('#grafico2').get(0).getContext('2d')

            var barChartOptions = {
                legend: {
                    display: true,
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
    </script>


    <!-- GRAFICO 3 -->
    <div class="col-md-6" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Contagem por Campanha - Total = <?= $total_g3 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="grafico3" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->
<script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg3 ?>],
            datasets: [{
                label: "",
                data: [<?= $datag3 ?>],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
                borderWidth: 2,
            }],
        }
        var barChartCanvas = $('#grafico3').get(0).getContext('2d')

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
</script>

<!-- GRAFICO 5 -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Contagem por Campanha / Status - Total = <?= $total_g5 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="grafico5" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->
<script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg5 ?>],
            datasets: [{
                    label: "Acessos",
                    data: [<?= $datag5_acessos ?>],
                    backgroundColor: '#3c8dbc',
                    borderWidth: 2,
                },
                {
                    label: "Total",
                    data: [<?= $datag5_total ?>],
                    backgroundColor: '#3c8dbc',
                    borderWidth: 2,
                },
                {
                    label: "Em Andamento",
                    data: [<?= $datag5_a ?>],
                    backgroundColor: '#00a65a',
                    borderWidth: 2,
                },
                {
                    label: "Vendidos",
                    data: [<?= $datag5_v ?>],
                    backgroundColor: '#00c0ef',
                    borderWidth: 2,
                },
                {
                    label: "Perdidos",
                    data: [<?= $datag5_c ?>],
                    backgroundColor: '#f56954',
                    borderWidth: 2,
                }
            ],
        }

        var barChartCanvas = $('#grafico5').get(0).getContext('2d')

        var barChartOptions = {
            legend: {
                display: true,
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


        var barChart_cpd = new Chart(barChartCanvas, {
            type: 'bar',
            data: barChartData,
            options: barChartOptions
        })

        var hiddenLabel = 'Acessos';
        var timerDuration = 1;
        var timer = setInterval(function() {
            timerDuration--;
            $('#timer').text(timerDuration + ' seconds');
        }, 1000);
        setTimeout(function() {
            var indexToHide = -1;

            // find which dataset matches the label we want to hide
            barChart_cpd.config.data.datasets.forEach(function(e, i) {
                if (e.label === hiddenLabel) {
                    indexToHide = i;
                }
            });

            // get the dataset meta object so we can hide it
            var meta = barChart_cpd.getDatasetMeta(indexToHide);

            // hide the dataset and re-render the chart
            meta.hidden = true;
            barChart_cpd.update();
        })



    })
</script>

<!-- GRAFICO 8 -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Acessos vs Oportunidade</h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-striped table-valign-middle">
                    <thead>
                        <tr>
                            <th>Campanha</th>
                            <th>Acessos</th>
                            <th class="text-center">Oport.</th>
                            <th class="text-center">Oport. %</th>
                            <th class="text-center">Em andamento</th>
                            <th class="text-center">Vendidos</th>
                            <th class="text-center">Vendidos %</th>
                            <th class="text-center">Perdidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?= $table_acessoxsite_itens ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->

<!-- GRAFICO 9 -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Acessos Campanhas / Dia</h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="grafico9" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->
<script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg9 ?>],
            datasets: [{
                    label: 'Todos',
                    data: [<?= $datag9 ?>],
                    fill: false,
                    borderColor: '#000000',
                    tension: 0.1
                },
                {
                    label: '<?= $array_opt_campanha[2] ?>',
                    data: [<?= $datag9c2 ?>],
                    fill: false,
                    borderColor: '#ffc107',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[3] ?>',
                    data: [<?= $datag9c3 ?>],
                    fill: false,
                    borderColor: '#00c0ef',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[4] ?>',
                    data: [<?= $datag9c4 ?>],
                    fill: false,
                    borderColor: '#f56954',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[5] ?>',
                    data: [<?= $datag9c5 ?>],
                    fill: false,
                    borderColor: '#00a65a',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[6] ?>',
                    data: [<?= $datag9c6 ?>],
                    fill: false,
                    borderColor: '#e83e8c',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[7] ?>',
                    data: [<?= $datag9c7 ?>],
                    fill: false,
                    borderColor: '#01ff70',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[8] ?>',
                    data: [<?= $datag9c8 ?>],
                    fill: false,
                    borderColor: '#6f42c1',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[9] ?>',
                    data: [<?= $datag9c9 ?>],
                    fill: false,
                    borderColor: '#6f42c1',
                    tension: 0.1,
                    hidden: true
                },
                {
                    label: '<?= $array_opt_campanha[10] ?>',
                    data: [<?= $datag9c10 ?>],
                    fill: false,
                    borderColor: '#6f42c1',
                    tension: 0.1,
                    hidden: true
                },
            ]
        }
        var barChartCanvas = $('#grafico9').get(0).getContext('2d')

        var barChartOptions = {
            title: {
                display: true,
                text: ''
            },
            legend: {
                display: true,
            },
            maintainAspectRatio: false,
            responsive: true,

            plugins: {
                labels: [{
                        render: 'label',
                        position: 'outside'
                    },
                    {
                        render: 'value'
                    }
                ]
            },



        }

        var barChart_cpd = new Chart(barChartCanvas, {
            type: 'line',
            data: barChartData,
            options: barChartOptions
        })


    })
</script>

<!-- GRAFICO 7 -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Contagem de Estado / Status - Total = <?= $total_g7 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="grafico7" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->
<script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg7 ?>],
            datasets: [{
                    label: "Total",
                    data: [<?= $datag7_total ?>],
                    backgroundColor: '#3c8dbc',
                    borderWidth: 2,
                },
                {
                    label: "Em Andamento",
                    data: [<?= $datag7_a ?>],
                    backgroundColor: '#00a65a',
                    borderWidth: 2,
                },
                {
                    label: "Vendidos",
                    data: [<?= $datag7_v ?>],
                    backgroundColor: '#00c0ef',
                    borderWidth: 2,
                },
                {
                    label: "Perdidos",
                    data: [<?= $datag7_c ?>],
                    backgroundColor: '#f56954',
                    borderWidth: 2,
                }
            ],
        }

        var barChartCanvas = $('#grafico7').get(0).getContext('2d')

        var barChartOptions = {
            legend: {
                display: true,
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
</script>

<!-- GRAFICO 4 -->
<div class="row pl-2 pr-2">
    <div class="col-md-12" style="float: left;">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Contagem Vendedor / Status - Total = <?= $total_g4 ?></h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <!-- 
            <canvas id="grafico4" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 50%;float:left"></canvas> -->
                <?php
                // print("<pre>" . print_r($labelg4_pie, true) . "</pre>");
                foreach ($labelg4_pie as $key => $value) {
                    echo '<canvas id="grafico4' . $key . '" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 50%;float:left"></canvas>';
                ?>
                    <script>
                        $(function() {
                            var oilCanvas = document.getElementById("<?= 'grafico4' . $key ?>");

                            var data = {
                                labels: ['andamento(<?= $value['andamento'] ?>)', 'vendido(<?= $value['vendido'] ?>)', 'pausado(<?= $value['pausado'] ?>)', 'perdido(<?= $value['cancelado'] ?>)'],
                                datasets: [{
                                    data: [<?= $value['andamento'] ?>, <?= $value['vendido'] ?>, <?= $value['pausado'] ?>, <?= $value['cancelado'] ?>],
                                    backgroundColor: ["#00c0ef", "#00a65a", "#e5e5e5", "#f56954", ]
                                }]
                            };

                            var options = {
                                title: {
                                    display: true,
                                    text: '<?= $value['usuario'] ?> (<?= $value['total'] ?>)'
                                },
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    labels: {
                                        render: 'percentage',
                                        fontColor: ['white', 'white', 'white'],
                                        precision: 2
                                    }
                                },
                            }

                            var pieChart = new Chart(oilCanvas, {
                                type: 'pie',
                                data: data,
                                options: options
                            });



                        })
                    </script>
                <?php
                }
                ?>
            </div>
        </div><!-- /.card -->
    </div>
</div><!-- /.row -->
<script>
    $(function() {
        var barChartData = {
            labels: [<?= $labelg4 ?>],
            datasets: [{
                    label: "Total",
                    data: [<?= $datag4_total ?>],
                    backgroundColor: '#3c8dbc',
                    borderWidth: 2,
                },
                {
                    label: "Em Andamento",
                    data: [<?= $datag4_a ?>],
                    backgroundColor: '#00a65a',
                    borderWidth: 2,
                },
                {
                    label: "Vendidos",
                    data: [<?= $datag4_v ?>],
                    backgroundColor: '#00c0ef',
                    borderWidth: 2,
                },
                {
                    label: "Perdidos",
                    data: [<?= $datag4_c ?>],
                    backgroundColor: '#f56954',
                    borderWidth: 2,
                }
            ],
        }

        var barChartCanvas = $('#grafico4').get(0).getContext('2d')

        var barChartOptions = {
            legend: {
                display: true,
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
</script>

<!-- GRAFICO -->
<div class="col-md-6 d-none" style="float: left;">
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">modelo Barras com multi datasets</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="bar-canvas3" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
        </div>
    </div><!-- /.card -->
</div>
<script>
    $(function() {
        new Chart(document.getElementById('bar-canvas3'), {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March'],
                datasets: [{
                        label: 'My First dataset',
                        data: [50445, 33655, 15900],
                        backgroundColor: [
                            '#FF6384',
                            '#FF6384',
                            '#FF6384'
                        ]
                    },
                    {
                        label: 'My Second dataset',
                        data: [40445, 23655, 35900],
                        backgroundColor: [
                            '#36A2EB',
                            '#36A2EB',
                            '#36A2EB'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    labels: {
                        render: 'value'
                    }
                },
                scales: {
                    xAxes: [{
                        stacked: true,
                    }],
                    yAxes: [{
                        stacked: true
                    }]
                }
            }
        });



    })
</script>





<!-- CARD BAR OPORTUNIDADE POR STATUS  -->
<div class="col-md-6 d-none" style="float: left;">
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">modelo</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="barChartperc" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
        </div>
    </div><!-- /.card -->
</div>
<script>
    $(function() {
        //- BAR CHART -
        //-------------
        var barChartData = {
            labels: ["HTML", "CSS", "JAVASCRIPT", "CHART.JS", "JQUERY", "BOOTSTRP"],
            datasets: [{
                label: "",
                data: [30, 50, 30, 100, 30, 90],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
                borderWidth: 2,
            }],
        }
        var barChartCanvas = $('#barChartperc').get(0).getContext('2d')

        var barChartOptions = {
            scales: {
                yAxes: [{
                    ticks: {
                        max: 100,
                        min: 0,
                        stepSize: 20
                    }
                }]
            },
            legend: {
                display: false
            },
            responsive: true,
            maintainAspectRatio: false,
            datasetFill: false,

            events: false,
            tooltips: {
                enabled: true
            },

            // animation: {
            //     duration: 500,
            //     easing: 'easeOutQuart',
            //     onComplete: function() {
            //         var ctx = this.chart.ctx;
            //         ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontFamily, 'normal', Chart.defaults.global.defaultFontFamily);
            //         ctx.textAlign = 'center';
            //         ctx.textBaseline = 'bottom';

            //         this.data.datasets.forEach(function(dataset) {
            //             for (var i = 0; i < dataset.data.length; i++) {
            //                 var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model,
            //                     scale_max = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._yScale.maxHeight;
            //                 ctx.fillStyle = '#444';
            //                 var y_pos = model.y - 5;
            //                 // Make sure data value does not get overflown and hidden
            //                 // when the bar's value is too close to max value of scale
            //                 // Note: The y value is reverse, it counts from top down
            //                 if ((scale_max - model.y) / scale_max >= 0.93)
            //                     y_pos = model.y + 20;
            //                 ctx.fillText(dataset.data[i] + '%', model.x, y_pos);
            //             }
            //         });
            //     }
            // }

        }

        var barChart = new Chart(barChartCanvas, {
            type: 'bar',
            data: barChartData,
            options: barChartOptions
        })



    })
</script>

<!-- CARD BAR OPORTUNIDADE POR STATUS  -->
<div class="col-md-6 d-none" style="float: left;">
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">modelo</h3>

            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <div class="card-body">
            <canvas id="barChart2" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
        </div>
    </div><!-- /.card -->
</div>
<script>
    $(function() {
        //- BAR CHART -
        //-------------
        var barChartData = {
            labels: ["HTML", "CSS", "JAVASCRIPT", "CHART.JS", "JQUERY", "BOOTSTRP"],
            datasets: [{
                label: "online tutorial subjects",
                data: [30, 40, 30, 35, 30, 20],
                backgroundColor: ['#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de'],
                borderWidth: 2,
            }],
        }
        var barChartCanvas = $('#barChart2').get(0).getContext('2d')

        var barChartOptions = {
            maintainAspectRatio: false,
            responsive: true,

        }

        var barChart = new Chart(barChartCanvas, {
            type: 'horizontalBar',
            data: barChartData,
            options: barChartOptions
        })



    })
</script>

