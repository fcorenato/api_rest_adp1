<?php
date_default_timezone_set('America/Sao_Paulo');

$result = 0;
//recebendo e tratando dados
// ========================   atualizado estoque  ========================
$arquivo = '../../import/fretes.txt';
if (file_exists($arquivo)) {
    if (filesize($arquivo) != 0) { //verifica se o arquivo esta vazio
        //acesando banco de dados
        require('../config/conexao.php');

        $limpa_tabela_entregas = mysql_query("TRUNCATE md_vendas_entregas;") or die(mysql_error());
        $limpa_tabela_entregas_fxcep = mysql_query("TRUNCATE md_vendas_entregas_fxcep;") or die(mysql_error());
        $arq = fopen($arquivo, 'r');
        while (!feof($arq)) {
            $dados[] = fgets($arq); // cria um array com o conteudo do arquivo 
        }
        foreach ($dados as $linha) {
            $explode = explode(';', $linha);
            list($id, $uf, $nome, $tranp_cod, $transp_nome, $cep_de, $cep_ate, $valor_de, $valor_ate, $peso_de, $peso_ate, $valor, $ad_valorem, $kg_adicional, $prazo) = $explode;

            //$cep_de = substr($cep_de,0,8);
            //$cep_ate = substr($cep_ate,0,8);

            $valor_de = str_replace(",", ".", str_replace(".", "", $valor_de));
            $valor_ate = str_replace(",", ".", str_replace(".", "", $valor_ate));
            $peso_de = str_replace(",", ".", str_replace(".", "", $peso_de));
            $peso_ate = str_replace(",", ".", str_replace(".", "", $peso_ate));
            $valor = str_replace(",", ".", str_replace(".", "", $valor));
            $ad_valorem = str_replace(",", ".", str_replace(".", "", $ad_valorem));
            $kg_adicional = str_replace(",", ".", str_replace(".", "", $kg_adicional));
            $prazo = str_replace(",", ".", str_replace(".", "", $prazo));
            $created_at = date("Y-m-d H:i:s");
            $updated_at = date("Y-m-d H:i:s");
            echo '-'.$id;
            $result = mysql_query("INSERT INTO `md_vendas_entregas` (`id`, `uf`, `nome`, `transp_cod`, `transp_nome`, `peso_de`, `peso_ate`, `valor`, `ad_valorem`, `kg_adicional`, `prazo`, `created_at`, `updated_at`, `status`) VALUES ($id, '$uf', '$nome', '$tranp_cod', '$transp_nome', '$peso_de', '$peso_ate', '$valor', '$$ad_valorem', '$kg_adicional', '$prazo', '$created_at', '$updated_at', 'A');") or die(mysql_error());

            
        } //fim do foreach
        //file_put_contents($arquivo, ''); // zerando arquivo de importacao
        $result = 1;

    } // fim do if que testa se o arquivo esta vazio
} // fim  do if que testa se o arquivo esta existe

//salvando

echo $result;
