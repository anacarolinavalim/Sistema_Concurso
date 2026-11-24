<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$leilaoDao = new LeilaoVagaDAO();
$id = (int)get('id');
$leilao = $leilaoDao->buscarPorId($id);
if ($leilao === null) {
    flash('erro', 'Leilão não encontrado.');
    redirecionar('admin/leiloes.php');
}

if (ehPost()) {
    $acao = post('acao');
    $erro = null;
    $ok = '';
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página.';
    } else {
        $inscId = (int)post('inscricao_id');
        switch ($acao) {
            case 'status':
                $erro = LeilaoService::alterarStatus($id, post('novo_status'));
                $ok = 'Situação do leilão atualizada.';
                break;
            case 'conferir':
                $itens = isset($_POST['itens']) && is_array($_POST['itens']) ? array_map('strval', $_POST['itens']) : [];
                $erro = $leilao->realizarCheckInDocumental($inscId, $itens, post('observacao'));
                $ok = 'Check-in documental registrado.';
                break;
            case 'desclassificar':
                $erro = LeilaoService::desclassificar($id, $inscId, post('motivo'));
                $ok = 'Candidato desclassificado.';
                break;
            case 'atribuir':
                $erro = ContratoLeilao::atribuirVaga($id, $inscId);
                $ok = 'Vaga atribuída com sucesso.';
                break;
            case 'desistencia':
                $contrato = (new ContratoLeilaoDAO())->buscarPorId((int)post('contrato_id'));
                if ($contrato === null || $contrato->getLeilaoId() !== $id) {
                    $erro = 'Contrato não encontrado neste leilão.';
                } else {
                    $erro = $contrato->registrarDesistencia(post('data_desistencia'), post('motivo'));
                    $ok = 'Desistência registrada. Suspensão de ' . DIAS_SUSPENSAO . ' dias aplicada para este cargo.';
                }
                break;
            case 'encerrar_contrato':
                $erro = LeilaoService::encerrarContrato((int)post('contrato_id'));
                $ok = 'Contrato encerrado.';
                break;
            default:
                $erro = 'Ação inválida.';
        }
    }
    if ($erro !== null) {
        flash('erro', $erro);
    } else {
        flash('sucesso', $ok);
    }
    redirecionar('admin/leilao_chamada.php?id=' . $id);
}

$cargo = (new CargoDAO())->buscarPorId($leilao->getCargoId());
$chamada = LeilaoService::montarChamada($leilao);
$contratos = (new ContratoLeilaoDAO())->listarPorLeilao($id);
$ocupadas = (new ContratoLeilaoDAO())->contarOcupadas($id);
$status = $leilao->getStatus();
$emAndamento = $status === LeilaoVaga::STATUS_EM_ANDAMENTO;
$tituloPagina = 'Chamada do leilão #' . $id;
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Leilão nº <?= $id ?> <?= badgeStatus($status) ?></h1>
<p><a href="<?= e(url('admin/leiloes.php')) ?>">&larr; Voltar aos leilões</a></p>
<div class="caixa">
  <p><strong>Cargo:</strong> <?= e($cargo ? $cargo->getTitulo() : '-') ?><br>
     <strong>Data:</strong> <?= e(dataBr($leilao->getDataLeilao())) ?> – ano letivo <?= $leilao->getAnoLetivo() ?><br>
     <strong>Escolaridade:</strong> <?= e(Titulacao::rotulo($leilao->getEscolaridade())) ?> – <strong>Horário:</strong> <?= e($leilao->getHorario()) ?><br>
     <strong>Vagas:</strong> <?= $ocupadas ?> preenchidas de <?= $leilao->getVagasOfertadas() ?></p>
  <div class="acoes">
    <?php foreach ([
        LeilaoVaga::STATUS_AGENDADO => 'Voltar para Agendado',
        LeilaoVaga::STATUS_EM_ANDAMENTO => ($status === LeilaoVaga::STATUS_ENCERRADO ? 'Reabrir chamada' : 'Iniciar chamada'),
        LeilaoVaga::STATUS_ENCERRADO => 'Encerrar leilão',
    ] as $novo => $rotulo): ?>
      <?php if ($novo !== $status && !($novo === LeilaoVaga::STATUS_AGENDADO && $status !== LeilaoVaga::STATUS_EM_ANDAMENTO)): ?>
        <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="status"><input type="hidden" name="novo_status" value="<?= e($novo) ?>">
          <button type="submit" class="<?= $novo === LeilaoVaga::STATUS_EM_ANDAMENTO ? '' : 'sec' ?>"><?= e($rotulo) ?></button></form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>

<h2>Lista de chamada</h2>
<p class="ajuda">Fonte: <?= e($chamada['fonte']) ?>. <?= $emAndamento ? '' : 'Inicie a chamada para habilitar o check-in e a atribuição de vagas.' ?></p>
<?php if (!$chamada['linhas']): ?>
  <p>Nenhum candidato classificado para este cargo.</p>
<?php else: ?>
<div class="tabela-rolagem"><table>
  <tr><th>Pos.</th><th>Candidato</th><th>Hab.</th><th>Tempo</th><th>Titulação</th><th>Situação</th><th>Ação</th></tr>
  <?php foreach ($chamada['linhas'] as $r):
      $iid = (int)$r['inscricao_id'];
      $desclass = ($r['status'] === Inscricao::STATUS_DESCLASSIFICADA) || ($r['conferencia'] === ConferenciaLeilao::DESCLASSIFICADO);
      $contratado = $r['contratado'] !== null;
      $suspenso = $r['suspenso_ate'] !== null;
      $conferido = $r['conferencia'] === ConferenciaLeilao::CONFERIDO;
      $podeAgir = $emAndamento && !$desclass && !$contratado && !$suspenso && $r['status'] === Inscricao::STATUS_ATIVA;
      $exigidos = array_keys(DocumentoInscricao::TIPOS);
      if ((int)$r['tempo_servico_dias'] === 0) {
          $exigidos = array_values(array_diff($exigidos, ['contagem_tempo']));
      }
  ?>
    <tr id="insc-<?= $iid ?>">
      <td><?= (int)$r['posicao'] ?>º</td>
      <td><?= e($r['nome']) ?><br><span class="ajuda"><?= e(mascararCpf($r['cpf'])) ?></span></td>
      <td><?= (int)$r['habilitado'] ? 'Sim' : 'Não' ?></td>
      <td><?= e(intdiv((int)$r['tempo_servico_dias'], 365) . 'a ' . ((int)$r['tempo_servico_dias'] % 365) . 'd') ?></td>
      <td><?= e(Titulacao::rotulo($r['nivel_titulacao'])) ?></td>
      <td>
        <?php if ($contratado): ?><?= badge('Contratado neste leilão', 'ok') ?><br><?php endif; ?>
        <?php if ($desclass): ?><?= badge('Desclassificado', 'erro') ?><br><?php endif; ?>
        <?php if ($suspenso): ?><?= badge('Suspenso até ' . dataBr($r['suspenso_ate']), 'erro') ?><br><?php endif; ?>
        <?php if ($conferido && !$contratado): ?><?= badge('Documentos conferidos', 'ok') ?><br><?php endif; ?>
        <?php if ($r['outro_contrato'] !== null): ?><span class="ajuda">Contrato ativo em: <?= e($r['outro_contrato']) ?> (será registrada desistência declarada, sem suspensão)</span><?php endif; ?>
        <?php if ($r['conferencia_obs'] !== '' && $desclass): ?><br><span class="ajuda">Motivo: <?= e($r['conferencia_obs']) ?></span><?php endif; ?>
      </td>
      <td>
        <?php if ($podeAgir): ?>
          <?php if ($conferido): ?>
            <form method="post" onsubmit="return confirm('Atribuir a vaga a este candidato?');"><?= csrfCampo() ?>
              <input type="hidden" name="acao" value="atribuir"><input type="hidden" name="inscricao_id" value="<?= $iid ?>">
              <button type="submit">Atribuir vaga</button></form>
          <?php endif; ?>
          <details><summary><?= $conferido ? 'Refazer check-in' : 'Check-in documental' ?></summary>
            <form method="post"><?= csrfCampo() ?>
              <input type="hidden" name="acao" value="conferir"><input type="hidden" name="inscricao_id" value="<?= $iid ?>">
              <p class="ajuda">Confirme originais e cópias (frente e verso):</p>
              <?php foreach ($exigidos as $tipo): ?>
                <label class="check"><input type="checkbox" name="itens[]" value="<?= e($tipo) ?>"> <?= e(DocumentoInscricao::TIPOS[$tipo]) ?></label>
              <?php endforeach; ?>
              <label for="obs-<?= $iid ?>">Observação</label><input type="text" id="obs-<?= $iid ?>" name="observacao" maxlength="250">
              <p><button type="submit">Registrar check-in</button></p>
            </form>
          </details>
          <details><summary>Desclassificar</summary>
            <form method="post" onsubmit="return confirm('Desclassificar este candidato? A inscrição ficará como Desclassificada.');"><?= csrfCampo() ?>
              <input type="hidden" name="acao" value="desclassificar"><input type="hidden" name="inscricao_id" value="<?= $iid ?>">
              <label for="mot-<?= $iid ?>">Motivo (omissão de dados, documentação incompleta ou divergência)</label>
              <input type="text" id="mot-<?= $iid ?>" name="motivo" maxlength="250" required>
              <p><button type="submit" class="perigo">Desclassificar</button></p>
            </form>
          </details>
        <?php elseif (!$emAndamento && !$desclass && !$contratado && !$suspenso): ?>
          <span class="ajuda">Chamada não iniciada</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>

<h2>Contratos deste leilão</h2>
<?php if (!$contratos): ?><p>Nenhuma vaga atribuída ainda.</p><?php else: ?>
<div class="tabela-rolagem"><table>
  <tr><th>Candidato</th><th>Contratado em</th><th>Situação</th><th>Detalhes</th><th>Ações</th></tr>
  <?php foreach ($contratos as $c): ?>
    <tr>
      <td><?= e($c['nome']) ?><br><span class="ajuda"><?= e(mascararCpf($c['cpf'])) ?></span></td>
      <td><?= e(dataHoraBr($c['data_contratacao'])) ?></td>
      <td><?= badgeStatus($c['status']) ?></td>
      <td><?php if ($c['status'] === 'Desistido'): ?>
            <?= e($c['tipo_encerramento'] === 'Declarada' ? 'Desistência declarada (troca de vaga)' : 'Abandono/desistência') ?> em <?= e(dataBr($c['data_desistencia'])) ?><br>
            <span class="ajuda"><?= e((string)$c['observacao']) ?></span>
          <?php else: ?><?= e((string)$c['observacao']) ?><?php endif; ?></td>
      <td>
        <?php if ($c['status'] === 'Ativo'): ?>
          <details><summary>Registrar desistência/abandono</summary>
            <form method="post" onsubmit="return confirm('Registrar desistência? O candidato será suspenso por <?= (int)DIAS_SUSPENSAO ?> dias neste cargo.');"><?= csrfCampo() ?>
              <input type="hidden" name="acao" value="desistencia"><input type="hidden" name="contrato_id" value="<?= (int)$c['id'] ?>">
              <label for="dd-<?= (int)$c['id'] ?>">Data formal da desistência</label>
              <input type="date" id="dd-<?= (int)$c['id'] ?>" name="data_desistencia" value="<?= e(Calendario::hoje()) ?>" max="<?= e(Calendario::hoje()) ?>" required>
              <label for="dm-<?= (int)$c['id'] ?>">Motivo</label>
              <input type="text" id="dm-<?= (int)$c['id'] ?>" name="motivo" maxlength="250" required>
              <p><button type="submit" class="perigo">Registrar e suspender</button></p>
            </form>
          </details>
          <form method="post" onsubmit="return confirm('Encerrar o contrato normalmente?');"><?= csrfCampo() ?>
            <input type="hidden" name="acao" value="encerrar_contrato"><input type="hidden" name="contrato_id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="sec pequeno">Encerrar contrato</button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
