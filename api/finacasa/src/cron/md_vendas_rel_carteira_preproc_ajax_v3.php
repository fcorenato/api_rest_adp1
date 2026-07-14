<?php
ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);
date_default_timezone_set('America/Sao_Paulo');
require('../config/conexao.php');
// require('../../src/config/SUsuario.php');
include_once('../../sys_functions.php');

// ============== AJAX CARTEIRA ==============================================
// Arquivo para gerar a carteira e distribuir estoque. Gerando no final o status de cada pedido.

//VARIAVEIS DE CONTROLE
$atender_parcial_estoque = TRUE;
$atender_parcial_op = TRUE;
$atender_parcial_pc = TRUE;
$consulta_estoque = TRUE;
$consulta_op = FALSE;
$consulta_pc = TRUE;
$processar_carteira = TRUE;
$imprimi_item_carteira = TRUE;
$subtotal_por_pedido = TRUE;
$subtotal_por_pedido_ultimo = TRUE;
$pesquisa_por_pedido = '';
$pesquisa_por_produto  = '';
$pesquisa_por_cliente  = '';
$pesquisa_por_unidade  = '';
$imprimi_apenas_resumo_ref = FALSE;
$imprimi_valores = TRUE; //imprimir valores R$ na carteira


// ============== PROCESSAR CARTEIRA     ====================================
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
$total_pedido_pendente = 0;
$total_pedido_valor = 0;
$total_pedido_op = 0;
$total_pedido_pc = 0;


$total_geral_valor = 0;
$total_geral_qtde = 0;
$total_geral_reservado = 0;
$total_geral_pendente = 0;
$total_geral_est = 0;
$total_geral_op = 0;
$total_geral_pc = 0;

if ($processar_carteira) {
    //chamdada api pedidos get
    // include_once('../../src/api/bling_pedido_vendas_get.php');
    include_once('../../src/api/bv3_pv_get.php');
    //chamdada api prod_estoque get
    include_once('../../src/api/bling_prod_estoque_biv_get.php');
    //chamdada api Op get
    //include_once('../../src/api/bling_op_get.php');
    //chamdada api PC get
    // include_once('../../src/api/bling_pedido_compras_get.php');
    include_once('../../src/api/bv3_pc_get.php');
} //  fim do IF  atualizar estoque no BIV ===================================================

echo '<hr> dados processados finalizados v2<hr>';
