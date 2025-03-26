<?php
date_default_timezone_set('America/Sao_Paulo');
//require('../config/conexaosql.php');

//recebendo POST 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //PARAMETROS
    $imprimi_item = TRUE;


    //////////////////////////////// RELATORIO ESTOQUE DISP
    if (isset($_POST['rel_post'])) {
        $rel_post = strtoupper($_POST['rel_post']);
        if ($rel_post == 'REL_ESTOQUE') {
            $subtotal_por_pedido_ultimo = FALSE;
            $imprimi_item_carteira = 0;
            $imprimi_consulta_estoque_apos_atendimento = 1;
        }
    }

    if (isset($_POST['ref'])) {
        $ref = strtoupper($_POST['ref']);
        if ($ref != '') {
            $pesquisa_por_ref = $ref;
        }
    }

    if (isset($_POST['fabricante'])) {
        $fabricante = strtoupper($_POST['fabricante']);
        if ($fabricante != 'ALL') {
            $pesquisa_por_fabricante = " AND B1_FABRIC = '$fabricante' ";
        }
    }

    if (isset($_POST['saldo'])) {
        $saldo_pesq = strtoupper($_POST['saldo']);

        if ($saldo_pesq != 'ALL') {
            $pesquisa_por_saldo = $saldo_pesq;
        }
    }

    if (isset($_POST['grupo'])) {
        $grupo = strtoupper($_POST['grupo']);
        if ($grupo != 'ALL') {
            $pesquisa_por_grupo = " AND B1_GRUPO = '$grupo' ";
        }
    }

    if (isset($_POST['hist_venda'])) {
        $retro = 6;
        $periodo = strtoupper($_POST['periodo']);
        // Se periodo fechado nao considerar o mes atual
        if ($periodo == 'F') {
            if ($retro != '') {
                $data_inicio = date('Ym', strtotime('-' . $retro . ' months')) . '01'; // 1 dia do mes
                $data_final = date('Ymt', strtotime('-1 months'));  // ultimo dia do mes
            }

            //definindo nome colunas qtde meses selecionado
            for ($i = 1; $i <= $retro + 1; $i++) {
                $colunames[] = date('n/Y', strtotime('-' . $i . ' months'));
            }
        }

        // Se periodo aberto  considerar o mes atual
        if ($periodo == 'A') {
            if ($retro != '') {
                $data_inicio = date('Ym', strtotime('-' . $retro + 1 . ' months')) . '01'; // 1 dia do mes
                $data_final = date('Ymd');  // ultimo dia do mes

                //definindo nome colunas qtde meses selecionado
                for ($i = 0; $i <= $retro + 1; $i++) {
                    $colunames[] = date('n/Y', strtotime('-' . $i . ' months'));
                }
            }
        }
    }

    if (isset($_POST['status'])) {
        $status = strtoupper($_POST['status']);
        if ($status != 'ALL') {
            if ($status == 'A') {
                $pesquisa_por_status_prod = " AND B1_MSBLQL = '2' ";
            } else {
                $pesquisa_por_status_prod = " AND B1_MSBLQL = '1' ";
            }
        }
    }
} // fim do POST

//chamdada api bling para listar todos os produtos cadastrados
include_once('../../src/api/bling_produtos_get.php');
//gerado array: produtos_array


//totalizando estoque por item:
include_once('../../src/api/bling_prod_estoque_get.php');
foreach ($estoquedisp as $key_op => $value_op) {
    $est_array_qtde_it[$value_op['ref']] += $value_op['saldo_disp'];
    $est_array_deposito[$value_op['ref']][$value_op['deposito']] += $value_op['saldo_disp'];
}

//totalizando ops por item:
include_once('../../src/api/bling_op_get.php');
foreach ($op_array as $key_op => $value_op) {
    $op_array_qtde_it[$value_op['op_ref']] += $value_op['op_qtde'];
}

//totalizando Pedido de comrpas por item:

include_once('../../src/api/bling_pedido_compras_get.php');
foreach ($pc_array as $key_pc => $value_pc) {
    $pc_array_qtde_it[$value_pc['pc_ref']] += $value_pc['pc_qtde'];
}


//totalizando Pedido de vendas por item:
include_once('../../src/api/bling_pedido_vendas_get.php');
foreach ($pedido_vendas_array as $key_pv => $value_pv) {
    $pv_array_qtde_it[$value_pv['item_ref']] += $value_pv['item_qtde'];
}

require('../config/conexao.php');
//historico de vendas
$query1 = "SELECT YEAR(p.pedido_conv_date) as ano, MONTH(p.pedido_conv_date) AS mes,i.codigo, SUM(i.qtde) as qtd  FROM `md_vendas_pedidos` as p 
LEFT JOIN md_vendas_pedidos_itens as i ON i.pedido_id = p.id
WHERE p.status = 'P'
AND i.status = 'A'
AND cast(p.pedido_conv_date as date) between '$data_inicio' and '$data_final'
GROUP BY ano, MES, i.codigo
    ";


//gerando relatório
$retro = 6; // $_POST['hist_venda'];
$result_query1 = mysql_query($query1);
$qtde_query1 = mysql_num_rows($result_query1);
// echo 'registro historico ='. $qtde_query1 .'<hr>';

if ($qtde_query1 > 0) {
    while ($campos = mysql_fetch_array($result_query1)) {

        $prod_mes_ano = trim($campos['codigo']) . '-' . $campos['mes'] . '/' . $campos['ano'];
        // =======  carrega o array com os dados de venda por produto ============
        //$venda_prod_array_mesano[$prod_mes_ano] = round($campos['QTDV'], 2);
        $venda_prod_mesames_array[$prod_mes_ano] += round($campos['qtd'], 2);

        $venda_prod_array[trim($campos['codigo'])] += round($campos['qtd'], 2);
    }
}

// var_dump($venda_prod_array);



$relatorio_result .= '<table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed tabela_carteira">';
$relatorio_result .= '
        <thead>
        <tr>
            <th>REFs</th>
            <th>DESCRIÇÃO</th>
            <th>FABRICANTE</th>
            <th>UM</th>
            <th class="dados-extras d-none">VM</th>
            <th class="dados-extras d-none">VH</th>
            <th class="dados-extras d-none">VC</th>
            <th class="dados-extras d-none">AG</th>
            <th>ESTQ TOTAL</th>
            <th>OP</th>
            <th>PC</th>
            <th>PV</th>
            <th class="bg-success" data-toggle="tooltip" title="SALDO PROD = ESTQ + PED. COMP. + ORD. PROD. - PED. VENDA">SALDO DISP</th>
            <th>VEND (6m)</th>
            <th>GIRO (6m)</th>
            <th data-toggle="tooltip" title="Prev de duração do estoque em meses">DISP MÊS</th>
       ';
//imprimindo colunas meses
for ($i = 0; $i < $retro; $i++) {
    $idx = ($retro - 1) - $i;
    $relatorio_result .= '<th class="dados-extras d-none">' . $colunames[$idx] . '</th>';
}

$relatorio_result .= '
            </tr>
            </thead>
            <tbody>';

foreach ($produtos_array as $key_prod => $value_prod) {
    //estoque do item

    $ref_it = trim($value_prod['ref']);
    $saldo_disp_item = $est_array_qtde_it[$ref_it] + $op_array_qtde_it[$ref_it] + $pc_array_qtde_it[$ref_it] - $pv_array_qtde_it[$ref_it];

    //aplicando filtros
    if ($pesquisa_por_ref) {
        $imprimi_item = $pesquisa_por_ref == $ref_it ? TRUE : FALSE;
    }
    if ($pesquisa_por_saldo) {
        $imprimi_item = FALSE;
        if ($pesquisa_por_saldo == 'P' and $saldo_disp_item > 0) {
            $imprimi_item = TRUE;
        }
        if ($pesquisa_por_saldo == 'N' and $saldo_disp_item < 0) {
            $imprimi_item = TRUE;
        }
    }

    if ($imprimi_item) {
        $relatorio_result .= '
        <tr class="' . $bg_total . ' tr_result" >
            <td>' . $value_prod['ref'] . '</td>
            <td>' . $value_prod['ref_desc'] . '</td>
            <td>VIVARTE</td>
            <td>' . $value_prod['ref_um'] . '</td>
            <td align="right" class="dados-extras d-none">' . number_format($est_array_deposito[$ref_it]['VM-PA'],    2, ',', '.') . '</td>
            <td align="right" class="dados-extras d-none">' . number_format($est_array_deposito[$ref_it]['VH-PA'],    2, ',', '.') . '</td>
            <td align="right" class="dados-extras d-none">' . number_format($est_array_deposito[$ref_it]['VC-PA'],    2, ',', '.') . '</td>
            <td align="right" class="dados-extras d-none">' . number_format($est_array_deposito[$ref_it]['AG-PA'],    2, ',', '.') . '</td>
            <td align="right">' . number_format($est_array_qtde_it[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($op_array_qtde_it[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($pc_array_qtde_it[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($pv_array_qtde_it[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($saldo_disp_item,    2, ',', '.') . '</td>
            <td align="right">' . number_format($venda_prod_array[$ref_it],    2, ',', '.') . '</td>
            <td align="right">' . number_format($venda_prod_array[$ref_it] / 6,    2, ',', '.') . '</td>
            <td align="right">' . number_format($saldo_disp_item / ($venda_prod_array[$ref_it] / 6),    2, ',', '.') . '</td>
        ';

        //exibindo venda por mes
        for ($i = 0; $i < $retro; $i++) {
            $idx = ($retro - 1) - $i;
            $venda_index ='';
            $venda_index = $ref_it . '-' . $colunames[$idx];
            $relatorio_result .= '<td class="dados-extras d-none" align="right">' . number_format($venda_prod_mesames_array[$venda_index],    2, ',', '.') . '</td>';
        }

        $estoque_pressado .= '                       
                    </tr>';
    }
} //fim do imprimir_consulta_apos_atendimentos

$relatorio_result .= '
    </tbody>
    </table>';
//var_dump($estoquedisp);
echo $relatorio_result;
