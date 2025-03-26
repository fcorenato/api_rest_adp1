<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');
require('../config/conexao.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // SE PERFIL FOR VENDEDOR EXIBIR APENAS OS DISPONIVEIS
    if ($perfil == 'V') {
        $exibir_apenas_disp = 'display:none;';
    } else {
        $exibir_apenas_disp = ' ';
    }

    $referencia = $_POST['ref'];
    $estoque_sem_outlet = ' AND (local !="VH-OUTLET" AND local != "AG-OUTLET") ';
    $query0 = "select * from md_estoque_disponivel_detail 
    where referencia = '$referencia'
    $estoque_sem_outlet
    ";
    $result_query0 = mysql_query($query0);
    $qtde_query0 = mysql_num_rows($result_query0);

    if ($qtde_query0 > 0) {
        while ($dados_estq = mysql_fetch_array($result_query0)) {
            $update = $dados_estq['update_at'];
            $update_at = date("d/m/Y H:i", strtotime($dados_estq['update_at'])) . 'hs';
            $unidade_medida_produto_pesquisado = $dados_estq['unid_medida'];
            //ESTOQUE
            if ($dados_estq['tipo_estq'] == 'ESTQ') {
                $estoque_res .= '
            <tr>
                    <td>' . $dados_estq['local'] . '</td>
                    <td>' . $unidade_medida_produto_pesquisado . '</td>
                    <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($dados_estq['saldo'],    2, ',', '.') . '</td>
                    <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($dados_estq['empenho'],    2, ',', '.') . '</td>
                    <td align="right">' . number_format($dados_estq['saldo_disp'],    2, ',', '.') . '</td>
    
                </tr>
                ';

                $total_disp_estoque_saldo += $dados_estq['saldo'];
                $total_disp_estoque_empenho += $dados_estq['empenho'];
                $total_disp_estoque_diponivel += $dados_estq['saldo_disp'];;
            }


            //ORDEM DE PRODUCAO
            if ($dados_estq['tipo_estq'] == 'OP') {
                $op_res .= '
            <tr>
                    <td>' . $dados_estq['local'] . '</td>
                    <td>' . $dados_estq['data_prev'] . '</td>
                    <td>' . $unidade_medida_produto_pesquisado . '</td>
                    <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($dados_estq['saldo'],    2, ',', '.') . '</td>
                    <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($dados_estq['empenho'],    2, ',', '.') . '</td>
                    <td align="right">' . number_format($dados_estq['saldo_disp'],    2, ',', '.') . '</td>
    
                </tr>
                ';

                $total_disp_op_saldo += $dados_estq['saldo'];
                $total_disp_op_empenho += $dados_estq['empenho'];
                $total_disp_op_diponivel += $dados_estq['saldo_disp'];;
            }

            //PEDIDO DE COMPRA
            if ($dados_estq['tipo_estq'] == 'PC') {
                $pc_res .= '
            <tr>
                    <td>' . $dados_estq['local'] . '</td>
                    <td>' . $dados_estq['data_prev'] . '</td>
                    <td>' . $dados_estq['unid_medida'] . '</td>
                    <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($dados_estq['saldo'],    2, ',', '.') . '</td>
                    <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($dados_estq['empenho'],    2, ',', '.') . '</td>
                    <td align="right">' . number_format($dados_estq['saldo_disp'],    2, ',', '.') . '</td>
    
                </tr>
                ';

                $total_disp_pc_saldo += $dados_estq['saldo'];
                $total_disp_pc_empenho += $dados_estq['empenho'];
                $total_disp_pc_diponivel += $dados_estq['saldo_disp'];
            }
        }
    }

    //veficando tempo da ultima atualizacao
    $date1 = strtotime($update);
    $date2 = strtotime(date("Y-m-d H:i:s"));

    // Formulate the Difference between two dates
    $diff = abs($date2 - $date1);
    $years = floor($diff / (365 * 60 * 60 * 24));
    $months = floor(($diff - $years * 365 * 60 * 60 * 24)
        / (30 * 60 * 60 * 24));
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
        $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

    $hours = floor(($diff - $years * 365 * 60 * 60 * 24
        - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24)
        / (60 * 60));
    $minutes = floor(($diff - $years * 365 * 60 * 60 * 24
        - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24
        - $hours * 60 * 60) / 60);
    if ($minutes > 20) {
        $atu_alert = 'badge-warning';

        //reportando evento via email
        $data = date("d/m/Y H:m");
        $nome = 'Portal Biv - Erro Atualização Estoque';
        $email_from = 'biv@vetromani.com.br';

        $msg = "<h3>O estoque no BIV esta desatualizado. Checar CRON<h3></br>
        Usuário: $usuario_nome <br />
        Última atualização em: $update_at ($hours horas e $minutes minutos atrás )";

        // emails para quem será enviado o formulário
        $emailenviar = "fco.renatogomes@gmail.com,renato@vetromani.com.br";
        $destino = $emailenviar;
        $assunto = "BIV - Evento de erro: Estoque BIV desatualizado.";

        // É necessário indicar que o formato do e-mail é html
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $headers .= 'From: BIV Vetromani <' . $email_from . '>';
        //$headers .= "Bcc: $EmailPadrao\r\n";

        $enviaremail = mail($destino, $assunto, $msg, $headers);
    } else {
        $atu_alert = 'badge-success';
    }
    // ============ ESTOQUE DISPONIVEL  ============

    
    $resultado_estoque .= '
        <div class="col">
        <strong> <i class="fas fa-cubes"></i> Disponibilidade do Produto: ' . $referencia  . '</strong>
        <small class="badge '.$atu_alert.' float-right"><i class="far fa-clock"></i> Dados atualizados em: ' . $update_at . ' ('.$hours.'H:'.$minutes.'m atrás )</small>
        <hr>
        <strong> Estoque:</strong>        
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
                <th>Armazém</th>
				<th style="width: 50px;">UN</th>
				<th style="width: 160px;'.$exibir_apenas_disp.'">Quantidade</th>
				<th style="width: 160px;'.$exibir_apenas_disp.'">Empenho</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

    $resultado_estoque .= $estoque_res;

    //total geral
    $total_geral_disp_saldo += $total_disp_estoque_saldo;
    $total_geral_disp_empenho += $total_disp_estoque_empenho;
    $total_geral_disp_disponivel += $total_disp_estoque_diponivel;

    $resultado_estoque .= '
		<tr class="bg_subtotal_rel" >
			<td>TOTAL </td>
            <td></td>
			<td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_disp_estoque_saldo,    2, ',', '.') . '</td>
			<td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_disp_estoque_empenho,    2, ',', '.') . '</td>
			<td align="right" class="bg-success">' . number_format($total_disp_estoque_diponivel,    2, ',', '.') . '</td>
			
        </tr>
        </tbody>
		</table>';


    // ============ OPs DISPONIVEL  ============

    $resultado_estoque .= '<strong> Ordem de Produção:</strong>';
    $resultado_estoque .= '
        <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
        <thead>
		<tr class="bg_subtotal_rel">
				<th >OP</th>
                <th>Data Prev</th>
				<th style="width: 50px;">UM</th>
				<th style="width: 160px;'.$exibir_apenas_disp.'">Quantidade</th>
				<th style="width: 160px;'.$exibir_apenas_disp.'">Empenho</th>
				<th style="width: 160px;" class="bg-success">Disponivel</th>
        </tr>
        </thead>
        <tbody>';

    $resultado_estoque .= $op_res;

    //total geral
    $total_geral_disp_saldo += $total_disp_op_saldo;
    $total_geral_disp_empenho += $total_disp_op_empenho;
    $total_geral_disp_disponivel += $total_disp_op_diponivel;

    $resultado_estoque .= '
		<tr class="bg_subtotal_rel" >
			<td colspan="3" > TOTAL </td>
			<td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_disp_op_saldo,    2, ',', '.') . '</td>
			<td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_disp_op_empenho,    2, ',', '.') . '</td>
			<td align="right" class="bg-success">' . number_format($total_disp_op_diponivel,    2, ',', '.') . '</td>
        </tr>
        </tbody>
		</table>
	';


    // ============ PCs DISPONIVEL  ============

    $resultado_estoque .= '<strong> Pedido de Compra:</strong>';
    $resultado_estoque .= '
    <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
    <thead>
    <tr class="bg_subtotal_rel">
            <th >OP</th>
            <th>Data Prev</th>
            <th style="width: 50px;">UM</th>
            <th style="width: 160px;'.$exibir_apenas_disp.'">Quantidade</th>
            <th style="width: 160px;'.$exibir_apenas_disp.'">Empenho</th>
            <th style="width: 160px;" class="bg-success">Disponivel</th>
    </tr>
    </thead>
    <tbody>';

    $resultado_estoque .= $pc_res;

    //total geral
    $total_geral_disp_saldo += $total_disp_pc_saldo;
    $total_geral_disp_empenho += $total_disp_pc_empenho;
    $total_geral_disp_disponivel += $total_disp_pc_diponivel;

    $resultado_estoque .= '
    <tr class="bg_subtotal_rel" >
        <td colspan="3" > TOTAL </td>
        <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_disp_pc_saldo,    2, ',', '.') . '</td>
        <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_disp_pc_empenho,    2, ',', '.') . '</td>
        <td align="right" class="bg-success">' . number_format($total_disp_pc_diponivel,    2, ',', '.') . '</td>
    </tr>
    </tbody>
    </table>
    ';


    // ============ TOTAL GERAL DISPONIVEL  ============

    $resultado_estoque .= '
    
    <table id="tabela_relatorio" class="table table-sm table-hover table-bordered tabela_carteira">
    <thead>
    <tr class="bg_subtotal_rel">
            <th>GERAL</th>

            <th style="width: 160px;'.$exibir_apenas_disp.'">Quantidade</th>
            <th style="width: 160px;'.$exibir_apenas_disp.'">Empenho</th>
            <th style="width: 160px;" class="bg-success">Disponivel</th>

    </tr>
    </thead>
    <tbody>
    <tr class="bg_subtotal_rel">
        <td >TOTAL </td>
        <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_geral_disp_saldo,    2, ',', '.') . '</td>
        <td align="right" style="'.$exibir_apenas_disp.'">' . number_format($total_geral_disp_empenho,    2, ',', '.') . '</td>
        <td align="right" class="bg-success">' . number_format($total_geral_disp_disponivel,    2, ',', '.') . '</td>
    </tr>
    </tbody>
    </table>
    </div>
    ';
} // fim do POST

echo $resultado_estoque;
