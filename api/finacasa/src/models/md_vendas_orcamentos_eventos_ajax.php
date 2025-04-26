<?php
date_default_timezone_set('America/Sao_Paulo');
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //listando eventos do documento
    //conect DB
    require('../config/conexao.php');
    if (isset($_POST['iddoc'])) {
        $iddoc = $_POST['iddoc'];

        $pesquisa = mysql_query("SELECT * FROM md_vendas_pedidos_eventos
        LEFT JOIN sys_usuarios ON evento_user = codigo
        WHERE pedido_id =  $iddoc  
        ORDER BY created_at DESC");
        $linhas = mysql_num_rows($pesquisa);
        if ($linhas == 0) {
            $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum evento encontrado para o documento: '.$iddoc.'.</td></tr></table>';
        } else {
            //cabecalho da tabela resultado
            $resultado_rel = '
              <h5>Eventos do orçamento: <span id="iddoc">'.$iddoc.'</span></h5>
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-striped table-head-fixed">
              <thead>
                <tr>
                  <th>Data - Hora</th>
                  <th>Evento</th>
                  <th>Descrição</th>
                  <th>Usuário</th>
                </tr>
              </thead>
              <tbody>

              ';
            while ($dados = mysql_fetch_array($pesquisa)) {
              $data_evento = date("d/m/Y H:i", strtotime($dados["created_at"]));
                $resultado_rel .= '
                <tr class="tr_result">
                    <td>' . $data_evento . '</td>
                    <td>' . $dados["evento"] . '</td>
                    <td>' . $dados["descricao"] . '</td>
                    <td>' . $dados["nome_completo"] . '</td>
                </tr>';
            }
            // rodape da tabela resultado
            $resultado_rel .= '
            </tbody>
            </table>
            </div>';
        }
    }
} // fim do POST

echo $resultado_rel;