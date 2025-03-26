<?php
set_time_limit(0);

date_default_timezone_set('America/Sao_Paulo');

//verifiando session 
require('src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];
$aprovador = $_SESSION["aprovador"];
$cod_vend = $_SESSION["cod_vend"];
$perfil = $_SESSION["perfil"];


//buscando atividades agendada para usuario
require('src/config/conexao.php');
$query = "SELECT ev.oportunidade_id, ac.descricao,  ev.acao_data, op.status as op_status FROM `md_crm_eventos` ev 
          LEFT JOIN md_crm_acao ac ON ac.id = ev.acao
          LEFT JOIN md_crm_oportunidade as op ON op.id = ev.oportunidade_id
          WHERE usuario_id = $usuario_codigo 
          AND acao > 0 
          AND acao_status = 'A'
          AND op.status != 'C' AND op.status != 'V'
          ORDER BY acao_data DESC;";
// echo $query;
$pesquisa = mysql_query($query);
$linhas = mysql_num_rows($pesquisa);
$num_regs = 0;
if ($linhas > 0) {
  while ($dados_avisos = mysql_fetch_array($pesquisa)) {
    $num_regs++;
    $data_hoje = date('Y-m-d');
    $dia1 = strtotime($data_hoje);
    $dia2 = strtotime($dados_avisos["acao_data"]);
    $dif_dia =  ($dia2 - $dia1) / 86400;
    $dif_dia =  $dif_dia < 0 ?  $dif_dia * -1 : $dif_dia;
    $cor_regs = "badge-info";
    if ($dados_avisos["acao_data"] < $data_hoje) {
      $status_acao = '<span class="badge badge-danger">atrasado '.$dif_dia.' dias</span>';
      $cor_regs = "badge-danger";
    } else if ($dados_avisos["acao_data"] == $data_hoje) {
      $status_acao = '<span class="badge badge-warning"> hoje </span>';
      if ($cor_regs != "badge-danger") {
        $cor_regs = "badge-warning";
      }
    } else if ($dados_avisos["acao_data"] > $data_hoje) {
      $status_acao = '<span class="badge badge-info">agendado '.$dif_dia.' dias</span>';
    }
    $avisos_list .= '
        <div class="dropdown-divider"></div>
          <a href="md_crm_oportunidade_upd.php?id=' . $dados_avisos["oportunidade_id"] . '" class="dropdown-item">
            <i class="fas fa-handshake mr-2"></i> ' . $dados_avisos["descricao"] . ' OP: ' . $dados_avisos["oportunidade_id"] . '
            <span class="float-right text-muted text-sm">dia ' . date("d/m/Y", strtotime($dados_avisos["acao_data"])) . ' ' . $status_acao . '</span>
         </a>
    ';
  }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="icon" href="dist/img/fav.png">

  <title>BIV Vivarte</title>

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <!-- pace-progress -->
  <link rel="stylesheet" href="plugins/pace-progress/themes/black/pace-theme-flat-top.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Select2 -->
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <!-- Sys style -->
  <link rel="stylesheet" href="dist/css/sys_style.css">
  <link rel="stylesheet" type="text/css" href="dist/css/sys_style_print.css" media="print" />

  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

  <!-- AOS animation	-->
  <link href="plugins/aos/aos.css" rel="stylesheet">
  <script src="plugins/aos/aos.js"></script>

  <style type="text/css" media="print">
    .noprint {
      display: none;
    }

    .tabela_relatorio {
      height: auto !important;
    }

    .dataTables_scrollBody {
      height: auto !important;
    }

    .subtotal_rel {
      background-color: #ccc !important;
    }
  </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed pace-success">
  <div class="wrapper">