<?php

class Adp1
{
    public function mostrar($parametros)
    {
        // Configurações de conexão com o banco de dados
        require('Adp_conect.php');

        //parametros
        $props = explode('-', $parametros);
        $emp = $props[0];
        $data_inicio= $props[1];
        $data_fim= $props[2];

        // 1 total de venda de combustivel por tipo de combustivel por frentista
        $sql1 = "SELECT f.matr AS matricula,f.nome,p.codpro,p.nompro,count(nompro) AS bicadas, sum(litros) litros from abastecimentos a
                left join produtos p ON a.codpro = p.codpro
                left join funcionarios f on a.idfrentista = f.matr
                where data >= '$data_inicio' AND data <= '$data_fim'
                AND p.codgru = '001'
                group by f.matr,f.nome,p.codpro,p.nompro
                order by f.matr,codpro
                ";

        $sql = $con->prepare($sql1);
        $sql->execute();

        $resultados = array();


        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (!$resultados) {
            throw new Exception("Dados nao localizados! parametros: $emp - $data_inicio - $data_fim");
        }

        return $resultados;
    }
}
