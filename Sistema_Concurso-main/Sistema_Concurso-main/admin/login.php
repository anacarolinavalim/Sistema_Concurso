<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Sessao::admin()) {
    redirecionar('admin/index.php');
}
$erro = '';
if (ehPost()) {
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $senha = (string)($_POST['senha'] ?? '');
        $a = Admin::autenticar(post('email'), $senha);
        if ($a !== null && $a->login($senha)) {
            $a->exibirPainel();
        }
        $erro = 'E-mail ou senha incorretos.';
        Auditoria::registrar('LOGIN_FALHA', 'administrador', null, 'Tentativa de login admin: ' . cortar(post('email'), 60));
    }
}
$tituloPagina = 'Login da Administração';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Administração – Entrar</h1>
<?php if ($erro !== ''): ?><div class="alerta erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
<form method="post" class="caixa" style="max-width:460px">
  <?= csrfCampo() ?>
  <label for="email">E-mail</label><input type="email" id="email" name="email" value="<?= e(post('email')) ?>" required autocomplete="username">
  <label for="senha">Senha</label><input type="password" id="senha" name="senha" required autocomplete="current-password">
  <p><button type="submit">Entrar</button></p>
</form>
<?php require RAIZ . '/includes/rodape.php'; ?>
