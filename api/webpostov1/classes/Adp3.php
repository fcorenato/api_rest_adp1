<?php

class Adp3
{
    public function mostrar($parametros)
    {
        // Configurações de conexão com o banco de dados
        require('Adp_conect.php');

        //parametros
        $props = explode('-', $parametros);
        $emp = $props[0];
        $data_inicio = $props[1];
        $data_fim = $props[2];

        // 3 - total de valor de  vendas 'ADITIVOS', 'AUTOMOTIVOS', 'FILTROS', 'LUBRIFICANTES'
        
        $sql3 = "SELECT SUM(i.valitem) AS total FROM itemped i
                JOIN produtos pr
                    ON pr.codpro = i.codpec
                JOIN grupos g
                    ON g.codi = pr.codgru
                JOIN funcionarios f
                    ON f.matr = i.vei_ds_funcionario
                WHERE i.dtfiscal BETWEEN '$data_inicio' AND '$data_fim'
                AND g.grupo IN ('ADITIVOS', 'AUTOMOTIVOS', 'FILTROS', 'LUBRIFICANTES')";


        $sql = $con->prepare($sql3);
        $sql->execute();

        $resultados = array();


        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (!$resultados) {
            throw new Exception("Nenhum resgistro encontrado com os filtros: emp:$emp  - data: $data_inicio ate $data_fim");
        }

        return $resultados;
    }
}
