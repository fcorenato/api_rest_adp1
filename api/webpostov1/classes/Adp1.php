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
        $sql1 = "SELECT f.matr AS matricula, f.nome, p.codpro, p.nompro,
                COUNT(CASE WHEN a.imprimiu = 'S' THEN 1 END) AS bicadas,
                SUM(litros) litros
            FROM abastecimentos a
            LEFT JOIN produtos p ON a.codpro = p.codpro
            LEFT JOIN funcionarios f ON a.idfrentista = f.matr
            WHERE data >= '$data_inicio' AND data <= '$data_fim'
            AND p.codgru = '001'
            GROUP BY f.matr, f.nome, p.codpro, p.nompro
            ORDER BY f.matr, codpro
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
