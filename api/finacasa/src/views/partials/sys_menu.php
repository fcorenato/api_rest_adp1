  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light"><!-- style="background-color: #ffba6b;" -->
    <a href="sys_home" class="d-md-none" style="width: 85%;"><img src="dist/img/logo.png" alt="Vivarte" class="img-fluid" style="width: 150px;"></a>
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a id="btn_menu_lateral" class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="sys_home" class="nav-link"> <img src="dist/img/logo.png" alt="Vivarte" class="img-fluid" style="width: 150px;"> <span class="btn bg-warning">  ****  AMBIENTE DE HOMOLOGAÇÃO   ****  </span></a>
    </ul>
    
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="sys_home" class="brand-link" style="background: radial-gradient(ellipse farthest-side at 100% 100%,#ffbc6f 40%,#ffab48 90%);">
      <img src="dist/img/brand-logo.png" alt="Logo" class="brand-image" style="opacity: .8">
      <span class="brand-text font-weight-light"> Portal BIV</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="dist/img/avatar0.png" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="src/config/logout.php" class="d-block" data-toggle="tooltip" data-placement="right" title="Sair"><?= substr($usuario_nome, 0, 18); ?>&nbsp; <i class="fas fa-door-open"></i></a>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <?php
      if ($perfil == 'V') {
        include('sys_menu_itens_vend_externo.php');
      } else if ($perfil == 'G' || $perfil == 'U') {
        include('sys_menu_itens_gestor.php');
      }
      ?>

      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>