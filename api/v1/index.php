<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'classes/Estoque.php';
require_once 'classes/Adp0.php';
require_once 'classes/Adp1.php';
require_once 'classes/Adp2.php';
require_once 'classes/Adp3.php';
require_once 'classes/Adp4.php';
require_once 'classes/Adp5.php';
require_once 'classes/Adp6.php';
require_once 'classes/Adp7.php';
require_once 'classes/Adp8.php';
require_once 'classes/Adp9.php';

class Rest
{
	public static function open($requisicao)
	{
		$url = explode('/', $requisicao['url']);
		
		$classe = ucfirst($url[0]);
		array_shift($url);

		$metodo = $url[0];
		array_shift($url);

		$parametros = array();
		$parametros = $url;

		try {
			if (class_exists($classe)) {
				if (method_exists($classe, $metodo)) {
					$retorno = call_user_func_array(array(new $classe, $metodo), $parametros);

					return json_encode(array('status' => 'sucesso', 'dados' => $retorno));
				} else {
					return json_encode(array('status' => 'erro', 'dados' => 'Método inexistente!'));
				}
			} else {
				return json_encode(array('status' => 'erro', 'dados' => 'Classe inexistente!'));
			}	
		} catch (Exception $e) {
			return json_encode(array('status' => 'erro', 'dados' => $e->getMessage()));
		}
		
	}
}

if (isset($_REQUEST)) {
	echo Rest::open($_REQUEST);
}