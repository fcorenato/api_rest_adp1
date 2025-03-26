<?php
date_default_timezone_set('America/Sao_Paulo');
//require('../config/conexaosql.php');
require('../../src/config/SUsuario.php');

// ============== AJAX CARTEIRA ==============================================
// Arquivo para gerar a carteira e distribuir estoque. Gerando no final o status de cada pedido.

//VARIAVEIS DE CONTROLE
$consula_estoque = TRUE;
$consula_op = TRUE;
$consula_pc = TRUE;
$processar_carteira = TRUE;
$imprimi_item_carteira = TRUE;
$subtotal_por_pedido = TRUE;
$subtotal_por_pedido_ultimo = TRUE;
$pesquisa_por_pedido = '';
$pesquisa_por_produto  = '';
$pesquisa_por_cliente  = '';
$pesquisa_por_unidade  = '';
$imprimi_apenas_resumo_ref = FALSE;

//recebendo POST 
if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if (isset($_POST['pedido']) and $_POST['pedido'] != '') {
        $num_pedidos = str_replace(' ', '', $_POST['pedido']);
        $pesquisa_por_pedido = explode(",", $num_pedidos);
        //$subtotal_por_pedido_ultimo = FALSE;
    } else {
        $pesquisa_por_pedido = 0;
    }

    if (isset($_POST['cliente']) and $_POST['cliente'] != '') {
        $pesquisa_por_cliente = strtoupper($_POST['cliente']);
        //$subtotal_por_pedido_ultimo = FALSE;
    }

    if (isset($_POST['status_ped'])) {
        $pesquisa_por_status_ped = strtoupper($_POST['status_ped']);
        //$subtotal_por_pedido_ultimo = FALSE;
    }

    if (isset($_POST['empresa'])) {
        $pesquisa_por_empresa = strtoupper($_POST['empresa']);
        //$subtotal_por_pedido_ultimo = FALSE;
        if ($pesquisa_por_empresa == 'VETROMANI') {
            $filtro_empresa=array(4988,4989,4990);
        } else if ($pesquisa_por_empresa == 'VIVARTE') {
            $filtro_empresa=array(4991,4992);
        }
    }


    if (isset($_POST['unidade'])) {
        $pesquisa_por_unidade = strtoupper($_POST['unidade']);
        //$subtotal_por_pedido_ultimo = FALSE;
    }


    if (isset($_POST['referencia'])) {
        $referencia = str_replace(" ", "", $_POST['referencia']); //eliminar espaços 
        $produto = strtoupper($referencia);
        if ($produto != "") {
            $pesquisa_por_produto = " AND SC6.C6_PRODUTO LIKE '%$produto%' ";
            $subtotal_por_pedido = FALSE;
        } else {
            $pesquisa_por_produto = "";
        };
    }

    //SE CONSULTA VINDO DA TELA DE CONSULTA DE PRODUTO
    if (isset($_POST['ref'])) {
        $referencia = str_replace(" ", "", $_POST['ref']); //eliminar espaços 
        $produto = strtoupper($referencia);
        if ($produto != "") {
            $pesquisa_por_produto = " AND SC6.C6_PRODUTO LIKE '%$produto%' ";
            $subtotal_por_pedido = FALSE;
            $imprimi_apenas_resumo_ref = TRUE;
        } else {
            $pesquisa_por_produto = "";
        };
    }
} // fim do POST




// ============== (4) PROCESSAR CARTEIRA     ====================================
// FUNCAO PARA GERAR TIMESTAMP E CALCULCAR DIFERENCA ENTRE DATAS NO FORMATO DD/MM/AAAA
function geraTimestamp($data)
{
    $partes = explode('/', $data);
    return mktime(0, 0, 0, $partes[1], $partes[0], $partes[2]);
}
//variaveis de controle e de subtotais
$num_pedido_check = 'inicial';
$produto_check = 'inicial';
$total_pedido_qtde = 0;
$total_pedido_reservado = 0;
$total_pedido_liberada = 0;
$total_pedido_pendente = 0;
$total_pedido_valor = 0;
$total_pedido_op = 0;
$total_pedido_pc = 0;


$total_geral_valor = 0;
$total_geral_qtde = 0;
$total_geral_reservado = 0;
$total_geral_pendente = 0;
$total_geral_sugest = 0;
$total_geral_op = 0;
$total_geral_pc = 0;

// filiais ID
$filiais_id =  array(
    4997 => 'Consolidadora',
    4988 => 'Div Matriz',
    4989 => 'Div Atacado',
    4990 => 'Div Import',
    4991 => 'Viv CE',
    4992 => 'Viv RN',
);


if ($processar_carteira) {
    //chamda api
    require('../../src/api/apiflex-apiv1-orcamentos.php');

    //verificar estoque dos produtos presentes na carteira
    foreach ($resultado->data->solicitacao as $orc) {
        if (!is_array($orc->produtos->produto)) {
            foreach ($orc->produtos->produto as $item) {
                if (is_object($item->nome)) {
                    $list_prod_carteira[$item->nome] = 0;
                }
            }
        }

    }

    if (!isset($resultado) or empty($resultado)) {
        $carteira_processada = '
        <table class="table table-sm table-hover table-bordered">
            <tr>
                <td>NAO ENCONTRADO ITEMS EM CARTEIRA</td>
            </tr>
        </table>
        ';
    } else {
        $carteira_processada = '
            <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed tabela_carteira">
            <thead>
			<tr>
					<th>Filial</th>
					<th>Num Ped</th>
                    <th>Nome Cliente</th>
                    <th>Emissão</th>
					<th>Entrega</th>
					<th>Vendedor</th>
					<th>Valor R$ c/ ipi</th>
					<th>Produto</th>
					<th>Lote</th>
					<th>Qtde Pedido</th>
					<th>Qtde Reserv</th>
					<th>EST Sugest</th>
					<th>Qtde Pend</th>
					<th>OP Sugest</th>
					<th>PC Sugest</th>
					<th>Faturado</th>
					<th>Data Prev</th>
					<th>Doc</th>
                    <th>Saldo Lote</th>
                    <th class="dados-extras d-none">UF</th>
                    <th class="dados-extras d-none">Cidade</th>
                    <th class="dados-extras d-none">Bairro</th>
                    <th class="dados-extras d-none">Volume</th>
                    <th class="dados-extras d-none">Peso Bruto</th>
                    <th class="dados-extras d-none">Frete</th>
                    <th class="dados-extras d-none" style="width: auto" >Msg Nota</th>
                    <th class="dados-extras d-none" style="width: 200px" >Obs Ped Cli</th>
            </tr>
            </thead>
            <tbody>
			';


        foreach ($resultado->data->solicitacao as $orc) {
            $exibe_ped  =  False;
            if ($pesquisa_por_status_ped == 'AP' and ($orc->faturado == 'N' or $orc->faturado == 'P')) {
                $exibe_ped = true;
            } else if ($pesquisa_por_status_ped == 'A' and ($orc->faturado == 'N')) {
                $exibe_ped = true;
            } else if ($pesquisa_por_status_ped == 'P' and ($orc->faturado == 'P')) {
                $exibe_ped = true;
            } else if ($pesquisa_por_status_ped == 'F' and ($orc->faturado == 'S')) {
                $exibe_ped = true;
            } else if ($pesquisa_por_status_ped == 'TODOS') {
                $exibe_ped = true;
            }

            if ($exibe_ped) {
                if (is_object($orc->cliente)) {
                    $cliente_pedido = '<span style="background-color: orange">off cliente</span>';
                } else {
                    $cliente_pedido = substr($orc->cliente, 0, 25);
                }

                if (is_object($orc->vendedor)) {
                    $vendedor_pedido = '<span style="background-color: orange">off vendedor</span>';
                } else {
                    $vendedor_pedido = substr($orc->vendedor, 0, 12);
                }
                $pedido_cabecalho .= '
                <tr class="tr_result">
                <td>' . $filiais_id[$orc->id_empresa] . '</td>
                <td>' . substr($orc->documento, -5) . '</td>
                <td>' . $cliente_pedido . '</td>
                <td>' . $orc->emissao . '</td>
                <td>' . $orc->data_prev_faturamento . '</td>
                <td>' . $vendedor_pedido . '</td>
                ';

                $pedido_cauda .= '
            
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>' . $orc->faturado . '</td>
                <td></td>
                <td></td>
                <td></td>
                
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                <td class="dados-extras d-none"></td>
                
                </tr>
                ';

                if (is_array($orc->produtos->produto)) {
                    foreach ($orc->produtos->produto as $item) {
                        if (is_object($item->nome)) {
                            $nome_item_pedido = '<span style="background-color: orange">off produto</span>';
                        } else {
                            $nome_item_pedido = $item->nome;
                        }

                        $valor_total_item = $item->valor_item + $item->ipi_valor - $item->valor_desconto;
                        $pedito_item .= '
                        <td>' . number_format($valor_total_item,    2, ',', '.') . '</td>
                        <td>' . $nome_item_pedido . '</td>
                        <td></td>
                        <td>' . number_format($item->quantidade,    2, ',', '.') . '</td>
                        ';
                        $carteira_processada .= $pedido_cabecalho . $pedito_item . $pedido_cauda;
                        $pedito_item = '';

                        // totalizando pedidos
                        $total_pedido_valor += $valor_total_item;
                        $total_pedido_qtde += $item->quantidade;

                        //Totalizando GERAL
                        $total_geral_valor += $valor_total_item;
                        $total_geral_qtde += $item->quantidade;
                    }
                } else {
                    foreach ($orc->produtos as $item) {
                        if (is_object($item->nome)) {
                            $nome_item_pedido = '<span style="background-color: orange">off produto</span>';
                        } else {
                            $nome_item_pedido = $item->nome;
                        }
                        $valor_total_item = $item->valor_item + $item->ipi_valor - $item->valor_desconto;
                        $pedito_item .= '
                        <td>' . number_format($valor_total_item,    2, ',', '.') . '</td>
                        <td>' . $nome_item_pedido . '</td>
                        <td></td>
                        <td>' . number_format($item->quantidade,    2, ',', '.') . '</td>
                        ';
                        $carteira_processada .= $pedido_cabecalho . $pedito_item . $pedido_cauda;
                        $pedito_item = '';

                        // totalizando pedidos
                        $total_pedido_valor += $valor_total_item;
                        $total_pedido_qtde += $item->quantidade;

                        //Totalizando GERAL
                        $total_geral_valor += $valor_total_item;
                        $total_geral_qtde += $item->quantidade;
                    }
                }


                // ========== subtotal do pedido   ========
                $total_pedido_cfrete = $orc->valor_frete;
                $total_pedido_cfrete += $total_pedido_valor;
                $carteira_processada .= '
            <tr class="bg_subtotal_rel tr_result" >
            <td>' . $filiais_id[$orc->id_empresa] . '</td>
            <td>' . substr($orc->documento, -5) . '</td>
            <td>' . $cliente_pedido . '</td>
            <td>' . $orc->emissao . '</td>
            <td>' . $orc->data_prev_faturamento . '</td>
            <td>' . $vendedor_pedido . '</td>
            <td>' . number_format($total_pedido_valor,    2, ',', '.') . '</td>
            <td>' . number_format($total_pedido_cfrete,    2, ',', '.') . '</td>
            <td></td>
            <td>' . number_format($total_pedido_qtde,    2, ',', '.') . '</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            <td class="dados-extras d-none"></td>
            </tr>
            ';

                //Totalizando GERAL
                $total_geral_valor_cfrete += $total_pedido_cfrete;

                // limpando variaveis
                $pedido_cabecalho = '';
                $pedido_cauda = '';
                $total_pedido_valor = 0;
                $total_pedido_qtde = 0;
                $total_pedido_cfrete = 0;
            }
        }
    } // fim do if qtde_result > 0
    // ===============   Total GERAL===========  
    $carteira_processada .= '
    <tr class="bg_subtotal_rel tr_result" >
    <td colspan="6">TOTAL GERAL </td>
    <td>' . number_format($total_geral_valor,    2, ',', '.') . '</td>
    <td>' . number_format($total_geral_valor_cfrete,    2, ',', '.') . '</td>
    <td></td>
    <td>' . number_format($total_geral_qtde,    2, ',', '.') . '</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    <td class="dados-extras d-none"></td>
    </tr>
    <tr>
    ';
} // fim do se PROCESSAR CARTERIA



echo $carteira_processada;
