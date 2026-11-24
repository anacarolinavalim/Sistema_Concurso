<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$cargoId = (int)get('cargo');
$status = get('status');
if (!in_array($status, ['Ativa', 'Cancelada', 'Desclassificada'], true)) {
    $status = '';
}
$busca = get('q');
$lista = (new InscricaoDAO())->listarParaAdmin($cargoId, $status, $busca);
$cargos = Cargo::listarCargos(false);
$opcCargos = [0 => 'Todos os cargos'];
foreach ($cargos as $c) {
    $opcCargos[$c->getID()] = $c->getTitulo();
}
$tituloPagina = 'Inscrições';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Inscrições</h1>
<form method="get" class="caixa">
  <div class="linha-campos">
    <div><label for="q">Nome ou CPF</label><input type="text" id="q" name="q" value="<?= e($busca) ?>"></div>
    <div><label for="cargo">Cargo</label><select id="cargo" name="cargo"><?= opcoesSelect($opcCargos, $cargoId) ?></select></div>
    <div><label for="status">Situação</label>
      <select id="status" name="status"><?= opcoesSelect(['' => 'Todas', 'Ativa' => 'Ativa', 'Cancelada' => 'Cancelada', 'Desclassificada' => 'Desclassificada'], $status) ?></select></div>
  </div>
  <p><button type="submit">Filtrar</button></p>
</form>
<p class="ajuda"><?= count($lista) ?> resultado(s) (máx. 300).</p>
<div class="tabela-rolagem"><table>
  <tr><th>Nº</th><th>Candidato</th><th>CPF</th><th>Cargo</th><th>Tempo</th><th>Titulação</th><th>Situação</th><th>Versão</th><th></th></tr>
  <?php foreach ($lista as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td><td><?= e($r['nome']) ?></td><td><?= e(formatarCpf($r['cpf'])) ?></td>
      <td><?= e($r['titulo_cargo']) ?></td><td><?= e(intdiv((int)$r['tempo_servico_dias'], 365) . 'a ' . ((int)$r['tempo_servico_dias'] % 365) . 'd') ?></td>
      <td><?= e(Titulacao::rotulo($r['nivel_titulacao'])) ?></td><td><?= badgeStatus($r['status']) ?></td><td><?= (int)$r['versao_atual'] ?></td>
      <td><a href="<?= e(url('admin/inscricao_ver.php?id=' . (int)$r['id'])) ?>">Detalhes</a></td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
