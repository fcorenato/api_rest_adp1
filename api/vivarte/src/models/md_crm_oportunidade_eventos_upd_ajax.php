<?php
date_default_timezone_set('America/Sao_Paulo');
//verifiando session 
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
$result_evento = 99;
//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $idevento = strtoupper($_POST['idevento']);
    $data_update = $orc_updated_at = date("Y-m-d H:i:s");
    require('../config/conexao.php');

    $query_update = "UPDATE `md_crm_eventos` SET acao_status = 'F', acao_data_update = '$data_update' WHERE id = $idevento";

    $result_evento = mysql_query("$query_update") or die(mysql_error());
    $result_evento = $query_update;
}

echo $result_evento;
