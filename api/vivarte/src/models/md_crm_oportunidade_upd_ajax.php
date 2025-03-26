<?php
date_default_timezone_set('America/Sao_Paulo');
require('../../src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];

/* status oportunindade no portal
<option value="A">EM ANDAMENTO</option>
<option value="P">PAUSADO</option>
<option value="V">VENDIDO</option>
<option value="C">PERDIDO</option>
*/
$array_opt_status = array('A' => 'EM ANDAMENTO', 'P' => 'PAUSADO', 'V' => 'VENDIDO', 'C' => 'PERDIDO', 'I' => 'INVÁLIDO');
$array_opt_etapa = array('1' => 'CONTATO INICIAL', '2' => 'ORÇAMENTO', '3' => 'NEGOCIAÇÃO');
$array_opt_tipo = array('1' => 'VENDA CONSUMIDOR FINAL', '2' => 'VENDA REVENDA', '3' => 'ESPECIFICADOR', '4' => 'ABERTURA LOJA', '5' => 'CONSTRUTORA');
$array_opt_funil = array('1' => 'PADRÃO', '2' => 'LEAD ENCAMINHADO PARA REVENDA');
$array_opt_campanha = array('1' => 'SEM CAMPANHA', '2' => 'GOOGLE PESQ TERMO', '3' => 'GOOGLE PESQ CONCORRENTE',  '4' => 'GOOGLE REMARKETING BLACKFRIDAY',  '5' => 'META GERAÇÃO LEAD',  '6' => 'META BLACKFRIDAY',  '7' => '7-REMARKETING WHATSAPP-EMAIL' ,  '8' => 'INSTAGRAM ORGANICO');
$array_opt_fonte = array('1' => 'SITE', '2' => 'WHATSAPP', '3' => 'INSTAGRAM');

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
    $ID = strtoupper($_POST['ID']);
    $oportunidade_id = $ID;
    $vendedor = $_POST['vendedor'];
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
    $C6_OBS_ITEM =  protect($_POST['C6_OBS_ITEM']);


    //conectando banco
    require('../config/conexao.php');

    //registrando eventos de alteracao do cabecalho 
    $desc_evento = '';
    //pesquisando dados do orçamento
    $pesquisa = mysql_query("SELECT * FROM md_crm_oportunidade as p WHERE p.id = '$ID'")  or die(mysql_error());
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        echo '<script>parent.location="md_crm_oportunidade.php?act=noloc"</script>';
    } else {
        $dados_opt = mysql_fetch_array($pesquisa);
    }

    $status_atual = $dados_opt['status'];
    if ($status_oportunidade != $status_atual) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Status Alterado de ' . $array_opt_status[$status_atual] . ' para ' . $array_opt_status[$status_oportunidade] . '<br>';
        if ($status_oportunidade != 'C') {
            $motivo_perda = 0;
        }
    }

    if ($dados_opt['motivo_perda_id'] != $motivo_perda) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Motivo de perda Alterado<br>';
    }

    if ($dados_opt['etapa_atual_id'] != $etapa_atual) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Etapa Alterada de ' . $array_opt_etapa[$dados_opt['etapa_atual_id']] . ' para ' . $array_opt_etapa[$etapa_atual] . '<br>';
    }

    if ($dados_opt['tipo_id'] != $tipo) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Tipo Alterado de ' . $array_opt_tipo[$dados_opt['tipo_id']] . ' para ' . $array_opt_etapa[$tipo] . '<br>';
    }

    if ($dados_opt['funil_id'] != $funil) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Funil Alterado de ' . $array_opt_funil[$dados_opt['funil_id']] . ' para ' . $array_opt_funil[$funil] . '<br>';
    }

    if ($dados_opt['campanha_id'] != $campanha) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Campanha Alterado de ' . $array_opt_campanha[$dados_opt['campanha_id']] . ' para ' . $array_opt_campanha[$campanha] . '<br>';
    }

    if ($dados_opt['fonte_id'] != $fonte) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Fonte Alterada de ' . $array_opt_fonte[$dados_opt['fonte_id']] . ' para ' . $array_opt_fonte[$fonte] . '<br>';
    }

    if ($dados_opt['data_inicio'] != $data_inicio) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Data início Alterado de ' . date("d/m/Y", strtotime($dados_opt['data_inicio'])) . ' para ' . date("d/m/Y", strtotime($data_inicio)) . '<br>';
    }

    if ($dados_opt['valor'] != $valor) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Valor Alterado de R$ ' . number_format($dados_opt['valor'], 2, ',', '.') . ' para R$ ' . number_format($valor, 2, ',', '.') . '<br>';
    }

    if ($dados_opt['nome'] != $nome) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Nome Alterado de ' . $dados_opt['nome'] . ' para ' . $nome . '<br>';
    }

    if ($dados_opt['email'] != $email) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Email Alterado de ' . $dados_opt['email'] . ' para ' . $email . '<br>';
    }

    if ($dados_opt['telefone'] != $telefone) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Telefone Alterado de ' . $dados_opt['telefone'] . ' para ' . $telefone . '<br>';
    }

    if ($dados_opt['uf'] != $uf) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Estado Alterado de ' . $dados_opt['uf'] . ' para ' . $uf . '<br>';
    }

    if ($dados_opt['vendedor_id'] != $vendedor) {
        $desc_evento .= '<i class="fas fa-caret-right"></i> Vendedor da Oportunidade Alterado <br>';
    }

    if ($dados_opt['revenda_id'] != $revenda) {
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




    //salvando alteracao cabecalho oportunidade
    mysql_query("START TRANSACTION");

    $query_update = "UPDATE md_crm_oportunidade SET vendedor_id='$vendedor', tipo_id='$tipo',funil_id='$funil', fonte_id='$fonte',campanha_id='$campanha', data_inicio='$data_inicio', valor='$valor',nome='$nome',email='$email',telefone='$telefone',uf='$uf',etapa_atual_id='$etapa_atual',tabela_id='$tabela',update_at='$updated_at', motivo_perda_id='$motivo_perda', revenda_id='$revenda',status='$status_oportunidade' WHERE id = $ID";

    $upd = mysql_query("$query_update") or die(mysql_error());

    $qtde_itens = count($C6_COD);


    //se foi enviado itens
    if ($qtde_itens > 0) {
        //salvando itens
        for ($i = 0; $i < $qtde_itens; $i++) {
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
                $query_update_item = "UPDATE md_crm_oportunidade_itens SET updated_at='$updated_at',qtde_cx='$it_qtdcx',qtde_frac='$it_qtdfrac',qtde='$it_qtde',prc_tab='$it_prcven',ipi='$it_ipi',peso='$it_pesob' WHERE id = $it_id";

                $upd2 = mysql_query("$query_update_item") or die(mysql_error());

                //commit
                if ($upd and $upd2) {
                    //$desc_evento .= '<i class="fas fa-caret-right"></i> Item alterado: ' . $it_cod . ' Qtde: ' . $it_qtde .'<br>';
                    mysql_query("COMMIT");
                    $result = 1;
                } else {
                    mysql_query("ROLLBACK");
                    $result = $upd2;
                }
            } else if ($it_status == 'N' and $it_id == 0) {
                //SE ITEM NOVO ADICIONAR NO DOCUMENTO
                $sql_inserir_itens = "INSERT INTO md_crm_oportunidade_itens (created_at, updated_at, oportunidade_id, codigo, descricao, unidade, qtde_cx, qtde_frac, qtde, prc_tab, desconto, ipi, peso, data_prev_fatura, obs,  status ) VALUES ";
                $sql_inserir_itens .= " ('{$updated_at}','{$updated_at}','{$ID}','{$it_cod}','{$it_desc}','{$it_um}','{$it_qtdcx}','{$it_qtdfrac}','{$it_qtde}','{$it_prcven}','{$it_desconto}','{$it_ipi}','{$it_pesob}','{$it_dtentrega}','{$it_obs}','A')";

                $sql_insert_item = mysql_query($sql_inserir_itens) or die(mysql_error());

                //commit
                if ($upd and $sql_insert_item) {
                    $desc_evento .= '<i class="fas fa-caret-right"></i> Item Incluido: ' . $it_cod . ' Qtde: ' . $it_qtde . '<br>';
                    mysql_query("COMMIT");
                    $result = 1;
                } else {
                    mysql_query("ROLLBACK");
                    $result = $sql_insert_item;
                }
            } else if ($it_status == 'D' and $it_id <> 0) {
                // MARCADO PARA DELETAR E TEM ID = ALTERA STATUS PARA D = DELETADO
                $sql_del_item = mysql_query("UPDATE md_crm_oportunidade_itens SET status='D' WHERE id = $it_id") or die(mysql_error());

                //commit
                if ($upd and $sql_del_item) {
                    $desc_evento .= '<i class="fas fa-caret-right"></i> Item excluido: ' . $it_cod . '<br>';
                    mysql_query("COMMIT");
                    $result = 1;
                } else {
                    mysql_query("ROLLBACK");
                    $result = $sql_del_item;
                }
            }
        }
    } else {
        //se nao tem item sql_insert_item = true
        $sql_insert_item = TRUE;
        //commit
        if ($upd and $sql_insert_item) {
            mysql_query("COMMIT");
            $result = 1;
        } else {
            mysql_query("ROLLBACK");
            $result = $sql_insert_item;
        }
    }

    //registrando evento
    $query_evento = "INSERT INTO md_crm_eventos 
    (oportunidade_id, created_at, etapa_id, usuario_id, descricao, acao, acao_data, acao_status, status) VALUES ('$oportunidade_id', '$updated_at', '$etapa_atual', '$usuario_codigo', '$desc_evento', '', '', '', 'A')";

    $result_evento = mysql_query("$query_evento") or die(mysql_error());
}
echo $result;
// echo 'qtde item= ' . $qtde_itens . ' status = ' . $it_status . ' - cod ' . $it_cod;
