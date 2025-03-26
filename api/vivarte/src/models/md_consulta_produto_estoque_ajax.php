<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');
require('../config/conexao.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	//se perfil vendedor externo nao exibe colunas 
    if ($perfil == 'V') {
        $display_none = 'class="d-none"';
    }

    $referencia = $_POST['ref'];

    // ============== PESQUISANDO O ESTOQUE DISPONIVEL ============== 
	$total_disp_estoque_diponivel =0;
	$total_geral_disp_disponivel = 0;

	$result1 = mysql_query("SELECT * FROM md_estoque_bling WHERE referencia = '$referencia'") or die (mysql_error());
	$cont_num_r1 = mysql_num_rows($result1);

	$resultado_estoque .= '
	<h5>Estoque do produto: '.$referencia.'</h5>
	<table class="table table-sm table-hover table-bordered" >
	<tr align="center" class="bg_subtotal_rel">
			<th colspan="4">ESTOQUE</th>
	</tr>
	<tr class="bg_subtotal2_rel"    >
		<th>Armazem</th>
		<th ' . $display_none . '>Saldo</th>
		<th ' . $display_none . '>Empenho</th>
		<th class="bg-success">Disponivel</th>

	</tr>';

	if($cont_num_r1 > 0){
			while($list = mysql_fetch_array($result1)){
				$empenho = $list["saldo"] + ($list["saldo_disp"] *-1);
				$resultado_estoque .= '
		    	<tr>
					<td align="left">'.$list["deposito"].'</td>
					<td align="right" ' . $display_none . '>'.number_format($list["saldo"],2, ',', '').'</td>
					<td align="right" ' . $display_none . '>'.number_format($empenho,2, ',', '').'</td>
					<td align="right"><strong>'.number_format($list["saldo_disp"],2, ',', '').'</strong></td>
				</tr>
		    	';
				
		    	$total_geral_saldo += $list["saldo"];
				$total_geral_empenho += $empenho;
				$total_geral_saldo_disp += $list["saldo_disp"];
			}
	}

	$resultado_estoque .= '
	<tr class="bg_subtotal2_rel" >
		<td>TOTAL </td>
		<td align="right"  ' . $display_none . '>'.number_format($total_geral_saldo,2, ',', '').'</td>
		<td align="right"  ' . $display_none . '>'.number_format($total_geral_empenho,2, ',', '').'</td>
		<td align="right">'.number_format($total_geral_saldo_disp,2, ',', '').'</td>
	</tr>
	</table>
	';
	/*
	// ==============  PESQUISANDO O ORDEM DE PRODUCAO ============== 
	$total_disp_op_diponivel = 0;
	
	$result2 = mysql_query("SELECT * FROM ordem_producao WHERE referencia = '$referencia' and qtde > 0") or die (mysql_error());
	$cont_num_r2 = mysql_num_rows($result2);

	$resultado_estoque .= '
	
	<table class="table table-sm table-hover table-bordered tabela_carteira" >
	<tr align="center" class="bg_subtotal_rel">
			<th colspan="3">ORDEM PROD</th>
	</tr>
	<tr class="bg_subtotal2_rel">
			<th style="width: 80px;">OP</th>
			<th>Disponivel</th>
			<th style="width: 80px;">Data Prev</th>

	</tr>';

	if($cont_num_r2 > 0){
			while($list = mysql_fetch_array($result2)){
				$data_prev_op = substr($list["data"], 8, 2) .'/'.substr($list["data"], 5, 2) .'/'. substr($list["data"], 0,4);
			
				$resultado_estoque .= '
		    	<tr>
					<td align="right">'.$list["op"].'</td>
					<td align="right">'.number_format($list["qtde"],2, ',', '').'</td>
					<td>'.$data_prev_op.'</td>
				</tr>
		    	';
		    	$total_disp_op_diponivel += $list["qtde"];
		    	$total_geral_disp_disponivel += $list["qtde"];
			}
	}
	
	$resultado_estoque .= '
		<tr class="bg_subtotal2_rel" >
			<td >TOTAL </td>
			<td align="right">'.number_format($total_disp_op_diponivel,2, ',', '').'</td>
			<td></td>
		</tr>
		</table>
	';


	// ==============  PESQUISANDO O PEDIDO DE COMPRA ============== 
	$total_disp_pc_diponivel = 0;
	
	$result3 = mysql_query("SELECT * FROM pedido_compra WHERE referencia = '$referencia' and qtde > 0") or die (mysql_error());
	$cont_num_r3 = mysql_num_rows($result3);

	$resultado_estoque .= '
	
	<table class="table table-sm table-hover table-bordered tabela_carteira" >
	<tr align="center" class="bg_subtotal_rel">
			<th colspan="3">PED COMPRAS</th>
	</tr>
			<th style="width: 80px;">P.Compras</th>
			<th>Disponivel</th>
			<th style="width: 80px;">Data Prev</th>

	</tr>';

	if($cont_num_r3 > 0){
			while($list = mysql_fetch_array($result3)){
				$data_prev_pedido = substr($list["data"], 8, 2) .'/'.substr($list["data"], 5, 2) .'/'. substr($list["data"], 0,4);
				$resultado_estoque .= '
		    	<tr>
					<td align="right">'.$list["pedido"].'</td>
					<td align="right">'.number_format($list["qtde"],2, ',', '').'</td>
					<td>'.$data_prev_pedido.'</td>
				</tr>
		    	';
		    	$total_disp_pc_diponivel += $list["qtde"];
		    	$total_geral_disp_disponivel += $list["qtde"];
			}
	}

	$resultado_estoque .= '
		<tr class="bg_subtotal2_rel" >
			<td>TOTAL </td>
			<td align="right">'.number_format($total_disp_pc_diponivel,2, ',', '').'</td>
			<td></td>
		</tr>
		</table>
	';

	// ============ TOTAL GERAL DISPONIVEL  ============

	$resultado_estoque .= '
	
	<table class="table table-sm table-hover table-bordered tabela_carteira" >
	<tr align="center" class="bg_subtotal_rel">
			<th colspan="3">TOTAL GERAL</th>
	</tr>
	<tr class="bg_subtotal2_rel">
			<th style="width: 80px;">P.Compras</th>
			<th>Disponivel</th>
			<th style="width: 80px;"></th>

	</tr>
		<tr class="bg_subtotal2_rel" >
			<td>TOTAL </td>
			<td align="right">'.number_format($total_geral_disp_disponivel,2, ',', '').'</td>
			<td></td>
		</tr>
		</table>
	';
 */

} // fim do POST

echo $resultado_estoque;
