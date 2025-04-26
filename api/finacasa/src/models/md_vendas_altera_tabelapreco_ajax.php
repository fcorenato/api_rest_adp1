<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');
//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filtro_pes = '';

    $refs = trim(strtoupper($_POST['list_refs']));
    $codigo_tabela = trim(strtoupper($_POST['tabela']));

    require('../config/conexao.php');
    if ($un_tabelas == '' or $codigo_tabela == 1) {
        $query1 = "SELECT DISTINCT p.referencia, p.preco, p.ipi 
            FROM md_cad_produtos AS p
            
            WHERE p.status = 'A'
                       
            AND p.referencia in ($refs)
            
            ORDER BY p.referencia
            LIMIT 20
        ";
    } else {
        $query1 = "SELECT DISTINCT p.referencia, ti.preco_venda as preco, p.ipi 
            FROM md_vendas_tabpreco_itens AS ti  
           
            LEFT JOIN md_cad_produtos AS p ON p.referencia = ti.referencia 
            LEFT JOIN md_vendas_tabpreco AS tp ON (tp.codigo = ti.codigo_tabela)
            
            WHERE p.status = 'A'
            AND tp.status = 'A'
            AND ti.status = 'A'
            
            AND ti.codigo_tabela = $codigo_tabela
            AND p.referencia in ($refs)
            AND ti.preco_venda > 0
            
            ORDER BY p.referencia
            LIMIT 20
        ";

    }
    

    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    if ($qtde_query1 == 0) {
        $resultado_pesq_cli .= '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado. '.$refs.' </td></tr></table>';
    } else {

        $resultado_pesq_cli = '
              <table id="resultado_alter_tabelapreco" class="table table-sm table-hover table-striped table-bordered table-head-fixed tabela_carteira sortable">
              <thead>
              <tr class="">
                <th>Codigo</th>
                 <th>Preço R$</th>
                 <th>ipi</th>
              </tr>
              </thead>
              <tbody>

              ';

        $B1_COD_CHECK = 'inicial';
        while ($campos = mysql_fetch_array($result_query1)) {
              
                $resultado_pesq_cli .= '
                <tr class="tr_result">
                    <td id="'.$campos["referencia"].'_COD_ALTERTAB">' . $campos["referencia"] . '</td>
                    <td id="'.$campos["referencia"].'_PRCVEN_ALTERTAB">' . number_format($campos["preco"],	2, ',', '.') . '</td>
                    <td id="'.$campos["referencia"].'_IPI_ALTERTAB">' . number_format($campos["ipi"],	2, ',', '.') . '</td>
			      </tr>
				';

        } // fim do while

        $resultado_pesq_cli .= '
            </tbody>
            </table>
            </div>';
    } // fim do if qtde_result1

} // fim do POST

echo $resultado_pesq_cli;
