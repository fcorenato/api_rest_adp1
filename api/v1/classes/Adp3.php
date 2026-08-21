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

        //se emp = 011 considera tambem os dados da emp =001
        $emp_in = "'" . $emp . "'";
        if ($emp == 11) {
            $emp_in = "'011','001'";
        }

        // 3 - total de venda de produtos sem ser combustivel (por valor) por data, por funcionario        
        // Venda Lojas
        
        $sql3 = "SELECT mvt.data_movimento,
                se.codigo as cod_empresa,
                p.codigo as cod_operador,
                p.fantasia_codinome as apelido,
        C.FUNCAO,
                ci.denominacao as categoria,
                SUM (IVC.quantidade) as QTD_TOTAL,
                SUM(IVC.TOTAL_ITEM) AS TOTAL_VALOR
            FROM	ITEM_VENDA_CF AS IVC
                INNER JOIN venda_cf AS vc ON vc.id_venda_cf = ivc.id_venda_cf
                INNER JOIN movimento_venda_terminal AS mvt ON mvt.id_movimento_venda_terminal = vc.id_movimento_venda_terminal
                INNER JOIN sis_empresa AS se ON mvt.id_empresa = se.id_empresa
                INNER JOIN item AS i ON ivc.id_item=i.id_item
                INNER JOIN categoria_item as ci ON ci.id_categoria_item = i.id_categoria_item
                LEFT JOIN pessoa as P ON P.ID_PESSOA = ivc.id_atendente
                LEFT JOIN COLABORADOR AS C ON (C.ID_COLABORADOR=P.ID_PESSOA AND C.ID_EMPRESA = '$emp')
            WHERE vc.cancelada = 'N'
                AND IVC.CANCELADO = 'N'
                AND IVC.ID_BICO_COMBUSTIVEL IS NULL
                AND SE.CODIGO IN ($emp_in)  -- identificador da empresa, para saber a lista de cod select * from sis_empresa
                AND MVT.DATA_MOVIMENTO BETWEEN '$data_inicio' AND '$data_fim'
            GROUP BY CATEGORIA, COD_OPERADOR, SE.CODIGO, MVT.DATA_MOVIMENTO, P.FANTASIA_CODINOME,C.FUNCAO
            ORDER BY CATEGORIA, COD_OPERADOR, SE.CODIGO, MVT.DATA_MOVIMENTO, P.FANTASIA_CODINOME";


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
