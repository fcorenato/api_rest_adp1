<?php
require('src/config/conexao.php');

$data_hoje =  date("Y-m-d");

//pesquisando orcamentos vencidos
$pesquisa = mysql_query("SELECT id, orc_data_valid FROM md_vendas_pedidos WHERE orc_data_valid < '$data_hoje' and (status = 'A' or status = 'B')") or die(mysql_error());
$linhas = mysql_num_rows($pesquisa);
echo 'registros:' . $linhas . '<br>';
if ($linhas > 0) {
  while ($dados = mysql_fetch_array($pesquisa)) {
    //echo $dados["id"] . '<br>';
    $ID_ORC = $dados["id"];
    $data_evento = $dados["orc_data_valid"];

    //incianco transacao para commit
    mysql_query("START TRANSACTION");

    //alterando status para vencido
    $status_ped_update = mysql_query("UPDATE `md_vendas_pedidos` SET status = 'V' WHERE id = '$ID_ORC'");

    //registrando evento
    $evento_ped_insert = mysql_query("INSERT INTO md_vendas_pedidos_eventos (pedido_id, created_at, evento_user, evento) VALUES ('$ID_ORC', '$data_hoje', 0, 'Orçamento Vencido')") or die(mysql_error());

    if ($status_ped_update and $evento_ped_insert) {
      mysql_query("COMMIT");
    } else {
      mysql_query("ROLLBACK");
    }
  }
}


/*

