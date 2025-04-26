<?php
date_default_timezone_set('America/Sao_Paulo');
require('../config/conexaosql.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filtro_pes = '';

    $codigo = strtoupper($_POST['codigo']);
    $nome = strtoupper($_POST['nome']);
    $cpf_cnpj_pesq = strtoupper($_POST['cnpj']);
    
    //chamdada api pedidos get
    include_once('../../src/api/bling_cliente_get.php');

    $qtde_query1 = count($cliente_array);
    if ($qtde_query1 == 0) {
        $resultado_pesq_cli .= '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado. </td></tr></table>';
    } else {

        $resultado_pesq_cli = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-striped table-bordered table-head-fixed tabela_carteira sortable">
              <thead>
              <tr class="">
                <th>Acão</th>
                <th>Codigo</th>
                <th>Razão Social</th>
                <th>Nome Fantasia</th>
                <th>CNPJ/CPF</th>
              </tr>
              </thead>
              <tbody>

              ';

        $B1_COD_CHECK = 'inicial';
        foreach ($cliente_array as $key_cli => $value_cli) {
                $resultado_pesq_cli .= '
                <tr class="tr_result">
                    <td align="center">
                        <a href="#" type="submit" id="btn_cancelar" class="btn btn-sm btn-success w-100 consulta_cliente" cod="'. $_POST['cnpj'] .'" style="font-size:0.6rem" ><i class="fas fa-check"></i></i></a>
                    </td>
                    <td>' . $value_cli["id"] . '</td>
                    <td>' . $value_cli["nome"] . '</td>
                    <td>' . $value_cli["fantasia"] . '</td>
                    <td>' . $value_cli["cnpj"] . '</td>
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
