<?php
//montando Json do pedido a ser enviado
//require('../config/conexao.php');
//pesquisando dados do orçamento
//$ID_ORC = '10660';
require('../../src/config/SUsuario.php');
require('../../sys_functions.php');

$pv = mysql_query("SELECT * FROM md_vendas_pedidos as p WHERE p.id = $ID_ORC ")  or die(mysql_error());
$linhas_pv = mysql_num_rows($pv);
if ($linhas_pv == 0) {
    $result = 'API: Erro ao pesquisar o orçamento. Er001';
} else {
    $dadospv = mysql_fetch_array($pv);
    $frete_tipo = $dadospv['frete_tipo'];

    $empresa = $dadospv['empresa'];
    $orc_split_pgto = $dadospv['orc_split_pgto'];

    if ($dadospv['empresa'] == 'VETROMANI') {
        $pvFilial = '010102';
    } else {
        $pvFilial = '010202';
    }

    if ($dadospv['cliente_nomef'] == '') {
        $dadospv['cliente_nomef'] = substr($dadospv['cliente_razao'], 0, 4);
    }

    //tratando endereço
    $dadospv['endereco'] = substr($dadospv['endereco'], 0, 40);
    $dadospv['endereco'] = remove_accents($dadospv['endereco']);


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

    //definindo os vendedores
    $vend1 = '';
    $vend2 = '';
    $vend3 = '';
    $vend4 = '';

    $un_cod_protheus = $_SESSION["un_codigo"];
    $cod_vend_usr_logado = $_SESSION["cod_vend"];

    //idenficando codigo da unidade do usuario que cadastrou o orçamento    
    $pv_undv = mysql_query("SELECT un.cod_protheus FROM `sys_unidades` as un
    LEFT JOIN sys_usuarios as u ON u.unidade_codigo = un.codigo
    WHERE u.codigo = $dadospv[orc_created_user]")  or die(mysql_error());
    $linhas_pv_undv = mysql_num_rows($pv_undv);
    if ($linhas_pv_undv == 0) {
        $cod_und_vend1 = '';
    } else {
        $dados_pv_undv = mysql_fetch_array($pv_undv);
        //formatando o codigo da unidade para 6 digitos. completando com zeros a esquerda
        $cod_und_vend1 = str_pad($dados_pv_undv['cod_protheus'] , 6 , '0' , STR_PAD_LEFT);
    }

    // se unidade = 000004 (loja vetromani concept)
    if ($cod_und_vend1 == '000004') {
        $vend1 = $dadospv['vend1'];
        $vend2 = $dadospv['vend2'];
    } else {
        if ($cod_und_vend1 == '') {
            $vend1 = '000000';
        } else {
            $vend1 = $cod_und_vend1;
        }

        if ($dadospv['vend1'] == '000000') {
            $vend2 = '';
        } else {
            $vend2 = $dadospv['vend1'];
        }
    }

    //evitar problema no protheus de dois vendedores no mesmo pedido
    $vend4 = $cod_vend_usr_logado;
    if (($vend4 == $vend1) or ($vend4 == $vend2)) {
        $vend4 = '';
    }
    //SE PGTO SPLIT
    if ($orc_split_pgto == 'S' and $empresa == 'VIVARTE') {
        $comissao1 = 0;
    } else {
        $comissao1 = 25;
    }

    $cidade = remove_accents($dadospv['cidade']);

    $pvArray = array(
        "filial" => "$pvFilial",
        "condPg" => "$dadospv[cond_pgto]",
        "natureza" => "",
        "transportadora" => "",
        "tabelaPreco" => "$dadospv[tabela_preco]",
        "vendedor1" => "$vend1",
        "vendedor2" => "$vend2",
        "vendedor3" => "$vend3",
        "vendedor4" => "$vend4",
        "vendedor5" => "",
        "comissao1" => $comissao1,
        "comissao2" => 0,
        "comissao3" => 0,
        "comissao4" => 0,
        "comissao5" => 0,
        "desconto1" => (float) number_format($dadospv["desc1"], 2, '.', ''),
        "desconto2" => (float) number_format($dadospv["desc2"], 2, '.', ''),
        "desconto3" => (float) number_format($dadospv["desc3"], 2, '.', ''),
        "desconto4" => 0,
        "despesa" => 0,
        "tipoFrete" => "C",
        "frete" => (float) number_format($dadospv['frete_valor'], 2, '.', ''),
        "pesoLiquido" => (float) number_format($dadospv['total_peso'], 2, '.', ''),
        "pesoBruto" => (float) number_format($dadospv['total_peso'], 2, '.', ''),
        "volume" => (float) number_format($dadospv['total_volumes'], 2, '.', ''),
        "especie" => "CAIXA",
        "numPedCli" => "$dadospv[pedido_cliente]",
        "msgParaNota" => "$dadospv[msg_nota]",
        "obsPedCli" => "$dadospv[msg_pedido]",
        "dataEntrega" => "",
        "orcamento" => "$dadospv[id]",
        "mensagem" => "Venda do portal",
        "cliente" => array(
            [
                "nome" => "$dadospv[cliente_razao]",
                "loja" => "01",
                "razao" => "$dadospv[cliente_nomef]",
                "pessoa" => "$dadospv[cliente_tipo]",
                "tipo" => "F",
                "cnpj" => "$dadospv[cpf_cnpj]",
                "ie" => "$dadospv[insc_estadual]",
                "cep" => "$dadospv[cep]",
                "endereco" => "$dadospv[endereco]",
                "bairro" => "$dadospv[bairro]",
                "uf" => "$dadospv[uf]",
                "municipio" => "$cidade",
                "codMun" => "$cod_ibge_cidade",
                "email" => "$email_cli",
                "ddd" => "0",
                "telefone" => "$dadospv[telefone]",
                "vendedor" => "$dadospv[vend1]",
                "filialAtend" => "$pvFilial",
                "tabelaPreco" => "$dadospv[tabela_preco]",
                "condPgto" => "",
                "descComercial" => 0,
                "bloqVendaSemEstoque" => "2",
                "risco" => "A",
                "limiteCredito" => 1.99,
                "vencLimiteCredito" => "2050-07-01",
                "grupoVenda" => "000001",
                "optSimples" => "$dadospv[opt_simples]",
                "contribuinte" => "$dadospv[contribuinte]",
                "suframa" => "",
                "nascimento" => "1992-07-01"
            ]
        ),
    );
}

//pesquisando itens do orcamento
$pesquisa_itens = mysql_query("SELECT * FROM md_vendas_pedidos_itens as i 
    WHERE i.pedido_id = $ID_ORC
    AND i.status = 'A' AND i.qtde > 0 ")  or die(mysql_error());
$linhas_itens = mysql_num_rows($pesquisa_itens);
if ($linhas_itens == 0) {
    $result = 'API: Erro ao pesquisar itens do orçamento. Er002';
} else {
    $item = 1;
    while ($dadosi = mysql_fetch_array($pesquisa_itens)) {
        if ($orc_split_pgto == 'S' and $empresa == 'VIVARTE') {
            $preco_tab = (float) number_format($dadosi["prc_tab"] * 0.75, 2, '.', '');
            $comissao1 = 0;
        } else {
            $preco_tab = (float) number_format($dadosi["prc_tab"], 2, '.', '');
            $comissao1 = 25;
        }
        $pvi =  array(
            "item" => $item,
            "cod" => "$dadosi[codigo]",
            "quantidade" => (float) number_format($dadosi["qtde"], 2, '.', ''),
            "preco" => $preco_tab,
            "percDesconto" => 0,
            "valorDesconto" => 0,
            "dataEntrega" => "$dadosi[data_prev_fatura]",
            "comissao1" => $comissao1,
            "comissao2" => 0,
            "comissao3" => 0,
            "comissao4" => 0,
            "comissao5" => 0,
            "blister" => "0"
        );

        $pvArray["itens"][] = $pvi;

        $item++;
    }
}

$pvJson = json_encode($pvArray, true);
//echo "<pre>" . print_r($pvJson, true) . "</pre>";


// URL de SUA API
$url = 'http://191.37.68.150:8091/rest/WSPEDVEN';

//autenticacao
$username = 'admin';
$password = 'r2d43636';

// json do pedido a ser enviado
$body = $pvJson;

// cria um resource cURL
$ch = curl_init($url);
//autenticacao
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
// anexar o corpo em formato json da sua requisição
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
// definir o conteúdo do envio como json
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8"));
// ativar o recebimento de retorno da requisição
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// executar a requisição POST
$res = curl_exec($ch);
// encerra conexão e libera variável
curl_close($ch);

//echo '<hr>Resultado1:<br>';
//$resultado = json_decode($res);
$res = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($res));
$resultado = json_decode($res);
if (json_last_error() == 0) { // Sem erros
    $erro_json =  "sem erro ";
} else {
    $erro_json =  "Erro inesperado COD = " . json_last_error() . ' -- JSON CONTENT = ' . $res;
}


//retorno do cliente    
$cliente_erroCod = $resultado->Cliente[0]->erroCod;
$cliente_message = $resultado->Cliente[0]->message;
$cliente_codCli = $resultado->Cliente[0]->codCli;
$cliente_nomeCli = $resultado->Cliente[0]->nomeCli;

//retorno do pedido
$pedido_erroCod = $resultado->Pedido[0]->erroCod;
$pedido_message = $resultado->Pedido[0]->message;
$pedido_erroCod = $resultado->Pedido[0]->erroCod;
$pedido_codPed = $resultado->Pedido[0]->codPed;

if ($cliente_erroCod != '200') {
    $result =  'API Error 01: Não foi possível converter o orçamento ' . $dadospv['id'] . '. Cod: ' . $cliente_erroCod . '   Desc: ' . $cliente_message . ' JSON = ' . $res;
} else if ($pedido_erroCod != '200') {
    $result =  'API Error 02: Não foi possível converter o orçamento ' . $dadospv['id'] . '. Cod: ' . $pedido_erroCod . '   Desc: ' . $pedido_message . ' JSON = ' . $res;
} else {
    $integrou = TRUE;
    $ID_PED = $pedido_codPed;
}

//echo $result;

/* imprimindo retorno da API 
    echo 'cliente_erro Cod:' . $cliente_erroCod . '<br>';
    echo 'cliente_message:' . $cliente_message . '<br>';
    echo 'cliente_codCli:' . $cliente_codCli . '<br>';
    echo 'cliente_nomeCli:' . $cliente_nomeCli . '<br>';
    echo '<br>';
    echo 'pedido_erro Cod:' . $pedido_erroCod . '<br>';
    echo 'pedido_message:' . $pedido_message . '<br>';
    echo 'pedido_codPed:' . $pedido_codPed . '<br>';

    echo '<hr>Resultado2:<br>';
    echo '<pre>' . print_r(json_decode($res), true) . '</pre>';
    */
