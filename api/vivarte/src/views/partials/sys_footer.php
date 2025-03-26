</div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <strong>Copyright &copy; 2019-2022 <a href="https://renovesolucoes.com.br/">Renove Soluções</a>.</strong>
    Todos os direitos reservados.
    <div class="float-right d-none d-sm-inline-block">
      <b>Versão</b> 1.8.6
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<!-- pace-progress -->
<script src="plugins/pace-progress/pace.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- Toastr -->
<script src="plugins/toastr/toastr.min.js"></script>
<!-- Select2 -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- SWEET ALERT -->
<script src="plugins/sweetalert/sweetalert.min.js"></script>

<!-- Funçoes do Sistema -->
<script src="dist/js/sys_functions-v240822v1.js"></script>


<!-- Ativando Tooltip -->
<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip()
  })

  /** funcao para corrir comportamento do Tooltip do bootstrap que nao some ao clicar no elemento */
  $('[data-toggle="tooltip"]').tooltip({
      trigger : 'hover'
  })

  /* url alt 
  history.pushState({}, null, 'app');
  */
</script>


<!-- Ativando AOS Animate -->
<script>
  AOS.init();
</script>

</body>
</html>