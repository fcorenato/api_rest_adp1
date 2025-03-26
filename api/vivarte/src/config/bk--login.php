<?php
ob_start();
session_start(); //iniciando a sessao
require('conexao.php');

$resultado_login = 0;
//recebendo e tratando dados
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_usuario = $_POST["usuario"];
    $post_pwd = $_POST["password"];

    //Consultando usuario no sistema
    $pesquisa = mysql_query("SELECT u.codigo, u.usuario, u.nome_completo, u.status, u.senha, u.perfil, u.aprovador, u.analista_comerc, u.cod_vend,
    un.codigo as un_codigo, un.descricao, un.tabelas, un.orcamentos, un.armazens, un.cond_pgto_lib, un.split_pgto, un.fabricantes  FROM `sys_usuarios` as u
    LEFT JOIN sys_unidades as un ON u.unidade_codigo = un.codigo
    WHERE usuario = '$post_usuario' ");
    $linhas = mysql_num_rows($pesquisa);
    if ($linhas == 0) {
        $resultado_login = 0; //usuario nao encontrado
    } else {
        while ($dados = mysql_fetch_array($pesquisa)) {

            $cd_usuario = $dados["codigo"];
            $usuario = $dados["usuario"];
            $nome_completo = $dados["nome_completo"];
            $status = $dados["status"];
            $senha = $dados["senha"];
            $perfil = $dados["perfil"];
            $aprovador = $dados["aprovador"];
            $analista_comerc = $dados["analista_comerc"];
            $cod_vend = $dados["cod_vend"];
            $un_codigo = $dados["un_codigo"];
            $un_descricao = $dados["descricao"];
            $un_tabelas = $dados["tabelas"];
            $un_orcamentos = $dados["orcamentos"];
            $un_armazens = $dados["armazens"];
            $un_cond_pgto = $dados["cond_pgto_lib"];
            $un_split_pgto = $dados["split_pgto"];
            $un_fabricantes = $dados["fabricantes"];

        }

        if (($status == 'A') and ($usuario != $senha)) {
            if ($senha != $post_pwd) {
                $resultado_login = 2; // Senha invalida
            } else {
    
                $_SESSION["codigo_usuario"] = $cd_usuario;
                $_SESSION["usuario"] = $usuario;
                $_SESSION["status"] = $status;
                $_SESSION["nome_completo"] = $nome_completo;
                $_SESSION["perfil"] = $perfil;
                $_SESSION["aprovador"] = $aprovador;
                $_SESSION["analista_comerc"] = $analista_comerc;
                $_SESSION["cod_vend"] = $cod_vend;
                $_SESSION["un_codigo"] = $un_codigo;
                $_SESSION["un_descricao"] = $un_descricao;
                $_SESSION["un_tabelas"] = $un_tabelas;
                $_SESSION["un_orcamentos"] = $un_orcamentos;
                $_SESSION["un_armazens"] = $un_armazens;
                $_SESSION["un_cond_pgto"] = $un_cond_pgto;
                $_SESSION["un_split_pgto"] = $un_split_pgto;
                $_SESSION["un_fabricantes"] = $un_fabricantes;
    
                $resultado_login = 4; // login sucesso

                //enviar usuarios da unidade1 para a versao t1 
                /*
                if (($un_codigo == '1' or $un_codigo == '4') and $usuario != 'admin') {
                    $resultado_login = 5; // login sucesso enviar para bivt1
                }
                */
                
            }
        } else if (($status == 'P') or ($usuario == $senha)) {
            if ($senha != $post_pwd) {
                $resultado_login = 2; // Senha invalida
            } else {
                $resultado_login = 3; // Usuario precisa alterar senha
            }
        } else {
            $resultado_login = 1; // Usuario nao liberado para utilizar o sistema.
        }
       
    }
} //fim do POST

echo $resultado_login;
