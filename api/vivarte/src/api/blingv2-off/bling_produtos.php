<?php 
 $apikey = "c28ee8d5298c8a3e0b1b8ac5bc8f0290411c398aa024335a279c1e68a328ff64065fb99d";
 $outputType = "json";
 $url = 'https://bling.com.br/Api/v2/produtos/' . $outputType;
 $retorno = executeGetProducts($url, $apikey);
 echo $retorno;
 function executeGetProducts($url, $apikey){
     $curl_handle = curl_init();
     curl_setopt($curl_handle, CURLOPT_URL, $url . '&apikey=' . $apikey);
     curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
     $response = curl_exec($curl_handle);
     curl_close($curl_handle);
     return $response;
 }

?>