<?php
// Define o limitador de cache
session_cache_limiter('must-revalidate');
$cache_limiter = session_cache_limiter(); 

// Define tempo da sessão
session_cache_expire(18000); 
$cache_expire = session_cache_expire();

// Inicia a sessão
session_start();
if (!isset($_SESSION['loginAdmin'])){
	//echo '<script>alert("Voce nao efetuou o login!")</script>';
	echo '<script>parent.location="sys_login"</script>';
	exit;
}
?>