<?php

class Adp0
{
    public function mostrar()
    {
        // Configurações de conexão com o banco de dados
        require('Adp_conect.php');

        // 0 - lista de funcionario
        $sql0 = "SELECT E.CODIGO AS CODIGO_EMPRESA, 
            P.CODIGO AS COD_FUNC, 
            P.FANTASIA_CODINOME AS APELIDO, 
            P.NOME, 
            C.FUNCAO,
            C.SITUACAO AS ATIVO,
            EQ.DENOMINACAO AS TURNO 
            FROM COLABORADOR AS C
            
            INNER JOIN SIS_EMPRESA AS E 
            ON (E.ID_EMPRESA=C.ID_EMPRESA)
            INNER JOIN PESSOA AS P
            ON (C.ID_COLABORADOR=P.ID_PESSOA)
            INNER JOIN EQUIPE AS EQ
            ON (EQ.ID_EQUIPE=C.ID_EQUIPE)

            WHERE C.SITUACAO = '1'
            AND E.ID_EMPRESA != '290353166'
            ";
            



        $sql = $con->prepare($sql0);
        $sql->execute();

        $resultados = array();


        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (!$resultados) {
            throw new Exception("Nenhum pruduto no estoque!");
        }

        return $resultados;
    }
}
