<?php
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
		$this->Image($GLOBALS["logo_rel"], 12, 10, jpg);
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(45, $altura_linha, '', '0');
		$this->Cell(100, $altura_linha, utf8_decode('ORÇAMENTO: ' . $GLOBALS["id"]), '0', 0, 'C');
		$this->SetFont('Arial', 'B', 8);
		$this->Cell(45, $altura_linha, 'Validade: ' . $GLOBALS["data_val"] . '    ', '0', 1, 'R');
		$this->Ln(0); // Line break

		// segunda linha do cabeçalho	
		$this->SetFont('Arial', 'B', 10);
		$this->Cell(45, $altura_linha, '', '0');
		$this->Cell(100, $altura_linha, '', '0', 0, 'C');
		$this->SetFont('Arial', '', 8);
		$this->Cell(45, $altura_linha, 'Pag.:' . $this->PageNo() . '/{nb}', '0', 1, 'R');
		$this->Ln(4); // Line break

	}

	// rodape do relatorio

}

// Instanciando o objeto com a classe PDF contendo cabeçalho e roadape
$pdf = new PDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(TRUE, 12);
$pdf->AddPage();
$pdf->SetTitle(utf8_decode('finacasa - Orçamento'));
$pdf->SetCreator(utf8_decode('BIV - finacasa'));

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
$altura_linha = 5;
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
$pdf->Cell(105, $altura_linha, utf8_decode($dados['endereco'].' '.$dados['end_num']), '0', 0, 'L', $colore);
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
$altura_linha = 5;
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

// TOTAL DO ORCAMENTO

$altura_linha = 8;
$pdf->SetFont('Arial', 'B', 8);
$colore = False;
$pdf->Cell(145, $altura_linha, utf8_decode(' '), 'T', 0, 'C', $colore);
$colore = True;
$pdf->Cell(25, $altura_linha, utf8_decode('Frete R$ '), 'TL', 0, 'R', $colore);
$colore = False;
$pdf->Cell(20, $altura_linha, number_format($dados["frete_valor"], 2, ',', '.'), '1', 0, 'C', $colore);
$pdf->Ln(); //quebra linha
$pdf->SetFont('Arial', 'B', 8);
$colore = False;
$pdf->Cell(145, $altura_linha, utf8_decode(' '), '', 0, 'C', $colore);
$colore = True;
$pdf->Cell(25, $altura_linha, utf8_decode('Total Final R$ '), '1', 0, 'R', $colore);
$colore = False;
$pdf->Cell(20, $altura_linha, number_format($dados["total_final"], 2, ',', '.'), '1', 0, 'C', $colore);
$pdf->Ln(); //quebra linha

//TOTAL GERAL

//calculadndo produtos e servicos
$total_produtos = round($dados["total_cimp"], 2) * 0.75;
$total_produtos = round($total_produtos, 2);
$total_servicos = round($dados["total_cimp"], 2) - $total_produtos;
$total_servicos = round($total_servicos, 2);

$pdf->Ln(); //quebra linha
$altura_linha = 4;
$colore = True;
$pdf->SetFont('Arial', '', 6);
$pdf->Cell(20, $altura_linha, utf8_decode('Qtde'), 'TL', 0, 'C', $colore);
$pdf->Cell(20, $altura_linha, utf8_decode('Peso Total'), 'TL', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Total'), 'TL', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Desc Total'), 'TL', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Total'), 'TL', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Nota Fiscal'), 'TL', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Frete'), 'TL', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Total Final'), 'LRT', 0, 'C', $colore);
$pdf->Ln(); //quebra linha
$pdf->Cell(20, $altura_linha, utf8_decode('Volumes'), 'LB', 0, 'C', $colore);
$pdf->Cell(20, $altura_linha, utf8_decode('KG'), 'LB', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Produtos R$'), 'LB', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('Complementar R$'), 'LB', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LB', 0, 'C', $colore);
$pdf->Cell(25, $altura_linha, utf8_decode('R$'), 'LRB', 0, 'C', $colore);
$pdf->Ln(); //quebra linha
$colore = False;
$altura_linha = 6;
$pdf->SetFont('Arial', '', 6);
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
$colore = False;

$pdf->SetFont('Arial', 'B', 6);
$pdf->MultiCell(190, $altura_linha, utf8_decode('1 - SERÁ EMITIDO UMA NOTA FISCAL NO VALOR DE R$ ') . number_format($total_produtos + $dados["frete_valor"], 2, ',', '.') . utf8_decode(' PELA finacasa E UMA NOTA FISCAL COMPLEMENTAR NO VALOR DE R$ ') . number_format($total_servicos, 2, ',', '.') . utf8_decode('.'));

if ($empresa == 'finacasa') {
    $pdf->MultiCell(190, $altura_linha, utf8_decode('2 - FRETE: DEVERÁ SER PAGO À VISTA, NO ATO DO FECHAMENTO DO PEDIDO, DIRETAMENTE À finacasa.
	DADOS PARA PAGAMENTO DO FRETE: finacasa REVESTIMENTOS - Banco: Santander (033) Agencia: 3132 Conta Corrente: 13011698-0 CNPJ. 06.010.153/0003-68. PIX: 06.010.153/0003-68'),1, 'L', False);
} else if ($empresa == 'finacasa(AG)') {
	$pdf->MultiCell(190, $altura_linha, utf8_decode('2 - FRETE: DEVERÁ SER PAGO À VISTA, NO ATO DO FECHAMENTO DO PEDIDO, DIRETAMENTE À finacasa.
	DADOS PARA PAGAMENTO DO FRETE: AGAS ROCHAS ORNAMENTAIS - Banco: C6 Bank (336) Agencia: 0001 Conta Corrente: 22500368-6 CNPJ. 48.061.210/0001-16.   PIX: 48.061.210/0001-16'),1, 'L', False);

}

//verificar se tabela promocional
if ($dados['tabela_preco'] == 200) {
    $msg_promo = "16 - Este orçamento é válido somente enquanto durarem os estoques.";
}

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
$pdf->MultiCell(190, $altura_linha, utf8_decode($msg_promo));

$pdf->Ln(); //quebra linha
$colore = False;
$altura_linha = 6;
$pdf->Ln(); //quebra linha



//Assinatura
$pdf->Ln(5); //quebra linha

$colore = False;
$altura_linha = 4;
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(60, $altura_linha, utf8_decode('Cliente'), 'T', 0, 'C', $colore);
$pdf->Cell(70, $altura_linha, '', '', 0, 'C', $colore);
$pdf->Cell(60, $altura_linha, utf8_decode('Vendedor'), 'T', 0, 'C', $colore);

//publicando
$pdf->Output('BIV - finacasa Orcamento ' . $id . '.pdf', 'I');
