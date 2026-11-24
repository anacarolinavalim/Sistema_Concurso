<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$cpfBusca = somenteDigitos(get('cpf'));
$encontradas = [];
if (get('cpf') !== '') {
    $encontradas = (new InscricaoDAO())->listarAtivasPorCpf($cpfBusca);
}
$lista = (new RecursoDAO())->listar();
$janela = Calendario::janelaRecursoAberta();
$tituloPagina = 'Recursos';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Recursos</h1>
<div class="alerta <?= $janela ? 'sucesso' : 'aviso' ?>">
  Prazo de protocolo: <?= e(dataBr(Calendario::cfg('recurso_data_inicio'))) ?> e <?= e(dataBr(Calendario::cfg('recurso_data_fim'))) ?>,
  das <?= e(Calendario::cfg('recurso_hora_inicio')) ?> às <?= e(Calendario::cfg('recurso_hora_fim')) ?>.
  <?= $janela ? 'O protocolo está ABERTO agora.' : 'Fora do prazo agora: novos protocolos estão bloqueados.' ?>
</div>

<h2>Registrar novo protocolo</h2>
<form method="get" class="caixa acoes">
  <label for="cpf" style="margin:0">CPF do candidato:</label>
  <input type="text" id="cpf" name="cpf" value="<?= e(get('cpf')) ?>" style="width:220px" inputmode="numeric" required>
  <button type="submit">Buscar inscrições</button>
</form>
<?php if (get('cpf') !== ''): ?>
  <?php if (!$encontradas): ?>
    <p>Nenhuma inscrição ativa encontrada para este CPF.</p>
  <?php else: ?>
    <div class="tabela-rolagem"><table>
      <tr><th>Candidato</th><th>Cargo</th><th></th></tr>
      <?php foreach ($encontradas as $i): ?>
        <tr><td><?= e($i['nome']) ?></td><td><?= e($i['titulo_cargo']) ?></td>
          <td><a class="botao sec pequeno" href="<?= e(url('admin/recurso_novo.php?inscricao_id=' . (int)$i['id'])) ?>">Registrar recurso</a></td></tr>
      <?php endforeach; ?>
    </table></div>
  <?php endif; ?>
<?php endif; ?>

<h2>Recursos protocolados</h2>
<?php if (!$lista): ?><p>Nenhum recurso protocolado.</p><?php else: ?>
<div class="tabela-rolagem"><table>
  <tr><th>Protocolo</th><th>Candidato</th><th>Cargo</th><th>Data</th><th>Parecer</th><th></th></tr>
  <?php foreach ($lista as $r): ?>
    <tr><td><?= e($r['numero_protocolo']) ?></td><td><?= e($r['nome']) ?></td><td><?= e($r['titulo_cargo']) ?></td>
      <td><?= e(dataHoraBr($r['data_protocolo'])) ?></td><td><?= badgeStatus($r['parecer_deferimento']) ?></td>
      <td><a href="<?= e(url('admin/recurso_ver.php?id=' . (int)$r['id'])) ?>">Abrir</a></td></tr>
  <?php endforeach; ?>
</table></div>
<?php endif; ?>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
