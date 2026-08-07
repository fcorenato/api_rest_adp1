<?php

class Adp2
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

        // 2 - qtde de venda por tipo adminstradora shellbox - correto considerar cupom distintos:
        $sql2 = "SELECT
                f.matr AS matricula,
                f.nome AS nome,
                COUNT(DISTINCT CASE WHEN c.valor <> 0.01 THEN c.cupom END)
                + COUNT(CASE WHEN c.valor = 0.01 THEN 1 END) AS qtde
            FROM cartes c
            JOIN administradora a ON a.codadm = c.codadm
            JOIN funcionarios f ON f.matr = c.matricula
            WHERE a.codadm IN ('027', '017', '045')
            AND c.movto BETWEEN '$data_inicio' AND '$data_fim'
            GROUP BY f.matr, f.nome
            ORDER BY f.nome;       
        ";
       

        $sql = $con->prepare($sql2);
        $sql->execute();

        $resultados = array();


        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (!$resultados) {
            throw new Exception("Nenhum pruduto no estoque!");
        }

        return $sql2;
    }
}
