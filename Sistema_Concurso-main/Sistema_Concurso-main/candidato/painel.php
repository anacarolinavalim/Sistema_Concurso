<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
$inscricoes = $cand->gerenciarInscricoes();
$abertas = Calendario::inscricoesAbertas();
$ativas = 0;
foreach ($inscricoes as $i) {
    if ($i['status'] === Inscricao::STATUS_ATIVA) {
        $ativas++;
    }
}
$versaoDao = new VersaoInscricaoDAO();
$tituloPagina = 'Meu Painel';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Olá, <?= e($cand->getNome()) ?></h1>
<p><strong>Fase atual:</strong> <?= e(Calendario::faseAtual()) ?><br>
   Prazo de inscrição: <?= e(dataHoraBr(Calendario::inscricaoInicio()->format('Y-m-d H:i:s'))) ?> até <?= e(dataHoraBr(Calendario::inscricaoFim()->format('Y-m-d H:i:s'))) ?></p>
<p class="acoes">
  <?php if ($abertas && $ativas < MAX_INSCRICOES): ?>
    <a class="botao" href="<?= e(url('candidato/inscricao_form.php')) ?>">Nova inscrição</a>
  <?php endif; ?>
  <a class="botao sec" href="<?= e(url('candidato/dados.php')) ?>">Meus dados</a>
  <a class="botao sec" href="<?= e(url('candidato/senha.php')) ?>">Alterar senha</a>
</p>
<?php if (!$abertas): ?>
  <div class="alerta aviso">As inscrições estão fora do prazo: não é possível criar, alterar ou cancelar inscrições.</div>
<?php elseif ($ativas >= MAX_INSCRICOES): ?>
  <div class="alerta info">Você atingiu o limite de <?= (int)MAX_INSCRICOES ?> inscrições ativas. Cancele uma para fazer outra.</div>
<?php endif; ?>

<h2>Minhas inscrições</h2>
<?php if (!$inscricoes): ?>
  <p>Você ainda não possui inscrições.</p>
<?php else: ?>
  <div class="tabela-rolagem"><table>
    <tr><th>Cargo</th><th>Tempo de serviço</th><th>Titulação</th><th>Situação</th><th>Última alteração</th><th>Versão</th><th>Ações</th></tr>
    <?php foreach ($inscricoes as $i): ?>
      <tr>
        <td><?= e($i['titulo_cargo']) ?></td>
        <td><?= e(tempoServicoTexto((int)$i['tempo_servico_dias'])) ?></td>
        <td><?= e(Titulacao::rotulo($i['nivel_titulacao'])) ?></td>
        <td><?= badgeStatus($i['status']) ?></td>
        <td><?= e(dataHoraBr($i['ultima_alteracao'])) ?></td>
        <td><?= (int)$i['versao_atual'] ?></td>
        <td class="acoes">
          <a class="botao sec pequeno" href="<?= e(url('candidato/comprovante.php?id=' . (int)$i['id'])) ?>">Comprovante</a>
          <?php if ($abertas && $i['status'] === Inscricao::STATUS_ATIVA): ?>
            <a class="botao sec pequeno" href="<?= e(url('candidato/inscricao_form.php?id=' . (int)$i['id'])) ?>">Editar</a>
            <form method="post" action="<?= e(url('candidato/inscricao_cancelar.php')) ?>" onsubmit="return confirm('Cancelar esta inscrição?');">
              <?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
              <button type="submit" class="perigo pequeno">Cancelar</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table></div>

  <h2>Histórico de comprovantes</h2>
  <p class="ajuda">Cada alteração gera um novo comprovante. Todas as versões ficam registradas.</p>
  <?php foreach ($inscricoes as $i): ?>
    <details>
      <summary><?= e($i['titulo_cargo']) ?> – <?= count($versaoDao->listarPorInscricao((int)$i['id'])) ?> versão(ões)</summary>
      <div class="tabela-rolagem"><table>
        <tr><th>Versão</th><th>Data/hora</th><th>Código</th><th>Registrado por</th><th></th></tr>
        <?php foreach ($versaoDao->listarPorInscricao((int)$i['id']) as $v): ?>
          <tr>
            <td><?= (int)$v['versao'] ?></td>
            <td><?= e(dataHoraBr($v['data_registro'])) ?></td>
            <td><?= e($v['codigo_validacao']) ?></td>
            <td><?= e($v['usuario_nome']) ?></td>
            <td><a href="<?= e(url('candidato/comprovante.php?id=' . (int)$i['id'] . '&v=' . (int)$v['versao'])) ?>">Abrir</a></td>
          </tr>
        <?php endforeach; ?>
      </table></div>
    </details>
  <?php endforeach; ?>
<?php endif; ?>
<?php require RAIZ . '/includes/rodape.php'; ?>
