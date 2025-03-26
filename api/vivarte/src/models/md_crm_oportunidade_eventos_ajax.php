<?php
date_default_timezone_set('America/Sao_Paulo');
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //listando eventos do documento
    //conect DB
    require('../config/conexao.php');
    if (isset($_POST['iddoc'])) {
        $iddoc = $_POST['iddoc'];

        $query_eventos = "SELECT *, e.id as evento_id, e.descricao as desc_opt, e.acao_data, e.status,  et.descricao as desc_evento FROM md_crm_eventos as e 
                            LEFT JOIN md_crm_etapa as et ON et.id = e.etapa_id
                            LEFT JOIN sys_usuarios as us ON us.codigo = e.usuario_id
                            WHERE e.oportunidade_id = '$iddoc'
                            ORDER By e.id DESC";

        $pesquisa = mysql_query("$query_eventos");
        $linhas = mysql_num_rows($pesquisa);
        if ($linhas == 0) {
            $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum evento encontrado para a oportunidade: ' . $iddoc . '.</td></tr></table>';
        } else {
            //cabecalho da tabela resultado
            $resultado_rel = '
            <table id="tabela_relatorio " class="table table-sm table-hover table-bordered table-striped table-head-fixed">
              <thead>
                <tr>
                    <th>Data</th>
                    <th>Etapa</th>
                    <th>Responsável</th>
                    <th>Descrição</th>
                </tr>
              </thead>
              <tbody>

              ';
            while ($dados_eventos = mysql_fetch_array($pesquisa)) {
                $create_data =  date("d/m/Y H:i", strtotime($dados_eventos["created_at"]));
                $status_evento = $dados_eventos["acao_status"];
                $data_hoje = date('Y-m-d');

                $dia1 = strtotime($data_hoje);
                $dia2 = strtotime($dados_eventos["acao_data"]);
                $dif_dia =  ($dia2 - $dia1) / 86400;
                $dif_dia =  $dif_dia < 0 ?  $dif_dia * -1 : $dif_dia;

                if ($dados_eventos["acao_data"] < $data_hoje) {
                    $status_acao = '<span class="badge badge-danger">atrasado '.$dif_dia.' dias</span>';
                } else if ($dados_eventos["acao_data"] == $data_hoje) {
                    $status_acao = '<span class="badge badge-warning">hoje</span>';
                } else if ($dados_eventos["acao_data"] > $data_hoje) {
                    $status_acao = '<span class="badge badge-info">agendado '.$dif_dia.' dias</span>';
                }

                if ($dados_eventos["acao"] > 0) {
                    $icone_evento = '<i class="fas fa-bolt text-warning"></i>';
                    if ($status_evento == 'A') {
                        $concluir_evento = '<a href="#" class="acao_finalizar" idacao="' . $dados_eventos["evento_id"] . '"><span class="badge badge-success"><i class="fas fa-check"></i> Finalizar</span></a>';
                    } else {
                        $status_acao = '';
                        $concluir_evento = '<span class="badge badge-secondary"> Feito </span>';
                    }
                } else {
                    $icone_evento = '<i class="fas fa-file-alt"></i>';
                }

                $acao_data =  date("d/m/Y", strtotime($dados_eventos["acao_data"]));
                if ($dados_eventos["acao"] == 1) {
                    $descricao_ev = '<i class="fab fa-whatsapp-square"></i> Fazer Contato Whatsapp em ' . $acao_data . ' ' . $status_acao . ' ' . $concluir_evento . '<br>';
                } else  if ($dados_eventos["acao"] == 2) {
                    $descricao_ev = '<i class="fas fa-phone-square"> <i class="fab fa-whatsapp-square"></i></i> Fazer Contato Telefone em ' . $acao_data . ' ' . $status_acao . ' ' . $concluir_evento . '<br>';
                } else  if ($dados_eventos["acao"] == 3) {
                    $descricao_ev = '<i class="fas fa-envelope-square"></i> Fazer Contato E-mail em ' . $acao_data . ' ' . $status_acao . ' ' . $concluir_evento . '<br>';
                } else {
                    $descricao_ev = '';
                }
                $descriacao_ev = $descricao_ev . $dados_eventos["desc_opt"];
                $resultado_rel .= '
                    <tr class="tr_result">
                        <td>' . $icone_evento . ' ' . $create_data . '</td>
                        <td>' . $dados_eventos["desc_evento"] . '</td>
                        <td>' . $dados_eventos["nome_completo"] . '</td>
                        <td>' . $descriacao_ev . '</td>

                    </tr>
                ';
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
