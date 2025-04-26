<?php
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require('../config/conexao.php');

    //se receber solicitacao para deletar
    if (isset($_POST['delete_codigo']) != "") {
        $codigo = $_POST['delete_codigo'];
        $upd = mysql_query("UPDATE md_sangria SET status='C' WHERE codigo = $codigo") or die(mysql_error());

        if (mysql_affected_rows() > 0) {
            $resultado_rel = 1;
        } else {
            $resultado_rel = 0;
        }
    } else {


        $pesquisa = mysql_query("SELECT * FROM sys_usuarios u  where perfil = 'P'");
        $linhas = mysql_num_rows($pesquisa);
        if ($linhas == 0) {
            $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
        } else {
            //cabecalho da tabela resultado
            $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-striped table-head-fixed" style="font-size: 0.9rem !important">
              <thead>
                <tr>
                <th data-field="id">Codigo</th>
                <th data-field="price">Nome</th>
                <th data-field="price">Tipo</th>
                <th data-field="price">E-mail</th>
                <th data-field="price">CPF / CNPJ</th>
                <th data-field="price">Telefone</th>
                <th data-field="price">Data Aniversário</th>
                <th data-field="price">Instagram Pessoal</th>
                <th data-field="price">Instagram Comercial</th>
                <th data-field="price">Nome Empresa</th>
                <th data-field="price">Status</th>
                <th data-field="price">Ações</th>
                </tr>
              </thead>
              <tbody>

              ';
            while ($dados = mysql_fetch_array($pesquisa)) {
                if ($dados["status"] == 'A') {
                    $status = '<span class="badge bg-success">Ativo</span>';
                } else if ($dados["status"] == 'I'){
                    $status = '<span class="badge bg-warning">Inativo</span>';
                } else if ($dados["status"] == 'P'){
                    $status = '<span class="badge bg-warning">Redefinir Senha</span>';
                }

                if ($dados["data_niver"] != '') {
                    $data_niver = date("d/m/Y", strtotime($dados["data_niver"]));
                } else {
                    $data_niver = '';
                }

                $resultado_rel .= '
                <tr class="tr_result">
                <td>' . $dados["codigo"] . '</td>
                <td>' . $dados["nome_completo"] . '</td>
                <td>' . $dados["tipo_parceiro"] . '</td>
                <td>' . $dados["email"] . '</td>
                <td>' . $dados["cpf_cnpj"] . '</td>
                <td>' . $dados["telefone"] . '</td>
                <td>' . $data_niver . '</td>
                <td><a target="blank" href="https://www.instagram.com/' . $dados["instagram_pessoal"] . '" /a>' . $dados["instagram_pessoal"] . '</td>
                <td><a target="blank" href="https://www.instagram.com/' . $dados["instagram_comercial"] . '" /a>' . $dados["instagram_comercial"] . '</td>
                <td>' . $dados["nome_empresa"] . '</td>
                <td align="center">' . $status . '</td>
                <td>
                    <a class="btn btn-info btn-sm" href="md_vendas_parceiro_upd.php?codigo=' . $dados["codigo"] . '"><i class="fas fa-pen"></i></a>
                </td>
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
