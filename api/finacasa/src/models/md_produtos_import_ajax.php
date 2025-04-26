<?php
date_default_timezone_set('America/Sao_Paulo');
//importando produtos do bling
//chamdada api produtos cadatu
include_once('../../src/api/bling_produtos_cadatu_get.php');
$qtde_prod_import = 0;
$qtde_prods = count($produtos_array);
if ($qtde_prods == 0) {
    $resultado_pesq_cli .= '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado. </td></tr></table>';
} else {
    //acesando banco de dados
    require('../config/conexao.php');
    $limpa_tabela_entregas = mysql_query("TRUNCATE md_cad_produtos;") or die(mysql_error());
    foreach ($produtos_array as $key_prod => $value_prod) {

        $referencia = $value_prod["ref"];
        $descricao = $value_prod["ref_desc"];
        $unidade = $value_prod["ref_um"];
        $preco = $value_prod["preco"];
        $qtde_cx = $value_prod["qtde_cx"];
        $fraciona_cx = 'S';
        $dias_prod = 0;
        $marca = $value_prod["marca"];
        $tipo = 'ME';
        $ipi = 0;
        $peso = $value_prod["pesoBruto"];
        $status = 'A';
        $updated_at = date("Y-m-d H:i:s");

        $result = mysql_query("INSERT INTO `md_cad_produtos`(`referencia`, `descricao`, `unidade`, `preco`, `qtde_cx`, `fraciona_cx`, `dias_prod`, `marca`, `tipo`, `ipi`, `peso`, `status`, `updated_at`) VALUES ('$referencia', '$descricao', '$unidade', '$preco', '$qtde_cx', '$fraciona_cx', '$dias_prod', '$marca', '$tipo', '$ipi', '$peso', '$status', '$updated_at');") or die(mysql_error());

        $qtde_prod_import++;
       
    } // fim do foreach
    $result = $qtde_prod_import;
    echo $result;
}
