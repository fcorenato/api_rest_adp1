<?php

//montando Json do pedido a ser enviado
require('../config/conexao.php');
//pesquisando dados do orçamento
// require('../../src/config/SUsuario.php');
require('../../sys_functions.php');


// $ID_ORC = '3412';

$pv = mysql_query("SELECT * FROM md_vendas_pedidos as p 
                    LEFT JOIN md_vendas_cpgto as cp ON p.cond_pgto = cp.codigo
                    LEFT JOIN sys_unidades as un ON p.unidade_codigo = un.codigo
                    WHERE p.id = $ID_ORC ")  or die(mysql_error());
$linhas_pv = mysql_num_rows($pv);
if ($linhas_pv == 0) {
    $result = 'API: Erro ao pesquisar o orçamento. Er001';
    $integra_bling = false;
} else {
    $integra_bling = true;
    $dadospv = mysql_fetch_array($pv);
    if ($dadospv['frete_tipo'] == 'C') {
        $frete_tipo = '0';
    } else {
        $frete_tipo = '1';
    }

    $empresa = $dadospv['empresa'];
    $orc_split_pgto = $dadospv['orc_split_pgto'];


    if (($dadospv['cod_ibge'] == 0) or ($dadospv['cod_ibge'] == '') or (is_null($dadospv['cod_ibge'])) or (!isset($dadospv['cod_ibge'])) or (strlen($dadospv['cod_ibge']) <> 7)) {
        //$cod_ibge_cidade = '1234567';
        //chamada API viacep
        $cep = $dadospv['cep'];
        include('../api/viacep.php');
        $cod_ibge_cidade = $viacep_ibge;

        //salvando codigo ibge no pedido
        $atu_pv_ibge = mysql_query("UPDATE md_vendas_pedidos SET cod_ibge='$cod_ibge_cidade' WHERE id = $ID_ORC");
    } else {
        $cod_ibge_cidade = $dadospv['cod_ibge'];
    }

    if ($dadospv['email'] == '') {
        $email_cli = 'email@v.com.br';
    } else {
        $email_cli = $dadospv['email'];
    }

    $un_cod_protheus = $_SESSION["un_codigo"];
    $cod_vend_usr_logado = $_SESSION["cod_vend"];

    //idenficando codigo da unidade do usuario que cadastrou o orçamento    
    $pv_undv = mysql_query("SELECT un.bling_vend_id FROM `sys_unidades` as un
    LEFT JOIN sys_usuarios as u ON u.unidade_codigo = un.codigo
    WHERE u.codigo = $dadospv[orc_created_user]")  or die(mysql_error());
    $linhas_pv_undv = mysql_num_rows($pv_undv);
    if ($linhas_pv_undv == 0) {
        $cod_und_vend1_bling1 = '';
    } else {
        $dados_pv_undv = mysql_fetch_array($pv_undv);
        //formatando o codigo da unidade para 6 digitos. completando com zeros a esquerda
        // $cod_und_vend1 = str_pad($dados_pv_undv['cod_protheus'], 6, '0', STR_PAD_LEFT);
        $cod_und_vend1_bling1 = $dados_pv_undv['bling_vend_id'];
    }

    // verificando se cond. pgto tem ID no bling
    $erro_msg = '';
    if ($dadospv['id_bling_cpgto'] == NULL or $dadospv['id_bling_cpgto'] == 0) {
        $erro_msg .= ' ERRO: Cond. Pgto. não possui ID do Bling';
    }


    //SE PGTO SPLIT
    if ($orc_split_pgto == 'S' and $empresa == 'VIVARTE') {
        $comissao1 = 0;
    } else {
        $comissao1 = 25;
    }

    $cidade = remove_accents($dadospv['cidade']);
}

// ========== pesquisando itens do orcamento =================
$query_pesq_itens = "SELECT i.*, p.id_bling_prod FROM md_vendas_pedidos_itens as i 
    LEFT JOIN md_cad_produtos as p ON i.codigo = p.referencia
    WHERE i.pedido_id = $ID_ORC
    AND i.status = 'A' AND i.qtde > 0 ";

// echo '<hr>'.$query_pesq_itens.'<hr>';

$pesquisa_itens = mysql_query($query_pesq_itens)  or die(mysql_error());


$linhas_itens = mysql_num_rows($pesquisa_itens);
if ($linhas_itens == 0) {
    $result = 'API: Erro ao pesquisar itens do orçamento. Er002';
    $integra_bling = false;
} else {
    $integra_bling = true;
    $item = 1;
    $total_valor_ipi = 0;
    while ($dadosi = mysql_fetch_array($pesquisa_itens)) {
        $id_bling_prod = $dadosi["id_bling_prod"];
        // $preco_tab = (float) number_format($dadosi["prc_tab"] - $dadosi["desconto"], 2, '.', '');
        
        //verificando se item é pallet fracionado
        // if ($dadosi["permitePalletAberto"] == 'S' and $dadosi["app_tx_pal"] == 'S') {
            if ($dadosi["taxaPalletAbertoR"] <> 0) {
                $dadosi["prc_tab"] = $dadosi["prc_tab"] + ($dadosi["taxaPalletAbertoR"] / $dadosi["qtde"]);
            } else if ($dadosi["taxaPalletAberto"] <> 0) {
                $dadosi["prc_tab"] = $dadosi["prc_tab"] + ($dadosi["taxaPalletAberto"] * $dadosi["prc_tab"] /100);
            } else if ($dadosi["precoProdPalletAbertoR"] <> 0) {
                $dadosi["prc_tab"] = $dadosi["precoProdPalletAbertoR"];
            }
            // }
            
            // $preco_tab = $dadosi["prc_tab"] - $dadosi["desconto"];
            $preco_tab = round($dadosi["prc_tab"] - $dadosi['desconto'], 2);


        $dados_itens .= '
            {
                "quantidade": ' . $dadosi['qtde'] . ',
                "valor": ' . $preco_tab . ',
                "descricao": "' . $$dadosi['descricao'] . '",
                "codigo": "' . $dadosi['codigo'] . '",
                "unidade": "' . $dadosi['unidade'] . '",
                "desconto": 0,
                "aliquotaIPI":' . $dadosi['ipi'] . ',
                
                "produto": {
                    "id": "' . $id_bling_prod . '"
                },
                "comissao": {
                    "base": "0",
                    "aliquota": "0",
                    "valor": "0"
                }
            },';

        $item++;
        $total_valor_item = round($dadosi['qtde'] * $preco_tab, 2);

        //se tem ipi, calcula total tributo ipi
        if ($dadosi['ipi'] > 0) {
            $total_valor_ipi += round($total_valor_item * $dadosi['ipi'] / 100, 2);
        }
    }

    // Remove a última vírgula da string 
    $dados_itens = rtrim($dados_itens, ',');
}
// print("ITENS DO PEDIDO: <pre>" . print_r($dados_itens, true) . "</pre>");


// ================================================================================================
if ($erro_msg != '') {
    $bling_api_cod_erro_msg = $erro_msg;
    $integra_bling = false;
    $integrou = false;
}


// $integra_bling = false;

if ($integra_bling) {
    //data conversao do pedido
    $pedidopvconv = date("Y-m-d");

    //buscando cliente ou cadastrando
    $cpf_cnpj_pesq = $dadospv['cpf_cnpj'];

    //se nao contribuinte
    $ie_cliente = $dadospv['contribuinte'] == 9 ? '' :  $dadospv['insc_estadual'];

    $dados_cli = '{
        "nome": "' . $dadospv['cliente_razao'] . '",
        "tipo": "' . $dadospv['cliente_tipo'] . '",
        "situacao": "A",
        "numeroDocumento": "' . $cpf_cnpj_pesq . '",
        "indicadorIe": ' . $dadospv['contribuinte'] . ',
        "ie": "' . $ie_cliente . '",
        "email": "' . $email_cli . '",
        "telefone": "' . $dadospv['telefone'] . '",
        "celular": "' . $dadospv['telefone'] . '",
        "endereco": {
            "geral": {
                "endereco": "' . $dadospv['endereco'] . '",
                "cep": "' . $dadospv['cep'] . '",
                "bairro": "' . $dadospv['bairro'] . '",
                "municipio": "' . $cidade . '",
                "uf": "' . $dadospv['uf'] . '",
                "numero": "' . $dadospv['end_num'] . '",
                "complemento": " "
                }
            }
        }';

    //API para atualizar ou incluir cliente    
    include_once('bv3_cliente_post.php');


    //token 
    include('bv3_get_token.php');
    // echo "Token Vivarte<hr>";
    // echo '<hr>token = '. $token . '<hr>';

    //cond pgto id VIVARTE
    $pv_id_bling_cpgto = $dadospv['id_bling_cpgto'];

    //id loja/tabela de preco VIVARTE
    $pv_id_loja = '0';



    //id cliente no bling
    $id_cli_bling = $id_cli_bling_vivarte;

    //cod_vendedor_bling
    $bling_vend_id = $cod_und_vend1_bling1;

    //categoria venda
    $categoria = '14627480772';


    // echo "<hr>Bling cliente id Vivarte = $id_cli_bling_vivarte e AGAS = $id_cli_bling_agas <hr>";

    // echo "Dados do cliente <hr>";
    // print("<pre>" . print_r($dados_cli, true) . "</pre>");

    //Calculando parcelas
    if ($dadospv['qtde_parc'] > 1) {

        $valor_total = $dadospv['total_final'] + $dadospv['credito1'];
        $condicao = $dadospv['condicao'];
        $cond_array = explode(',', $condicao);
        $num_parcelas = sizeof($cond_array);

        $valor_parcela = round($valor_total / $num_parcelas, 2);
        $sobra = round($valor_total - ($valor_parcela * $num_parcelas), 2);
        $parcelas = array();
        // Adiciona a sobra à primeira parcela
        $parcelas[0] = $valor_parcela + $sobra;
        // Adiciona o valor das demais parcelas
        for ($i = 1; $i < $num_parcelas; $i++) {
            $parcelas[$i] = $valor_parcela;
        }

        // dados parcelas
        foreach ($parcelas as $indice => $valor) {
            $data_venc_parc = date('Y-m-d', strtotime("+$cond_array[$indice] days", strtotime($pedidopvconv)));
            $dados_parcelas .= '
                {
                    "id": 17963513078,
                    "dataVencimento": "' . $data_venc_parc . '",
                    "valor":' . $valor . ',
                    "formaPagamento": {
                        "id": ' . $pv_id_bling_cpgto . '
                    },
                    "observacoes": " "
                },';
        }
        // Remove a última vírgula da string 
        $dados_parcelas = rtrim($dados_parcelas, ',');
    } else {
        $valor_total = $dadospv['total_final'] + $dadospv['credito1'];;
        $dados_parcelas = '
            {
                "id": 112233,
                "dataVencimento": "' . $pedidopvconv . '",
                "valor":' . $valor_total . ',
                "formaPagamento": {
                    "id": ' . $pv_id_bling_cpgto . '
                },
                "observacoes": " "
            }';
    }

    $dados_pv = '
 
    {
        "contato": {
            "id": "' . $id_cli_bling . '",
            "tipoPessoa": "' . $dadospv['cliente_tipo'] . '",
            "numeroDocumento": "' . $cpf_cnpj_pesq . '"
        },
        "data": "' . $pedidopvconv . '",
        "dataPrevista": "' . $PED_PREV_ENT . '",
        "dataSaida": "' . $PED_PREV_ENT . '",
        "itens": [
            ' . $dados_itens . '
            
        ],
        "tributacao": {
            "totalICMS": 0,
            "totalIPI": ' . $total_valor_ipi . '
        },
        "parcelas": [
            ' . $dados_parcelas . '
            
        ],

        "loja": {
            "id": ' . $pv_id_loja . '
        },
        "numeroPedidoCompra": "' . $ID_ORC . '",
        "observacoes": "' . str_replace(array("\r", "\n"), ' ', $dadospv['msg_pedido']) . '",
        "observacoesInternas": "' . str_replace(array("\r", "\n"), ' ', $dadospv['msg_interna']) . '",
        "desconto": {
            "valor": "0",
            "unidade": "REAL"
        },
        "categoria": {
            "id": "' . $categoria . '"
        },

        "transporte": {
            "fretePorConta": ' . $frete_tipo . ',
            "frete": "' . $dadospv['frete_valor'] . '",
            "quantidadeVolumes": "' . $dadospv['total_volumes'] . '",
            "pesoBruto": "' . $dadospv['total_peso'] . '",
            "prazoEntrega": "' . $dadospv['transp_prazo'] . '",
            "contato": {
            "nome": " "
            
            },
            
            "volumes": [
            {
                "id": "",
                "servico": "",
                "codigoRastreamento": ""
            }
            
            ]
        },
        "vendedor": {
            "id": "' . $bling_vend_id . '"
        },
        
        "taxas": {
            "taxaComissao": "0",
            "custoFrete": "0",
            "valorBase": "0>"
        }
    }  
  ';

    // print("<pre>" . print_r($dados_pv, true) . "</pre>");

    //inicializando CURL =================================================================
    $url = "https://api.bling.com.br/Api/v3/pedidos/vendas";
    $curl = curl_init();

    // Define as opções do cURL individualmente
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $dados_pv);


    // Define os cabeçalhos HTTP
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token . '',
        'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
    ));

    $response = curl_exec($curl);
    // var_dump($response);
    curl_close($curl);

    //finalizando CURL ====================================================================
    $resultado = json_decode($response);
    // print("<pre>" . print_r($resultado, true) . "</pre>");

    if ($resultado->error) {
        foreach ($resultado->error->fields as $key => $field) {
            $campos .= " code: $field->code | msg: $field->msg | element: $field->element | namespece: $field->namespace";
        }
        $msg = 'Erro api bling v3 (bv3_cliente_post.php AC) = type: ' . $resultado->error->message . ' - ' . $resultado->error->description . ' - Detalhes: ' . $campos;

        $bling_api_cod_erro = $resultado->error->message;
        $bling_api_cod_erro_msg = $msg;

        $msg .= ' |  pedido = ' . $ID_ORC;

        echo '===>  ' . $msg . ' <===';
        echo '<br>Dados do pedido' . $dados_pv . '<hr>';

        $integrou = false;
    } else {
        $bling_pv_id = $resultado->data->id;
        $bling_api_pedidovenda = $resultado->data->id;
        $bling_pv_nome = $resultado->data->numero;

        // echo 'Pedido cadastrado ID= ' . $bling_pv_id . '<br>';

        // BUSCANDO NUMERO DO PEDIDO PELO ID
        //inicializando CURL =================================================================
        $url = "https://api.bling.com.br/Api/v3/pedidos/vendas/$bling_pv_id";
        // echo $url . '<hr>';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Bearer ' . $token . '',
                'Cookie: PHPSESSID=btthevjjl77h84ft9hdn3ikved'
            ),
        ));
        $retorno2 = curl_exec($curl);
        curl_close($curl);
        //finalizando CURL ====================================================================

        $resultado2 = json_decode($retorno2);
        // print("<pre>" . print_r($resultado2, true) . "</pre>");

        if ($resultado2->error) {
            $msg = 'Erro api bling v3 (bv3_pv_get.php AB) = type: ' . $resultado2->error->message . ' - ' . $resultado2->error->description;

            $msg .= ' |  pedido = ' . $ID_ORC;


            // echo $msg;
            // print("<pre>" . print_r($resultado2, true) . "</pre>");
        } else {
            if ($resultado2->data->numero > 0) {
                $bling_api_pedidovenda = $resultado2->data->numero;
            }

            // echo 'Pedido cadastrado Numero= ' . $bling_api_pedidovenda . '<br>';
        }


        $integrou = true;
    }
}
