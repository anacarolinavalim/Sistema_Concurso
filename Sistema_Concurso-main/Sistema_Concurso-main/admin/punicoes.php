<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$lista = (new PunicaoCandidatoDAO())->listar();
$hoje = Calendario::hoje();
$tituloPagina = 'Suspensões';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Suspensões por desistência (<?= (int)DIAS_SUSPENSAO ?> dias)</h1>
<p class="ajuda">O candidato suspenso não pode participar de novos leilões do mesmo cargo durante o período (anos de 2027 e 2028). O bloqueio vale do dia da desistência até a data final, inclusive.</p>
<?php if (!$lista): ?><p>Nenhuma suspensão registrada.</p><?php else: ?>
<div class="tabela-rolagem"><table>
  <tr><th>Candidato</th><th>Cargo</th><th>Início</th><th>Fim</th><th>Situação</th><th>Motivo</th></tr>
  <?php foreach ($lista as $p): ?>
    <tr><td><?= e($p['nome']) ?><br><span class="ajuda"><?= e(mascararCpf($p['cpf'])) ?></span></td><td><?= e($p['titulo_cargo']) ?></td>
      <td><?= e(dataBr($p['data_inicio'])) ?></td><td><?= e(dataBr($p['data_fim'])) ?></td>
      <td><?= ($p['data_inicio'] <= $hoje && $p['data_fim'] >= $hoje) ? badge('Vigente', 'erro') : badge('Encerrada') ?></td>
      <td><?= e($p['motivo']) ?></td></tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
