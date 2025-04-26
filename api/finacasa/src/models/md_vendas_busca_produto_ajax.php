<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');
require('../config/conexao.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filtro_pes = '';

    $codigo = trim(strtoupper($_POST['codigo_prod']));
    $nome = strtoupper($_POST['nome_prod']);
    $codigo_tabela = strtoupper($_POST['codigo_tabela']);
    $empresa_orc = strtoupper($_POST['empresa_orc']);

    //verificando se foi definito fabricantes para unidade , se sim trazer apenas produtos deles
    if ($un_fabricantes != "") {
        $filtro_fabricante = " and p.marca in ($un_fabricantes) ";
    }

    //gerando relatório
    //se nao informado tabelas no cadastro da unidade
    if ($un_tabelas == '') {
        $query1 = "
            SELECT DISTINCT p.referencia,
                    p.descricao,
                    p.unidade,
                    p.preco as preco_venda,
                    p.ipi,
                    p.qtde_cx,
                    p.peso,
                    p.dias_prod,
                    p.marca,
                    p.permitePalletAberto, p.qtdePallet, p.taxaPalletAbertoR, p.taxaPalletAberto, p.precoProdPalletAbertoR
                
            FROM   md_cad_produtos AS p
                
            WHERE  p.status = 'A'
            and prod_pai != 'PAI' 
            AND p.referencia LIKE '%$codigo%'
            AND p.descricao LIKE '%$nome%'
            AND  p.preco > 0
            ORDER  BY p.referencia
            LIMIT  20
            ";
    } else {
        $query1 = "
            SELECT DISTINCT p.referencia, p.descricao, p.unidade, p.preco, p.ipi, p.qtde_cx, p.peso, p.dias_prod, p.marca, p.permitePalletAberto, p.qtdePallet, p.taxaPalletAbertoR, p.taxaPalletAberto, p.precoProdPalletAbertoR, ti.preco_venda, ti.fraciona   
            FROM md_cad_produtos AS p
            LEFT JOIN md_vendas_tabpreco_itens AS ti ON p.referencia = ti.referencia 
            LEFT JOIN md_vendas_tabpreco AS tp ON (tp.codigo = ti.codigo_tabela)
            WHERE p.status = 'A'
            and prod_pai != 'PAI' 
            AND tp.status = 'A'
            AND ti.status = 'A'
            
            AND ti.codigo_tabela = '$codigo_tabela'
            AND p.referencia LIKE '%$codigo%'
            AND p.descricao LIKE '%$nome%'
            AND ti.preco_venda > 0
            $filtro_fabricante
            ORDER BY p.referencia
            LIMIT 20
            ";
    }

    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    if ($qtde_query1 == 0) {
        $resultado_pesq_cli .= '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado. </td></tr></table>';
    } else {

        $resultado_pesq_cli = '
              <table id="tabela_produto" class="table table-sm table-hover table-striped table-bordered table-head-fixed tabela_carteira sortable">
              <thead>
              <tr class="">
                <th>Acão</th>
                <th>Codigo</th>
                <th>marca</th>
                <th>Descrição</th>
                <th>UM</th>
                <th>IPI %</th>
                <th>Qtd/Cx</th>
                <th class="d-none">Frac</th>
                <th class="d-none">Peso B</th>
                <th>Preço R$</th>
                <th>Pallet Aberto</th>
                <th>qtde Pallet</th>
                <th>Taxa PA R$</th>
                <th>Taxa PA %</th>
                <th>Preço PA R$</th>
              </tr>
              </thead>
              <tbody>

              ';

        $B1_COD_CHECK = 'inicial';
        while ($campos = mysql_fetch_array($result_query1)) {
            $campos["referencia"] = trim($campos["referencia"]);
            if ($campos["preco_venda"] == '0.00' || $campos["preco_venda"] == '0.00') {
                $btn_selecionar = '';
            } else {
                $btn_selecionar = '<a href="#" type="submit" id="btn_cancelar" class="btn btn-sm btn-success w-48 consulta_prod" cod="' . $campos["referencia"] . '" style="font-size:0.6rem" ><i class="fas fa-check"></i></i></a>
                    <a class="d-none" href="#" style="font-size:0.6rem;"class="btn btn-sm btn-info w-40 consulta_estoque" prod="' . $campos["referencia"] . '" ><i class="fas fa-th-large"></i></a>
                    ';
            }

            //se tabela for 238 (tabela Vivarte zona franca revenda)
            // nao calcula IPI 
            if ($codigo_tabela == '238' or $empresa_orc == 'VIVARTE(AG)') {
                $ipi_produto = 0;
            } else {
                $ipi_produto = $campos["ipi"];
            }

            $resultado_pesq_cli .= '
                <tr class="tr_result">
                    <td style="width:80px;padding:5px 0px;text-align:center;">
                        ' . $btn_selecionar . '
                        
                        
                    </td>
                    <td id="' . $campos["referencia"] . '_COD">' . $campos["referencia"] . '</td>
                    <td id="' . $campos["referencia"] . '_MARCA">' . $campos["marca"] . '</td>
                    <td id="' . $campos["referencia"] . '_DESC">' . $campos["descricao"] . '</td>
                    <td id="' . $campos["referencia"] . '_UM">' . $campos["unidade"] . '</td>
                    <td id="' . $campos["referencia"] . '_IPI">' . number_format($ipi_produto,    2, ',', '.') . '</td>
                    <td id="' . $campos["referencia"] . '_YQTDCXA">' . number_format($campos["qtde_cx"],    2, ',', '.') . '</td>
                    <td class="d-none" id="' . $campos["referencia"] . '_FRACIONA">' . $campos["fraciona"] . '</td>
                    <td class="d-none" id="' . $campos["referencia"] . '_PESBRU">' . number_format($campos["peso"],    2, ',', '.') . '</td>
                    <td id="' . $campos["referencia"] . '_PRCVEN">' . number_format($campos["preco_venda"],    2, ',', '.') . '</td>
                    <td id="' . $campos["referencia"] . '_permitePalletAberto">' . $campos["permitePalletAberto"] . '</td>
                    <td id="' . $campos["referencia"] . '_qtdePallet">' . number_format($campos["qtdePallet"],    2, ',', '.') . '</td>
                    <td id="' . $campos["referencia"] . '_taxaPalletAbertoR">' . number_format($campos["taxaPalletAbertoR"],    2, ',', '.') . '</td>
                    <td id="' . $campos["referencia"] . '_taxaPalletAberto">' . number_format($campos["taxaPalletAberto"],    2, ',', '.') . '</td>
                    <td id="' . $campos["referencia"] . '_precoProdPalletAbertoR">' . number_format($campos["precoProdPalletAbertoR"],    2, ',', '.') . '</td>
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
