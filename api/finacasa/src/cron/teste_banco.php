<?php

require('../config/conexao.php');
//criar array com dados do produto:
echo 'Criar array com dados do produto:<br>';
$query1 = "SELECT * FROM md_cad_produtos";
$result_query1 = mysql_query($query1) or die(mysql_error());
$qtde_query1 = mysql_num_rows($result_query1);
echo "qtde_query1: " . $qtde_query1 . "<br>";
if ($qtde_query1 > 0) {
    while ($campos = mysql_fetch_array($result_query1)) {
        $prod_array_ipi[trim($campos['referencia'])] = $campos['ipi'];
        $prod_array_peso[trim($campos['referencia'])] = $campos['peso'];
        $prod_array_qtdecx[trim($campos['referencia'])] = $campos['qtde_cx'];
    }
}
echo "print prod_array_qtdecx: ";
print("<pre>" . print_r($prod_array_qtdecx, true) . "</pre>");
