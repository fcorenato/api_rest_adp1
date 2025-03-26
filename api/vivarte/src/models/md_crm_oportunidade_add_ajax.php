<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];

function protect(&$str)
{
    /*** Função para retornar uma string/Array protegidos contra SQL/Blind/XSS Injection*/
    if (!is_array($str)) {
        $str = preg_replace('/(from|select|insert|delete|where|drop|union|order|update|database)/i', '', $str);
        $str = preg_replace('/(&lt;|<)?script(\/?(&gt;|>(.*))?)/i', '', $str);
        $tbl = get_html_translation_table(HTML_ENTITIES);
        $tbl = array_flip($tbl);
        $str = addslashes($str);
        $str = strip_tags($str);
        return strtr($str, $tbl);
    } else {
        return array_filter($str, "protect");
    }
}

//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $vendedor = $usuario_codigo;
    $tipo = strtoupper(protect($_POST['tipo']));
    $funil = strtoupper(protect($_POST['funil']));
    $etapa_incial = strtoupper(protect($_POST['etapa_incial']));
    $data_inicio = strtoupper(protect($_POST['data_inicio']));
    $valor = str_replace(',', '.', str_replace('.', '', $_POST['valor']));
    $nome = strtoupper(protect($_POST['nome']));
    $email = strtoupper(protect($_POST['email']));
    $telefone = strtoupper(protect($_POST['telefone']));
    $uf = strtoupper(protect($_POST['uf']));
    $fonte = strtoupper(protect($_POST['fonte']));
    $campanha = strtoupper(protect($_POST['campanha']));
    $etapa_atual = strtoupper(protect($_POST['etapa_atual']));
    $tabela = strtoupper(protect($_POST['C5_TABELA']));
    $created_at = date("Y-m-d H:i:s");
    $updated_at = date("Y-m-d H:i:s");
    $status_oportunidade = strtoupper(protect($_POST['status']));
    $motivo_perda = strtoupper(protect($_POST['motivo_perda']));
    $revenda = strtoupper(protect($_POST['revenda']));
    if ($status_oportunidade != 'C') {
        $motivo_perda = 0;
    }

    if ($funil != 2) {
        $revenda = 0;
    }

    //itens do documento ARRAYS
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
    $C6_OBS_ITEM =  protect($_POST['C6_OBS_ITEM']);




    //salvando
    require('../config/conexao.php');

    mysql_query("START TRANSACTION");

    $query1 = "INSERT INTO `md_crm_oportunidade` 
                (`id`, `vendedor_id`, `tipo_id`, `funil_id`, `etapa_inicial_id`, `fonte_id`, `campanha_id`, `data_inicio`, `valor`, `nome`, `email`, `telefone`, `uf`, `etapa_atual_id`, `create_at`, `update_at`, `motivo_perda_id`, `revenda_id`, `status`) VALUES 
                (NULL, '$vendedor', '$tipo', ' $funil', '$etapa_incial', '$fonte', '$campanha ', '$data_inicio', ' $valor', '$nome', '$email', '$telefone', '$uf', '$etapa_atual', '  $created_at', '$updated_at', '$motivo_perda', '$revenda', '$status_oportunidade')";

    $result = mysql_query($query1) or die(mysql_error());

    //pegando codigo id inserido
    $oportunidade_id = mysql_insert_id();
    //itens enviados
    $qtde_itens = count($C6_COD);

    //se foi enviado itens
    if ($qtde_itens > 0) {
        $sql_inserir_itens = "INSERT INTO md_crm_oportunidade_itens (created_at, updated_at, oportunidade_id, codigo, descricao, unidade, qtde_cx, qtde_frac, qtde, prc_tab, desconto, ipi, peso, data_prev_fatura, obs,  status ) VALUES ";
        
        for ($i = 0; $i < $qtde_itens; $i++) {
            //echo "| $id[$i] | $nome[$i] |<br>";
            // $result .= "| $C6_COD[$i] | $C6_DESC[$i] | | $C6_UM[$i] | $C6_YQTDCXA[$i] | $C6_QTDE[$i] | $C6_PRCVEN[$i] | $C6_DESCPRC[$i] | $C6_IPI[$i] | $C6_PESBRU[$i] | $C6_ENTREG[$i] | <FIM ITEM> ";

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
            $it_obs = $C6_OBS_ITEM[$i];
            $it_status = 'A';

            $sql_inserir_itens .= " ('{$updated_at}','{$updated_at}','{$oportunidade_id}','{$it_cod}','{$it_desc}','{$it_um}','{$it_qtdcx}','{$it_qtdfrac}','{$it_qtde}','{$it_prcven}','{$it_desconto}','{$it_ipi}','{$it_pesob}','{$it_dtentrega}','{$it_obs}','A'),";
        }

        $sql_inserir_itens = substr($sql_inserir_itens, 0, strlen($sql_inserir_itens) - 1); //retirar ultimo , da sql
        $result_item = mysql_query($sql_inserir_itens) or die(mysql_error());
    } else {
        //se nao tem item result = true
        $result_item = TRUE;
    }

    //registrando evento
    $desc_evento = '<i class="fas fa-caret-right"></i> Oportunidade cadastrada por ' . $usuario_nome .'<br>';
    if ($qtde_itens > 0 ) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Item Incluido: ' . $it_cod . ' Qtde: ' . $it_qtde .'<br>';
    }    
    if ($motivo_perda > 0) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Motivo de perda Alterado<br>';
    }
    if ($revenda > 0) {
        //pesquisando revendsa
        $query1 = "SELECT codigo, descricao  FROM sys_unidades  
                    WHERE status = 'A'
                    AND codigo = $revenda
                    ";

        $result_query1 = mysql_query($query1);
        $qtde_query1 = mysql_num_rows($result_query1);

        if ($qtde_query1 == 0) {
            $desc_revenda .= '';
        } else {
            while ($campos = mysql_fetch_array($result_query1)) {
                $desc_revenda = $campos['descricao'];
            }
        }
        $desc_evento .= '<i class="fas fa-caret-right"></i> Oportunidade encaminhado para revenda ' . $revenda . ' - ' . $desc_revenda . '<br>';
}
    $query_evento = "INSERT INTO md_crm_eventos 
   (oportunidade_id, created_at, etapa_id, usuario_id, descricao, acao, acao_data, acao_status, status) VALUES ('$oportunidade_id', '$created_at', '$etapa_atual', '$usuario_codigo', '$desc_evento', '', '', '', 'A')";

    $result_evento = mysql_query("$query_evento") or die(mysql_error());



    if ($result and $result_item) {
        mysql_query("COMMIT");
    } else {
        mysql_query("ROLLBACK");
    }
}
echo $result;
