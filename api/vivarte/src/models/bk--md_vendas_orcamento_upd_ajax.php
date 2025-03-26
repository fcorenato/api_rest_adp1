<?php
require('../../src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];

function protect( &$str ) {
    /*** Função para retornar uma string/Array protegidos contra SQL/Blind/XSS Injection*/
           if( !is_array( $str ) ) {                      
                   $str = preg_replace( '/(from|select|insert|delete|where|drop|union|order|update|database)/i', '', $str );
                   $str = preg_replace( '/(&lt;|<)?script(\/?(&gt;|>(.*))?)/i', '', $str );
                   $tbl = get_html_translation_table( HTML_ENTITIES );
                   $tbl = array_flip( $tbl );
                   $str = addslashes( $str );
                   $str = strip_tags( $str );
                   return strtr( $str, $tbl );
           } else {
                   return array_filter( $str, "protect" );
           }
   }
   
//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $ID = strtoupper($_POST['ID']);
    $A1_COD = strtoupper(protect($_POST['A1_COD']));
    $A1_LOJA = strtoupper(protect($_POST['A1_LOJA']));
    $A1_NOME = strtoupper(protect($_POST['A1_NOME']));
    $A1_NREDUZ = strtoupper(protect($_POST['A1_NREDUZ']));
    $A1_FILIAL = strtoupper(protect($_POST['A1_FILIAL']));
    $A1_PESSOA = strtoupper(protect($_POST['A1_PESSOA']));
    $A1_CGC = strtoupper(protect($_POST['A1_CGC']));
    $A1_TEL = strtoupper(protect($_POST['A1_TEL']));
    $A1_EMAIL = strtoupper(protect($_POST['A1_EMAIL']));
    $A1_CEP = strtoupper(protect($_POST['A1_CEP']));
    $A1_END = strtoupper(protect($_POST['A1_END']));
    $A1_BAIRRO = strtoupper(protect($_POST['A1_BAIRRO']));
    $A1_MUN = strtoupper(protect($_POST['A1_MUN']));
    $A1_EST = strtoupper(protect($_POST['A1_EST']));
    $C5_VEND1 = strtoupper(protect($_POST['C5_VEND1']));
    $C5_VEND2 = strtoupper(protect($_POST['C5_VEND2']));
    $C5_YPEDCLI = strtoupper(protect($_POST['C5_YPEDCLI']));
    $C5_YDTVALI = strtoupper(protect($_POST['C5_YDTVALI']));
    $C5_CONDPAG = strtoupper(protect($_POST['C5_CONDPAG']));
    $C5_TABELA = strtoupper(protect($_POST['C5_TABELA']));
    $C5_TPFRETE = strtoupper($_POST['C5_TPFRETE']);
    $C5_TRANSP_COD = strtoupper($_POST['C5_TRANSP_COD']);
    $C5_TRANSP_NOME = strtoupper($_POST['C5_TRANSP_NOME']);
    $C5_TRANSP_PRAZO = strtoupper($_POST['C5_TRANSP_PRAZO']);
    $C5_FRETE = str_replace(',', '.', str_replace('.', '', $_POST['C5_FRETE']));
    $C5_DESC1 = str_replace(',', '.', str_replace('.', '', $_POST['C5_DESC1']));
    $C5_DESC2 = str_replace(',', '.', str_replace('.', '', $_POST['C5_DESC2']));
    $C5_DESC3 = str_replace(',', '.', str_replace('.', '', $_POST['C5_DESC3']));
    $C5_MENNOTA = strtoupper(protect($_POST['C5_MENNOTA']));
    $C5_YOBSPED = strtoupper(protect($_POST['C5_YOBSPED']));
    $C5_INSTINT = strtoupper(protect($_POST['C5_INSTINT']));
    $totalgeral_volumes = str_replace(',', '.', str_replace('.', '', $_POST['totalgeral_volumes']));
    $totalgeral_pesob = str_replace(',', '.', str_replace('.', '', $_POST['totalgeral_pesob']));
    $totalgeral_desc = str_replace(',', '.', str_replace('.', '', $_POST['totalgeral_desc']));
    $totalgeral_cimpostos = str_replace(',', '.', str_replace('.', '', $_POST['totalgeral_cimpostos']));
    $totalgeral_final = str_replace(',', '.', str_replace('.', '', $_POST['totalgeral_final']));

    //status do documento
    $status = 'A';
    //SE STATUS ORC RECEBE B = BLOQUEIA
    if (strtoupper($_POST['C5_STATUS']) == 'B') {
        $status = 'B';
    }
    // SE ALGUM ITEM ESTA COM CX FRACIONADA = BLOQUEIA
    if (strtoupper($_POST['C5_CXFRAC']) == 'S') {
        $status = 'B';
    }

    $orc_updated_at = date("Y-m-d H:i:s");
    $pedido_num = '0';
    $pedido_filial = '0';
    $pedido_conv_date = '0';
    $pedido_conv_user = '0';

    //itens do documento ARRAYS
    $C6_ID = $_POST['C6_ID'];
    $C6_STATUS = $_POST['C6_STATUS'];
    $C6_COD = $_POST['C6_COD'];
    $C6_DESC = $_POST['C6_DESC'];
    $C6_UM = $_POST['C6_UM'];
    $C6_YQTDCXA = $_POST['C6_YQTDCXA'];
    $C6_FRACIONA = $_POST['C6_FRACIONA'];
    $C6_QTDE = $_POST['C6_QTDE'];
    $C6_PRCVEN = $_POST['C6_PRCVEN'];
    $C6_DESCPRC = $_POST['C6_DESCPRC'];
    $C6_IPI = $_POST['C6_IPI'];
    $C6_PESBRU = $_POST['C6_PESBRU'];
    $C6_ENTREG = $_POST['C6_ENTREG'];
    $C6_OBS_ITEM = $_POST['C6_OBS_ITEM'];

    //salvando cabecalho
    require('../config/conexao.php');
    mysql_query("START TRANSACTION");
    $upd = mysql_query("UPDATE md_vendas_pedidos SET
    orc_updated_at='$orc_updated_at', cliente_razao ='$A1_NOME', cpf_cnpj ='$A1_CGC', cliente_tipo='$A1_PESSOA', telefone='$A1_TEL', email='$A1_EMAIL', cep='$A1_CEP', endereco='$A1_END', bairro='$A1_BAIRRO', cidade='$A1_MUN',  uf='$A1_EST', vend1='$C5_VEND1', vend2='$C5_VEND2', pedido_cliente='$C5_YPEDCLI', orc_data_valid='$C5_YDTVALI',  cond_pgto='$C5_CONDPAG', tabela_preco='$C5_TABELA', frete_tipo='$C5_TPFRETE', transp_codigo='$C5_TRANSP_COD', transp_nome='$C5_TRANSP_NOME', transp_prazo='$C5_TRANSP_PRAZO',frete_valor='$C5_FRETE', desc1='$C5_DESC1', desc2='$C5_DESC2', desc3='$C5_DESC3', msg_nota='$C5_MENNOTA', msg_pedido='$C5_YOBSPED', msg_interna='$C5_INSTINT', total_volumes='$totalgeral_volumes', total_peso='$totalgeral_pesob', total_desc='$totalgeral_desc', total_cimp='$totalgeral_cimpostos', total_final='$totalgeral_final', status='$status' WHERE id = $ID") or die(mysql_error());

    //salvando itens
    $sql_inserir_itens = "INSERT INTO md_vendas_pedidos_itens (created_at, updated_at, pedido_id, codigo, descricao, unidade, qtde_cx, qtde, prc_tab, desconto, ipi, peso, data_prev_fatura, obs,  status ) VALUES ";
    for ($i = 0; $i < count($C6_COD); $i++) {
        $it_id = $C6_ID[$i];
        $it_status = $C6_STATUS[$i];
        $it_cod = $C6_COD[$i];
        $it_desc = $C6_DESC[$i];
        $it_um = $C6_UM[$i];
        $it_qtdcx = str_replace(',', '.', str_replace('.', '', $C6_YQTDCXA[$i]));
        $it_qtdfrac = $C6_FRACIONA[$i];
        $it_qtde = str_replace(',', '.', str_replace('.', '', $C6_QTDE[$i]));
        $it_prcven = str_replace(',', '.', str_replace('.', '', $C6_PRCVEN[$i]));
        $it_desconto = str_replace(',', '.', str_replace('.', '', $C6_DESCPRC[$i]));
        $it_ipi = str_replace(',', '.', str_replace('.', '', $C6_IPI[$i]));
        $it_pesob = str_replace(',', '.', str_replace('.', '', $C6_PESBRU[$i]));
        $it_dtentrega = $C6_ENTREG[$i];
        $it_obs = protect($C6_OBS_ITEM[$i]);

        if ($it_status == 'A' and $it_id <> 0) {
            //SE ITEM ATIVO E COM ID = ATUALIZA ITEM NO DOCUMENTO
            $upd2 = mysql_query("UPDATE md_vendas_pedidos_itens SET
            updated_at='$orc_updated_at', qtde='$it_qtde', data_prev_fatura='$it_dtentrega', obs='$it_obs', prc_tab='$it_prcven', desconto='$it_desconto', ipi='$it_ipi', status='$it_status' WHERE id = $it_id") or die(mysql_error());

            //commit
            if ($upd and $upd2) {
                mysql_query("COMMIT");
            } else {
                mysql_query("ROLLBACK");
            }
        } else if ($it_status == 'N' and $it_id == 0) {
            //SE ITEM NOVO ADICIONAR NO DOCUMENTO
            $sql_inserir_itens = "INSERT INTO md_vendas_pedidos_itens (created_at, updated_at, pedido_id, codigo, descricao, unidade, qtde_cx, qtde_frac, qtde, prc_tab, desconto, ipi, peso, data_prev_fatura, obs,  status ) VALUES ";
            $sql_inserir_itens .= " ('{$orc_updated_at}','{$orc_updated_at}','{$ID}','{$it_cod}','{$it_desc}','{$it_um}','{$it_qtdcx}','{$it_qtdfrac}','{$it_qtde}','{$it_prcven}','{$it_desconto}','{$it_ipi}','{$it_pesob}','{$it_dtentrega}','{$it_obs}','A')";
            $sql_insert_item = mysql_query($sql_inserir_itens) or die(mysql_error());

            //commit
            if ($upd and $sql_insert_item) {
                mysql_query("COMMIT");
            } else {
                mysql_query("ROLLBACK");
            }
        } else if ($it_status == 'D' and $it_id <> 0) {
            // MARCADO PARA DELETAR E TEM ID = ALTERA STATUS PARA D = DELETADO
            $sql_del_item = mysql_query("UPDATE md_vendas_pedidos_itens SET status='D' WHERE id = $it_id") or die(mysql_error());

            //commit
            if ($upd and $sql_del_item) {
                mysql_query("COMMIT");
            } else {
                mysql_query("ROLLBACK");
            }
        }

        //$upd = mysql_query("UPDATE md_vendas_pedidos_itens SET updated_at='$orc_updated_at', qtde='$it_qtde', data_prev_fatura='$it_dtentrega', obs='$it_obs', status='$it_status' WHERE id = $it_id") or die(mysql_error());

        $result .= $it_id . ' == ';
    }

    //registrando evento
    $result = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID', '$orc_updated_at', '$usuario_codigo', 'Editado')") or die(mysql_error());
}
//$result2 = 'resultado = '.$orc_created_at.' - '. $orc_updated_at.' - '. $pedido_num.' - '. $pedido_filial.' - '. $pedido_conv_date.' - '. $pedido_conv_user.' - '. $A1_COD.' - '. $A1_LOJA.' - '. $A1_NOME.' - '. $A1_NREDUZ.' - '. $A1_FILIAL.' - '. $A1_PESSOA.' - '. $A1_CGC.' - '. $A1_TEL.' - '. $A1_EMAIL.' - '. $A1_END.' - '. $A1_BAIRRO.' - '. $A1_MUN.' - '. $A1_EST.' - '. $C5_VEND1.' - '. $C5_VEND2.' - '. $C5_YPEDCLI.' - '. $C5_YDTVALI.' - '. $C5_CONDPAG.' - '. $C5_TABELA.' - '. $C5_TPFRETE.' - '. $C5_FRETE.' - '. $C5_DESC1.' - '. $C5_DESC2.' - '. $C5_DESC3.' - '. $C5_MENNOTA.' - '. $C5_YOBSPED.' - '. $totalgeral_volumes.' - '. $totalgeral_pesob.' - '. $totalgeral_desc.' - '. $totalgeral_cimpostos.' - '. $totalgeral_final;






echo $result;
