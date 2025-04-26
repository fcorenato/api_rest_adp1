<?php
//montando Json do pedido a ser enviado
require('../config/conexao.php');
require('../config/SUsuario.php');
require('../../sys_functions.php');
$apikey = $un_bling_apikey;

//pesquisando dados do orçamento
//$ID_ORC = '10';



function executeSendOrder($url, $data)
{
    $curl_handle = curl_init();
    curl_setopt($curl_handle, CURLOPT_URL, $url);
    curl_setopt($curl_handle, CURLOPT_POST, count($data));
    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($curl_handle);
    curl_close($curl_handle);
    return $response;
}

$pv = mysql_query("SELECT * FROM md_vendas_pedidos as p 
                    LEFT JOIN md_vendas_cpgto as cp ON p.cond_pgto = cp.codigo
                    LEFT JOIN sys_unidades as un ON p.unidade_codigo = un.codigo
                    LEFT JOIN sys_usuarios as us ON p.vend1 = us.codigo
                    WHERE p.id = $ID_ORC ")  or die(mysql_error());
$linhas_pv = mysql_num_rows($pv);
if ($linhas_pv == 0) {
    $result = 'API: Erro ao pesquisar o orçamento. Er001';
} else {
    $dadospv = mysql_fetch_array($pv);
    $frete_tipo = $dadospv['frete_tipo'];
    $frete_tipo = 'R'; //R = CIF no bling

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
        $cod_und_vend1 = str_pad($dados_pv_undv['cod_protheus'], 6, '0', STR_PAD_LEFT);
    }

    // verificando se cond. pgto tem ID no bling
    $erro_msg = '';
    if ($dadospv['id_bling_cpgto'] == NULL or $dadospv['id_bling_cpgto'] == 0) {
        $erro_msg .= ' ERRO: Cond. Pgto. não possui ID do Bling';
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


    //SE PGTO SPLIT
    if ($orc_split_pgto == 'S' and $empresa == 'VIVARTE') {
        $comissao1 = 0;
    } else {
        $comissao1 = 25;
    }

    $cidade = remove_accents($dadospv['cidade']);
}

// ========== pesquisando itens do orcamento =================
$pesquisa_itens = mysql_query("SELECT * FROM md_vendas_pedidos_itens as i 
    WHERE i.pedido_id = $ID_ORC
    AND i.status = 'A' AND i.qtde > 0 ")  or die(mysql_error());
$linhas_itens = mysql_num_rows($pesquisa_itens);
if ($linhas_itens == 0) {
    $result = 'API: Erro ao pesquisar itens do orçamento. Er002';
} else {
    $item = 1;
    while ($dadosi = mysql_fetch_array($pesquisa_itens)) {
        //verificando se aplica taxa pallet aberto
        $taxa_item = 0;

        if ($dadosi["app_tx_pal"] == 'S') {
            if ($dadosi["taxaPalletAbertoR"] > 0) {
              //se taxa valor R$
              $taxa_item = $dadosi["taxaPalletAbertoR"] / $dadosi["qtde"];
            } else if ($dadosi["taxaPalletAberto"] > 0) {
              //se taxa %
              $dadosi["prc_tab"] = $dadosi["prc_tab"] + ($dadosi["prc_tab"] * $dadosi["taxaPalletAberto"] / 100);
            } else if ($dadosi["precoProdPalletAbertoR"] > 0) {
              $dadosi["prc_tab"] = $dadosi["precoProdPalletAbertoR"];
            }
          }


        $preco_tab = (float) number_format($dadosi["prc_tab"] - $dadosi["desconto"] + $taxa_item, 2, '.', '');
        //$preco_tab = (float) number_format($dadosi["prc_tab"], 2, '.', '');
        $comissao1 = 25;

        

        $pvXmlItens .= "
        <item>
            <codigo>$dadosi[codigo]</codigo>
            <descricao>$dadosi[descricao]</descricao>
            <un>$dadosi[unidade]</un>
            <qtde>$dadosi[qtde]</qtde>
            <vlr_unit>$preco_tab</vlr_unit>
        </item>
        ";

        $item++;
    }
}



// ================================================================================================
if ($erro_msg != '') {
    $bling_api_cod_erro_msg = $erro_msg;
    $integra_bling = false;
    $integrou = false;
} else {
    //cond pgto id
    $pv_id_bling_cpgto = $dadospv['id_bling_cpgto'];

    //id loja/tabela de preco VIVARTE
    $pv_id_loja = '';

    $integra_bling = true;
}

if ($integra_bling) {

    $pvXml = "
    <pedido>
            <loja>$pv_id_loja</loja>                             // ID da loja que identifica a tabela exe: TABELA COMISS //
            <vendedor>$dadospv[nome_completo]</vendedor>     // Nome vendedor cadastro no bling
            <numeroOrdemCompra>$ID_ORC</numeroOrdemCompra>     //num orcamento BIV
            <data_prevista>$PED_PREV_ENT</data_prevista>       // Data prev entrega
            <cliente>
                <nome>$dadospv[cliente_razao]</nome>
                <tipoPessoa>$dadospv[cliente_tipo]</tipoPessoa>
                <endereco>$dadospv[endereco]</endereco>
                <cpf_cnpj>$dadospv[cpf_cnpj]</cpf_cnpj>
                <ie>$dadospv[insc_estadual]</ie>
                <numero>$dadospv[end_num]</numero>
                <complemento>  </complemento>
                <bairro>$dadospv[bairro]</bairro>
                <cep>$dadospv[cep]</cep>
                <cidade>$cidade</cidade>
                <uf>$dadospv[uf]</uf>
                <fone>$dadospv[telefone]</fone>
                <email>$email_cli</email>
            </cliente>
            <transporte>
                <transportadora>$dadospv[transp_nome]</transportadora>
                <tipo_frete>$frete_tipo</tipo_frete>
                <peso_bruto>$dadospv[total_peso]</peso_bruto>
                <qtde_volumes>$dadospv[total_volumes]</qtde_volumes>
            </transporte>
            <itens>
                $pvXmlItens
            </itens>
            <idFormaPagamento>$pv_id_bling_cpgto</idFormaPagamento>
            <vlr_frete>$dadospv[frete_valor]</vlr_frete>
            <vlr_desconto></vlr_desconto>
            <obs>$dadospv[msg_nota]</obs>
            <obs_internas>$dadospv[msg_pedido] $dadospv[msg_interna]</obs_internas>
        </pedido>
    ";

    $pvXml = str_replace('&', '&amp;', $pvXml);
    
    $url = 'https://bling.com.br/Api/v2/pedido/json/';
    $xml = $pvXml;
    $posts = array(
        "apikey" => $apikey,
        "xml" => rawurlencode($xml)
    );
    $retorno = executeSendOrder($url, $posts);

    $resultado = json_decode($retorno);

    if ($resultado->retorno->erros) {
        $bling_api_cod_erro = $resultado->retorno->erros[0]->erro->cod;
        $bling_api_cod_erro_msg = $resultado->retorno->erros[0]->erro->msg;
        //echo 'cod: ' . $bling_api_cod_erro . ' - ' . $bling_api_cod_erro_msg . '<hr>';
        //echo "<hr><pre>" . print_r($resultado, true) . "</pre>";
        $integrou = false;
    } else {
        $bling_api_pedidovenda = $resultado->retorno->pedidos[0]->pedido->numero;
        //echo "pedido integrado numero ".$bling_api_pedidovenda;
        //echo "<hr><pre>" . print_r($resultado, true) . "</pre>";
        $integrou = true;
    }
}
    //echo '<hr>';
    //echo "<pre>" . print_r($pvXml, true) . "</pre>";
