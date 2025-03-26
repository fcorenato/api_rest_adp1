<?php 
//chamdada api bling para listar todos os produtos cadastrados
include_once('../../src/api/bling_produtos_get.php');
//gerado array: produtos_array


//totalizando estoque por item:
include_once('../../src/api/bling_prod_estoque_get.php');
foreach ($estoquedisp as $key_op => $value_op) {
    $est_array_qtde_it[$value_op['ref']] += $value_op['saldo_disp'];
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

//percorrando o array com todos os produtos e calculando estoque 
//estoque + pc + op - PV
//obs: estoque negativo =  pedidos de vendas que ainda nao foram atendidos.

require('../config/conexao.php');
$limpa_tabela = mysql_query("TRUNCATE md_estoque_disponivel;") or die(mysql_error());
$update_at = date("Y-m-d H:i:s");
$qtde_prods = 0;
foreach ($produtos_array as $key_prod => $value_prod) {
    $ref_it = $value_prod['ref'];
    $saldo_disp_item_array[$ref_it] = $est_array_qtde_it[$ref_it] + $op_array_qtde_it[$ref_it] + $pc_array_qtde_it[$ref_it] - $pv_array_qtde_it[$ref_it];

    

    $result = mysql_query("INSERT INTO md_estoque_disponivel (referencia, estoque, op, ped_compra, ped_venda, saldo_disp, update_at ) VALUES ('$ref_it', '$est_array_qtde_it[$ref_it]', '$op_array_qtde_it[$ref_it]', '$pc_array_qtde_it[$ref_it]', '$pv_array_qtde_it[$ref_it]', ' $saldo_disp_item_array[$ref_it]','$update_at')") or die(mysql_error());

    if ($result) {
        echo 'Produto atualizado: '.$ref_it.' Saldo Disp = '.$saldo_disp_item_array[$ref_it].'  - Atualizado '.$update_at.' <=====> ';
    }

    $qtde_prods++;
}
echo ' =============================================================================================================================================================== \r\n total registros atualizados = '.$qtde_prods.'  - Atualizado '.$update_at.' ';
//echo 'Qtde produtos = '.$$qtde_prods.'<hr>';
//print("<pre>" . print_r($saldo_disp_item_array, true) . "</pre>");

?>