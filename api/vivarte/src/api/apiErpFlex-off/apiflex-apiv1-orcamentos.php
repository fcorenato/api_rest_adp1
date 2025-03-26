<?php
    //usuarios
    $user_CS = 'apidivetro0:vetro07api';
    $user_DM = 'apidivetro1:vetro07api';
    $user_DA = 'apidivetro2:vetro07api';
    $user_DI = 'apidivetro3:vetro07api';
    $user_VH = 'apivivarte1:vetro07api';
    $user_VC = 'apivivarte2:vetro07api';
    //user_em uso no curl
    $user_curl = $user_CS;
    
    // filiais ID
    $filiais_id =  array(
        4997 => 'CS',
        4988 => 'DA',
        4989 => 'DA',
        4990 => 'DI',
        4991 => 'VH',
        4992 => 'VC',
    );

    //APIS 
    /*
    api_v2/ncm - NCM com IPI
    api_v2/sku - 
    /api/venda/solicitacoes - orcamentos
     */

    //montando a consulta:
    $cs = 'https://sistema.erpflex.com.br/'; //ambiente
    $cs .= '/api/venda/solicitacoes?pagina=500';  
    // /api/venda/solicitacoes?pagina=20'
    //$cs .= '?ean=7898474577356'; // api  solicitacoes/busca?documento=0000000040
   // $cs .= ''; // filtro

    //inicializando CURL
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $cs,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_USERPWD => $user_curl,
        
    ));
    $response = curl_exec($curl);
    //  $resultado = json_decode($response);
    curl_close($curl);

    $xml = simplexml_load_string($response);
    $json = json_encode($xml);
    $resultado = json_decode($json);
    /*
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
					<th>Situação</th>
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
            if (is_object($orc->cliente)) {
                $cliente_pedido ="S";
            } else {
                $cliente_pedido = substr($orc->cliente, 0, 25);
            }

            if (is_object($orc->vendedor)) {
                $vendedor_pedido ="S";
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
                        $nome_item_pedido =" ";
                    } else {
                        $nome_item_pedido = $item->nome;
                    }

                    $valor_total_item = $item->valor_item + $item->ipi_valor - $item->valor_desconto;
                    $pedito_item .= '
                    <td>' . $valor_total_item . '</td>
                    <td>' . $nome_item_pedido . '</td>
                    <td></td>
                    <td>' . number_format($item->quantidade,    2, ',', '.') . '</td>
                    ';
                    $carteira_processada .= $pedido_cabecalho.$pedito_item.$pedido_cauda;
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
                        $nome_item_pedido =" ";
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
                    $carteira_processada .= $pedido_cabecalho.$pedito_item.$pedido_cauda;
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

    echo $carteira_processada;
    */
/*
      
    
    if ($resultado == '') {
        echo 'RESULTADO VAZIO. IMPRIMINDO VARIAVEL PRIMÁRIA: <br><hr>';
        echo $response;
    }
    echo '<hr>';
    echo "<pre>" . print_r($resultado, true) . "</pre>";

    $sp = ' | ';
    $br = '<br>';
    foreach ($resultado->data->solicitacao as $orc) {
        //cabecao pedido
        $i++;
        $total_pedido_frete = $orc->total_pedido + $orc->valor_frete;
        //$preco_venda = round($tab->preco,2);
        echo "filial: ".$filiais_id[$orc->id_empresa].
        " - doc: ".$orc->documento.
        " - cliente: " .$orc->cliente.
        " - Emissao: ".$orc->emissao.
        " - Entrega: ".$orc->data_prev_faturamento.
        " - frete: ".$orc->valor_frete.
        " - Valor pedido: ".$orc->total_pedido.
        " - total + frete: ".$total_pedido_frete.
        "<br>";

        //echo $orc->documento.$sp;
        $it = 0;
        echo $br;
        if (is_array($orc->produtos->produto)){
            foreach ($orc->produtos->produto as $item) {  
                $valor_total_item = $item->valor_item + $item->ipi_valor - $item->valor_desconto;
                echo "-------> Produto: " . $item->nome." - Qtde: " .$item->quantidade." - Prc IPI e Desc: ".$valor_total_item."<br>";
                $it++;
    
            }
        } else {
            foreach ($orc->produtos as $item) {  
                $valor_total_item = $item->valor_item + $item->ipi_valor - $item->valor_desconto;
                echo "-------> 1Produto: " . $item->nome." - Qtde: " .$item->quantidade." - Prc: ".$valor_total_item."<br>";
                $it++;
    
            }
        }
        
        Echo "<br> Total de itens do orcamento: ".$it;

        //$valor_frete_pedido = round($valor_frete_pedido,2);
                
        echo "<hr>";
    }
    Echo "<br> Total de Orcamentos: ".$i;
    */
    
    
?>
