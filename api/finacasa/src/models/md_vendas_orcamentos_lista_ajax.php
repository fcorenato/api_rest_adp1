<?php
date_default_timezone_set('America/Sao_Paulo');
/* status orcamentos no portal
A = ABERTO
B = BLOQUEADO
C = CANCELADO
G = AGUARDANDO CONFIRMACAO DE PAGAMENTO
N = AGUARDANDO ANALISE DOS DOCUMENTOS
P =  PED. VENDA
F = FATURADO
V = VENCIDO
*/
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  require('../../src/config/SUsuario.php');
  $aprovador = $_SESSION["aprovador"];
  $analista_comerc = $_SESSION["analista_comerc"];
  $un_codigo_user = 0;
  $un_codigo_user = $_SESSION["un_codigo"];
  $codigo_usuario = $_SESSION["codigo_usuario"];

  require('../config/conexao.php');

  //pesquisando vendedores no biv
  $query3 = "
            SELECT codigo, nome_completo, perfil FROM sys_usuarios AS us
            ORDER BY nome_completo
          ";

  $result_query3 = mysql_query($query3);
  $qtde_query3 = mysql_num_rows($result_query3);

  if ($qtde_query3 == 0) {
    $vendedores .= '';
  } else {
    while ($campos = mysql_fetch_array($result_query3)) {
      $vendedores[$campos['codigo']] = $campos['nome_completo'];
    }
  }

  // FILTROS DO RELATORIO

  //filtro por unidade
  if ($perfil == 'G') {
    if (isset($_POST['unidade']) and $_POST['unidade'] != '') {
      $und_pes = $_POST['unidade'];
      $pesquisa_por_unidade = "AND un.codigo = '$und_pes'";
    } else {
      $pesquisa_por_unidade = '';
    }
  } else if ($un_codigo_user > 0) {
    $pesquisa_por_unidade = "AND un.codigo = '$un_codigo_user'";
    $ver_apenas_seu_orcamento = " AND  pv.orc_created_user = '$codigo_usuario' ";
  } else {
    $pesquisa_por_unidade = "AND un.codigo = 'UNIDADE_NAO_DEFINIDA'";
  }

 
  //filtro por periodo
  if (isset($_POST['data_inicial']) and isset($_POST['data_final'])) {
    $tipo_data = $_POST['tipo_data'];
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    if ($data_inicial != "" and $data_final != "") {
      if ($tipo_data == 'CONVERSAO') {
        $pesquisa_por_data = " AND STR_TO_DATE(pv.pedido_conv_date, '%Y-%m-%d') BETWEEN '$data_inicial' AND '$data_final' ";
      } else {
        $pesquisa_por_data = " AND pv.orc_data_emissao between '$data_inicial' and '$data_final' ";
      }
    }
  }

  //data do dia para verificar orcamentos vencidos
  $data_hoje = date("Y-m-d"); //data atual formado 20201205
  if (isset($_POST['status'])) {
    $status_cod = strtoupper($_POST['status']);
    if ($status_cod != "") {
      if ($status_cod == "V") {
        $pesquisa_por_status = " AND (pv.status = 'A' or pv.status = 'B' or pv.status = 'V' ) AND pv.orc_data_valid < '$data_hoje' ";
      } else {
        $pesquisa_por_status = " AND pv.status = '$status_cod' ";
      }
    }
  }

  if (isset($_POST['vendedor'])) {
    $vendedor_cod = strtoupper($_POST['vendedor']);
    if ($vendedor_cod != "") {
      $pesquisa_por_vendedor = " AND pv.vend1 = '$vendedor_cod' ";
    }
  }

  if (isset($_POST['parceiro'])) {
    $parceiro_cod = strtoupper($_POST['parceiro']);
    if ($parceiro_cod != "") {
      $pesquisa_por_parceiro = " AND pv.vend2 = '$parceiro_cod' ";
    }
  }

  if (isset($_POST['num_orcamento'])) {
    $num_orcamento = trim(strtoupper($_POST['num_orcamento']));
    if ($num_orcamento != '') {
      $pesquisa_por_orc = " AND pv.id LIKE '%$num_orcamento%'";
    }
  }

  if (isset($_POST['referencia'])) {
    $referencia_cod = trim(strtoupper($_POST['referencia']));
    if ($referencia_cod != '') {
      $pesquisa_por_ref = " AND ip.codigo = '$referencia_cod'";
    }
  }


  //PESQUISANDO NO BANCO DE DADOS MYSQL

  $pesquisa = mysql_query("SELECT DISTINCT pv.id, pv.empresa, pv.orc_created_user, pv.cliente_codigo, pv.cliente_razao, pv.filial_atend, pv.cpf_cnpj,   pv.vend1, pv.vend2 , pv.orc_created_at, pv.orc_data_emissao, pv.orc_data_valid, pv.pedido_conv_date, pv.total_final, pv.status, un.descricao,un.codigo, tp.descricao as tpdescricao
  FROM md_vendas_pedidos as pv
  LEFT JOIN md_vendas_pedidos_itens as ip ON pv.id = ip.pedido_id
  LEFT JOIN md_vendas_tabpreco as tp ON tp.codigo = pv.tabela_preco
  LEFT JOIN sys_usuarios as us ON pv.orc_created_user = us.codigo
  LEFT JOIN sys_unidades as un ON pv.unidade_codigo = un.codigo
  WHERE pv.id > 0
  $pesquisa_por_orc
  $pesquisa_por_ref
  $pesquisa_por_status
  $pesquisa_por_data
  $pesquisa_por_vendedor
  $pesquisa_por_parceiro
  $pesquisa_por_unidade
  $ver_apenas_seu_orcamento
  ORDER BY pv.orc_created_at DESC");
  $linhas = mysql_num_rows($pesquisa);
  if ($linhas == 0) {
    $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
  } else {
    //cabecalho da tabela resultado
    $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-striped table-head-fixed">
              <thead>
                <tr>
                  <th>Orc</th>
                  <th>Tabela</th>
                  <th>Vendedor</th>
                  <th>Parceiro</th>
                  <th>Cli Cod</th>
                  <th>Razão Social</th>
                  <th>Data Emissão</th>
                  <th>Data Validade</th>
                  <th>Data Conversão</th>
                  <th>Valor R$</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>

              ';
    $total_valor_pesq = 0;
    $qtde_reg_pesq = 0;
    while ($dados = mysql_fetch_array($pesquisa)) {
      $status_doc = '';
      $doc_acoes = '';
      $qtde_reg_pesq++;
      $total_valor_pesq += $dados["total_final"];
      if ($dados["status"] == 'A') {
        $status_doc = '<span class="badge bg-success">Aberto</span>';
        $doc_acoes = '
              <a target="_blank" class="dropdown-item bg-dark" href="src/relpdf/orcamento.php?id=' . $dados["id"] . '&state=view"><i class="fas fa-print"> Imprimir</i></a>

              <a class="dropdown-item bg-success" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>

              <a class="dropdown-item bg-info" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=1"><i class="fas fa-file-import"></i> Converter</a>

              <a class="dropdown-item bg-danger" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=2" ><i class="fas fa-times"></i> Cancelar</a>';
      } else if ($dados["status"] == 'B') {
        $status_doc = '<span class="badge bg-warning">Bloqueado</span>';

        if ($aprovador == 'S') {
          $libera_doc = '<a class="dropdown-item bg-warning" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=3" ><i class="fas fa-unlock-alt"></i> Liberar</a>';
        } else {
          $libera_doc = '';
        }

        $doc_acoes = '
              <a class="dropdown-item bg-success" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '" ><i class="fas fa-pencil-alt"></i> Editar</a>

              ' . $libera_doc . '
              
              <a class="dropdown-item bg-danger" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=2" ><i class="fas fa-times"></i> Cancelar</a>
              ';
      } else if ($dados["status"] == 'C') {
        $status_doc = '<span class="badge bg-danger">Cancelado</span>';
        $doc_acoes = '';
      } else if ($dados["status"] == 'V') {
        $status_doc = '<span class="badge bg-orange text-white">Vencido</span>';
        $doc_acoes = '';
      } else if ($dados["status"] == 'G') {
        $status_doc = '<span class="badge bg-blue1 text-white">Conf Pgto</span>';
        $doc_acoes = '<a target="_blank" class="dropdown-item bg-dark" href="src/relpdf/orcamento.php?id=' . $dados["id"] . '&state=view"><i class="fas fa-print"> Imprimir</i></a>';
        if ($analista_comerc == 'S') {
          $doc_acoes = '<a class="dropdown-item bg-warning" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=4"><i class="fas fa-clipboard-check"></i> Analisar Doc</a>';
        }
      } else if ($dados["status"] == 'N') {
        $status_doc = '<span class="badge bg-blue0">Em Análise</span>';
        $doc_acoes .= '<a target="_blank" class="dropdown-item bg-dark" href="src/relpdf/orcamento.php?id=' . $dados["id"] . '&state=view"><i class="fas fa-print"> Imprimir</i></a>';
        if ($analista_comerc == 'S') {
          $doc_acoes .= '<a class="dropdown-item bg-warning" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=4"><i class="fas fa-clipboard-check"></i> Analisar Doc</a>';

          $doc_acoes .= '<a class="dropdown-item bg-warning" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=4"><i class="fas fa-clipboard-check"></i> Data Entrega</a>';
        }
      } else if ($dados["status"] == 'P') {
        $status_doc = '<span class="badge bg-primary">Ped. Venda</span>';
        $doc_acoes = '<a target="_blank" class="dropdown-item bg-dark" href="src/relpdf/orcamento.php?id=' . $dados["id"] . '&state=view"><i class="fas fa-print"> Imprimir</i></a>';
        if ($analista_comerc == 'S') {
          $doc_acoes .= '<a class="dropdown-item bg-warning" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=5"><i class="fas fa-clipboard-check"></i> Data Entrega</a>';
          $doc_acoes .= '<a class="dropdown-item bg-info" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=6"><i class="fas fa-user-tie"></i> Parceiro</a>';
        }
        if ($aprovador == 'S') {
          $doc_acoes .= '<a class="dropdown-item bg-danger" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=2" ><i class="fas fa-times"></i> Cancelar</a>';
        }
      } else if ($dados["status"] == 'F') {
        $status_doc = '<span class="badge bg-blue2 text-white">Faturado</span>';
        $doc_acoes = '<a target="_blank" class="dropdown-item bg-dark" href="src/relpdf/orcamento.php?id=' . $dados["id"] . '&state=view"><i class="fas fa-print"> Imprimir</i></a>';
        if ($aprovador == 'S') {
          //$doc_acoes .= '<a class="dropdown-item bg-danger" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view&converte=2" ><i class="fas fa-times"></i> Cancelar</a>';
        }
      }

      if ($dados['pedido_conv_date'] != '' AND $dados['pedido_conv_date'] != 0 AND $dados['status'] == 'P') {
        $data_conv  = date("d/m/Y", strtotime($dados["pedido_conv_date"]));
      } else {$data_conv = '';}

      $resultado_rel .= '
      <tr class="tr_result">
            <td>' . $dados["id"] . '</td>
            <td>' . $dados["tpdescricao"] . '</td>
            <td>' . $vendedores[$dados["vend1"]] . '</td>
            <td>' . $vendedores[$dados["vend2"]] . '</td>
            <td>' . $dados["cliente_codigo"] . '</td>
            <td>' . $dados["cliente_razao"] . '</td>
            <td>' . date("d/m/Y", strtotime($dados["orc_created_at"])) . '</td>
            <td>' . date("d/m/Y", strtotime($dados["orc_data_valid"])) . '</td>
            <td>' . $data_conv . '</td>
            <td align="right">' . number_format($dados["total_final"], 2, ',', '.') . '</td>
            <td align="center">' . $status_doc . '</td>
            <td class="">
              <div class="dropdown">
                <a class="btn btn-sm btn-info dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  Ações
                </a>
                
                <div class="dropdown-menu bg-info" aria-labelledby="dropdownMenuLink">
                    <a class="dropdown-item bg-info consulta_eventos" iddoc="' . $dados["id"] . '" href="#" ><i class="far fa-clock"></i> Eventos</a>

                    <a class="dropdown-item bg-secondary" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view" ><i class="far fa-eye"></i> Visualizar</a>                   
      
                    ' . $doc_acoes . '
                  
                </div>
              </div>
            </td>
            <td class="d-none" >
               <a class="btn btn-xs btn-info consulta_eventos" iddoc="' . $dados["id"] . '" href="#" data-toggle="tooltip" data-placement="top" title="Eventos"><i class="far fa-clock"></i> </a>

              <a class="btn btn-xs btn-secondary" href="md_vendas_orcamento_upd.php?id=' . $dados["id"] . '&state=view" data-toggle="tooltip" data-placement="top" title="Visualizar"><i class="far fa-eye"></i></a>

              

              ' . $doc_acoes . '
         
            </td>
      </tr>
    ';
    }
    // rodape da tabela resultado
    $resultado_rel .= '
    <tr class="">
    <td align="right" >' . $qtde_reg_pesq . '</td>
    <td colspan="8">Registros pesquisados</td>
    <td align="right">Total R$</td>
    <td align="right">' . number_format($total_valor_pesq, 2, ',', '.') . '</td>
    <td colspan="2"></td>
    </tr>
    </tbody>
    </table>
    </div>';
  }
} // fim do POST

echo $resultado_rel;
