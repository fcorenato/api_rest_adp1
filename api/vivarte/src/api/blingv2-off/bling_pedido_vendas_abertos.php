    <?php
    $api_bling_status = 'on';
    echo 'api:<hr>';
    if ($api_bling_status == 'on') {
        //BLING CHAMADA  ===============================================
        $apikey = "631120e2eaeb4d44c00c6c4892c1cf9d5ccdd54f82ceb3fdcf2051342e5ebeea6bafb749";
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

        $data = 'filters=dataEmissao[01/01/2000 TO 31/12/2099]';
        $status_pedido = ';idSituacao[6,15]';
        $filter = $data . $status_pedido;

        //executando chamada
        $url = 'https://bling.com.br/Api/v2/pedidos/' . $outputType;
        $retorno = executeGetOrder($url, $filter, $apikey);
        //echo $retorno;
        function executeGetOrder($url, $filter, $apikey)
        {
            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url . '/&' . $filter . '&apikey=' . $apikey);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
            $response = curl_exec($curl_handle);
            curl_close($curl_handle);
            return $response;
        }


        // =============================================================== 
     
        //retorno da API convertido em objeto json
        $resultado = json_decode($retorno);
        var_dump($resultado);

        
    foreach ($resultado->retorno->pedidos as $ped) {
        //cabecao pedido
        $i++;
        echo "Num Ped: ".$ped->pedido->numero." Data Emissao: " .$ped->pedido->data." Frete:".$ped->pedido->valorfrete."<br>";
        echo "Cliente: " .$ped->pedido->cliente->nome."<br>";

        $valor_frete_pedido = $ped->pedido->valorfrete;
        $valor_frete_pedido = round($valor_frete_pedido,2);
        //itens pedido
        echo "PRODUTOD DO PEDIDO:<br>";
        $total_item_pedido = 0;
        foreach ($ped->pedido->itens as $item) {
            $total_item = $item->item->quantidade * $item->item->valorunidade;
            $total_item = round($total_item,2);
            $total_item_pedido += $total_item;
            echo "Produto: " . $item->item->codigo;
            echo " - " . $item->item->descricao;
            echo " | qtde: " . $item->item->quantidade;
            echo " | Prc: " . $item->item->valorunidade;
            echo " | Desc Unit: " . $item->item->descontoItem;
            echo " | Totals: " . $total_item.'<br>';

            echo '---  array ---<br>';
            echo 'bling'.' | '.'1'.' | '.$item->item->codigo.' | '.$item->item->quantidade.' | '.$item->item->valorunidade.' | '.$total_item.' | '.$ped->pedido->dataPrevista.' | '.'0'.' | '.$ped->pedido->cliente->id.' | '.'0'.' | '.$ped->pedido->numero.' | '.'bling'.' | '.'0'.' | '.'0'.' | '.'0'.' | '.'0'.' | '.$ped->pedido->data.' | '.$ped->pedido->cliente->id.' | '.'0'.' | '.$ped->pedido->cliente->nome.' | '.$ped->pedido->vendedor.' | '.$ped->pedido->cliente->bairro.' | '.$ped->pedido->cliente->cidade.' | '.'bling'.' | '.$ped->pedido->cliente->id.' | '.$ped->pedido->data.' | '.$ped->pedido->dataPrevista.' | '.'0'.' | '.'0'.' | '.$ped->pedido->vendedor.' | '.$ped->pedido->valorfrete.' | '.'msg'.' | '.'msg'.' | '.'0'.' | '.'0'.' | '.$ped->pedido->vendedor.' | '.'000077'.' | '.'CPGTO'.' | '.'0'.' | '.$item->item->codigo.' | '.'0'.' | '.'0'.' | '.'004'.' | '.$item->item->pesoBruto.' | '.'1';
            echo '<br>---  END array ---<br>';
            echo '<br>';
        }
        $total_item_pedido = $total_item_pedido + $valor_frete_pedido;
        echo "Total do pedido: R$" . number_format($total_item_pedido, 2, ',', '.');
        //echo "<br>Total do pedido: R$ ".$total_item_pedido + $valor_frete_pedido;

        
        echo "<hr>";
    }

    Echo "<br> Total de pedidos: ".$i;

    echo  '<hr>';
    echo "<pre>" . print_r($resultado, true) . "</pre>";

    }


    ?>
