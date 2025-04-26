<?php
    //API PARA CONSULTAR ENDEREÇO ATRAVES DO CEP
    
    //$cep = '62882070';
    //montando a consulta:
    $cs = 'https://viacep.com.br/ws/' .$cep .'/json';

    //inicializando CURL
    
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $cs,
        CURLOPT_RETURNTRANSFER => true,
    ));
    $res = curl_exec($curl);
    curl_close($curl);
    $resultado = json_decode($res);
    //var_dump($resultado);

    if ((array_key_exists("erro", $resultado)) or is_null($resultado)) {
        //echo '<hr> Erro';
        $viacep_ibge = '0';
    } else {
        
        $viacep_ibge = $resultado->ibge;
        
        /*
        $viacep_cep = $resultado->cep;
        $viacep_logradouro = $resultado->logradouro;
        $viacep_bairro = $resultado->bairro;
        $viacep_cidade = $resultado->localidade;
        $viacep_uf = $resultado->uf;

        echo '<hr>CEP: '.$viacep_cep;
        echo '<br>End: '.$viacep_logradouro.' - '. $viacep_bairro . ' - '. $viacep_cidade .' - '.$viacep_uf;
        echo '<br>Cod IBGE Cidade:: '.$viacep_ibge;
        */
    }

    

    
?>
