<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();

$id = (int)get('id');
$insc = null;
$existentes = [];
if ($id > 0) {
    $insc = (new InscricaoDAO())->buscarPorId($id);
    if ($insc === null || $insc->getCandidatoId() !== $cand->getID() || !$insc->estaAtiva()) {
        flash('erro', 'Inscrição não encontrada ou não pode ser alterada.');
        redirecionar('candidato/painel.php');
    }
    foreach ((new DocumentoInscricaoDAO())->listarPorInscricao($id) as $d) {
        $existentes[$d->getTipoDocumento()] = $d;
    }
}
if (!Calendario::inscricoesAbertas()) {
    flash('erro', 'Fora do prazo de inscrição.');
    redirecionar('candidato/painel.php');
}

$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $res = InscricaoService::salvar($cand, $insc ? $insc->getID() : null, $_POST, $_FILES);
        if (!$res['erros']) {
            flash('sucesso', 'Inscrição salva com sucesso! Um novo comprovante foi gerado.');
            redirecionar('candidato/comprovante.php?id=' . (int)$res['id']);
        }
        $erros = $res['erros'];
    }
}

// Valores do formulário (POST em caso de erro; senão os da inscrição)
$cargoSel = ehPost() ? (int)post('cargo_id') : ($insc ? $insc->getCargoId() : 0);
$nivelSel = ehPost() ? post('nivel_titulacao') : ($insc ? $insc->getNivelTitulacao() : '');
$tempoAnos = ehPost() ? post('tempo_anos', '0') : ($insc ? (string)intdiv($insc->getTempoServicoDias(), 365) : '0');
$tempoDias = ehPost() ? post('tempo_dias', '0') : ($insc ? (string)($insc->getTempoServicoDias() % 365) : '0');

$cargos = Cargo::listarCargos(true);
$mapaCargos = [];
foreach ($cargos as $c) {
    $mapaCargos[$c->getID()] = $c->getTitulacoesPermitidas();
}

$tituloPagina = $insc ? 'Editar inscrição' : 'Nova inscrição';
require RAIZ . '/includes/cabecalho.php';
?>
<h1><?= e($tituloPagina) ?></h1>
<p>Prazo até <?= e(dataHoraBr(Calendario::inscricaoFim()->format('Y-m-d H:i:s'))) ?>. Você pode alterar a inscrição quantas vezes quiser até o fim do prazo.</p>
<?php if ($erros): ?>
  <div class="alerta erro" role="alert"><strong>Corrija os itens abaixo:</strong><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" enctype="multipart/form-data" class="caixa">
  <?= csrfCampo() ?>
  <h2 style="margin-top:0">Cargo e pontuação</h2>
  <label for="cargo_id">Cargo pretendido</label>
  <select id="cargo_id" name="cargo_id" required>
    <option value="">Selecione...</option>
    <?php foreach ($cargos as $c): ?>
      <option value="<?= $c->getID() ?>" <?= $c->getID() === $cargoSel ? 'selected' : '' ?>><?= e($c->getTitulo()) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($insc && Recurso::impedirTrocaCargo($insc->getID())): ?>
    <p class="ajuda">Esta inscrição possui recurso protocolado: o cargo não pode ser trocado.</p>
  <?php endif; ?>

  <label>Tempo de serviço público na Rede Municipal de Carmo de Minas, no cargo pretendido (até 31/12/2026)</label>
  <div class="linha-campos">
    <div><label for="tempo_anos" class="ajuda">Anos completos</label>
      <input type="number" id="tempo_anos" name="tempo_anos" min="0" max="60" value="<?= e($tempoAnos) ?>"></div>
    <div><label for="tempo_dias" class="ajuda">Dias adicionais (0 a 364)</label>
      <input type="number" id="tempo_dias" name="tempo_dias" min="0" max="364" value="<?= e($tempoDias) ?>"></div>
  </div>
  <p class="ajuda">Cada ano é contado como 365 dias. Informe 0 e 0 se não possui tempo de serviço.</p>

  <label for="nivel_titulacao">Titulação</label>
  <select id="nivel_titulacao" name="nivel_titulacao" required>
    <option value="">Selecione...</option>
    <?php foreach (Titulacao::todos() as $cod => $rot): ?>
      <option value="<?= e($cod) ?>" <?= $cod === $nivelSel ? 'selected' : '' ?>><?= e($rot) ?></option>
    <?php endforeach; ?>
  </select>
  <p class="ajuda" id="ajuda-titulacao">Apenas as titulações aceitas para o cargo escolhido são válidas.</p>

  <h2>Documentos (PDF, JPG ou PNG – até <?= (int)(UPLOAD_MAX_BYTES / 1048576) ?> MB cada)</h2>
  <?php foreach (DocumentoInscricao::TIPOS as $tipo => $rotulo): ?>
    <label for="doc_<?= e($tipo) ?>"><?= e($rotulo) ?><?= $tipo === 'contagem_tempo' ? ' <span class="ajuda">(obrigatório se informou tempo de serviço)</span>' : '' ?></label>
    <input type="file" id="doc_<?= e($tipo) ?>" name="doc_<?= e($tipo) ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png">
    <?php if (isset($existentes[$tipo])): ?>
      <span class="ajuda">Arquivo atual: <a href="<?= e(url('candidato/arquivo.php?id=' . $existentes[$tipo]->getID())) ?>" target="_blank" rel="noopener"><?= e($existentes[$tipo]->getNomeOriginal()) ?></a> – envie outro para substituir.</span>
    <?php endif; ?>
  <?php endforeach; ?>

  <p style="margin-top:20px"><button type="submit">Salvar inscrição</button>
     <a class="botao sec" href="<?= e(url('candidato/painel.php')) ?>">Voltar</a></p>
</form>
<script>
(function () {
  var mapa = <?= json_encode($mapaCargos) ?>;
  var cargo = document.getElementById('cargo_id');
  var nivel = document.getElementById('nivel_titulacao');
  function filtrar() {
    var permitidas = mapa[cargo.value] || null;
    for (var i = 0; i < nivel.options.length; i++) {
      var o = nivel.options[i];
      if (o.value === '') { continue; }
      o.disabled = permitidas !== null && permitidas.indexOf(o.value) === -1;
      if (o.disabled && o.selected) { nivel.value = ''; }
    }
  }
  cargo.addEventListener('change', filtrar);
  filtrar();
})();
</script>
<?php require RAIZ . '/includes/rodape.php'; ?>
