<?php
if ($_SERVER["REQUEST_METHOD"] == "GET") {
	$id = $_GET['id'];

	require('../config/conexao.php');
	//require('../config/SUsuario.php');

	//array de produto black november
	$array_prod_blacknovember = array('BTV2575', 'CS5008601A', 'CS5041016A', 'CS5043117A', 'CS5045307A', 'CS5048013A', 'CS5066076A', 'CS5066082A', 'DMRUR83052', 'DMRUR83057', 'ETTV3030', 'LE50180011', 'PLAZ0101', 'POFL5315', 'POFP3030', 'POXI7575', 'PT59682', 'PT60527', 'PT61311', 'PT61837', 'PT61856', 'TJBS22976', 'TJCM22976', 'TJYK22976', 'TTVR7575', 'TUGL2375', 'TUGR2375', 'TUGR7575');

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
        SELECT E4_CODIGO, E4_TIPO, E4_YDESCCP, E4_DESCRI FROM SE4010 AS SE4
        WHERE SE4.D_E_L_E_T_ = ''
        AND E4_CODIGO = '$cpag'
          ";

	$result_query2 = mysql_query($query2);
	$qtde_query2 = mysql_num_rows($result_query2);

	if ($qtde_query2 > 0) {
		while ($campos = mysql_fetch_array($result_query2)) {
			$cpag_desc = $campos['E4_DESCRI'];
		}
	}

	//pesquisando vendedores
	$cvend1 = $dados['vend1'];
	$cvend2 = $dados['vend2'];

	$cvend_desc = '';
	$query3 = "
            SELECT A3_COD, A3_NREDUZ, A3_TIPO FROM SA3010 AS SA3
            WHERE (A3_COD = '$cvend1' OR A3_COD = '$cvend2')
            AND SA3.D_E_L_E_T_ = ''
          ";

	$result_query3 = mysql_query($query3);
	$qtde_query3 = mysql_num_rows($result_query3);

	if ($qtde_query3 == 0) {
		$select_cond_pgto .= '';
	} else {
		while ($campos = mysql_fetch_array($result_query3)) {
			$vendedores[$campos['A3_COD']] = $campos['A3_NREDUZ'];
		}
	}
	$nome_vend1 = $vendedores[$dados["vend1"]];
	$nome_vend2 = $vendedores[$dados["vend2"]];


	//================ Iniciando PDF   ================
	require('../../plugins/fpdf/fpdf.php');
	define('FPDF_FONTPATH', '../../plugins/fpdf/font/');
	class PDF extends FPDF
	{
		// cabeçalho do relatorio
		function Header()
		{

			// primeira linha do cabeçalho
			$altura_linha = 4;
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(45, $altura_linha, ' ', '0');
			$this->Cell(100, $altura_linha, ' 	', '0', 0, 'C');
			$this->SetFont('Arial', '', 8);
			$this->Cell(45, $altura_linha, 'Data: ' . date("d/m/Y - H:i", time()) . '    ', '0', 1, 'R');
			$this->Ln(0); // Line break

			// segunda linha do cabeçalho	
			// Logo
			$this->Image('../../dist/img/logo_relatorio_VETROMANI.jpg', 12, 10, jpg);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(45, $altura_linha, '', '0');
			$this->Cell(100, $altura_linha, utf8_decode('ORÇAMENTO: ' . $GLOBALS["id"]), '0', 0, 'C');
			$this->SetFont('Arial', 'B', 8);
			$this->Cell(45, $altura_linha, 'Validade: ' . $GLOBALS["data_val"] . '    ', '0', 1, 'R');
			$this->Ln(0); // Line break

			// segunda linha do cabeçalho	
			$this->Image('../../dist/img/logo_relatorio_VIVARTE.jpg', 13, 18, jpg);
			$this->SetFont('Arial', 'B', 10);
			$this->Cell(45, $altura_linha, '', '0');
			$this->Cell(100, $altura_linha, '', '0', 0, 'C');
			$this->SetFont('Arial', '', 8);
			$this->Cell(45, $altura_linha, 'Pag.:' . $this->PageNo() . '/{nb}', '0', 1, 'R');
			$this->Ln(0); // Line break

		}

		// rodape do relatorio

	}

	// Instanciando o objeto com a classe PDF contendo cabeçalho e roadape
	$pdf = new PDF('P', 'mm', 'A4');
	$pdf->AliasNbPages();
	$pdf->SetAutoPageBreak(TRUE, 12);
	$pdf->AddPage();
	$pdf->SetTitle(utf8_decode('Vetromani - Orçamento'));
	$pdf->SetCreator(utf8_decode('BIV - Vetromani'));

	//cor de preenchimento das celulas
	$pdf->SetFillColor(230, 230, 230);

	//Largua das colunas do relatorio
	$larguras = array('40', '40', '40', '40', '40');
	//altura das linhas
	$altura_linha = 4;
	$altura_linha_titulo = 8;
	//cor de fundo igual a falso
	$colore = True;
	//define font para totalizacao dos titulos
	$pdf->SetFont('Arial', 'B', 8);

	$pdf->SetLineWidth(0.1);
	$pdf->SetDrawColor(140, 140, 140);
	$pdf->Ln(); //quebra linha
	$pdf->Cell(190, $altura_linha_titulo, utf8_decode('Informações do Cliente '), 'B', 0, 'L', $colore);
	$pdf->Ln(); //quebra linha
	$altura_linha = 6;
	$colore = false;
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Cod Cliente: '), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, utf8_decode($dados['cliente_codigo']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Nome: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(105, $altura_linha, substr(utf8_decode($dados['cliente_razao']), 0, 55), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Ln(); //quebra linha
	$pdf->Cell(20, $altura_linha, utf8_decode('CNPJ/CPF:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, utf8_decode($dados['cpf_cnpj']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('N. Fantasia: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(105, $altura_linha, substr(utf8_decode($dados['cliente_nomef']), 0, 55), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Ln(); //quebra linha
	$pdf->Cell(20, $altura_linha, utf8_decode('CEP:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, utf8_decode($dados['cep']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Endereço:'), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(105, $altura_linha, utf8_decode($dados['endereco']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Ln(); //quebra linha

	$pdf->Cell(20, $altura_linha, utf8_decode('Bairro:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, substr(utf8_decode($dados['bairro']), 0, 28), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Cidade:'), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(70, $altura_linha, utf8_decode($dados['cidade']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('UF: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(15, $altura_linha, utf8_decode($dados['uf']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Ln(); //quebra linha
	$pdf->Cell(20, $altura_linha, utf8_decode('Telefone:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, utf8_decode($dados['telefone']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('E-mail:'), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(105, $altura_linha, substr(utf8_decode($dados['email']), 0, 55), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);

	$pdf->Ln(); //quebra linha
	$colore = True;
	$pdf->Cell(190, $altura_linha_titulo, utf8_decode('Informações do Orçamento '), 'B', 0, 'L', $colore);
	$pdf->Ln(); //quebra linha
	$altura_linha = 6;
	$colore = false;
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Unidade:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, utf8_decode($dados['descricao']), '0', 0, 'L', $colore);
	$pdf->Ln(); //quebra linha
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Vendedor:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, substr(utf8_decode($nome_vend1), 0, 55), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Cond. Pgto: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(60, $altura_linha, utf8_decode($dados['cond_pgto']) . '-' . $cpag_desc, '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Tabela Preço: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode($dados['tabela_preco']), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Ln(); //quebra linha


	$pdf->Cell(20, $altura_linha, utf8_decode('Parceiro:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, substr(utf8_decode($nome_vend2), 0, 55), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Transp: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(60, $altura_linha, utf8_decode($dados["transp_nome"]), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Valor Frete: '), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(25, $altura_linha, 'R$ ' . number_format($dados["frete_valor"], 2, ',', '.'), '0', 0, 'L', $colore);

	$pdf->Ln(); //quebra linha

	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Desc. C.Pgto:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(40, $altura_linha, number_format($dados["desc1"], 2, ',', '.') . '%', '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Desc. Faixa:'), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(60, $altura_linha, number_format($dados["desc2"], 2, ',', '.') . '%', '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Desc. Comercial:'), '0', 0, 'R', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->Cell(25, $altura_linha, number_format($dados["desc3"], 2, ',', '.') . '%', '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Ln(); //quebra linha
	$pdf->Cell(20, $altura_linha, utf8_decode('Observação:'), '0', 0, 'L', $colore);
	$pdf->SetFont('Arial', '', 8);
	$pdf->MultiCell(170, $altura_linha, utf8_decode($dados['msg_pedido']));
	$pdf->Ln(2); //quebra linha

	// ==== itens do orcamento ====
	$colore = True;
	$altura_linha = 10;
	$colore = True;
	$pdf->SetLineWidth(0.1);
	$pdf->SetDrawColor(140, 140, 140);
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(25, $altura_linha, utf8_decode('Ref:'), '1', 0, 'L', $colore);
	$pdf->Cell(60, $altura_linha, utf8_decode('Descrição:'), '1', 0, 'L', $colore);
	$pdf->Cell(5, $altura_linha, utf8_decode('UN'), '1', 0, 'C', $colore);
	$pdf->Cell(15, $altura_linha, utf8_decode('Qtde:'), '1', 0, 'C', $colore);
	$pdf->Cell(15, $altura_linha, utf8_decode('Prc Tab:'), '1', 0, 'C', $colore);
	$pdf->Cell(15, $altura_linha, utf8_decode('Desc R$:'), '1', 0, 'C', $colore);
	$pdf->Cell(15, $altura_linha, utf8_decode('Prc Unit:'), '1', 0, 'C', $colore);
	$pdf->Cell(5, $altura_linha, utf8_decode('IPI'), '1', 0, 'C', $colore);
	$pdf->Cell(15, $altura_linha, utf8_decode('Prc Final'), '1', 0, 'C', $colore);
	$pdf->Cell(20, $altura_linha, utf8_decode('Total R$'), '1', 0, 'C', $colore);
	$pdf->Ln(); //quebra linha

	$colore = False;
	$altura_linha = 5;
	$pdf->SetFont('Arial', '', 8);
	//cor de preenchimento das celulas
	$pdf->SetFillColor(245, 245, 245);
	if ($qtde_itens == 0) {
		echo '<script>parent.location="md_vendas_orcamento.php?act=noloc"</script>';
	} else {
		while ($dadosi = mysql_fetch_array($pesquisa2)) {
			if ($dadosi["data_prev_fatura"] != '') {
				$datdata_prev_fatura = date("d/m/y", strtotime($dadosi["data_prev_fatura"]));
			}

			//verificando se item é caixa fracioanda
			if ($dadosi["qtde_frac"] != '1') {
				$qtd = round($dadosi["qtde"] * 100, 0);
				$qtdcx = round($dadosi["qtde_cx"] * 100, 0);
				$resto_div = ($qtd % $qtdcx);
				$resto_div = round($resto_div, 2);
				if (($resto_div) * 100 != 0) {
					$cx_frac = '*';
				} else {
					$cx_frac = '';
				}
			} else {
				$cx_frac = '';
			}


			$prc_unit = $dadosi["prc_tab"] - $dadosi["desconto"];
			$prc_unit_final = $prc_unit + ($prc_unit * $dadosi["ipi"] / 100);
			$total_item = $dadosi["qtde"] * ($prc_unit + ($prc_unit * $dadosi["ipi"] / 100));
			$desc_item = trim($dadosi['descricao']);
			$tam_desc = strlen($desc_item);
			$desc_item_l1 = '';
			$desc_item_l2 = '';
			$desc_item_l1 = substr($desc_item, 0, 31);
			$hif = '';
			if ($tam_desc > 31) {
				$desc_item_l2 = substr($desc_item, 31, 31);
				$hif = '-';
			}

			//verificar se produto é da blacknovember
			if (in_array($dadosi['codigo'], $array_prod_blacknovember)) {
				$msg_prod_blacknovember = "16 - Preço promocional campanha Black November válido somente para pedidos faturados e entregues dentro do mês de novembro.";
			}

			$pdf->Cell(25, $altura_linha, utf8_decode($dadosi['codigo']), "T", 0, "L", $colore);
			$pdf->Cell(60, $altura_linha, utf8_decode($desc_item_l1) . $hif, "T", 0, "L", $colore);
			$pdf->Cell(5, $altura_linha, utf8_decode($dadosi['unidade']), "T", 0, "C", $colore);

			$pdf->Cell(15, $altura_linha, $cx_frac . number_format($dadosi["qtde"], 2, ',', '.'), "T", 0, "C", $colore);
			$pdf->Cell(15, $altura_linha, number_format($dadosi["prc_tab"], 2, ',', '.'), "T", 0, "C", $colore);
			$pdf->Cell(15, $altura_linha, number_format($dadosi["desconto"], 2, ',', '.'), "T", 0, "C", $colore);
			$pdf->Cell(15, $altura_linha, number_format($prc_unit, 2, ',', '.'), "T", 0, "C", $colore);
			$pdf->Cell(5, $altura_linha, number_format($dadosi["ipi"], 2, ',', '.'), "T", 0, "C", $colore);
			$pdf->Cell(15, $altura_linha, number_format($prc_unit_final, 2, ',', '.'), "T", 0, "C", $colore);
			$pdf->Cell(20, $altura_linha, number_format($total_item, 2, ',', '.'), "T", 0, "C", $colore);
			//se desc produto maior que 42 caracteres , exibir segunda linhas com os demais
			if ($desc_item_l2) {
				$pdf->Ln(); //quebra linha
				$pdf->Cell(25, $altura_linha, '', "0", 0, "L", $colore);
				$pdf->Cell(60, $altura_linha, utf8_decode($desc_item_l2), "0", 0, "L", $colore);
			}
			$pdf->Ln(); //quebra linha
			if ($dadosi['obs'] <> '' || $datdata_prev_fatura <> '') {
				if ($datdata_prev_fatura <> '') {
					$obs_data_prev_fatura = 'Prev fatura = ' . $datdata_prev_fatura . '. ';
				}
				$pdf->SetFont("Arial", "B", 8);
				$pdf->Cell(20, $altura_linha, utf8_decode("Obs do item: "), "", 0, "L", $colore);
				$pdf->SetFont("Arial", "", 7);
				$pdf->Cell(170, $altura_linha, $obs_data_prev_fatura . utf8_decode($dadosi['obs']), "", 0, "L", $colore);
				$pdf->Ln(); //quebra linha
			}

			if ($colore) {
				$colore = False;
			} else {
				$colore = True;
			}
		}
	}

	//cor de preenchimento das celulas
	$pdf->SetFillColor(230, 230, 230);

	//TOTAL GERAL

	//calculadndo produtos e servicos
	$total_produtos = round($dados["total_cimp"],2)*0.75;
	$total_produtos = round($total_produtos,2);
	$total_servicos = round($dados["total_cimp"],2) - $total_produtos;
	$total_servicos = round($total_servicos,2);

	$pdf->Ln(); //quebra linha
	$altura_linha = 4;
	$colore = True;
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, utf8_decode('Qtde'), 'TL', 0, 'C', $colore);
	$pdf->Cell(20, $altura_linha, utf8_decode('Peso Total'), 'TL', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Total'), 'TL', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Desc Total'), 'TL', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Total'), 'TL', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Serviços'), 'TL', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Frete'), 'TL', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Total Final'), 'LRT', 0, 'C', $colore);
	$pdf->Ln(); //quebra linha
	$pdf->Cell(20, $altura_linha, utf8_decode('Volumes'), 'LB', 0, 'C', $colore);
	$pdf->Cell(20, $altura_linha, utf8_decode('KG'), 'LB', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('Produtos R$'), 'LB', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LRB', 0, 'C', $colore);
	$pdf->Ln(); //quebra linha
	$colore = False;
	$altura_linha = 8;
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(20, $altura_linha, number_format($dados["total_volumes"], 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(20, $altura_linha, number_format($dados["total_peso"], 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, number_format($dados["total_cimp"] + $dados["total_desc"], 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, number_format($dados["total_desc"], 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, number_format($total_produtos, 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, number_format($total_servicos, 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, number_format($dados["frete_valor"], 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Cell(25, $altura_linha, number_format($dados["total_final"], 2, ',', '.'), '1', 0, 'C', $colore);
	$pdf->Ln(); //quebra linha

	//Termos comerciais
	$pdf->Ln(2); //quebra linha
	$colore = True;
	$altura_linha = 4;

	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(190, $altura_linha_titulo, utf8_decode('Termos Comerciais '), '0', 0, 'L', $colore);
	$pdf->Ln(); //quebra linha
	$pdf->SetFont('Arial', '', 6);
	
	if ($dados['empresa'] == 'VIVARTE' OR $dados['tabela_preco'] == '236' OR $dados['tabela_preco'] == '237') {
		$pdf->MultiCell(190, $altura_linha, utf8_decode('1 - Será emitido uma nota fiscal no valor de R$ ').number_format($total_produtos, 2, ',', '.').utf8_decode(' pela VIVARTE e uma nota fiscal complementar no valor de R$').number_format($total_servicos, 2, ',', '.').utf8_decode(' pela Revenda.'));
		
		$pdf->MultiCell(190, $altura_linha, utf8_decode('2 - O prazo de entrega só é contado a partir da confirmação do pagamento do pedido.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('3 - Os valores desse orçamento incluem ICMS, IPI e DIFAL. Qualquer outra cobrança de taxas/impostos no destino é de responsabilidade exclusiva do cliente, ficando a vendedora isenta de quaisquer custos, inclusive de cobranças extras de atraso/estocagem pela transportadora.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('4 - Este orçamento não garante reserva de estoque.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('5 - A previsão de faturamento/entrega pode sofrer alteração a depender da disponibilidade do estoque no fechamento do pedido.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('6 - O prazo constante no orçamento refere-se ao período para liberação e despacho por parte da vendedora. O prazo de trânsito para entrega é de total responsabilidade da transportadora terceirizada contratada. Eventuais atrasos na entrega do produto, reconhecidamente provocados por terceiros e alheios ao controle da vendedora, não são passíveis de ressarcimento.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('7 - A entrega dos produtos será efetuada no endereço constante no campo endereço de entrega. Caso o endereço informado não seja o correto ou não haja pessoa disponível para recebimento na data agendada, será cobrado 50% do valor do frete a título de despesa com re-entrega que deve ser pago antecipadamente.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('8 - Em caso de necessidade de capatazia para descarregamento, esta deve ser providenciada pelo cliente.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('9 - Entrega efetuada na garagem ou térreo.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('10 - Peças em grandes formatos, precisam ser içadas, serviço terceirizado a ser contratado pelo cliente.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('11 - Em situações consideradas pela transportadora como atípica (ex. Entrega em horários especiais, dificuldade de acessibilidade do ponto de descarregamento, etc...) Poderá ser cobrado uma taxa extra de entrega não inclusa nesse orçamento.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('12 - OS PRODUTOS ESTÃO SUJEITOS A VARIAÇÃO DE CORES E TONALIDADES EM RELAÇÃO AS AMOSTRAS . No caso de pedidos complementares, não garantimos a mesma tonalidade.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('13 - A vendedora não se responsabiliza pelos quantitativos comprados, sendo de responsabilidade do cliente a conferência das quantidades especificadas.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('14 - NÃO SERÃO ACEITAS RECLAMAÇÕES OU TROCAS DE PRODUTOS APLICADOS. VERIFIQUE TODO O MATERIAL ANTES DE EXECUTAR A INSTALAÇÃO NA OBRA. Leia atentamente o manual técnico dos produtos disponível nos sites dos fabricantes e enviados junto com os produtos.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('15 - Devoluções ou trocas somente serão aceitas por motivo de defeito ou em caso de mercadoria em não conformidade com o pedido.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('16 - O ato do pagamento do valor do pedido é considerado concordância tácita, por parte do cliente, com as cláusulas deste Termo de Venda.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode($msg_prod_blacknovember));

		$pdf->Ln(); //quebra linha
		$colore = False;
		$altura_linha = 6;
		$pdf->Cell(190, $altura_linha, utf8_decode(' OBS: DADOS PARA PAGAMENTO: VIVARTE REVESTIMENTOS - Banco Do Brasil (001)   Agência: 1836-8 Conta corrente: 114530-4   CNPJ: 06.010.153/0001-04.
		  -  PIX: 06010153000104'), '1', 0, 'L', $colore);
		$pdf->Ln(); //quebra linha
	} else if ($dados['empresa'] == 'VETROMANI') {
		$pdf->MultiCell(190, $altura_linha, utf8_decode('1 - O prazo de entrega só é contado a partir da confirmação do pagamento do pedido.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('2 - Os valores desse orçamento incluem ICMS, IPI e DIFAL. Qualquer outra cobrança de taxas/impostos no destino é de responsabilidade exclusiva do cliente, ficando a vendedora isenta de quaisquer custos, inclusive de cobranças extras de atraso/estocagem pela transportadora.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('3 - Este orçamento não garante reserva de estoque.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('4 - A previsão de faturamento/entrega pode sofrer alteração a depender da disponibilidade do estoque no fechamento do pedido.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('5 - O prazo constante no orçamento refere-se ao período para liberação e despacho por parte da vendedora. O prazo de trânsito para entrega é de total responsabilidade da transportadora terceirizada contratada. Eventuais atrasos na entrega do produto, reconhecidamente provocados por terceiros e alheios ao controle da vendedora, não são passíveis de ressarcimento.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('6 - A entrega dos produtos será efetuada no endereço constante no campo endereço de entrega. Caso o endereço informado não seja o correto ou não haja pessoa disponível para recebimento na data agendada, será cobrado 50% do valor do frete a título de despesa com re-entrega que deve ser pago antecipadamente.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('7 - Em caso de necessidade de capatazia para descarregamento, esta deve ser providenciada pelo cliente.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('8 - Entrega efetuada na garagem ou térreo.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('9 - Peças em grandes formatos, precisam ser içadas, serviço terceirizado a ser contratado pelo cliente.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('10 - Em situações consideradas pela transportadora como atípica (ex. Entrega em horários especiais, dificuldade de acessibilidade do ponto de descarregamento, etc...) Poderá ser cobrado uma taxa extra de entrega não inclusa nesse orçamento.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('11 - OS PRODUTOS ESTÃO SUJEITOS A VARIAÇÃO DE CORES E TONALIDADES EM RELAÇÃO AS AMOSTRAS . No caso de pedidos complementares, não garantimos a mesma tonalidade.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('12 - A vendedora não se responsabiliza pelos quantitativos comprados, sendo de responsabilidade do cliente a conferência das quantidades especificadas.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('13 - NÃO SERÃO ACEITAS RECLAMAÇÕES OU TROCAS DE PRODUTOS APLICADOS. VERIFIQUE TODO O MATERIAL ANTES DE EXECUTAR A INSTALAÇÃO NA OBRA. Leia atentamente o manual técnico dos produtos disponível nos sites dos fabricantes e enviados junto com os produtos.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('14 - Devoluções ou trocas somente serão aceitas por motivo de defeito ou em caso de mercadoria em não conformidade com o pedido.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode('15 - O ato do pagamento do valor do pedido é considerado concordância tácita, por parte do cliente, com as cláusulas deste Termo de Venda.'));
		$pdf->MultiCell(190, $altura_linha, utf8_decode($msg_prod_blacknovember));

		$pdf->Ln(); //quebra linha
		$colore = False;
		$altura_linha = 6;
		$pdf->Cell(190, $altura_linha, utf8_decode(' OBS: DADOS PARA PAGAMENTO: DIVETRO IND COM VIDROS - Banco Do Brasil (001) - Agência: 3515-7 - Conta corrente: 33180-5 CNPJ: 08.017.658/0001-18  -  PIX: 08017658000118'), '1', 0, 'L', $colore);
		$pdf->Ln(); //quebra linha
	}


	//Assinatura
	$pdf->Ln(); //quebra linha
	$pdf->Ln(); //quebra linha
	$pdf->Ln(); //quebra linha
	$colore = False;
	$altura_linha = 4;
	$pdf->SetFont('Arial', 'B', 8);
	$pdf->Cell(60, $altura_linha, utf8_decode('Cliente'), 'T', 0, 'C', $colore);
	$pdf->Cell(70, $altura_linha, '', '', 0, 'C', $colore);
	$pdf->Cell(60, $altura_linha, utf8_decode('Vendedor'), 'T', 0, 'C', $colore);

	//publicando
	$pdf->Output('Vetromani Orcamento ' . $id . '.pdf', 'I');
} //fim do se get 
