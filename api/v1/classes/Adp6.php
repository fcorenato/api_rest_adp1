<?php

class Adp6
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

        // 4 - leitura dos tanques      
        $sql4 = "WITH RECURSIVE niveis(id_categoria_item, id_categoria_item_pai) AS (
            SELECT	id_categoria_item,
                id_categoria_item_pai,
                1 AS nivel
            FROM	categoria_item
            WHERE	id_categoria_item IN ('399')
            UNION ALL
            SELECT	ci.id_categoria_item,
                ci.id_categoria_item_pai,
                nv.nivel +1
            FROM	categoria_item AS ci
                INNER JOIN niveis AS nv ON (nv.id_categoria_item = ci.id_categoria_item_pai))
        SELECT	r.*,
            COALESCE((SELECT preco
                FROM	item_empresa
                WHERE	id_item = r.id_item
                AND	id_empresa = 1), 0.00) AS preco
        FROM	(
            SELECT	r1.id_item,
                se.nome as empresa,
                i.codigo AS codigo_item,
                i.denominacao AS denominacao_item,
                le.denominacao,
                I.denominacao_reduzida as TIPO_COMBUSTIVEL,
                le.capacidade_tanque,
                COALESCE(f.rsaldoquantidade,0) AS saldo_quantidade,
                COALESCE(f.rsaldovalor, 0) AS saldo_valor
            FROM (
            SELECT DISTINCT id_empresa,id_local_estoque,id_item
            FROM movimento_estoque
            WHERE id_empresa in ('1','3613024','4256178','4256179','11071253','11072304','11820375','24949465')) AS r1
                INNER JOIN LATERAL (SELECT * FROM f_obtem_saldo_estoque(r1.id_item, r1.id_local_estoque, '$data_fim')) f ON TRUE
                INNER JOIN local_estoque AS le ON (le.id_local_estoque = r1.id_local_estoque AND le.id_empresa = r1.id_empresa)
                INNER JOIN item AS i ON (i.id_item = r1.id_item)
                INNER JOIN categoria_item AS ci ON (ci.id_categoria_item = i.id_categoria_item)
                INNER JOIN unidade_medida AS um ON (um.id_unidade_medida = i.id_unidade_medida)
                INNER JOIN SIS_EMPRESA AS se ON (le.id_empresa=se.id_empresa)
            WHERE		ci.id_categoria_item IN (SELECT id_categoria_item FROM niveis)
            AND	i.tipo_item = 1
            AND se.codigo = '$emp'
        ) AS r
        ORDER BY empresa,
            denominacao_item 
        ";

        //echo $sql4.'<hr>';

        $sql = $con->prepare($sql4);
        $sql->execute();
        //print_r($sql->errorInfo());
        $errosql = $sql->errorInfo();
        //echo 'erro <hr>';
        $resultados = array();


        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (!$resultados) {
            throw new Exception("Nenhum dado encontrado! ($errosql[2])");
        }

        return $resultados;
    }
}
