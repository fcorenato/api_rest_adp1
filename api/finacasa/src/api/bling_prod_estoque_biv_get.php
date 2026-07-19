<?php
// require('../config/SUsuario.php');
require('../config/conexao.php');

//gerando relatório
$query1 = "select * from md_estoque_bling where saldo_disp > 0";

$result_query1 = mysql_query($query1);
$qtde_query1 = mysql_num_rows($result_query1);
echo "qtde_query1: " . $qtde_query1 . "<br>";

if ($qtde_query1 != 0) {
    while ($campos = mysql_fetch_array($result_query1)) {
        $estoquedisp[] = array(
            'ref' => $campos['referencia'],
            'ref_desc' => $campos['descricao'],
            'ref_um' => $campos['un'],
            'saldo_disp' =>  $campos['saldo'],
            'saldo_disp_atu' =>  $campos['saldo'],
            'deposito' => $campos['deposito']
        );
    }
} else {
    $estoquedisp[] = array(
        'ref' => 0,
        'ref_desc' => 0,
        'ref_um' => 0,
        'saldo_disp' =>  0,
        'saldo_disp_atu' => 0,
        'deposito' => 0
    );
}

print("<pre>" . print_r($estoquedisp, true) . "</pre>");

?>