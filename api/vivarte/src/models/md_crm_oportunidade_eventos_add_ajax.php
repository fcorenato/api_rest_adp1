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

//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cabecalho do documento
    $oportunidade_id = strtoupper($_POST['ev_idopt']);
    $etapa_atual = $_POST['ev_etapa_atual'];
    $desc_evento = $_POST['ev_descricao'];
    $acao = $_POST['ev_acao'];
    $acao_data = $_POST['ev_data_acao'];
    $acao_status = 'A';
    $created_at = date("Y-m-d H:i:s");

    require('../config/conexao.php');

    //registrando evento
    $query_evento = "INSERT INTO md_crm_eventos 
    (oportunidade_id, created_at, etapa_id, usuario_id, descricao, acao, acao_data, acao_status, status) VALUES ('$oportunidade_id', '$created_at', '$etapa_atual', '$usuario_codigo', '$desc_evento', '$acao', '$acao_data', '$acao_status', 'A')";

    $result_evento = mysql_query("$query_evento") or die(mysql_error());
}

echo $result_evento;
