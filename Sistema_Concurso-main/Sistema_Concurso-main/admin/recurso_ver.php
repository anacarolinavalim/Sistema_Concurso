<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$recDao = new RecursoDAO();
$id = (int)get('id');
$recurso = $recDao->buscarPorId($id);
if ($recurso === null) {
    flash('erro', 'Recurso não encontrado.');
    redirecionar('admin/recursos.php');
}
$inscId = $recurso->getInscricaoId();
$erros = [];

if (ehPost()) {
    $acao = post('acao');
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } elseif ($acao === 'parecer') {
        $e1 = RecursoService::emitirParecer($id, post('parecer'), post('justificativa'));
        if ($e1 === null) {
            flash('sucesso', 'Parecer registrado.');
            redirecionar('admin/recurso_ver.php?id=' . $id);
        }
        $erros[] = $e1;
    } elseif ($acao === 'ajuste') {
        $erros = InscricaoService::ajusteAdmin($inscId, $_POST);
        if (!$erros) {
            flash('sucesso', 'Dados ajustados. Uma nova versão da inscrição foi registrada.');
            redirecionar('admin/recurso_ver.php?id=' . $id);
        }
    }
    $recurso = $recDao->buscarPorId($id);
}

$d = (new InscricaoDAO())->buscarDetalhe($inscId);
$cargo = (new CargoDAO())->buscarPorId((int)$d['cargo_id']);
$postAjuste = ehPost() && post('acao') === 'ajuste';
$val = function (string $campo, string $atual) use ($postAjuste): string {
    return e($postAjuste ? post($campo) : $atual);
};
$anos = $postAjuste ? post('tempo_anos', '0') : (string)intdiv((int)$d['tempo_servico_dias'], 365);
$diasAd = $postAjuste ? post('tempo_dias', '0') : (string)((int)$d['tempo_servico_dias'] % 365);
$nivelSel = $postAjuste ? post('nivel_titulacao') : $d['nivel_titulacao'];
$opcNivel = [];
foreach (($cargo ? $cargo->getTitulacoesPermitidas() : []) as $cod) {
    $opcNivel[$cod] = Titulacao::rotulo($cod);
}
$tituloPagina = 'Recurso ' . $recurso->getNumeroProtocolo();
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Recurso <?= e($recurso->getNumeroProtocolo()) ?> <?= badgeStatus($recurso->getParecerDeferimento()) ?></h1>
<p><a href="<?= e(url('admin/recursos.php')) ?>">&larr; Voltar</a> |
   <a href="<?= e(url('admin/inscricao_ver.php?id=' . $inscId)) ?>">Ver inscrição nº <?= $inscId ?></a></p>
<?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="caixa">
  <p><strong>Candidato:</strong> <?= e($d['nome']) ?> (CPF <?= e(formatarCpf($d['cpf'])) ?>)<br>
     <strong>Cargo (bloqueado para troca):</strong> <?= e($d['titulo_cargo']) ?><br>
     <strong>Protocolado em:</strong> <?= e(dataHoraBr($recurso->getDataProtocolo())) ?></p>
  <p><strong>Descrição:</strong><br><?= nl2br(e($recurso->getDescricao())) ?></p>
  <?php if ($recurso->getParecerDeferimento() !== Recurso::PENDENTE): ?>
    <p><strong>Parecer:</strong> <?= e($recurso->getParecerDeferimento()) ?> em <?= e(dataHoraBr($recurso->getDataParecer())) ?><br>
       <?= nl2br(e($recurso->getParecerJustificativa())) ?></p>
  <?php endif; ?>
</div>

<h2>Parecer</h2>
<form method="post" class="caixa">
  <?= csrfCampo() ?><input type="hidden" name="acao" value="parecer">
  <label for="parecer">Decisão</label>
  <select id="parecer" name="parecer"><?= opcoesSelect([Recurso::DEFERIDO => 'Deferido', Recurso::INDEFERIDO => 'Indeferido'], $recurso->getParecerDeferimento()) ?></select>
  <label for="justificativa">Justificativa</label>
  <textarea id="justificativa" name="justificativa" required><?= e($postAjuste ? '' : ($recurso->getParecerJustificativa() !== '' ? $recurso->getParecerJustificativa() : post('justificativa'))) ?></textarea>
  <p><button type="submit">Salvar parecer</button></p>
</form>

<h2>Ajuste operacional de dados</h2>
<?php if ($recurso->getParecerDeferimento() !== Recurso::DEFERIDO): ?>
  <p class="alerta info">O ajuste de dados só fica disponível quando o recurso é <strong>deferido</strong>.</p>
<?php else: ?>
  <p class="ajuda">Atualize apenas o que o recurso comprovou. O cargo não pode ser alterado. Toda mudança fica registrada na auditoria (quem, quando, IP, valor antigo e novo).</p>
  <form method="post" class="caixa">
    <?= csrfCampo() ?><input type="hidden" name="acao" value="ajuste">
    <label for="nome">Nome completo</label><input type="text" id="nome" name="nome" value="<?= $val('nome', $d['nome']) ?>" required>
    <div class="linha-campos">
      <div><label for="data_nascimento">Data de nascimento</label><input type="date" id="data_nascimento" name="data_nascimento" value="<?= $val('data_nascimento', $d['data_nascimento']) ?>" required></div>
      <div><label for="telefone">Telefone</label><input type="tel" id="telefone" name="telefone" value="<?= $val('telefone', $d['telefone']) ?>" required></div>
      <div><label for="email">E-mail</label><input type="email" id="email" name="email" value="<?= $val('email', $d['email']) ?>" required></div>
    </div>
    <label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco" value="<?= $val('endereco', $d['endereco']) ?>" required>
    <div class="linha-campos">
      <div><label for="rg">RG/CIN</label><input type="text" id="rg" name="rg" value="<?= $val('rg', $d['rg']) ?>" required></div>
      <div><label for="titulo_eleitor">Título de eleitor</label><input type="text" id="titulo_eleitor" name="titulo_eleitor" value="<?= $val('titulo_eleitor', $d['titulo_eleitor']) ?>" required></div>
    </div>
    <div class="linha-campos">
      <div><label for="tempo_anos">Tempo de serviço – anos</label><input type="number" id="tempo_anos" name="tempo_anos" min="0" max="60" value="<?= e($anos) ?>"></div>
      <div><label for="tempo_dias">Dias adicionais (0–364)</label><input type="number" id="tempo_dias" name="tempo_dias" min="0" max="364" value="<?= e($diasAd) ?>"></div>
      <div><label for="nivel_titulacao">Titulação</label><select id="nivel_titulacao" name="nivel_titulacao"><?= opcoesSelect($opcNivel, $nivelSel) ?></select></div>
    </div>
    <p><button type="submit">Salvar ajuste</button></p>
  </form>
<?php endif; ?>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
