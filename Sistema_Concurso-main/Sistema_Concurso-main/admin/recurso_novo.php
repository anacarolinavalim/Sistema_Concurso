<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$inscricaoId = (int)(ehPost() ? post('inscricao_id') : get('inscricao_id'));
$d = (new InscricaoDAO())->buscarDetalhe($inscricaoId);
if ($d === null) {
    flash('erro', 'Inscrição não encontrada.');
    redirecionar('admin/recursos.php');
}
$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } else {
        $res = RecursoService::registrar($inscricaoId, post('descricao'));
        if (!$res['erros']) {
            flash('sucesso', 'Recurso protocolado com sucesso.');
            redirecionar('admin/recurso_ver.php?id=' . (int)$res['id']);
        }
        $erros = $res['erros'];
    }
}
$tituloPagina = 'Novo recurso';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Registrar protocolo de recurso</h1>
<p><strong><?= e($d['nome']) ?></strong> (CPF <?= e(formatarCpf($d['cpf'])) ?>) – cargo: <?= e($d['titulo_cargo']) ?></p>
<p class="ajuda">O cargo da inscrição não pode ser alterado durante a fase de recursos.</p>
<?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="caixa">
  <?= csrfCampo() ?>
  <input type="hidden" name="inscricao_id" value="<?= $inscricaoId ?>">
  <label for="descricao">Descrição do recurso (conforme protocolo presencial)</label>
  <textarea id="descricao" name="descricao" required><?= e(post('descricao')) ?></textarea>
  <p><button type="submit">Registrar protocolo</button> <a class="botao sec" href="<?= e(url('admin/recursos.php')) ?>">Voltar</a></p>
</form>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
