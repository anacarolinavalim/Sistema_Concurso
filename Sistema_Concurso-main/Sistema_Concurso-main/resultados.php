<?php
require_once __DIR__ . '/includes/bootstrap.php';
$classDao = new ClassificacaoDAO();
$def = Calendario::classificacaoVisivel('Definitiva') ? $classDao->ultima('Definitiva') : null;
$pre = Calendario::classificacaoVisivel('Preliminar') ? $classDao->ultima('Preliminar') : null;

$tipo = get('tipo');
if (!in_array($tipo, ['definitiva', 'preliminar', 'recursos'], true)) {
    $tipo = $def ? 'definitiva' : 'preliminar';
}
$classificacao = ($tipo === 'definitiva') ? $def : (($tipo === 'preliminar') ? $pre : null);

$cargos = [];
$cargoId = (int)get('cargo');
$itens = [];
if ($classificacao) {
    $cargos = $classDao->cargosDaClassificacao((int)$classificacao['id']);
    if ($cargoId === 0 && $cargos) {
        $cargoId = (int)$cargos[0]['id'];
    }
    $itens = $classDao->itens((int)$classificacao['id'], $cargoId);
}
$recursos = ($tipo === 'recursos' && $def) ? (new RecursoDAO())->listarPublico() : [];

$tituloPagina = 'Consultar Resultados';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Consultar Resultados</h1>
<p class="acoes">
  <a class="botao <?= $tipo === 'preliminar' ? '' : 'sec' ?>" href="?tipo=preliminar">Classificação Preliminar</a>
  <a class="botao <?= $tipo === 'recursos' ? '' : 'sec' ?>" href="?tipo=recursos">Resultado dos Recursos</a>
  <a class="botao <?= $tipo === 'definitiva' ? '' : 'sec' ?>" href="?tipo=definitiva">Classificação Definitiva</a>
</p>

<?php if ($tipo === 'recursos'): ?>
  <h2>Resultado dos Recursos</h2>
  <?php if (!$def): ?>
    <p class="alerta info">O resultado dos recursos será publicado em <?= e(dataBr(Calendario::cfg('definitiva_data'))) ?>.</p>
  <?php elseif (!$recursos): ?>
    <p>Nenhum recurso julgado até o momento.</p>
  <?php else: ?>
    <div class="tabela-rolagem"><table>
      <tr><th>Protocolo</th><th>Candidato</th><th>Cargo</th><th>Parecer</th><th>Data</th></tr>
      <?php foreach ($recursos as $r): ?>
        <tr><td><?= e($r['numero_protocolo']) ?></td><td><?= e($r['nome']) ?></td><td><?= e($r['titulo_cargo']) ?></td>
            <td><?= badgeStatus($r['parecer_deferimento']) ?></td><td><?= e(dataBr($r['data_parecer'])) ?></td></tr>
      <?php endforeach; ?>
    </table></div>
  <?php endif; ?>
<?php elseif (!$classificacao): ?>
  <h2>Classificação <?= $tipo === 'definitiva' ? 'Definitiva' : 'Preliminar' ?></h2>
  <p class="alerta info">Esta classificação ainda não foi publicada. Data programada:
    <?= e(dataBr(Calendario::cfg($tipo === 'definitiva' ? 'definitiva_data' : 'preliminar_data'))) ?>.</p>
<?php else: ?>
  <h2>Classificação <?= e($classificacao['tipo']) ?> <span class="ajuda">(gerada em <?= e(dataHoraBr($classificacao['data_geracao'])) ?>)</span></h2>
  <?php if (!$cargos): ?>
    <p>Nenhuma inscrição classificada.</p>
  <?php else: ?>
    <form method="get" class="acoes">
      <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
      <label for="cargo" style="margin:0">Cargo:</label>
      <select id="cargo" name="cargo" style="width:auto;max-width:100%" onchange="this.form.submit()">
        <?php foreach ($cargos as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $cargoId ? 'selected' : '' ?>><?= e($c['titulo_cargo']) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button type="submit">Ver</button></noscript>
      <a class="botao sec pequeno" href="<?= e(url('classificacao_pdf.php?id=' . (int)$classificacao['id'])) ?>">Baixar PDF (todos os cargos)</a>
    </form>
    <?php tabelaClassificacao($itens); ?>
    <p class="ajuda">Critérios de ordenação: habilitação/escolaridade mínima; maior tempo de serviço na Rede Municipal de Carmo de Minas no cargo; maior titulação; maior idade.</p>
  <?php endif; ?>
<?php endif; ?>
<?php require RAIZ . '/includes/rodape.php'; ?>
