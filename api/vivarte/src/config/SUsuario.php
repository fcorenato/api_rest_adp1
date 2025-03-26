<?php
// Define o limitador de cache
session_cache_limiter('must-revalidate');
$cache_limiter = session_cache_limiter(); 

// Define tempo da sessão
session_cache_expire(18000); 
$cache_expire = session_cache_expire();

// Inicia a sessão
session_start();
if (!isset($_SESSION['usuario'])){
	//echo '<script>alert("Voce nao efetuou o login!")</script>';
	//echo '<script>parent.location="/biv/sys_login"</script>';
	header("location: sys_login.php");
	exit;
} else {
$usuario_codigo = $_SESSION["codigo_usuario"];
$usuario = $_SESSION["usuario"];
$usuario_nome = $_SESSION["nome_completo"];
$perfil = $_SESSION["perfil"];
$aprovador = $_SESSION["aprovador"];
$cod_vend = $_SESSION["cod_vend"];
$un_codigo = $_SESSION["un_codigo"];
$un_descricao = $_SESSION["un_descricao"];
$un_tabelas = $_SESSION["un_tabelas"];
$un_orcamentos = $_SESSION["un_orcamentos"];
$un_armazens = $_SESSION["un_armazens"];
$un_cond_pgto = $_SESSION["un_cond_pgto"];
$un_fabricantes = $_SESSION["un_fabricantes"];
$orc_create = $_SESSION["orc_create"];

//indicando base de teste:
$baseteste = FALSE;

}

//ativar manutencao
$manutencao = 0;
if ($manutencao == 1) {
    header("location: sys_manutencao.php");
}
?>