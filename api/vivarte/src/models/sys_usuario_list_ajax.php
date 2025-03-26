<?php
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require('../config/conexao.php');

    //============= FILTROS ================
    //FILTRO POR EMAIL
    if ($_POST['email'] != '') {
        $emailspesq = trim($_POST['email']);
        $filtro_email = " AND us.email = '$emailspesq'";
    } else {
        $filtro_email = '';
    }

    //FILTRO POR UNIDADE
    if ($_POST['unidade'] > 0) {
        $undpesq = $_POST['unidade'];
        $filtro_unidade = " AND un.codigo = $undpesq";
    } else {
        $filtro_unidade = '';
    }


    //FILTRO POR STATUS UNIDADE
    if ($_POST['undstatus'] != '') {
        $undstatuspesq = $_POST['undstatus'];
        $filtro_und_status = " AND un.status = '$undstatuspesq'";
    } else {
        $filtro_und_status = '';
    }

    //FILTRO POR STATUS USUARIO
    if ($_POST['status'] != '') {
        $statuspesq = $_POST['status'];
        $filtro_status = " AND us.status = '$statuspesq'";
    } else {
        $filtro_status = '';
    }

    $query = "SELECT us.codigo, us.usuario, us.nome_completo, us.email, us.telefone, us.data_niver, us.ultimo_login,  us.perfil, us.status, un.descricao,  un.tabelas, un.status as unid_status
                  FROM `sys_usuarios` AS us
                  LEFT JOIN sys_unidades AS un ON ( us.unidade_codigo = un.codigo)
                  WHERE us.perfil = 'V'
                  $filtro_unidade 
                  $filtro_und_status
                  $filtro_status 
                  $filtro_email 
                  AND un.descricao != ''    
                  AND un.codigo > 4
                  ORDER BY un.descricao, us.nome_completo";
    // echo $query;
    $pesquisa = mysql_query($query);
    $linhas = mysql_num_rows($pesquisa);
    $num_regs = 0;
    if ($linhas == 0) {
        $resultado_rel = '<table><tr class="bg-warning"><td class="p-3"><img style="width:30px;padding-bottom:4px;" src="dist/img/alert.png" /> Ops! Nenhum registro encontrado.</td></tr></table>';
    } else {
        //cabecalho da tabela resultado
        $resultado_rel = '
              <table id="tabela_relatorio" class="table table-sm table-hover table-bordered table-striped table-head-fixed" style="font-size: 0.9rem !important">
              <thead>
                <tr>
                <th data-field="id">Codigo</th>
                <th data-field="price">Nome Completo</th>
                <th data-field="price">E-mail</th>
                <th data-field="price">Telefone</th>
                <th data-field="price">Aniversário</th>
                <th data-field="price">Perfil</th>
                <th data-field="price">Unidade</th>
                <th data-field="price">Tabelas</th>
                <th data-field="price">Último Acesso</th>
                <th data-field="price">Status</th>
                <th data-field="price">Ações</th>
                </tr>
              </thead>
              <tbody>

              ';
        while ($dados = mysql_fetch_array($pesquisa)) {
            $num_regs++;
            if ($dados["status"] == 'A') {
                $status = '<span class="badge bg-success">Ativo</span>';
            } else if ($dados["status"] == 'I') {
                $status = '<span class="badge bg-warning">Inativo</span>';
            } else if ($dados["status"] == 'P') {
                $status = '<span class="badge bg-info">Redefinir Senha</span>';
            }

            if ($dados["data_niver"] != '') {
                $data_niver = date("d/m/Y", strtotime($dados["data_niver"]));
            } else {
                $data_niver = '';
            }

            if ($dados["ultimo_login"] != '') {
                $ultimo_login = date("d/m/Y H:i", strtotime($dados["ultimo_login"]));
            } else {
                $ultimo_login = '';
            }


            if ($dados["unid_status"] == 'I') {
                $unidstatus = '<span class="badge bg-warning">Unidade Inativa</span>';
            } else {$unidstatus = '';}

            $resultado_rel .= '
                <tr class="tr_result">
                <td>' . $dados["codigo"] . '</td>
                <td>' . $dados["nome_completo"] . '</td>
                <td>' . $dados["email"] . '</td>
                <td>' . $dados["telefone"] . '</td>
                <td>' . $data_niver . '</td>
                <td align="center">' . $dados["perfil"] . '</td>
                <td>' . $dados["descricao"] . ' '.$unidstatus.'</td>
                <td align="center">' . $dados["tabelas"] . '</td>
                <td align="center">' . $ultimo_login . '</td>
                <td align="center">' . $status . '</td>
                <td>
                    <a class="btn btn-info btn-sm" href="sys_usuario_upd.php?codigo=' . $dados["codigo"] . '"><i class="fas fa-pen"></i></a>
                </td>
                </tr>
                ';
        }
        // rodape da tabela resultado
        $resultado_rel .= '
            <tr>
                <td colspan="8">Qtde Registros: '.$num_regs.'</td>
            </tr>
                            </tbody>
                            </table>
                            </div>';
    }
} // fim do POST

echo $resultado_rel;
