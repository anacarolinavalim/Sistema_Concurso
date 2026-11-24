<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = Sessao::exigirAdmin();
$classDao = new ClassificacaoDAO();

if (ehPost()) {
    $tipo = post('tipo');
    if (!csrfValido() || !in_array($tipo, ['Preliminar', 'Definitiva'], true)) {
        flash('erro', 'Requisição inválida.');
    } elseif (!Calendario::inscricoesEncerradas()) {
        flash('erro', 'A classificação só pode ser gerada após o encerramento das inscrições.');
    } elseif ($tipo === 'Definitiva' && !Calendario::recursosEncerrados()) {
        flash('erro', 'A classificação definitiva só pode ser gerada após o fim do prazo de recursos.');
    } elseif ($tipo === 'Definitiva' && $classDao->ultima('Preliminar') === null) {
        flash('erro', 'Gere antes a classificação preliminar.');
    } else {
        $id = (new MotorClassificacao())->gerarSnapshot($tipo, $admin->getID());
        flash('sucesso', 'Classificação ' . $tipo . ' gerada. Ela ficará visível ao público em ' .
            dataBr(Calendario::cfg($tipo === 'Definitiva' ? 'definitiva_data' : 'preliminar_data')) . '.');
        redirecionar('admin/classificacao.php?id=' . $id);
    }
    redirecionar('admin/classificacao.php');
}

$lista = $classDao->listar();
$sel = (int)get('id');
$classSel = $sel > 0 ? $classDao->buscarPorId($sel) : null;
$cargosSel = [];
$cargoId = (int)get('cargo');
$itens = [];
if ($classSel) {
    $cargosSel = $classDao->cargosDaClassificacao($sel);
    if ($cargoId === 0 && $cargosSel) {
        $cargoId = (int)$cargosSel[0]['id'];
    }
    $itens = $classDao->itens($sel, $cargoId);
}
$tituloPagina = 'Classificação';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Classificação</h1>
<p>Critérios (em ordem): habilitação/escolaridade mínima do cargo → maior tempo de serviço na Rede Municipal → maior titulação → maior idade.
   Cada geração cria uma "foto" da classificação; o público vê a mais recente de cada tipo a partir da data programada
   (preliminar: <?= e(dataBr(Calendario::cfg('preliminar_data'))) ?>; definitiva: <?= e(dataBr(Calendario::cfg('definitiva_data'))) ?>).</p>
<div class="caixa acoes">
  <form method="post"><?= csrfCampo() ?><input type="hidden" name="tipo" value="Preliminar"><button type="submit">Gerar classificação PRELIMINAR</button></form>
  <form method="post" onsubmit="return confirm('Gerar a classificação DEFINITIVA com os dados atuais (após recursos)?');"><?= csrfCampo() ?><input type="hidden" name="tipo" value="Definitiva"><button type="submit">Gerar classificação DEFINITIVA</button></form>
</div>

<h2>Classificações geradas</h2>
<?php if (!$lista): ?><p>Nenhuma classificação gerada.</p><?php else: ?>
<div class="tabela-rolagem"><table>
  <tr><th>Nº</th><th>Tipo</th><th>Gerada em</th><th>Itens</th><th></th></tr>
  <?php foreach ($lista as $c): ?>
    <tr><td><?= (int)$c['id'] ?></td><td><?= e($c['tipo']) ?></td><td><?= e(dataHoraBr($c['data_geracao'])) ?></td><td><?= (int)$c['total'] ?></td>
      <td class="acoes"><a href="<?= e(url('admin/classificacao.php?id=' . (int)$c['id'])) ?>">Ver</a>
        <a href="<?= e(url('classificacao_pdf.php?id=' . (int)$c['id'])) ?>">PDF</a></td></tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>

<?php if ($classSel): ?>
  <h2>Classificação <?= e($classSel['tipo']) ?> nº <?= (int)$classSel['id'] ?></h2>
  <?php if (!$cargosSel): ?><p>Sem itens.</p><?php else: ?>
    <form method="get" class="acoes">
      <input type="hidden" name="id" value="<?= (int)$classSel['id'] ?>">
      <label for="cargo" style="margin:0">Cargo:</label>
      <select id="cargo" name="cargo" style="width:auto;max-width:100%" onchange="this.form.submit()">
        <?php foreach ($cargosSel as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $cargoId ? 'selected' : '' ?>><?= e($c['titulo_cargo']) ?></option><?php endforeach; ?>
      </select>
      <noscript><button type="submit">Ver</button></noscript>
    </form>
    <?php tabelaClassificacao($itens); ?>
  <?php endif; ?>
<?php endif; ?>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
