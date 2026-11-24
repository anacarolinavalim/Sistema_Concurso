<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$admin = Sessao::exigirAdmin();
$erros = [];
if (ehPost()) {
    $acao = post('acao');
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } elseif ($acao === 'novo') {
        $a = new Admin();
        $a->setNome(post('nome'));
        $a->setEmail(strtolower(post('email')));
        $erros = $a->cadastrar((string)($_POST['senha'] ?? ''));
        if (!$erros) {
            Auditoria::registrar('ADMIN_CRIADO', 'administrador', $a->getID(), $a->getEmail());
            flash('sucesso', 'Administrador cadastrado.');
            redirecionar('admin/admins.php');
        }
    } elseif ($acao === 'senha') {
        if ((string)($_POST['nova'] ?? '') !== (string)($_POST['nova2'] ?? '')) {
            $erros[] = 'A confirmação da nova senha não confere.';
        } elseif (($m = Validador::senha((string)($_POST['nova'] ?? ''))) !== null) {
            $erros[] = $m;
        } elseif (!$admin->alterarSenha((string)($_POST['atual'] ?? ''), (string)$_POST['nova'])) {
            $erros[] = 'Senha atual incorreta.';
        } else {
            flash('sucesso', 'Sua senha foi alterada.');
            redirecionar('admin/admins.php');
        }
    }
}
$lista = (new AdminDAO())->listar();
$tituloPagina = 'Administradores';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Administradores</h1>
<?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="tabela-rolagem"><table>
  <tr><th>Nome</th><th>E-mail</th><th>Criado em</th></tr>
  <?php foreach ($lista as $a): ?>
    <tr><td><?= e($a['nome']) ?></td><td><?= e($a['email']) ?></td><td><?= e(dataHoraBr($a['data_criacao'])) ?></td></tr>
  <?php endforeach; ?>
</table></div>

<h2>Novo administrador</h2>
<form method="post" class="caixa" style="max-width:520px">
  <?= csrfCampo() ?><input type="hidden" name="acao" value="novo">
  <label for="nome">Nome</label><input type="text" id="nome" name="nome" value="<?= e(post('acao') === 'novo' ? post('nome') : '') ?>" required>
  <label for="email">E-mail</label><input type="email" id="email" name="email" value="<?= e(post('acao') === 'novo' ? post('email') : '') ?>" required>
  <label for="senha">Senha (mín. 8, letras e números)</label><input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password">
  <p><button type="submit">Cadastrar</button></p>
</form>

<h2>Alterar minha senha</h2>
<form method="post" class="caixa" style="max-width:520px">
  <?= csrfCampo() ?><input type="hidden" name="acao" value="senha">
  <label for="atual">Senha atual</label><input type="password" id="atual" name="atual" required autocomplete="current-password">
  <label for="nova">Nova senha</label><input type="password" id="nova" name="nova" required minlength="8" autocomplete="new-password">
  <label for="nova2">Confirmar nova senha</label><input type="password" id="nova2" name="nova2" required minlength="8" autocomplete="new-password">
  <p><button type="submit">Alterar senha</button></p>
</form>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
