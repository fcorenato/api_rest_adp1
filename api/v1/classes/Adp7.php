<?php

class Adp7
{
    public function mostrar($parametros)
    {
        // Configurações de conexão com o banco de dados
        require('Adp_conect.php');

        //parametros
        $props = explode('-', $parametros);
        $emp = $props[0];
        $numnf = $props[1];

        // 7 - consulta de nf para promo vouche caminhoneiro      
        $sql4 = "SELECT v.data_cupom,
        se.codigo           AS cod_empresa,
        se.nome             AS nome_fantasia,
        v.numero_cupom,
        p.codigo            AS cod_operador,
        p.fantasia_codinome AS apelido,
        pp.codigo           AS cod_cliente,
        pp.nome             AS cliente,
        i.codigo            AS cod_produto,
        i.denominacao       AS descricao,                   
        v.checksum,
        i.denominacao_reduzida,
        ci.codigo           AS cod_grupo,
        SUM(iv.quantidade) AS quantidade,
        SUM(iv.total_item) AS total_item
FROM   item_venda_cf iv
        INNER JOIN item i
                ON iv.id_item = i.id_item
        INNER JOIN venda_cf v
                ON ( iv.id_venda_cf = v.id_venda_cf )
        INNER JOIN movimento_venda_terminal mvt
                ON ( mvt.id_movimento_venda_terminal =
                    v.id_movimento_venda_terminal )
        INNER JOIN pessoa p
                ON iv.id_atendente = p.id_pessoa
        INNER JOIN pessoa pp
                ON v.id_cliente = pp.id_pessoa
        INNER JOIN sis_empresa se
                ON mvt.id_empresa = se.id_empresa
        INNER JOIN categoria_item ci
                ON ci.id_categoria_item = i.id_categoria_item
WHERE  se.codigo='$emp'
       AND v.cancelada = 'N'
       AND IV.cancelado = 'N'
       AND (i.denominacao_reduzida = 'S10 AD' OR i.denominacao_reduzida = 'S10AD' OR i.denominacao_reduzida = 'DS10')
       AND v.data_cupom >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       AND v.numero_cupom = '$numnf' 
            GROUP BY v.data_cupom, se.codigo, se.nome, v.numero_cupom, p.codigo, p.fantasia_codinome,  pp.codigo, pp.nome, i.codigo, i.denominacao, v.checksum, i.denominacao_reduzida, ci.codigo
        ";


        $sql = $con->prepare($sql4);
        $sql->execute();
        //print_r($sql->errorInfo());
        //echo 'erro <hr>';
        $resultados = array();


        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        if (!$resultados) {
            throw new Exception("Nenhum dado encontrado!");
        }

        return $resultados;
    }
}
