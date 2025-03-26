<?php
date_default_timezone_set('America/Sao_Paulo');
//recebendo e tratando periodo selecioando
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $cep = strtoupper($_POST['cep']);


    //PESQUISANDO NO BANCO DE DADOS MYSQL
    require('../config/conexao.php');
    //FUNCAO TRIM PRA REMORAR POSSIVEIS ESPACOS ANTES E DEPOIS DO NUM DO CEP
    $pesquisa = mysql_query("SELECT * FROM md_vendas_entregas
                           WHERE (TRIM(cep_de) <= TRIM('$cep') and TRIM(cep_ate) >= TRIM('$cep'))");
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_rel = '<table id="tabela_frete" class="table table-sm table-hover table-striped table-bordered table-head-fixed tabela_carteira sortable">
            <tr>
                <td>Ops! Frete não calculado. Nenhum registro encontrado.</td>
            </tr>
        </table>';
    } else {

        $resultado_rel = '
              <table id="tabela_frete" class="table table-sm table-hover table-striped table-bordered table-head-fixed tabela_carteira sortable">
              <thead>
              <tr class="">
                <th>id</th>
                <th>uf</th>
                <th>nome</th>
                <th>trasnp cod</th>
                <th>transp nome</th>
                <th>tipo_frete</th>
                <th>cep_de</th>
                <th>cep_ate</th>
                <th>valor_de</th>
                <th>valor_ate</th>
                <th>peso_de</th>
                <th>peso_ate</th>
                <th>frete_min</th>
                <th>valor</th>
                <th>advalorem</th>
                <th>kg_adicional</th>
                <th>prazo</th>
             
              </tr>
              </thead>
              <tbody>

              ';

        while ($dados = mysql_fetch_array($pesquisa)) {
            $resultado_rel .= '
            <tr class="itens_frete">
                  <td class="frete_id">' . $dados["id"] . '</td>
                  <td class="frete_uf">' . $dados["uf"] . '</td>
                  <td class="frete_nome">' . $dados["nome"] . '</td>
                  <td class="frete_transp_cod">' . $dados["transp_cod"] . '</td>
                  <td class="frete_transp_nome">' . $dados["transp_nome"] . '</td>
                  <td class="frete_tipo_frete">' . $dados["tipo_frete"] . '</td>
                  <td class="frete_cep_de">' . $dados["cep_de"] . '</td>
                  <td class="frete_cep_ate">' . $dados["cep_ate"] . '</td>
                  <td class="frete_valor_de">' . $dados["valor_de"] . '</td>
                  <td class="frete_valor_ate">' . $dados["valor_ate"] . '</td>
                  <td class="frete_peso_de">' . $dados["peso_de"] . '</td>
                  <td class="frete_peso_ate">' . $dados["peso_ate"] . '</td>
                  <td class="frete_frete_min">' . $dados["frete_min"] . '</td>
                  <td class="frete_valor">' . $dados["valor"] . '</td>
                  <td class="frete_ad_valorem">' . $dados["ad_valorem"] . '</td>
                  <td class="frete_kg_adicional">' . $dados["kg_adicional"] . '</td>
                  <td class="frete_prazo">' . $dados["prazo"] . '</td>
            </tr>';

        }
        $resultado_rel .= '
            </tbody>
            </table>
            </div>';
    }


} // fim do POST

echo  $resultado_rel;
