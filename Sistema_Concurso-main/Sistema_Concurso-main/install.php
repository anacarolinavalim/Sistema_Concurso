<?php
// Instalador: cria o PRIMEIRO administrador. Depois de usar, APAGUE este arquivo do servidor.
require_once __DIR__ . '/includes/bootstrap.php';
$dao = new AdminDAO();
$bloqueado = $dao->contar() > 0;
$erros = [];
if (!$bloqueado && ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } else {
        $a = new Admin();
        $a->setNome(post('nome'));
        $a->setEmail(strtolower(post('email')));
        $erros = $a->cadastrar((string)($_POST['senha'] ?? ''));
        if (!$erros) {
            Auditoria::registrar('INSTALACAO', 'administrador', $a->getID(), 'Primeiro administrador criado');
            flash('sucesso', 'Administrador criado. APAGUE o arquivo install.php do servidor e faça login.');
            redirecionar('admin/login.php');
        }
    }
}
$tituloPagina = 'Instalação';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Instalação – primeiro administrador</h1>
<?php if ($bloqueado): ?>
  <div class="alerta erro">A instalação já foi concluída. Apague o arquivo <code>install.php</code> do servidor.</div>
<?php else: ?>
  <?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" class="caixa" style="max-width:460px">
    <?= csrfCampo() ?>
    <label for="nome">Nome</label><input type="text" id="nome" name="nome" value="<?= e(post('nome')) ?>" required>
    <label for="email">E-mail</label><input type="email" id="email" name="email" value="<?= e(post('email')) ?>" required>
    <label for="senha">Senha (mín. 8, letras e números)</label><input type="password" id="senha" name="senha" required minlength="8">
    <p><button type="submit">Criar administrador</button></p>
  </form>
<?php endif; ?>
<?php require RAIZ . '/includes/rodape.php'; ?>
