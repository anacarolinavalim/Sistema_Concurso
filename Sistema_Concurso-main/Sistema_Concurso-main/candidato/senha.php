<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
$erro = '';
if (ehPost()) {
    if (!csrfValido()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ((string)($_POST['nova'] ?? '') !== (string)($_POST['nova2'] ?? '')) {
        $erro = 'A confirmação da nova senha não confere.';
    } elseif (($m = Validador::senha((string)($_POST['nova'] ?? ''))) !== null) {
        $erro = $m;
    } elseif (!$cand->alterarSenha((string)($_POST['atual'] ?? ''), (string)$_POST['nova'])) {
        $erro = 'Senha atual incorreta.';
    } else {
        flash('sucesso', 'Senha alterada com sucesso.');
        redirecionar('candidato/painel.php');
    }
}
$tituloPagina = 'Alterar senha';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Alterar senha</h1>
<?php if ($erro !== ''): ?><div class="alerta erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
<form method="post" class="caixa" style="max-width:460px">
  <?= csrfCampo() ?>
  <label for="atual">Senha atual</label><input type="password" id="atual" name="atual" required autocomplete="current-password">
  <label for="nova">Nova senha <span class="ajuda">(mín. 8, com letras e números)</span></label><input type="password" id="nova" name="nova" required minlength="8" autocomplete="new-password">
  <label for="nova2">Confirmar nova senha</label><input type="password" id="nova2" name="nova2" required minlength="8" autocomplete="new-password">
  <p><button type="submit">Salvar</button> <a class="botao sec" href="<?= e(url('candidato/painel.php')) ?>">Voltar</a></p>
</form>
<?php require RAIZ . '/includes/rodape.php'; ?>
