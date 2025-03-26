<?php
$apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
$outputType = "json";

//filtros
/*
    codigo statua pedido no bling em (24/ago/21)
    6	Em aberto
    9	Atendido = pedido finalizado (emitido nf)
    12	Cancelado
    15	Em andamento = pedidos com pgto confirmados
    18	Venda Agenciada
    21	Em digitação
    24	Verificado
    10928	Amostras e Bonificações
*/

// Filtros 
$data_de = '01/01/2022';
$data_ate = '31/12/2022';
$data_faixa = 'dataEmissao[' . $data_de . '%20TO%20' . $data_ate . ']';
$status_pedido = 'idSituacao[6,15,10928]';
$filtro_pesq = 'filters=' . $status_pedido;

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0) {
    $api_pagina++;

    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/pedidos/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url . '/&apikey=' . $apikey,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);

    if ($resultado->retorno->erros) {
        $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
        $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
        //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
    } else {
        $api_qtde_pedido = 0;
        $api_qtde_itens = 0;
        foreach ($resultado->retorno->pedidos as $ped) {
            $api_qtde_pedido++;
            $valor_frete_pedido = $ped->pedido->valorfrete;
            $valor_frete_pedido = round($valor_frete_pedido, 2);
            //cabecao pedido
            /*
        
        echo "Num Ped: " . $ped->pedido->numero . " Data Emissao: " . $ped->pedido->data . " Frete:" . $ped->pedido->valorfrete . "<br>";
        echo "Cliente: " . $ped->pedido->cliente->nome . "<br>";

        
        //itens pedido
        echo "PRODUTOS DO PEDIDO:<br>";
        $total_item_pedido = 0;
        */
            foreach ($ped->pedido->itens as $item) {
                $api_qtde_itens++;
                $desconto = str_replace(".", "", $ped->pedido->desconto);
                $desconto = str_replace(",", ".", $desconto);


                $total_item = $item->item->quantidade * $item->item->valorunidade;
                //aplicando desconto do pedido
                $total_item  = $total_item * (1 - ($desconto / 100));
                $total_item = round($total_item, 2);
                $total_item_pedido += $total_item;

                //se cliente PF ou PJ
                $clitipo = '';
                $clidoc_tam = strlen($ped->pedido->cliente->cnpj);
                if ($clidoc_tam == '11') {
                    $clitipo = 'PF';
                } else if ($clidoc_tam == '14') {
                    $clitipo = 'PJ';
                } else {
                    $clitipo = '';
                }
              
                
                /*
            echo "Produto: " . $item->item->codigo;
            echo " - " . $item->item->descricao;
            echo " | qtde: " . $item->item->quantidade;
            echo " | Prc: " . $item->item->valorunidade;
            echo " | Desc Unit: " . $item->item->descontoItem;
            echo " | Totals: " . $total_item . '<br>';

            //echo '---  array ---<br>';
            //echo 'bling'.' | '.'1'.' | '.$item->item->codigo.' | '.$item->item->quantidade.' | '.$item->item->valorunidade.' | '.$total_item.' | '.$ped->pedido->dataPrevista.' | '.'0'.' | '.$ped->pedido->cliente->id.' | '.'0'.' | '.$ped->pedido->numero.' | '.'bling'.' | '.'0'.' | '.'0'.' | '.'0'.' | '.'0'.' | '.$ped->pedido->data.' | '.$ped->pedido->cliente->id.' | '.'0'.' | '.$ped->pedido->cliente->nome.' | '.$ped->pedido->vendedor.' | '.$ped->pedido->cliente->bairro.' | '.$ped->pedido->cliente->cidade.' | '.'bling'.' | '.$ped->pedido->cliente->id.' | '.$ped->pedido->data.' | '.$ped->pedido->dataPrevista.' | '.'0'.' | '.'0'.' | '.$ped->pedido->vendedor.' | '.$ped->pedido->valorfrete.' | '.'msg'.' | '.'msg'.' | '.'0'.' | '.'0'.' | '.$ped->pedido->vendedor.' | '.'000077'.' | '.'CPGTO'.' | '.'0'.' | '.$item->item->codigo.' | '.'0'.' | '.'0'.' | '.'004'.' | '.$item->item->pesoBruto.' | '.'1';
            //echo '<br>---  END array ---<br>';
            echo '<br>';
            */
                $pedido_vendas_array_api[] = array(
                    'ped_ud' => $ped->pedido->loja,
                    'ped_num' => $ped->pedido->numero,
                    'ped_web_num' => $ped->pedido->numeroOrdemCompra,
                    'ped_situacao' => $ped->pedido->situacao,
                    'ped_emissao' => $ped->pedido->data,
                    'ped_previsao' => $ped->pedido->dataPrevista,
                    'ped_valorfrete' => $ped->pedido->valorfrete,
                    'cond_pgto' => $ped->pedido->parcelas[0]->parcela->forma_pagamento->descricao,
                    'item_valor' => $total_item,
                    'item_ref' => trim($item->item->codigo),
                    'item_qtde' => $item->item->quantidade,
                    'item_pesobruto' => $item->item->pesoBruto,
                    'item_pesototal' => round($item->item->pesoBruto * $item->item->quantidade, 2),
                    'cliente_nome' => $ped->pedido->cliente->nome,
                    'cliente_tipo' => $clitipo, 
                    'cliente_uf' => $ped->pedido->cliente->uf,
                    'cliente_cidade' => $ped->pedido->cliente->cidade,
                    'cliente_bairro' => $ped->pedido->cliente->bairro,
                    'vendedor_nome'  => $ped->pedido->vendedor,
                    'est_sugest' => '',
                    'op_sugest' => '',
                    'pc_sugest' => '',
                    'qtde_pend' => '',
                    'situacao' => '',
                    'situacao_color' => '',
                    'data_prev' => '',
                    'doc' => '',
                    'saldo_est' => ''
                );
            }
            //$total_item_pedido = $total_item_pedido + $valor_frete_pedido;
            //echo "Total do pedido: R$" . number_format($total_item_pedido, 2, ',', '.');
            //echo "<br>Total do pedido: R$ ".$total_item_pedido + $valor_frete_pedido;



        }
    }
    usleep(400000);
}

// ====================================== ACESSANDO AGAS ==================================================

$apikey = "7d0dc1fce7ece5e83815bcd73e97122777c6941f9238e72457d7a633bf7e82d03726ad86";
$outputType = "json";

$api_pagina = 0;
$bling_api_cod_erro = 0;

while ($bling_api_cod_erro == 0) {
    $api_pagina++;

    //inicializando CURL =================================================================
    $url = 'https://bling.com.br/Api/v2/pedidos/page=' . $api_pagina . '/' . $outputType . '/&' . $filtro_pesq;

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url . '/&apikey=' . $apikey,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $retorno = curl_exec($curl);
    curl_close($curl);
    //finalizando CURL ====================================================================

    $resultado = json_decode($retorno);

    if ($resultado->retorno->erros) {
        $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
        $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
        //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
    } else {
        $api_qtde_pedido = 0;
        $api_qtde_itens = 0;
        foreach ($resultado->retorno->pedidos as $ped) {
            $api_qtde_pedido++;
            $valor_frete_pedido = $ped->pedido->valorfrete;
            $valor_frete_pedido = round($valor_frete_pedido, 2);
            //cabecao pedido
            /*
        
        echo "Num Ped: " . $ped->pedido->numero . " Data Emissao: " . $ped->pedido->data . " Frete:" . $ped->pedido->valorfrete . "<br>";
        echo "Cliente: " . $ped->pedido->cliente->nome . "<br>";

        
        //itens pedido
        echo "PRODUTOS DO PEDIDO:<br>";
        $total_item_pedido = 0;
        */
            foreach ($ped->pedido->itens as $item) {
                $api_qtde_itens++;
                $desconto = str_replace(".", "", $ped->pedido->desconto);
                $desconto = str_replace(",", ".", $desconto);


                $total_item = $item->item->quantidade * $item->item->valorunidade;
                //aplicando desconto do pedido
                $total_item  = $total_item * (1 - ($desconto / 100));
                $total_item = round($total_item, 2);
                $total_item_pedido += $total_item;

                //se cliente PF ou PJ
                $clitipo = '';
                $clidoc_tam = strlen($ped->pedido->cliente->cnpj);
                if ($clidoc_tam == '11') {
                    $clitipo = 'PF';
                } else if ($clidoc_tam == '14') {
                    $clitipo = 'PJ';
                } else {
                    $clitipo = '';
                }

                /*
            
            echo "Produto: " . $item->item->codigo;
            echo " - " . $item->item->descricao;
            echo " | qtde: " . $item->item->quantidade;
            echo " | Prc: " . $item->item->valorunidade;
            echo " | Desc Unit: " . $item->item->descontoItem;
            echo " | Totals: " . $total_item . '<br>';

            //echo '---  array ---<br>';
            //echo 'bling'.' | '.'1'.' | '.$item->item->codigo.' | '.$item->item->quantidade.' | '.$item->item->valorunidade.' | '.$total_item.' | '.$ped->pedido->dataPrevista.' | '.'0'.' | '.$ped->pedido->cliente->id.' | '.'0'.' | '.$ped->pedido->numero.' | '.'bling'.' | '.'0'.' | '.'0'.' | '.'0'.' | '.'0'.' | '.$ped->pedido->data.' | '.$ped->pedido->cliente->id.' | '.'0'.' | '.$ped->pedido->cliente->nome.' | '.$ped->pedido->vendedor.' | '.$ped->pedido->cliente->bairro.' | '.$ped->pedido->cliente->cidade.' | '.'bling'.' | '.$ped->pedido->cliente->id.' | '.$ped->pedido->data.' | '.$ped->pedido->dataPrevista.' | '.'0'.' | '.'0'.' | '.$ped->pedido->vendedor.' | '.$ped->pedido->valorfrete.' | '.'msg'.' | '.'msg'.' | '.'0'.' | '.'0'.' | '.$ped->pedido->vendedor.' | '.'000077'.' | '.'CPGTO'.' | '.'0'.' | '.$item->item->codigo.' | '.'0'.' | '.'0'.' | '.'004'.' | '.$item->item->pesoBruto.' | '.'1';
            //echo '<br>---  END array ---<br>';
            echo '<br>';
            */
                $pedido_vendas_array_api[] = array(
                    'ped_ud' => $ped->pedido->loja,
                    'ped_num' => $ped->pedido->numero,
                    'ped_web_num' => $ped->pedido->numeroOrdemCompra,
                    'ped_situacao' => $ped->pedido->situacao,
                    'ped_emissao' => $ped->pedido->data,
                    'ped_previsao' => $ped->pedido->dataPrevista,
                    'ped_valorfrete' => $ped->pedido->valorfrete,
                    'ped_volume' => $ped->pedido->transporte->qtde_volumes,
                    'cond_pgto' => $ped->pedido->parcelas[0]->parcela->forma_pagamento->descricao,
                    'item_valor' => $total_item,
                    'item_ref' => trim($item->item->codigo),
                    'item_qtde' => $item->item->quantidade,
                    'item_pesobruto' => $item->item->pesoBruto,
                    'item_pesototal' => round($item->item->pesoBruto * $item->item->quantidade, 2),
                    'cliente_nome' => $ped->pedido->cliente->nome,
                    'cliente_tipo' => $clitipo, 
                    'cliente_uf' => $ped->pedido->cliente->uf,
                    'cliente_cidade' => $ped->pedido->cliente->cidade,
                    'cliente_bairro' => $ped->pedido->cliente->bairro,
                    'vendedor_nome'  => $ped->pedido->vendedor,
                    'est_sugest' => '',
                    'op_sugest' => '',
                    'pc_sugest' => '',
                    'qtde_pend' => '',
                    'situacao' => '',
                    'situacao_color' => '',
                    'data_prev' => '',
                    'doc' => '',
                    'saldo_est' => ''
                );
            }
            //$total_item_pedido = $total_item_pedido + $valor_frete_pedido;
            //echo "Total do pedido: R$" . number_format($total_item_pedido, 2, ',', '.');
            //echo "<br>Total do pedido: R$ ".$total_item_pedido + $valor_frete_pedido;



        }
    }
    usleep(400000);
}






// consultando a data de entrega no orcamento biv
require('../config/conexao.php');
foreach ($pedido_vendas_array_api as $key_ped => $value_ped) {
    $id_ped_web = $value_ped['ped_web_num'];
    //$pedido_vendas_array_api[$key_ped]['ped_emissao'] = 'a0';
    if ($id_ped_web != "") {
        $query_pw = "SELECT pedido_prev_ent, total_volumes FROM md_vendas_pedidos WHERE id = $id_ped_web";
        $result_query_pw = mysql_query($query_pw);
        $qtde_query_pw = mysql_num_rows($result_query_pw);
        if ($qtde_query_pw > 0) {
            while ($campos_pw = mysql_fetch_array($result_query_pw)) {
                if ($campos_pw['pedido_prev_ent'] != '' || $campos_pw['pedido_prev_ent'] != NULL) {
                    //$data_em_pw = date("d/m/y", strtotime($campos_pw['pedido_prev_ent']));
                    $pedido_vendas_array_api[$key_ped]['ped_previsao'] = $campos_pw['pedido_prev_ent'];
                }

                //se pedido do bling sem volume informado pegar do BIV
                if ($campos_pw['total_volumes'] != '' || $campos_pw['total_volumes'] != NULL) {
                    //$data_em_pw = date("d/m/y", strtotime($campos_pw['pedido_prev_ent']));
                    $pedido_vendas_array_api[$key_ped]['ped_volume'] = $campos_pw['total_volumes'];
                }
            }
        }
    }
}


// ORDENANDO POR DATA DE PREVISAO(ENTREGA) E NUM PEDIDO PARA FICAR OS ITENS DO PEIDO JUNTOS
function sortArray($array)
{
    usort($array, function ($a, $b) {
        if ($a['ped_previsao'] == $b['ped_previsao']) {
            return ($a['ped_num'] < $b['ped_num']) ? -1 : 1;
        }
        return ($a['ped_previsao'] < $b['ped_previsao']) ? -1 : 1;
    });
    return $array;
}


$pedido_vendas_array = sortArray($pedido_vendas_array_api);
print("<pre>" . print_r($array_ordenado, true) . "</pre>");
