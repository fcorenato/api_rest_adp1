<?php
date_default_timezone_set('America/Sao_Paulo');
/* status oportunindade no portal
<option value="A">EM ANDAMENTO</option>
<option value="P">PAUSADO</option>
<option value="V">VENDIDO</option>
<option value="C">PERDIDO</option>
*/
$array_opt_status = array('A' => 'EM ANDAMENTO', 'P' => 'PAUSADO', 'V' => 'VENDIDO', 'C' => 'PERDIDO');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  require('../../src/config/SUsuario.php');
  //data do dia para calculos
  $data_hoje = date("Y-m-d"); //data atual formado 20201205

  //filtro por periodo
  if (isset($_POST['data_inicial']) and isset($_POST['data_final'])) {
    $tipo_data = $_POST['tipo_data'];
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    if ($data_inicial != "" and $data_final != "") {
      if ($tipo_data == 'CONVERSAO') {
        $pesquisa_por_data = " AND STR_TO_DATE(op.data_inicio, '%Y-%m-%d') BETWEEN '$data_inicial' AND '$data_final' ";
      } else {
        $pesquisa_por_data = " AND op.data_inicio between '$data_inicial' and '$data_final' ";
      }
    }
  }

  //filtro status
  if (isset($_POST['status'])) {
    $status_cod = strtoupper($_POST['status']);
    if ($status_cod != "") {
      $pesquisa_por_status = " AND op.status = '$status_cod' ";
    }
  }

  //filtro etapa
  if (isset($_POST['etapa'])) {
    $etapa_cod = strtoupper($_POST['etapa']);
    if ($etapa_cod != "") {
      $pesquisa_por_etapa_cod = " AND op.etapa_atual_id = '$etapa_cod' ";
    }
  }

  // filtro por campanha
  if (isset($_POST['campanha'])) {
    $campanha_cod = strtoupper($_POST['campanha']);
    if ($campanha_cod != "" and $campanha_cod != "Todos") {
      $pesquisa_por_campanha = " AND op.campanha_id = '$campanha_cod' ";
    }
  }

  // filtro por campanha
  if (isset($_POST['local'])) {
    $local = strtoupper($_POST['local']);
    if ($local == "SHOWROOM") {
      $pesquisa_por_local = " AND us.unidade_codigo = 129 ";
    }

    if ($local == "FABRICA") {
      $pesquisa_por_local = " AND us.unidade_codigo = 1 ";
    }
  }

  // filtro por vendedor
  if (isset($_POST['vendedor'])) {
    $vendedor_cod = strtoupper($_POST['vendedor']);
    if ($vendedor_cod != "") {
      $pesquisa_por_vendedor = " AND op.vendedor_id = '$vendedor_cod' ";
    }
  }

  // filtro por cliente
  if (isset($_POST['cliente_nome'])) {
    $cliente_nome = strtoupper($_POST['cliente_nome']);
    if ($cliente_nome != "") {
      $pesquisa_por_cliente_nome = " AND op.nome like '%$cliente_nome%' ";
    }
  }


  require('../config/conexao.php');



  //PESQUISANDO NO BANCO DE DADOS MYSQL
  $sql1 = "SELECT DISTINCT 
  op.id, et.descricao as etapa, op.uf, op.nome, us.nome_completo as vendedor, op.data_inicio, op.valor, op.status,  cp.descricao as desc_campanha, cp.id as id_campanha, ft.descricao as desc_fonte, 
  (select DATE_FORMAT(ev.created_at, '%Y-%m-%d') from md_crm_eventos as ev where ev.oportunidade_id = op.id ORDER BY ev.created_at DESC LIMIT 1) as ult_intercao 
  FROM md_crm_oportunidade as op
  LEFT JOIN md_crm_etapa as et ON et.id = op.etapa_atual_id
  LEFT JOIN md_crm_campanha as cp ON cp.id = op.campanha_id
  LEFT JOIN md_crm_fonte as ft ON ft.id = op.fonte_id
  LEFT JOIN md_crm_tipo as tp ON tp.id = op.tipo_id
  LEFT JOIN sys_usuarios as us ON us.codigo = op.vendedor_id
  WHERE op.id > 0
  AND op.status != 'I'
  $pesquisa_por_data
  $pesquisa_por_campanha
  $pesquisa_por_local
  $pesquisa_por_vendedor
  $pesquisa_por_cliente_nome
  $pesquisa_por_status
  $pesquisa_por_etapa_cod
  ORDER BY op.id DESC, op.data_inicio DESC";

  $pesquisa = mysql_query($sql1);

  $linhas = mysql_num_rows($pesquisa);
  if ($linhas == 0) {
    $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
  } else {
    //cabecalho da tabela resultado
    $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-striped table-head-fixed">
              <thead>
                <tr>
                    <th>Código</th>
                    <th>Etapa</th>
                    <th>Campanha</th>
                    <th>Fonte</th>
                    <th>UF</th>
                    <th>Nome</th>
                    <th>Vendedor</th>
                    <th>Data Criado</th>
                    <th class="d-none">Tarefas</th>
                    <th>Valor R$</th>
                    <th>Interação</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
              </thead>
              <tbody>

              ';
    $total_valor_pesq = 0;
    $qtde_reg_pesq = 0;
    while ($dados = mysql_fetch_array($pesquisa)) {

      //pesquisando eventos do orçamento para listar tarefas pendentes
      $idopt = $dados["id"];
      $query_eventos = "SELECT *, e.descricao as desc_opt, et.descricao as desc_evento FROM md_crm_eventos as e 
                    LEFT JOIN md_crm_etapa as et ON et.id = e.etapa_id
                    LEFT JOIN sys_usuarios as us ON us.codigo = e.usuario_id
                    WHERE e.oportunidade_id = '$idopt' AND acao > 0
                    ORDER By e.id DESC";
      $pesquisa_eventos = mysql_query("$query_eventos")  or die(mysql_error());
      $linhas = mysql_num_rows($pesquisa_eventos);
      if ($linhas == 0) {
        // echo '<script>parent.location="md_crm_oportunidade.php?act=noloc"</script>';
      } else {
        while ($dados_eventos = mysql_fetch_array($pesquisa_eventos)) {
          $create_data =  date("d/m/Y H:i", strtotime($dados_eventos["created_at"]));

          if ($dados_eventos["acao"] > 0) {
            $icone_evento = '<i class="fas fa-bolt text-warning"></i>';
          } else {
            $icone_evento = '<i class="fas fa-file-alt"></i>';
          }

          $acao_data =  date("d/m/Y", strtotime($dados_eventos["acao_data"]));
          if ($dados_eventos["acao"] == 1) {
            $descricao_ev = '<i class="fab fa-whatsapp-square"></i> Fazer Contato Whatsapp em ' . $acao_data . '<br>';
          } else  if ($dados_eventos["acao"] == 2) {
            $descricao_ev = '<i class="fas fa-phone-square"> <i class="fab fa-whatsapp-square"></i></i> Fazer Contato Telefone em ' . $acao_data . '<br>';
          } else  if ($dados_eventos["acao"] == 3) {
            $descricao_ev = '<i class="fas fa-envelope-square"></i> Fazer Contato E-mail em ' . $acao_data . '<br>';
          } else {
            $descricao_ev = '';
          }
          $descriacao_ev = $descricao_ev . $dados_eventos["desc_opt"];
          $op_eventos .= '
        <tr class="tr_result">
            <td>' . $icone_evento . ' ' . $create_data . '</td>
            <td>' . $dados_eventos["desc_evento"] . '</td>
            <td>' . $dados_eventos["nome_completo"] . '</td>
            <td >' . $descriacao_ev . '</td>

        </tr>
        ';
        }
      }

      $qtde_reg_pesq++;
      $total_valor_pesq += $dados["valor"];

      //tratanto o status
      if ($dados["status"] == 'A') {
        $status_doc = '<span class="badge bg-success">Em andamento</span>';
        $doc_acoes = '
        <a class="dropdown-item bg-success" href="md_crm_oportunidade_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>';
      } else if ($dados["status"] == 'P') {
        $status_doc = '<span class="badge bg-secondary">Pausado</span>';

        $doc_acoes = '
        <a class="dropdown-item bg-success" href="md_crm_oportunidade_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>';
      } else if ($dados["status"] == 'C') {
        $status_doc = '<span class="badge bg-danger">Perdido</span>';
        $doc_acoes = '
        <a class="dropdown-item bg-success" href="md_crm_oportunidade_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>';
      } else if ($dados["status"] == 'V') {
        $status_doc = '<span class="badge bg-primary">Vendido</span>';
        $doc_acoes = '
        <a class="dropdown-item bg-success" href="md_crm_oportunidade_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>';
      } else if ($dados["status"] == 'I') {
        $status_doc = '<span class="badge bg-secondary">Inválido</span>';
        $doc_acoes = '
        <a class="dropdown-item bg-success" href="md_crm_oportunidade_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>';
      }

      //calculando dias da ultima interacao
      $data_hoje = date('Y-m-d');
      $dia1 = strtotime($data_hoje);
      $dia2 = strtotime($dados["ult_intercao"]);
      $dif_dia =  ($dia2 - $dia1) / 86400;
      $dif_dia =  $dif_dia < 0 ?  $dif_dia * -1 : $dif_dia;
      if ($dados["status"] == 'A') {
        if ($dif_dia >= 15) {
          $badger_color = 'warning';
        } else if ($dif_dia >= 30) {
          $badger_color = 'danger';
        } else {
          $badger_color = 'info';
        }
      } else {
        $badger_color = ' d-none';
      }
      $resultado_rel .= '
            <tr class="tr_result">
            <td>' . $dados["id"] . '</td>
            <td>' . $dados["etapa"] . '</td>
            <td>' . $dados["id_campanha"] . '-' . $dados["desc_campanha"] . '</td>
            <td>' . $dados["desc_fonte"] . '</td>
            <td>' . $dados["uf"] . '</td>
            <td>' . $dados["nome"] . '</td>
            <td>' . $dados["vendedor"] . '</td>
            <td>' . date("d/m/Y", strtotime($dados["data_inicio"])) . '</td>
            <td class="d-none">Contato <i class="fas fa-phone-square"> <i class="fab fa-whatsapp-square"></i></i></td>
            <td align="right">' . number_format($dados["valor"], 2, ',', '.') . '</td>
            <td><span class="badge badge-' . $badger_color . '">há ' . $dif_dia . ' dias</span>' . date("d/m/Y", strtotime($dados["ult_intercao"])) . '</td>
            <td>' . $status_doc . '</td>
            
            <td class="">
              <div class="dropdown">
                <a class="btn btn-sm btn-info dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  Ações
                </a>
                
                <div class="dropdown-menu bg-info" aria-labelledby="dropdownMenuLink">
                    <a class="dropdown-item bg-info consulta_eventos" iddoc="' . $dados["id"] . '" href="#" ><i class="far fa-clock"></i> Eventos</a>
                 
      
                    ' . $doc_acoes . '
                  
                </div>
              </div>
            </td>
            <td class="d-none" >
               <a class="btn btn-xs btn-info consulta_eventos" iddoc="' . $dados["id"] . '" href="#" data-toggle="tooltip" data-placement="top" title="Eventos"><i class="far fa-clock"></i> </a>

              <a class="btn btn-xs btn-secondary" href="md_crm_oportunidade_upd.php?id=' . $dados["id"] . '&state=view" data-toggle="tooltip" data-placement="top" title="Visualizar"><i class="far fa-eye"></i></a>

              

              ' . $doc_acoes . '
         
            </td>
      </tr>
    ';
    }
    // rodape da tabela resultado
    $resultado_rel .= '
    <tr class="">
    <td align="right" >' . $qtde_reg_pesq . '</td>
    <td colspan="6">Registros pesquisados</td>
    <td align="right">Total R$</td>
    <td align="right">' . number_format($total_valor_pesq, 2, ',', '.') . '</td>
    <td colspan="3"></td>
    </tr>
    </tbody>
    </table>
    </div>';
  }
} // fim do POST

echo $resultado_rel;
