<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
if (ehPost()) {
    if (csrfValido() && post('acao') === 'alternar') {
        $id = (int)post('id');
        (new CargoDAO())->alternarAtivo($id);
        Auditoria::registrar('CARGO_ATIVO_ALTERNADO', 'cargo', $id, '');
        flash('sucesso', 'Situação do cargo atualizada.');
    }
    redirecionar('admin/cargos.php');
}
$cargos = Cargo::listarCargos(false);
$tituloPagina = 'Cargos';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Cargos (Anexo I)</h1>
<p><a class="botao" href="<?= e(url('admin/cargo_form.php')) ?>">Novo cargo</a></p>
<div class="tabela-rolagem"><table>
  <tr><th>Cargo</th><th>Escolaridade mínima</th><th>Titulações aceitas</th><th>Situação</th><th>Ações</th></tr>
  <?php foreach ($cargos as $c): ?>
    <tr>
      <td><?= e($c->getTitulo()) ?></td>
      <td><?= e(Titulacao::rotulo($c->getEscolaridade())) ?></td>
      <td><?= e(implode(', ', array_map('Titulacao::rotulo', $c->getTitulacoesPermitidas()))) ?></td>
      <td><?= $c->getAtivo() ? badge('Ativo', 'ok') : badge('Inativo') ?></td>
      <td class="acoes">
        <a class="botao sec pequeno" href="<?= e(url('admin/cargo_form.php?id=' . $c->getID())) ?>">Editar</a>
        <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="alternar"><input type="hidden" name="id" value="<?= $c->getID() ?>">
          <button type="submit" class="sec pequeno"><?= $c->getAtivo() ? 'Desativar' : 'Ativar' ?></button></form>
      </td>
    </tr>
  <?php endforeach; ?>
</table></div>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
