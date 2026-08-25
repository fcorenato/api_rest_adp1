<?php
ini_set('default_socket_timeout', 300);
ini_set('max_execution_time', 300);
class Adp8
{
    public function mostrar($parametros)
    {
        // Configurações de conexão com o banco de dados
        require('Adp_conect.php');
        //id empresa
        $id_emp = array();
        $id_emp['001'] = '1'; //G7 DOMINGÃO
        $id_emp['002'] = '3613024'; //G7 DOMINGÃO
        $id_emp['003'] = '4256178'; //G7 SANTA RITA
        $id_emp['004'] = '4256179'; //G7 PACAJUS
        $id_emp['005'] = '11071253'; //G7 ITAITINGA
        $id_emp['006'] = '11072304'; //G7 CASCAVEL
        $id_emp['007'] = '11820375'; //G7 RUSSAS
        $id_emp['008'] = '24949465'; //G7 RETORNO
        $id_emp['011'] = '291051868'; //G7 RETORNO

        //parametros
        $props = explode('-', $parametros);
        $emp = $id_emp[$props[0]];
        $ano_rel = $props[1];


        // 8 - consulta relatorio DRE anual -- Mapa Anual de Resultados e Indicadores     
        $sql4 = "WITH tmp_variacao_estoque AS (
            SELECT vve.id_empresa, 
                DATE(vve.data_movimento) AS data,
               SUM(vve.valor_diferenca) AS total_variacao_estoque 
            FROM sis_empresa se 
            INNER JOIN vw_dre_variacao_estoque(se.id_empresa, TO_DATE(CAST($ano_rel AS varchar) || '0101', 'YYYYMMDD'), TO_DATE(CAST($ano_rel AS varchar) || '1231', 'YYYYMMDD')) vve ON vve.id_empresa = se.id_empresa
            WHERE se.id_empresa IN ($emp)
            GROUP BY vve.id_empresa, DATE(vve.data_movimento) 
        )
        
        ,tmp_despesas AS (
            SELECT r.nome_empresa,
            r.data_despesa_receita,
            r.denominacao,
            r.valor_despesa,
            r.classificacao_despesa_receita,
            r.denominacao_categoria
        FROM  (
                       
        WITH RECURSIVE tmp_pagamento_titulos(id_titulo_raiz, 
              id_titulo_financeiro, 
              id_titulo_filho, 
              id_titulo_pai, 
              valor_titulo, 
              situacao, 
              data_liquidacao, 
              id_conta_liquidacao,
              juros_cobranca, 
              multa_cobranca, 
              desconto_cobranca, 
              retencao_tributaria, 
              valor_liquidado, 
              valor_ajuste,
              valor_recebido,
              valor_amortizado,
              valor_residual,
              valor_titulo_recalculado,
              valor_amortizado_recalculado,
              valor_residual_recalculado) AS 
          (
            SELECT  tf.id_titulo_financeiro AS id_titulo_raiz,
              tf.id_titulo_financeiro,
              tf.id_titulo_filho,
              tf.id_titulo_pai,   
              tf.valor_titulo,
              tf.situacao,
              tf.data_liquidacao,
              tf.id_conta_liquidacao,
              tf.juros_cobranca,
              tf.multa_cobranca,
              tf.desconto_cobranca,
              tf.retencao_tributaria,
              tf.valor_liquidado,
              (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) AS valor_ajuste,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_recebido,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) 
              ELSE 
                tf.valor_titulo 
              END) AS DECIMAL(12,2)) AS valor_residual,
              tf.valor_titulo AS valor_titulo_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) 
              ELSE 
                tf.valor_titulo 
              END) AS DECIMAL(12,2)) AS valor_residual_recalculado
            FROM  titulo_financeiro tf
              INNER JOIN (
                  SELECT DISTINCT pdr.id_titulo_financeiro
                  FROM  despesa_receita AS dr
                    INNER JOIN parcela_despesa_receita AS pdr ON (pdr.id_despesa_receita = dr.id_despesa_receita)
                    INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = dr.id_tipo_despesa_receita)
                  WHERE dr.id_empresa IN ($emp)
                  AND EXTRACT('YEAR' FROM dr.data_despesa_receita) <= $ano_rel
                  AND tdr.despesa_receita = 1
                  AND dr.situacao IN (2,3)
                                                                                                                           
                  UNION ALL
        
                  SELECT DISTINCT pnfe.id_titulo_financeiro
                  FROM  nota_fiscal_entrada AS nfe
                    INNER JOIN item_nfe AS infe ON (infe.id_nota_fiscal_entrada = nfe.id_nota_fiscal_entrada)
                    INNER JOIN parcela_nfe AS pnfe ON (pnfe.id_nota_fiscal_entrada = nfe.id_nota_fiscal_entrada)
                    INNER JOIN despesa_receita AS dr ON (dr.id_despesa_receita = infe.id_despesa)
                  WHERE nfe.id_empresa IN ($emp)
                  AND EXTRACT('YEAR' FROM nfe.entrada) <= $ano_rel 
                  AND nfe.situacao = 1                                                                               
                ) AS tmp_titulos ON (tmp_titulos.id_titulo_financeiro = tf.id_titulo_financeiro)
            UNION ALL
            SELECT  t.id_titulo_raiz,
              tf.id_titulo_financeiro,
              tf.id_titulo_filho,
              tf.id_titulo_pai,   
              tf.valor_titulo,
              tf.situacao,
              tf.data_liquidacao,
              tf.id_conta_liquidacao,
              tf.juros_cobranca,
              tf.multa_cobranca,
              tf.desconto_cobranca,
              tf.retencao_tributaria,
              tf.valor_liquidado,
              (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) AS valor_ajuste,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_recebido,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) 
              ELSE 
                tf.valor_titulo 
              END) AS DECIMAL(12,2)) AS valor_residual,
              CAST((CASE WHEN t.situacao = 8 THEN
                COALESCE((tf.valor_titulo * t.valor_residual_recalculado) / NULLIF(t.valor_residual, 0), (tf.valor_titulo * t.valor_residual_recalculado))
              ELSE 
                t.valor_residual_recalculado 
              END) AS DECIMAL(12,2)) AS valor_titulo_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                (CASE WHEN t.situacao = 8 THEN
                  COALESCE(((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) * (t.valor_residual_recalculado / NULLIF(t.valor_residual, 0))), (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))) 
                ELSE 
                  COALESCE((((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) / NULLIF(tf.valor_titulo, 0)) * t.valor_residual_recalculado), (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)))
                END)
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                (CASE WHEN t.situacao = 8 THEN
                  COALESCE(((tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))) * (t.valor_residual_recalculado / NULLIF(t.valor_residual, 0))), (tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))))
                ELSE 
                  (t.valor_residual_recalculado - COALESCE((((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) / NULLIF(tf.valor_titulo, 0)) * t.valor_residual_recalculado), (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))))
                END)
              ELSE 
                (CASE WHEN t.situacao = 8 THEN
                  COALESCE((tf.valor_titulo * t.valor_residual_recalculado) / NULLIF(t.valor_residual, 0), (tf.valor_titulo * t.valor_residual_recalculado)) 
                ELSE
                  t.valor_residual_recalculado 
                END)
              END) AS DECIMAL(12,2)) AS valor_residual_recalculado
            FROM  titulo_financeiro as tf,
              tmp_pagamento_titulos as t
            WHERE (tf.id_titulo_financeiro = t.id_titulo_filho OR tf.id_titulo_pai = t.id_titulo_financeiro)
          )
          
          --Liquidação de Despesas a Prazo
          SELECT  se.nome AS nome_empresa,
             tpt.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            tpt.valor_amortizado_recalculado AS valor_despesa,
            tdr.classificacao_despesa_receita,
            cdr.denominacao AS denominacao_categoria
          FROM  tmp_pagamento_titulos AS tpt
            INNER JOIN titulo_financeiro AS tf ON (tf.id_titulo_financeiro = tpt.id_titulo_raiz)
            INNER JOIN parcela_despesa_receita AS pdr ON (pdr.id_titulo_financeiro = tf.id_titulo_financeiro)
            INNER JOIN despesa_receita AS dr ON (dr.id_despesa_receita = pdr.id_despesa_receita)
            INNER JOIN tipo_despesa_receita AS tdr ON (dr.id_tipo_despesa_receita = tdr.id_tipo_despesa_receita)
            INNER JOIN sis_empresa AS se ON (se.id_empresa = dr.id_empresa)
            INNER JOIN categoria_despesa_receita AS cdr ON (cdr.id_categoria_despesa_receita = tdr.id_categoria_despesa_receita)
          WHERE dr.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM  tpt.data_liquidacao) = $ano_rel
        
          UNION ALL
        
          -- DESPESAS POR NOTA FISCAL DE ENTRADA
          SELECT  e.nome AS nome_empresa,
            r.data_liquidacao AS data_despesa_receita,
            r.denominacao AS denominacao,
            r.valor_despesa,
            0 AS classificacao_despesa_receita,
            r.denominacao_categoria AS denominacao_categoria
          FROM  (
            SELECT  tf.id_empresa,
              tf.id_titulo_financeiro,
              tpt.data_liquidacao,
              tdr.denominacao,
              cdr.denominacao AS denominacao_categoria,
              ROUND(tpt.valor_amortizado_recalculado * COALESCE(infe.total_produto / NULLIF(nfe.total_produto, 0), infe.total_produto), 2) AS valor_despesa
            FROM  tmp_pagamento_titulos AS tpt
            INNER JOIN titulo_financeiro AS tf ON (tf.id_titulo_financeiro = tpt.id_titulo_raiz)
            INNER JOIN parcela_nfe AS pnfe ON (pnfe.id_titulo_financeiro = tf.id_titulo_financeiro)
            INNER JOIN nota_fiscal_entrada AS nfe ON (nfe.id_nota_fiscal_entrada = pnfe.id_nota_fiscal_entrada)
            INNER JOIN item_nfe AS infe ON (infe.id_nota_fiscal_entrada = nfe.id_nota_fiscal_entrada)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = infe.id_tipo_despesa)
            INNER JOIN categoria_despesa_receita AS cdr ON (cdr.id_categoria_despesa_receita = tdr.id_categoria_despesa_receita)
            INNER JOIN conta_financeira AS cf ON (cf.id_conta_financeira = tpt.id_conta_liquidacao)
            INNER JOIN pessoa AS p ON (p.id_pessoa = nfe.id_fornecedor)
            INNER JOIN sis_empresa AS se ON (se.id_empresa = nfe.id_empresa)
            LEFT OUTER JOIN sis_usuario AS ul ON(ul.id_usuario = nfe.id_usuario_lancamento)
            WHERE tf.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM tpt.data_liquidacao) = $ano_rel ) AS r
            INNER JOIN sis_empresa AS e ON (e.id_empresa = r.id_empresa)
            
          UNION ALL
        
          --CONTAS A PAGAR
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.acrescimo_financeiro) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -1)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 1
          AND tf.situacao IN (2, 3)
          AND tf.acrescimo_financeiro > 0
          GROUP BY 1,2,3
          
          UNION ALL
          
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.juros_cobranca) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -2)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 1
          AND tf.situacao IN (2, 3)
          AND tf.juros_cobranca > 0
          GROUP BY 1,2,3
        
          UNION ALL
        
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.multa_cobranca) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -3)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 1
          AND tf.situacao IN (2, 3)
          AND tf.multa_cobranca > 0
          GROUP BY 1,2,3
        
          UNION ALL
          
          --CONTAS A RECEBER
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.desconto_financeiro) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -4)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 2
          AND tf.situacao IN (2, 3)
          AND tf.desconto_financeiro > 0
          GROUP BY 1,2,3
        
        
          UNION ALL
          
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.desconto_cobranca) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -5)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 2
          AND tf.situacao IN (2, 3)
          AND tf.desconto_cobranca > 0
          GROUP BY 1,2,3
        
        
          UNION ALL
        
          --CARTÕES DE CRÉDITO
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.valor_taxa_administracao) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -6)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 2
          AND tf.situacao IN (2, 3)
          AND tf.valor_taxa_administracao > 0
          GROUP BY 1,2,3
        
          UNION ALL
        
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            SUM(tf.tarifa_transacao) AS valor_despesa,
            3 AS classificacao_despesa_receita,
            'OUTRAS DESPESAS' AS denominacao_categoria
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -7)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.pagar_receber = 2
          AND tf.situacao IN (2, 3)
          AND tf.tarifa_transacao > 0
          GROUP BY 1,2,3
          
          UNION ALL
        
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
          SUM(tf.retencao_tributaria) AS valor_despesa,
          3 AS classificacao_despesa_receita,
          'OUTRAS DESPESAS' AS denominacao_categoria
        FROM  titulo_financeiro AS tf
          INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
          INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -8)
        WHERE tf.id_empresa IN ($emp)
        AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
        AND tf.pagar_receber = 2
        AND tf.situacao IN (2, 3)
        AND tf.retencao_tributaria > 0
        GROUP BY 1,2,3  
          ) AS r),
        
        tmp_receitas AS (
          SELECT  r.nome_empresa,
            r.data_despesa_receita,
            r.denominacao,
            r.valor_receita,
            r.classificacao_despesa_receita
          FROM
          (
          WITH RECURSIVE tmp_recebimento_titulos(id_titulo_raiz, 
              id_titulo_financeiro, 
              id_titulo_filho, 
              id_titulo_pai, 
              valor_titulo, 
              situacao, 
              data_liquidacao, 
              id_conta_liquidacao,
              juros_cobranca, 
              multa_cobranca, 
              desconto_cobranca, 
              retencao_tributaria, 
              valor_liquidado, 
              valor_ajuste,
              valor_recebido,
              valor_amortizado,
              valor_residual,
              valor_titulo_recalculado,
              valor_amortizado_recalculado,
              valor_residual_recalculado) AS 
          (
            SELECT  tf.id_titulo_financeiro AS id_titulo_raiz,
              tf.id_titulo_financeiro,
              tf.id_titulo_filho,
              tf.id_titulo_pai,   
              tf.valor_titulo,
              tf.situacao,
              tf.data_liquidacao,
              tf.id_conta_liquidacao,
              tf.juros_cobranca,
              tf.multa_cobranca,
              tf.desconto_cobranca,
              tf.retencao_tributaria,
              tf.valor_liquidado,
              (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) AS valor_ajuste,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_recebido,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) 
              ELSE 
                tf.valor_titulo 
              END) AS DECIMAL(12,2)) AS valor_residual,
              tf.valor_titulo AS valor_titulo_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) 
              ELSE 
                tf.valor_titulo 
              END) AS DECIMAL(12,2)) AS valor_residual_recalculado
            FROM  titulo_financeiro tf
              INNER JOIN (
                  SELECT  DISTINCT pdr.id_titulo_financeiro
                  FROM  despesa_receita AS dr
                    INNER JOIN parcela_despesa_receita AS pdr ON (pdr.id_despesa_receita = dr.id_despesa_receita)
                    INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = dr.id_tipo_despesa_receita)
                  WHERE EXTRACT('YEAR' FROM dr.data_despesa_receita) = $ano_rel
                  AND tdr.despesa_receita = 2
                  AND dr.situacao IN (2,3)        
                ) AS tmp_titulos ON (tmp_titulos.id_titulo_financeiro = tf.id_titulo_financeiro)
                
            UNION ALL
            
            SELECT  t.id_titulo_raiz,
              tf.id_titulo_financeiro,
              tf.id_titulo_filho,
              tf.id_titulo_pai,   
              tf.valor_titulo,
              tf.situacao,
              tf.data_liquidacao,
              tf.id_conta_liquidacao,
              tf.juros_cobranca,
              tf.multa_cobranca,
              tf.desconto_cobranca,
              tf.retencao_tributaria,
              tf.valor_liquidado,
              (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) AS valor_ajuste,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_recebido,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria) 
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) 
              ELSE 
                tf.valor_titulo 
              END) AS DECIMAL(12,2)) AS valor_residual,
                CAST((CASE WHEN t.situacao = 8 THEN
                COALESCE((tf.valor_titulo * t.valor_residual_recalculado) / NULLIF(t.valor_residual, 0), (tf.valor_titulo * t.valor_residual_recalculado))
                ELSE 
                t.valor_residual_recalculado 
                END) AS DECIMAL(12,2)) AS valor_titulo_recalculado,
                CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                (CASE WHEN t.situacao = 8 THEN
                  COALESCE(((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) * t.valor_residual_recalculado) / NULLIF(t.valor_residual, 0), ((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) * t.valor_residual_recalculado)) 
                ELSE 
                  COALESCE((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) / NULLIF((tf.valor_titulo * t.valor_residual_recalculado), 0), (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)))
                END)
              ELSE 
                0.00 
              END) AS DECIMAL(12,2)) AS valor_amortizado_recalculado,
              CAST((CASE WHEN tf.situacao IN (2, 3) THEN
                (CASE WHEN t.situacao = 8 THEN
                  COALESCE(((tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))) * t.valor_residual_recalculado) / NULLIF(t.valor_residual, 0), ((tf.valor_titulo - (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))) * t.valor_residual_recalculado))
                ELSE 
                  (t.valor_residual_recalculado - COALESCE((tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria)) / NULLIF((tf.valor_titulo * t.valor_residual_recalculado), 0), (tf.valor_liquidado - (tf.juros_cobranca + tf.multa_cobranca - tf.desconto_cobranca - tf.retencao_tributaria))))
                END)
              ELSE 
                (CASE WHEN t.situacao = 8 THEN
                  COALESCE((tf.valor_titulo * t.valor_residual_recalculado) / NULLIF(t.valor_residual, 0), (tf.valor_titulo * t.valor_residual_recalculado)) 
                ELSE
                t.valor_residual_recalculado 
                END)
              END) AS DECIMAL(12,2)) AS valor_residual_recalculado
            FROM  titulo_financeiro as tf,
              tmp_recebimento_titulos as t
            WHERE (tf.id_titulo_financeiro = t.id_titulo_filho OR tf.id_titulo_pai = t.id_titulo_financeiro)
          )
        
          --Liquidação de Receita a Prazo
          SELECT  se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            trt.valor_amortizado_recalculado AS valor_receita,
            tdr.classificacao_despesa_receita
          FROM  tmp_recebimento_titulos AS trt
            INNER JOIN titulo_financeiro AS tf ON (tf.id_titulo_financeiro = trt.id_titulo_raiz)
            INNER JOIN parcela_despesa_receita AS pdr ON (pdr.id_titulo_financeiro = tf.id_titulo_financeiro)
            INNER JOIN despesa_receita AS dr ON (dr.id_despesa_receita = pdr.id_despesa_receita)
            INNER JOIN tipo_despesa_receita AS tdr ON (dr.id_tipo_despesa_receita = tdr.id_tipo_despesa_receita)
            INNER JOIN sis_empresa AS se ON (se.id_empresa = dr.id_empresa)
          WHERE dr.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tdr.despesa_receita = 2
          AND dr.situacao IN (2, 3)
          
          UNION ALL
        
        -- Acréscimo Financeiro Recebido
          SELECT se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            tf.acrescimo_financeiro AS valor_receita,
            tdr.classificacao_despesa_receita
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -1000)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.situacao IN (2,3,10)  --LIQUIDADO
          AND tf.pagar_receber = 2
          AND tf.acrescimo_financeiro > 0
        
        UNION ALL
        
        -- JURO DE COBRANÇA RECEBIDO
          SELECT se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            tf.juros_cobranca AS valor_receita,
            tdr.classificacao_despesa_receita
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -2000)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.situacao IN (2,3,10)  --LIQUIDADO
          AND tf.pagar_receber = 2
          AND tf.juros_cobranca > 0
        
          UNION ALL
        
        -- MULTA DE COBRANÇA RECEBIDA
          SELECT se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            tf.multa_cobranca AS valor_receita,
            tdr.classificacao_despesa_receita
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -3000)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.situacao IN (2,3,10)  --LIQUIDADO
          AND tf.pagar_receber = 2
          AND tf.multa_cobranca > 0
        
          UNION ALL
        
        -- DESCONTO FINANCEIRO RECEBIDO
          SELECT se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            tf.desconto_financeiro AS valor_receita,
            tdr.classificacao_despesa_receita
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -4000)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.situacao IN (2,3,10)  --LIQUIDADO
          AND tf.pagar_receber = 1
          AND tf.desconto_financeiro > 0
        
        UNION ALL
        
        --DESCONTO DE COBRANÇA RECEBIDO
          SELECT se.nome AS nome_empresa,
            tf.data_liquidacao AS data_despesa_receita,
            tdr.denominacao AS denominacao,
            tf.desconto_cobranca AS valor_receita,
            tdr.classificacao_despesa_receita
          FROM  titulo_financeiro AS tf
            INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
            INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -5000)
          WHERE tf.id_empresa IN ($emp)
          AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
          AND tf.situacao IN (2,3,10)  --LIQUIDADO
          AND tf.pagar_receber = 1
          AND tf.desconto_cobranca > 0
        
        UNION ALL
        
        --RETENÇÃO TRIBUTARIA ACRESCIDA
        SELECT se.nome AS nome_empresa,
          tf.data_liquidacao AS data_despesa_receita,
          tdr.denominacao AS denominacao,
          tf.retencao_tributaria AS valor_receita,
          tdr.classificacao_despesa_receita
        FROM  titulo_financeiro AS tf
          INNER JOIN sis_empresa AS se ON (se.id_empresa = tf.id_empresa)
          INNER JOIN tipo_despesa_receita AS tdr ON (tdr.id_tipo_despesa_receita = -8000)
        WHERE tf.id_empresa IN ($emp)
        AND EXTRACT('YEAR' FROM tf.data_liquidacao) = $ano_rel
        AND tf.situacao IN (2,3,10)  --LIQUIDADO
        AND tf.pagar_receber = 1
        AND tf.retencao_tributaria > 0
          ) AS r),
        
        tmp_resumo_mensal AS (
          -- COMBUSTÍVEL
          SELECT  r.empresa,
            r.denominacao_item,
            EXTRACT('MONTH' FROM r.data) AS mes,
            COUNT(r.id_venda_cf) AS qtde_abastecimento,
            SUM(r.quantidade) AS litragem,
            SUM(r.valor_venda_combustivel) AS valor_venda_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_produto,
            SUM(r.custo) AS custo_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS custo_produto,
            CAST(CAST('' AS TEXT) AS VARCHAR(100)) AS denominacao_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_retirada,
            CAST(0 AS DECIMAL(12,2)) AS valor_investimento,
            CAST(0 AS DECIMAL(12,3)) AS valor_variacao_estoque,
            '' AS denominacao_categoria
          FROM  (
            SELECT  se.nome AS empresa,
              i.denominacao AS denominacao_item,
              mvt.data_movimento AS data,
              iv.id_venda_cf,
              iv.quantidade,
              (iv.total_item - iv.desconto_rateado + iv.acrescimo_rateado - iv.desconto_ajuste_rateado + iv.acrescimo_ajuste_rateado - iv.desconto_automatico_rateado + iv.acrescimo_automatico_rateado - iv.desconto_fidelidade_rateado) AS valor_venda_combustivel,
              me.valor AS custo
            FROM  movimento_venda_terminal AS mvt
              INNER JOIN venda_cf AS v ON (v.id_movimento_venda_terminal = mvt.id_movimento_venda_terminal)
              INNER JOIN item_venda_cf AS iv ON (iv.id_venda_cf = v.id_venda_cf)
              INNER JOIN movimento_estoque AS me ON (me.id_movimento_estoque = iv.id_movimento_estoque)
              INNER JOIN sis_empresa AS se ON (se.id_empresa = mvt.id_empresa)
              INNER JOIN item AS i ON (i.id_item = iv.id_item)
            WHERE mvt.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM mvt.data_movimento) = $ano_rel
            AND v.cancelada = 'N'
            AND iv.cancelado = 'N'
            AND NOT iv.id_bico_combustivel IS NULL
        
            UNION ALL
        
            SELECT  se.nome AS empresa,
              i.denominacao AS denominacao_item,
              nfs.emissao AS data,
              infs.id_item_nfs,
              infs.quantidade,
              infs.total_item AS valor_venda_combustivel,
              me.valor AS custo
            FROM  nota_fiscal_saida AS nfs
              INNER JOIN item_nfs AS infs ON (infs.id_nota_fiscal_saida = nfs.id_nota_fiscal_saida)
              INNER JOIN sis_referencia_registro AS rr ON (rr.id_chave_registro_a = infs.id_item_nfs AND rr.tipo_referencia = 'ref_1101_0411_movimento_estoque_nfs')
              INNER JOIN movimento_estoque AS me ON (me.id_movimento_estoque = rr.id_chave_registro_b)
              INNER JOIN item AS i ON (i.id_item = infs.id_item)
              INNER JOIN sis_empresa AS se ON (se.id_empresa = nfs.id_empresa)
            WHERE nfs.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM nfs.emissao) = $ano_rel
            AND nfs.situacao = 1 -- Confirmado
            AND EXISTS(SELECT 1 FROM cfop WHERE tipo_movimento_estoque != 4 AND gera_titulo_financeiro = 'S' AND id_tipo_movimento_estoque = 7 AND id_cfop = infs.id_cfop)
            AND infs.id_local_estoque IN (SELECT id_local_estoque FROM local_estoque WHERE tipo = 2)
        
            UNION ALL
        
            SELECT  se.nome AS nome_empresa,
              i.denominacao AS denominacao_item,
              nfe.emissao AS data,
              (infe.id_item_nfe * -1) AS id_item_nfe,
              (infe.quantidade * -1) AS quantidade,
              (infe.total_produto * -1) AS valor_venda_combustivel,
              (me.valor * -1) AS custo
            FROM  nota_fiscal_entrada AS nfe
              INNER JOIN item_nfe AS infe ON (infe.id_nota_fiscal_entrada = nfe.id_nota_fiscal_entrada)
              INNER JOIN movimento_estoque AS me ON (me.id_movimento_estoque = infe.id_movimento_estoque)
              INNER JOIN item AS i ON (i.id_item = infe.id_item)
              INNER JOIN sis_empresa AS se ON (se.id_empresa = nfe.id_empresa)
            WHERE nfe.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM nfe.emissao) = $ano_rel
            AND nfe.situacao = 1 -- Confirmado
            AND EXISTS(SELECT 1 FROM cfop WHERE tipo_movimento_estoque != 4 AND id_tipo_movimento_estoque = 3 AND id_cfop = infe.id_cfop)
            AND infe.id_local_estoque IN (SELECT id_local_estoque FROM local_estoque WHERE tipo = 2)
            ) AS r
          GROUP BY r.empresa,
            r.denominacao_item,
            mes
        
          UNION ALL
        
          SELECT  r2.empresa,
            CAST('Outros Produtos' AS TEXT) AS denominacao_item,
            EXTRACT('MONTH' FROM r2.data) AS mes,
            CAST(0 AS INTEGER) AS abastecimentos,
            CAST(0 AS DECIMAL(13,3)) AS litragem,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_combustivel,
            SUM(r2.valor_venda_produto) AS valor_venda_produto,
            CAST(0 AS DECIMAL(12,2)) AS custo_combustivel,
            SUM(COALESCE(r2.custo, 0)) AS custo_produto,
            CAST(CAST('' AS TEXT) AS VARCHAR(100)) AS denominacao_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_retirada,
            CAST(0 AS DECIMAL(12,2)) AS valor_investimento,
            CAST(0 AS DECIMAL(12,3)) AS valor_variacao_estoque,
            '' AS denominacao_categoria
          FROM  (
            SELECT  se.nome AS empresa,
              mvt.data_movimento AS data,
              (iv.total_item - iv.desconto_rateado + iv.acrescimo_rateado - iv.desconto_ajuste_rateado + iv.acrescimo_ajuste_rateado - iv.desconto_automatico_rateado + iv.acrescimo_automatico_rateado - iv.desconto_fidelidade_rateado) AS valor_venda_produto,
              COALESCE(me.valor, 0) AS custo
            FROM  movimento_venda_terminal AS mvt
              INNER JOIN venda_cf AS v ON (mvt.id_movimento_venda_terminal = v.id_movimento_venda_terminal)
              INNER JOIN item_venda_cf AS iv ON (v.id_venda_cf = iv.id_venda_cf)
              LEFT JOIN movimento_estoque AS me ON (me.id_movimento_estoque = iv.id_movimento_estoque)
              INNER JOIN sis_empresa AS se ON (se.id_empresa = mvt.id_empresa)
            WHERE mvt.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM mvt.data_movimento) = $ano_rel
            AND v.cancelada = 'N'
            AND iv.cancelado = 'N'
            AND iv.id_bico_combustivel IS NULL
        
            UNION ALL
        
            SELECT  se.nome AS empresa,
              nfs.emissao AS data,
              infs.total_item AS valor_venda_produto,
              me.valor AS custo
            FROM  nota_fiscal_saida AS nfs
              INNER JOIN item_nfs AS infs ON (infs.id_nota_fiscal_saida = nfs.id_nota_fiscal_saida)
              INNER JOIN sis_referencia_registro AS rr ON (rr.id_chave_registro_a = infs.id_item_nfs AND rr.tipo_referencia = 'ref_1101_0411_movimento_estoque_nfs')
              INNER JOIN movimento_estoque AS me ON (me.id_movimento_estoque = rr.id_chave_registro_b)
              INNER JOIN sis_empresa AS se ON (se.id_empresa = nfs.id_empresa)
            WHERE nfs.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM nfs.emissao) = $ano_rel
            AND nfs.situacao = 1 -- Confirmado
            AND EXISTS(SELECT 1 FROM cfop AS c WHERE c.tipo_movimento_estoque != 4 AND c.gera_titulo_financeiro = 'S' AND c.id_tipo_movimento_estoque = 7 AND c.id_cfop = infs.id_cfop)
            AND EXISTS(SELECT 1 FROM item_venda_cf AS iv WHERE iv.id_bico_combustivel IS NULL AND iv.id_item = infs.id_item AND DATE(iv.data_venda) = nfs.emissao AND iv.cancelado = 'N')
        
            UNION ALL
        
            SELECT  se.nome AS empresa,
              nfe.emissao AS data,
              (infe.total_produto * -1) AS valor_venda_produto,
              (me.valor * -1) AS custo
            FROM  nota_fiscal_entrada AS nfe
              INNER JOIN item_nfe AS infe ON (infe.id_nota_fiscal_entrada = nfe.id_nota_fiscal_entrada)
              INNER JOIN movimento_estoque AS me ON (me.id_movimento_estoque = infe.id_movimento_estoque)
              INNER JOIN sis_empresa AS se ON (se.id_empresa = nfe.id_empresa)
            WHERE nfe.id_empresa IN ($emp)
            AND EXTRACT('YEAR' FROM nfe.emissao) = $ano_rel
            AND nfe.situacao = 1 -- Confirmado
            AND EXISTS(SELECT 1 FROM cfop AS c WHERE c.tipo_movimento_estoque != 4 AND c.id_tipo_movimento_estoque = 3 AND c.id_cfop = infe.id_cfop)
            AND EXISTS(SELECT 1 FROM item_venda_cf AS iv WHERE iv.id_bico_combustivel IS NULL AND iv.id_item = infe.id_item AND DATE(iv.data_venda) = nfe.emissao AND iv.cancelado = 'N')
            ) AS r2
          GROUP BY r2.empresa,
            mes
        
          UNION ALL
        
            -- DESPESAS GERAIS
          SELECT  nome_empresa,
            CAST('' AS TEXT) AS denominacao_item,
            EXTRACT('MONTH' FROM data_despesa_receita) AS mes,
            CAST(0 AS INTEGER) AS abastecimentos,
            CAST(0 AS DECIMAL(12,3)) AS litragem,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_produto,
            CAST(0 AS DECIMAL(12,2)) AS custo_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS custo_produto,
            denominacao AS denominacao_despesa,
            valor_despesa AS valor_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_retirada,
            CAST(0 AS DECIMAL(12,2)) AS valor_investimento,
            CAST(0 AS DECIMAL(12,3)) AS valor_variacao_estoque,
            denominacao_categoria AS denominacao_categoria
          FROM  tmp_despesas
          WHERE classificacao_despesa_receita NOT IN (5,6)
          -- FIM DAS DESPESAS GERAIS
          
          UNION ALL
        
          -- RECEITAS
          SELECT  nome_empresa,
            CAST('' AS TEXT) AS denominacao_item,
            EXTRACT('MONTH' FROM data_despesa_receita) AS mes,
            CAST(0 AS INTEGER) AS abastecimentos,
            CAST(0 AS DECIMAL(12,3)) AS litragem,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_produto,
            CAST(0 AS DECIMAL(12,2)) AS custo_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS custo_produto,
            denominacao AS denominacao_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_despesa,
            valor_receita AS valor_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_retirada,
            CAST(0 AS DECIMAL(12,2)) AS valor_investimento,
            CAST(0 AS DECIMAL(12,3)) AS valor_variacao_estoque,
            '' AS denominacao_categoria
          FROM  tmp_receitas
          -- FIM DAS RECEITAS
        
          UNION ALL
        
        -- RETIRADAS
          SELECT  nome_empresa,
            CAST('' AS TEXT) AS denominacao_item,
            EXTRACT('MONTH' FROM data_despesa_receita) AS mes,
            CAST(0 AS INTEGER) AS abastecimentos,
            CAST(0 AS DECIMAL(12,3)) AS litragem,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_produto,
            CAST(0 AS DECIMAL(12,2)) AS custo_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS custo_produto,
            denominacao AS denominacao_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_receita,
            valor_despesa AS valor_retirada,
            CAST(0 AS DECIMAL(12,2)) AS valor_investimento,
            CAST(0 AS DECIMAL(12,3)) AS valor_variacao_estoque,
            denominacao_categoria AS denominacao_categoria
          FROM  tmp_despesas 
          WHERE classificacao_despesa_receita = 6
          -- FIM DAS RETIRADAS
        
          UNION ALL
        
          -- INVESTIMENTOS
          SELECT  nome_empresa,
            CAST('' AS TEXT) AS denominacao_item,
            EXTRACT('MONTH' FROM data_despesa_receita) AS mes,
            CAST(0 AS INTEGER) AS abastecimentos,
            CAST(0 AS DECIMAL(12,3)) AS litragem,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_produto,
            CAST(0 AS DECIMAL(12,2)) AS custo_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS custo_produto,
            denominacao AS denominacao_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_retirada,
            valor_despesa AS valor_investimento,
            CAST(0 AS DECIMAL(12,3)) AS valor_variacao_estoque,
            denominacao_categoria AS denominacao_categoria
          FROM  tmp_despesas 
          WHERE classificacao_despesa_receita = 5
          -- FIM INVESTIMENTOS
        
          UNION ALL
        
          -- VARIAÇÃO DE ESTOQUE
          SELECT  se.nome AS nome_empresa,
            CAST('' AS TEXT) AS denominacao_item,
            EXTRACT('MONTH' FROM data) AS mes,
            CAST(0 AS INTEGER) AS abastecimentos,
            CAST(0 AS DECIMAL(12,3)) AS litragem,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS valor_venda_produto,
            CAST(0 AS DECIMAL(12,2)) AS custo_combustivel,
            CAST(0 AS DECIMAL(12,2)) AS custo_produto,
            'Variacao de Estoque' AS denominacao_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_despesa,
            CAST(0 AS DECIMAL(12,2)) AS valor_receita,
            CAST(0 AS DECIMAL(12,2)) AS valor_retirada,
            CAST(0 AS DECIMAL(12,2)) AS valor_investimento,
            total_variacao_estoque AS valor_variacao_estoque,
            '' AS denominacao_categoria
        FROM  tmp_variacao_estoque AS tmp
          INNER JOIN sis_empresa AS se ON (se.id_empresa = tmp.id_empresa)
          ),
          sub_ordenacao AS (
        
        SELECT  empresa,
          ROW_NUMBER() OVER (PARTITION BY empresa) + 5 AS ordenacao,
          denominacao_categoria AS sessao_relatorio
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_categoria 
        HAVING  SUM(valor_despesa) != 0
        
        )
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(1 AS INTEGER) AS ordenacao,
          CAST('Total das Vendas' AS TEXT) AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS dezembro,
          SUM((valor_venda_combustivel + valor_venda_produto)) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(2 AS INTEGER) AS ordenacao,
          CAST('Total das Vendas' AS TEXT) AS sessao_relatorio,
          denominacao_item AS subsessao_relatorio,
          CAST('N' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_venda_combustivel + valor_venda_produto) ELSE 0 END) AS dezembro,
          SUM((valor_venda_combustivel + valor_venda_produto)) AS informacao_anual
        FROM  tmp_resumo_mensal
        WHERE ((valor_venda_combustivel > 0) OR (valor_venda_produto > 0))
        GROUP BY empresa,
          denominacao_item
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(3 AS INTEGER) AS ordenacao,
          CAST('(-) Custo Total' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (custo_combustivel + custo_produto) ELSE 0 END) AS dezembro,
          SUM((custo_combustivel + custo_produto)) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(4 AS INTEGER) AS ordenacao,
          CAST('(=) Lucro Bruto' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) ELSE 0 END) AS dezembro,
          SUM((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto)) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(5 AS INTEGER) AS ordenacao,
          CAST('(-) Despesas' AS TEXT) AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_despesa) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_despesa) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_despesa) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_despesa) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_despesa) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_despesa) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_despesa) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_despesa) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_despesa) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_despesa) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_despesa) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_despesa) ELSE 0 END) AS dezembro,
          SUM(valor_despesa) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST((SELECT s.ordenacao FROM sub_ordenacao AS s WHERE s.empresa = tmp_resumo_mensal.empresa AND s.sessao_relatorio = tmp_resumo_mensal.denominacao_categoria) AS INTEGER) AS ordenacao,
          denominacao_categoria AS sessao_relatorio,
          denominacao_despesa AS subsessao_relatorio,
          CAST('N' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_despesa) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_despesa) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_despesa) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_despesa) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_despesa) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_despesa) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_despesa) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_despesa) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_despesa) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_despesa) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_despesa) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_despesa) ELSE 0 END) AS dezembro,
          SUM(valor_despesa) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_categoria,
          denominacao_despesa 
        HAVING  SUM(valor_despesa) != 0
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST((SELECT s.ordenacao FROM sub_ordenacao AS s WHERE s.empresa = tmp_resumo_mensal.empresa AND s.sessao_relatorio = tmp_resumo_mensal.denominacao_categoria) AS INTEGER) AS ordenacao,
          denominacao_categoria AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_despesa) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_despesa) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_despesa) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_despesa) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_despesa) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_despesa) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_despesa) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_despesa) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_despesa) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_despesa) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_despesa) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_despesa) ELSE 0 END) AS dezembro,
          SUM(valor_despesa) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_categoria
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(90 AS INTEGER) AS ordenacao,
          CAST('(+) Receitas' AS TEXT) AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_receita) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_receita) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_receita) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_receita) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_receita) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_receita) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_receita) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_receita) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_receita) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_receita) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_receita) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_receita) ELSE 0 END) AS dezembro,
          SUM(valor_receita) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(91 AS INTEGER) AS ordenacao,
          CAST('(+) Receita' AS TEXT) AS sessao_relatorio,
          denominacao_despesa AS subsessao_relatorio,
          CAST('N' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_receita) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_receita) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_receita) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_receita) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_receita) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_receita) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_receita) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_receita) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_receita) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_receita) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_receita) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_receita) ELSE 0 END) AS dezembro,
          SUM(valor_receita) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_despesa
        HAVING  SUM(valor_receita) != 0
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(92 AS INTEGER) AS ordenacao,
          CAST('(=) Lucro Líquido' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque) ELSE 0 END) AS dezembro,
          SUM((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto) - valor_despesa + valor_receita + valor_variacao_estoque) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(93 AS INTEGER) AS ordenacao,
          CAST('(-) Retiradas' AS TEXT) AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_retirada) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_retirada) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_retirada) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_retirada) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_retirada) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_retirada) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_retirada) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_retirada) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_retirada) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_retirada) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_retirada) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_retirada) ELSE 0 END) AS dezembro,
          SUM(valor_retirada) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(94 AS INTEGER) AS ordenacao,
          CAST('(-) Retiradas' AS TEXT) AS sessao_relatorio,
          denominacao_despesa AS subsessao_relatorio,
          CAST('N' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_retirada) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_retirada) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_retirada) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_retirada) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_retirada) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_retirada) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_retirada) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_retirada) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_retirada) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_retirada) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_retirada) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_retirada) ELSE 0 END) AS dezembro,
          SUM(valor_retirada) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_despesa
        HAVING  SUM(valor_retirada) != 0
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(95 AS INTEGER) AS ordenacao,
          CAST('(-) Investimentos' AS TEXT) AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_investimento) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_investimento) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_investimento) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_investimento) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_investimento) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_investimento) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_investimento) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_investimento) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_investimento) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_investimento) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_investimento) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_investimento) ELSE 0 END) AS dezembro,
          SUM(valor_investimento) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(96 AS INTEGER) AS ordenacao,
          CAST('(-) Investimentos' AS TEXT) AS sessao_relatorio,
          denominacao_despesa AS subsessao_relatorio,
          CAST('N' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_investimento) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_investimento) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_investimento) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_investimento) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_investimento) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_investimento) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_investimento) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_investimento) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_investimento) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_investimento) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_investimento) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_investimento) ELSE 0 END) AS dezembro,
          SUM(valor_investimento) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_despesa
        HAVING  SUM(valor_investimento) != 0
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(97 AS INTEGER) AS ordenacao,
          CAST('Variação de Estoque' AS TEXT) AS sessao_relatorio,
          denominacao_despesa AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN (valor_variacao_estoque) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN (valor_variacao_estoque) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN (valor_variacao_estoque) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN (valor_variacao_estoque) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN (valor_variacao_estoque) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN (valor_variacao_estoque) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN (valor_variacao_estoque) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN (valor_variacao_estoque) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN (valor_variacao_estoque) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN (valor_variacao_estoque) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN (valor_variacao_estoque) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN (valor_variacao_estoque) ELSE 0 END) AS dezembro,
          SUM(valor_variacao_estoque) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa,
          denominacao_despesa
        HAVING  SUM(valor_variacao_estoque) != 0
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Valores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(98 AS INTEGER) AS ordenacao,
          CAST('(=) Saldo Final' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN ((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) ELSE 0 END) AS dezembro,
          SUM((valor_venda_combustivel + valor_venda_produto) - (custo_combustivel + custo_produto + valor_despesa) + valor_receita + valor_variacao_estoque - valor_retirada - valor_investimento) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(101 AS INTEGER) AS ordenacao,
          CAST('Volume Vendido (Litros)' AS TEXT) AS sessao_relatorio,
          CAST('Total' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) AS dezembro,
          SUM(litragem) AS informacao_anual
        FROM  tmp_resumo_mensal
        WHERE valor_venda_produto = 0
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(102 AS INTEGER) AS ordenacao,
          CAST('Volume Vendido (Litros)' AS TEXT) AS sessao_relatorio,
          denominacao_item AS subsessao_relatorio,
          CAST('N' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) AS dezembro,
          SUM(litragem) AS informacao_anual
        FROM  tmp_resumo_mensal
        WHERE valor_venda_produto = 0 
        AND valor_venda_combustivel > 0
        GROUP BY empresa,
          denominacao_item
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(103 AS INTEGER) AS ordenacao,
          CAST('Número Total de Abastecimentos' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(0 AS INTEGER) AS casas_decimais,
          SUM(CASE WHEN mes = 1 THEN qtde_abastecimento ELSE 0 END) AS janeiro,
          SUM(CASE WHEN mes = 2 THEN qtde_abastecimento ELSE 0 END) AS fevereiro,
          SUM(CASE WHEN mes = 3 THEN qtde_abastecimento ELSE 0 END) AS marco,
          SUM(CASE WHEN mes = 4 THEN qtde_abastecimento ELSE 0 END) AS abril,
          SUM(CASE WHEN mes = 5 THEN qtde_abastecimento ELSE 0 END) AS maio,
          SUM(CASE WHEN mes = 6 THEN qtde_abastecimento ELSE 0 END) AS junho,
          SUM(CASE WHEN mes = 7 THEN qtde_abastecimento ELSE 0 END) AS julho,
          SUM(CASE WHEN mes = 8 THEN qtde_abastecimento ELSE 0 END) AS agosto,
          SUM(CASE WHEN mes = 9 THEN qtde_abastecimento ELSE 0 END) AS setembro,
          SUM(CASE WHEN mes = 10 THEN qtde_abastecimento ELSE 0 END) AS outubro,
          SUM(CASE WHEN mes = 11 THEN qtde_abastecimento ELSE 0 END) AS novembro,
          SUM(CASE WHEN mes = 12 THEN qtde_abastecimento ELSE 0 END) AS dezembro,
          SUM(qtde_abastecimento) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(104 AS INTEGER) AS ordenacao,
          CAST('Ticket Médio (R$)' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(2 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN valor_venda_combustivel ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 2) AS dezembro,
          ROUND((CASE WHEN SUM(qtde_abastecimento) > 0 THEN SUM(valor_venda_combustivel) / SUM(qtde_abastecimento) ELSE 0 END), 2) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(105 AS INTEGER) AS ordenacao,
          CAST('Ticket Médio (Litros)' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN qtde_abastecimento ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN qtde_abastecimento ELSE 0 END)) ELSE 0 END), 3) AS dezembro,
          ROUND((CASE WHEN SUM(qtde_abastecimento) > 0 THEN SUM(litragem) / SUM(qtde_abastecimento) ELSE 0 END), 3) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(106 AS INTEGER) AS ordenacao,
          CAST('Faturamento por Litro' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS dezembro,
          ROUND((CASE WHEN SUM(litragem) > 0 THEN SUM(valor_venda_combustivel - custo_combustivel) / SUM(litragem) ELSE 0 END), 3) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(107 AS INTEGER) AS ordenacao,
          CAST('Custo por Litro' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN (custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS dezembro,
          ROUND((CASE WHEN SUM(litragem) > 0 THEN SUM(custo_combustivel) / SUM(litragem) ELSE 0 END), 3) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(108 AS INTEGER) AS ordenacao,
          CAST('Lucro Bruto por Litro' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN (valor_venda_combustivel - custo_combustivel) ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS dezembro,
          ROUND((CASE WHEN SUM(litragem) > 0 THEN SUM((valor_venda_combustivel - custo_combustivel)) / SUM(litragem) ELSE 0 END), 3) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(109 AS INTEGER) AS ordenacao,
          CAST('Despesa por Litro' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN valor_despesa ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS dezembro,
          ROUND((CASE WHEN SUM(litragem) > 0 THEN SUM(valor_despesa) / SUM(litragem) ELSE 0 END), 3) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        
        UNION ALL
        
        SELECT  empresa,
          CAST('Indicadores' AS VARCHAR(50)) AS tipo_informacao,
          CAST(110 AS INTEGER) AS ordenacao,
          CAST('Lucro Líquido por Litro' AS TEXT) AS sessao_relatorio,
          CAST('' AS TEXT) AS subsessao_relatorio,
          CAST('S' AS CHAR(1)) AS totalizador,
          CAST(3 AS INTEGER) AS casas_decimais,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 1 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 1 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS janeiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 2 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 2 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS fevereiro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 3 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 3 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS marco,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 4 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 4 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS abril,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 5 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 5 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS maio,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 6 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 6 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS junho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 7 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 7 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS julho,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 8 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 8 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS agosto,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 9 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 9 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS setembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 10 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 10 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS outubro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 11 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 11 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS novembro,
          ROUND((CASE WHEN SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END) > 0 THEN (SUM(CASE WHEN mes = 12 THEN (valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) ELSE 0 END) / SUM(CASE WHEN mes = 12 THEN litragem ELSE 0 END)) ELSE 0 END), 3) AS dezembro,
          ROUND((CASE WHEN SUM(litragem) > 0 THEN SUM(valor_venda_combustivel - (custo_combustivel + valor_despesa) + valor_variacao_estoque) / SUM(litragem) ELSE 0 END), 3) AS informacao_anual
        FROM  tmp_resumo_mensal
        GROUP BY empresa
        ORDER BY 1,2 DESC, 3, 4, 5
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
