<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$lista = (new LeilaoVagaDAO())->listar();
$tituloPagina = 'Leilões';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Leilão de vagas</h1>
<p><a class="botao" href="<?= e(url('admin/leilao_form.php')) ?>">Novo leilão</a></p>
<?php if (!$lista): ?><p>Nenhum leilão cadastrado.</p><?php else: ?>
<div class="tabela-rolagem"><table>
  <tr><th>Nº</th><th>Data</th><th>Ano letivo</th><th>Cargo</th><th>Escolaridade</th><th>Horário</th><th>Vagas</th><th>Situação</th><th></th></tr>
  <?php foreach ($lista as $l): ?>
    <tr>
      <td><?= (int)$l['id'] ?></td><td><?= e(dataBr($l['data_leilao'])) ?></td><td><?= (int)$l['ano_letivo'] ?></td>
      <td><?= e($l['titulo_cargo']) ?></td><td><?= e(Titulacao::rotulo($l['escolaridade'])) ?></td><td><?= e($l['horario']) ?></td>
      <td><?= (int)$l['ocupadas'] ?> / <?= (int)$l['vagas_ofertadas'] ?></td><td><?= badgeStatus($l['status']) ?></td>
      <td class="acoes"><a class="botao pequeno" href="<?= e(url('admin/leilao_chamada.php?id=' . (int)$l['id'])) ?>">Chamada</a>
        <a class="botao sec pequeno" href="<?= e(url('admin/leilao_form.php?id=' . (int)$l['id'])) ?>">Editar</a></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
