<?php
if ($_SERVER["REQUEST_METHOD"] == "GET") {
	$id = $_GET['id'];

	require('../config/conexao.php');
	//require('../config/SUsuario.php');

	//array de produto black november
	// $array_prod_blacknovember = array('BTV2575', 'CS5008601A', 'CS5041016A', 'CS5043117A', 'CS5045307A', 'CS5048013A', 'CS5066076A', 'CS5066082A', 'DMRUR83052', 'DMRUR83057', 'ETTV3030', 'LE50180011', 'PLAZ0101', 'POFL5315', 'POFP3030', 'POXI7575', 'PT59682', 'PT60527', 'PT61311', 'PT61837', 'PT61856', 'TJBS22976', 'TJCM22976', 'TJYK22976', 'TTVR7575', 'TUGL2375', 'TUGR2375', 'TUGR7575');

	//pesquisando dados do orçamento
	$pesquisa = mysql_query("SELECT * FROM md_vendas_pedidos as p
							LEFT JOIN sys_unidades as un ON p.unidade_codigo = un.codigo 
							WHERE p.id = '$id'")  or die(mysql_error());
	$linhas = mysql_num_rows($pesquisa);
	if ($linhas == 0) {
		echo '<script>parent.location="md_vendas_orcamento.php?act=noloc"</script>';
	} else {
		$dados = mysql_fetch_array($pesquisa);
		$data_val = date("d/m/Y", strtotime($dados["orc_data_valid"]));
		$empresa = $dados['empresa'];
		$orc_split_pgto = $dados['orc_split_pgto'];
		$frete_tipo = $dados['frete_tipo'];
		$frete_tipo_desc = '';
		if ($frete_tipo == 'C') {
			$frete_tipo_desc = 'CIF';
		}
		if ($frete_tipo == 'F') {
			$frete_tipo_desc = 'FOB';
		}
		if ($frete_tipo == 'T') {
			$frete_tipo_desc = 'C. TERCEIROS';
		}
		if ($frete_tipo == 'R') {
			$frete_tipo_desc = 'C. REMETENTE';
		}
		if ($frete_tipo == 'D') {
			$frete_tipo_desc = 'C. DESTINATARIO';
		}
		if ($frete_tipo == 'S') {
			$frete_tipo_desc = 'SEM FRETE';
		}

		if ($dados['empresa'] == 'VETROMANI'){
			$logo_rel = '../../dist/img/logo_relatorio_VIVARTE.jpg';
		} else {
			$logo_rel = '../../dist/img/logo_relatorio_VIVARTE.jpg';
		}
	}

	//pesquisando itens do orçamento
	$itens = '';
	$pesquisa2 = mysql_query("SELECT * FROM md_vendas_pedidos_itens as i WHERE i.pedido_id = '$id' and status = 'A'")  or die(mysql_error());
	$qtde_itens = mysql_num_rows($pesquisa2);


	//pesquisando condicao de pagto
	//require('../config/conexaosql.php');
	$cpag = $dados['cond_pgto'];
	$cpag_desc = '';
	$query2 = "
		SELECT codigo, tipo, desconto, descricao FROM md_vendas_cpgto as cpgto
        WHERE codigo = '$cpag'
          ";

	$result_query2 = mYsql_query($query2);
	$qtde_query2 = mYsql_num_rows($result_query2);

	if ($qtde_query2 > 0) {
		while ($campos = mysql_fetch_array($result_query2)) {
			$cpag_desc = $campos['descricao'];
		}
	}

	//pesquisando vendedores
	$cvend1 = $dados['vend1'];
	$cvend2 = $dados['vend2'];

	$cvend_desc = '';
	$query3 = "
			SELECT codigo, nome_completo, perfil, unidade_codigo FROM sys_usuarios AS us
            WHERE (codigo = '$cvend1' OR codigo = '$cvend2')
          ";

	$result_query3 = mysql_query($query3);
	$qtde_query3 = mysql_num_rows($result_query3);

	if ($qtde_query3 == 0) {
		$select_cond_pgto .= '';
	} else {
		while ($campos = mysql_fetch_array($result_query3)) {
			$vendedores[$campos['codigo']] = $campos['nome_completo'];
		}
	}
	$nome_vend1 = $vendedores[$dados["vend1"]];
	$nome_vend2 = $vendedores[$dados["vend2"]];


	//================ Iniciando PDF   ================

	if ($orc_split_pgto == 'S' AND $empresa == 'VIVARTE') {
		include('orcamento_split.php');
	} else {
		include('orcamento_normal.php');
	}
	
	
} //fim do se get 
