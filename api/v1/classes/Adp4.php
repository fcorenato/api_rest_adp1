<?php

class Adp4
{
    public function mostrar($parametros)
    {
        // Configurações de conexão com o banco de dados
        require('Adp_conect.php');

        //parametros
        $props = explode('-', $parametros);
        $idcli = $props[0];
        $data_criado= $props[1];
        $data_last_reg= $props[2];

        // 5 - dados VENDAS CANCELADAS por cliente CLUBE G7 - 
        $sql4 = "SELECT v.data_cupom,
                        se.codigo              AS cod_empresa,
                        se.nome                AS nome_fantasia,
                        v.numero_cupom,
                        v.cnpj_cpf_rodape,
                        p.codigo               AS cod_operador,
                        p.fantasia_codinome    AS apelido,
                        i.codigo               AS prod_codigo,
                        i.denominacao          AS prod_descricacao,
                        ci.denominacao         AS categoria,
                        i.denominacao_reduzida AS tipo_combustivel,
                        iv.quantidade,
                        iv.total_item,
                        iv.cancelado           AS item_cancelado,
                        v.cancelada            AS venda_cancelada
                FROM   venda_cf AS v
                        INNER JOIN item_venda_cf AS iv
                                ON ( v.id_venda_cf = iv.id_venda_cf )
                        INNER JOIN item AS i
                                ON ( iv.id_item = i.id_item )
                        INNER JOIN categoria_item AS ci
                                ON ( i.id_categoria_item = ci.id_categoria_item )
                        INNER JOIN ecf AS e
                                ON ( e.id_ecf = v.id_ecf )
                        INNER JOIN sis_empresa AS se
                                ON ( e.id_empresa = se.id_empresa )
                        LEFT JOIN pessoa AS p
                            ON ( iv.id_atendente = p.id_pessoa )
                WHERE  Date(data_cupom) >= Cast('$data_criado' AS DATE) AND Date(data_cupom) >= Cast('$data_last_reg' AS DATE)
                        AND v.cnpj_cpf_rodape = '$idcli' 
                ORDER BY Date(data_cupom)
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
