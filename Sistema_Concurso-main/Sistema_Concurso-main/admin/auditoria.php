<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$acao = get('acao');
$usuario = get('usuario');
$entidade = get('entidade');
$lista = (new LogDAO())->listar($acao, $usuario, $entidade);
$tituloPagina = 'Auditoria';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Logs de auditoria</h1>
<form method="get" class="caixa">
  <div class="linha-campos">
    <div><label for="acao">Ação contém</label><input type="text" id="acao" name="acao" value="<?= e($acao) ?>"></div>
    <div><label for="usuario">Usuário contém</label><input type="text" id="usuario" name="usuario" value="<?= e($usuario) ?>"></div>
    <div><label for="entidade">Entidade</label><input type="text" id="entidade" name="entidade" value="<?= e($entidade) ?>" placeholder="inscricao, recurso..."></div>
  </div>
  <p><button type="submit">Filtrar</button></p>
</form>
<p class="ajuda">Últimos <?= count($lista) ?> registros (máx. 300).</p>
<div class="tabela-rolagem"><table>
  <tr><th>Data/hora</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>Detalhes</th><th>IP</th></tr>
  <?php foreach ($lista as $l): ?>
    <tr><td><?= e(dataHoraBr($l['data_hora'])) ?></td><td><?= e($l['usuario_nome']) ?> <span class="ajuda">(<?= e($l['usuario_tipo']) ?>)</span></td>
      <td><?= e($l['acao']) ?></td><td><?= e($l['entidade']) ?><?= $l['entidade_id'] !== null ? ' #' . (int)$l['entidade_id'] : '' ?></td>
      <td><?= e((string)$l['detalhes']) ?></td><td><?= e($l['ip']) ?></td></tr>
  <?php endforeach; ?>
</table></div>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
