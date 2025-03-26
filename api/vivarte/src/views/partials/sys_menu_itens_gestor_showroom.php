<nav class="mt-2">
  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
    <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
    <li class="nav-item">
      <a href="md_vendas_dashboard1" class="nav-link">
        <i class="nav-icon fas fa-tachometer-alt"></i>
        <p>Dashboard</p>
      </a>
    </li>

    <li class="nav-item">
      <a href="md_vendas_orcamento" class="nav-link">
        <i class="nav-icon fas fa-file-invoice"></i>
        <p>Orçamentos</p>
      </a>
    </li>

    <li class="nav-item">
      <a href="md_consulta_produto" class="nav-link">
        <i class="nav-icon fas fa-search"></i>
        <p>Consultar Produto</p>
      </a>
    </li>

    <li class="nav-item">
      <a href="sys_parceiro" class="nav-link">
        <i class="nav-icon fas fa-user-tie"></i>
        <p>Parceiros</p>
      </a>
    </li>

    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fas fa-handshake"></i>
        <p>
          CRM
          <i class="fas fa-angle-left right"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="md_crm_oportunidade" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Oportunidades</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="md_crm_oportunidade_dashboard1" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Dashboards</p>
          </a>
        </li>

      </ul>
    </li>

    

    <li class="nav-header">RELATÓRIOS</li>
    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fa fa-print"></i>
        <p>
          Orçamentos
          <i class="fas fa-angle-left right"></i>
        </p>
      </a>

      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="md_orcamentos_rel2" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Orçamentos</p>
          </a>
        </li>
      </ul>

      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="md_orcamentos_rel3" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Orç Loja\Vendedor</p>
          </a>
        </li>
      </ul>
    </li>

    <?php 
    if ($perfil == 'G') {
      $exibir_rel_vendas = '';
    } else {
      $exibir_rel_vendas = 'd-none';
    }
    ?>
    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fa fa-print"></i>
        <p>
          Vendas
          <i class="fas fa-angle-left right"></i>
        </p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a href="md_vendas_rel_carteira" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Carteira</p>
          </a>
        </li>
        <li class="nav-item ">
          <a href="md_vendas_rel1" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Vendedor\Cli</p>
          </a>
        </li>
        <li class="nav-item ">
          <a href="md_vendas_rel2" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Vendedor\Prod</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="md_vendas_rel5" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Vendedor (Parceiro)</p>
          </a>
        </li>
        <li class="nav-item ">
          <a href="md_vendas_rel4" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Cliente\Prod</p>
          </a>
        </li>
        <li class="nav-item ">
          <a href="md_vendas_rel3" class="nav-link">
            <i class="far fa-circle nav-icon"></i>
            <p>Produto\Mês</p>
          </a>
        </li>
      </ul>
    </li>
    
    
  </ul>
</nav>