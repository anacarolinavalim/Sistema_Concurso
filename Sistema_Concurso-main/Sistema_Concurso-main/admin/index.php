<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = Sessao::exigirAdmin();
$inscDao = new InscricaoDAO();
$tituloPagina = 'Painel';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Painel administrativo</h1>
<p>Olá, <?= e($admin->getNome()) ?>. <strong>Fase atual:</strong> <?= e(Calendario::faseAtual()) ?></p>
<div class="cartoes">
  <div class="cartao"><strong><?= (new CandidatoDAO())->contar() ?></strong>Candidatos</div>
  <div class="cartao"><strong><?= $inscDao->contarPorStatus('Ativa') ?></strong>Inscrições ativas</div>
  <div class="cartao"><strong><?= $inscDao->contarPorStatus('Desclassificada') ?></strong>Desclassificadas</div>
  <div class="cartao"><strong><?= (new RecursoDAO())->contarPendentes() ?></strong>Recursos pendentes</div>
  <div class="cartao"><strong><?= (new ContratoLeilaoDAO())->contarAtivos() ?></strong>Contratos ativos</div>
  <div class="cartao"><strong><?= (new PunicaoCandidatoDAO())->contarAtivas(Calendario::hoje()) ?></strong>Suspensões vigentes</div>
</div>
<h2>Atalhos</h2>
<p class="acoes">
  <a class="botao sec" href="<?= e(url('admin/classificacao.php')) ?>">Gerar/publicar classificação</a>
  <a class="botao sec" href="<?= e(url('admin/recursos.php')) ?>">Registrar recursos</a>
  <a class="botao sec" href="<?= e(url('admin/leiloes.php')) ?>">Leilão de vagas</a>
  <a class="botao sec" href="<?= e(url('admin/configuracao.php')) ?>">Datas do edital</a>
</p>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
