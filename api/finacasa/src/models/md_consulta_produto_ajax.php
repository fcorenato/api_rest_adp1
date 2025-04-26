<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');

//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($un_tabelas == '') {$un_tabelas=1;}
    $tabelas_und = explode(',', $un_tabelas);
    $tabelas_und_count = count($tabelas_und);
    $und_pesq_sql = '(';
    foreach ($tabelas_und as $key => $value) {
        $tabelas .= 'ind' . $key . ' tab:' . $value;
        $und_pesq_sql .= "'" . $value . "',";
    }
    $und_pesq_sql .= "'0')";

    $codigo_produto = trim(strtoupper($_POST['produto']));

    //verificando se foi definito fabricantes para unidade , se sim trazer apenas produtos deles
    if ($un_fabricantes != "") {
        $filtro_fabricante = " and p.marca in ($un_fabricantes) ";
    }

    require('../config/conexao.php');

    //gerando relatório
    $query1 = "
        select p.referencia, p.descricao, p.unidade, p.qtde_cx, p.ipi, p.marca
        from md_cad_produtos AS p 
        LEFT JOIN md_estoque_bling AS e ON (p.referencia = e.referencia)
        WHERE p.status = 'A'
        and prod_pai != 'PAI' 
        and (p.referencia LIKE '%$codigo_produto%' OR p.descricao LIKE '%$codigo_produto%')
        and (p.tipo = 'PA' OR p.tipo = 'PI' OR p.tipo = 'ME')
        $filtro_fabricante
        group by  p.referencia, p.descricao, p.unidade, p.qtde_cx, p.ipi, p.marca
        order by p.referencia

 
        ";

    $result_query1 = mysql_query($query1);
    $qtde_query1 = mysql_num_rows($result_query1);

    if ($qtde_query1 == 0) {
        $resultado_rel .= '
				 <tr>
		              <td > REFERENCIA NAO ENCONTRADA </td>
		          </tr>';
    } else {
        $ref_pesq_sql = '(';
        while ($campos = mysql_fetch_array($result_query1)) {
            $ref_pesq_sql .= "'" . $campos['referencia'] . "',";
            $ref_encontradas[trim($campos['referencia'])] = array(trim($campos['descricao']), $campos['unidade'],  $campos['qtde_cx'],  $campos['ipi'],  $campos['marca']);
        }
        $ref_pesq_sql .= "'0')";


        //PESQUISANDO AS REFERENCIAS NAS TABELAS DE PRECO DA UNIDADE DO USUARIO

        //se nao informado tabelas no cadastro da unidade
        if ($un_tabelas == '1') {
            $query_prc = "
                select p.referencia, p.preco
                from md_cad_produtos AS p 
                WHERE p.status = 'A'
                and (p.referencia LIKE '%$codigo_produto%' OR p.descricao LIKE '%$codigo_produto%')
      
                $filtro_fabricante
                group by  p.referencia, p.preco
                order by p.referencia
            ";

            $result_query2 = mysql_query($query_prc);
            $qtde_query2 = mysql_num_rows($result_query2);

            if ($qtde_query2 > 0) {
                while ($da1 = mysql_fetch_array($result_query2)) {
                    $ref_precos[trim($da1['referencia']) . '-1'] = $da1['preco'];
                }
            }
        } else {
            $query_prc = "
                select ti.codigo_tabela, ti.referencia, ti.preco_venda
                from md_vendas_tabpreco_itens AS ti
                LEFT JOIN md_vendas_tabpreco AS tp ON (tp.codigo = ti.codigo_tabela)
                WHERE tp.status = 'A'
                AND ti.status = 'A'
                and ti.referencia in $ref_pesq_sql
                and ti.codigo_tabela in $und_pesq_sql
                order by ti.codigo_tabela, ti.referencia
            ";

            $result_query2 = mysql_query($query_prc);
            $qtde_query2 = mysql_num_rows($result_query2);

            if ($qtde_query2 > 0) {
                while ($da1 = mysql_fetch_array($result_query2)) {
                    $ref_precos[trim($da1['referencia']) . '-' . $da1['codigo_tabela']] = $da1['preco_venda'];
                }
            }
        }






        $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-head-fixed text-nowrap table-striped w-100">
              <thead>
              <tr class="">
                <th>Estoque</th>
                <th>Codigo</th>
                <th>Descrição</th>
                <th>Fornecedor</th>
                <th>UM</th>
                <th>Qtde Cx</th>
                ';
        //listando colunas com as as tabela da unidade
        foreach ($tabelas_und as $key => $value) {
            $resultado_rel .= '<th>Preço Tab. ' . $value . '</th>';
        }
        $resultado_rel .= '
                
              </tr>
              </thead>
              <tbody>

              ';

        //percorrendo arrays de referencia e preco montando o corpo da tabela resultado
        foreach ($ref_encontradas as $key => $value) {
            if ($value[5] < 0) {
                $value[5] = 0;
            }
            $resultado_rel .= '
                <tr>
                    <td align="center"><a href="#" class="text-success consulta_estoque" prod="' . $key . '" ><i class="fas fa-th-large"></i></a></td>
                     <td>' . $key . '</td>
                     <td>' . $value[0] . '</td>
                     <td>' . $value[4] . '</td>
                     <td>' . $value[1] . '</td>
                     <td align="right">' . number_format($value[2], 2, ',', '.') . '</td>
                ';
            //listando colunas com as as tabela da unidade
            foreach ($tabelas_und as $key2 => $value2) {
                //se tabela for 238 (tabela Vivarte zona franca revenda)
                // nao calcula IPI 
                if ($value2 == '238') {
                    $prc_venda = $ref_precos[$key . '-' . $value2];
                } else {
                    $prc_venda = $ref_precos[$key . '-' . $value2] + ($ref_precos[$key . '-' . $value2] * $value[3] / 100);
                }

                $resultado_rel .= '<td align="right"><span style="float: left;">R$</span> ' . number_format($prc_venda, 2, ',', '.') . '</td>';
            }
            $resultado_rel .= '
                     
                 </tr>
               ';
        }


        $resultado_rel .= '
            </tbody>
            </table>
            </div>';
    } // fim do if qtde_result1

} // fim do POST

echo $resultado_rel;
