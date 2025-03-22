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

        // 1 total de venda de combustivel  (quantidade), somado por data.. (todos os combustiveis juntos)
        $sql1 = "SELECT	mvt.data_movimento,
                        se.codigo as cod_empresa,
                        P.codigo as COD_OPERADOR,
                        P.fantasia_codinome AS apelido,
                        ci.denominacao as categoria,
                        SUM(ivc.quantidade) AS qtd_total
                FROM	item_venda_cf AS ivc
                        INNER JOIN venda_cf AS vc ON vc.id_venda_cf = ivc.id_venda_cf
                        INNER JOIN movimento_venda_terminal AS mvt ON mvt.id_movimento_venda_terminal = vc.id_movimento_venda_terminal
                        INNER JOIN pessoa AS p ON p.id_pessoa = ivc.id_atendente
                        INNER JOIN sis_empresa AS se ON mvt.id_empresa = se.id_empresa
                        INNER JOIN item AS i ON ivc.id_item=i.id_item
                        INNER JOIN categoria_item as ci ON ci.id_categoria_item = i.id_categoria_item
                WHERE	vc.cancelada = 'N'
                AND		ivc.cancelado = 'N'
                AND		NOT ivc.id_bico_combustivel IS NULL
                AND		se.codigo in ('$emp')  
                AND     ci.denominacao = 'COMBUSTIVEIS'
                AND		mvt.data_movimento BETWEEN '$data_inicio' AND '$data_fim'
                GROUP	BY categoria, cod_operador, se.codigo, mvt.data_movimento, apelido
                ORDER	BY categoria, cod_operador, se.codigo, mvt.data_movimento, apelido
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
