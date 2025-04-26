<?php
set_time_limit(0);

date_default_timezone_set('America/Sao_Paulo');

//verifiando session 
require ('src/config/SUsuario.php');
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];
$aprovador = $_SESSION["aprovador"];
$cod_vend = $_SESSION["cod_vend"];
$perfil = $_SESSION["perfil"];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <link rel="icon" href="dist/img/fav.png">

  <title>BIV Fina Casa</title>

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
   .noprint{display: none;}
   .tabela_relatorio{
     height: auto !important;
   }
   .dataTables_scrollBody{
    height: auto !important;
   }
   .subtotal_rel{
     background-color: #ccc !important;
   }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed pace-success">
<div class="wrapper">