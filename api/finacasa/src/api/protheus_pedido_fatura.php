<?php
/* PEDIDO FATURADO NO PROTHEUS */

$api_protheus_status = 'on';

if ($api_protheus_status == 'on') {
    // API - RECEBENDO DO BLING ESTOQUE DO PRODUTO DO ARMAZEM HORIZONTE 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require('../config/conexao.php');

        header("Access-Control-Allow-Origin: *");
        header('Cache-Control: no-cache, must-revalidate');
        header("Content-Type: text/plain; charset=UTF-8");
        header("HTTP/1.1 200 OK");

        //funcao para verificar se recebido json valido 
        function isValidJSON($str)
        {
            json_decode($str);
            return json_last_error() == JSON_ERROR_NONE;
        }

        //recebendo json via Post enviado pelo BLING
        $json_params = file_get_contents("php://input");

        if (strlen($json_params) > 0 && isValidJSON($json_params)) {
            $dados = json_decode($json_params);
            $nf = $dados->nota;
            $nPed = $dados->codPed;
            $chaveNfe = $dados->chave;
            $cod_orc = $dados->codOrc;
            //echo $nf;

            //echo '<pre>' . print_r(json_decode($json_params), true) . '</pre>';
            //echo  $nf;

            //salvando arquivo txt no log api
            $conteudo = $json_params;
            $fp = fopen("log/orc-faturado-nf-$nf.txt","wb");
            fwrite($fp, $conteudo);
            fclose($fp);

            //registrando fatuamento
            //se campo nulo atualizar com '' para poder concatenar
            $upd_nfe_num = mysql_query("UPDATE md_vendas_pedidos SET nfe_num='' WHERE id = $cod_orc and nfe_num is null");
            $upd_nfe_chave = mysql_query("UPDATE md_vendas_pedidos SET nfe_chave='' WHERE id = $cod_orc and nfe_chave is null");

            //registrando notas e chaves
            $upd = mysql_query("UPDATE md_vendas_pedidos SET nfe_num=concat(nfe_num, '$nf,'), nfe_chave=concat(nfe_chave, '$chaveNfe,'), status = 'F' WHERE id = $cod_orc");

            $data_hoje = date("Y-m-d H:i:s");
            
            $link = '- <a href="https://www.nfe.fazenda.gov.br/portal/consultaRecaptcha.aspx?tipoConsulta=resumo&nfe=';
            $link .= $chaveNfe;
            $link .='&tipoConteudo=7PhJ+gAVw2g=" target="_blank"> Consultar Nfe</a>';
            
            if ($upd) {
                //registrando evento
                $evento_ped_insert = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento, descricao) VALUES ('$cod_orc', '$data_hoje', 0, 'Faturado', 'Pedido faturado. Nota fiscal Nº: $nf $link')") or die(mysql_error());
                echo '200';
            } else {
                echo '400';
            }
            
        }
    } // fim do POST
}
