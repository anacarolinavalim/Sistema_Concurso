<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (Sessao::candidato()) {
    redirecionar('candidato/painel.php');
}
$erro = '';
if (ehPost()) {
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $login = post('login');
        $c = Candidato::autenticar($login, (string)($_POST['senha'] ?? ''));
        if ($c !== null && $c->login((string)$_POST['senha'])) {
            $c->exibirPainel();
        }
        $erro = 'CPF/e-mail ou senha incorretos.';
        Auditoria::registrar('LOGIN_FALHA', 'candidato', null, 'Tentativa de login: ' . cortar($login, 60));
    }
}
$tituloPagina = 'Entrar';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Entrar</h1>
<?php if ($erro !== ''): ?><div class="alerta erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
<form method="post" class="caixa" style="max-width:460px">
  <?= csrfCampo() ?>
  <label for="login">CPF ou e-mail</label>
  <input type="text" id="login" name="login" value="<?= e(post('login')) ?>" required autocomplete="username">
  <label for="senha">Senha</label>
  <input type="password" id="senha" name="senha" required autocomplete="current-password">
  <p><button type="submit">Entrar</button> <a href="<?= e(url('cadastro.php')) ?>">Criar cadastro</a></p>
</form>
<?php require RAIZ . '/includes/rodape.php'; ?>
