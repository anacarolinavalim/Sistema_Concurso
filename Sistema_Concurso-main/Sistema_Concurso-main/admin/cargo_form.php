<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$dao = new CargoDAO();
$id = (int)get('id');
$cargo = $id > 0 ? $dao->buscarPorId($id) : new Cargo();
if ($cargo === null) {
    flash('erro', 'Cargo não encontrado.');
    redirecionar('admin/cargos.php');
}
$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } else {
        $cargo->setTitulo(post('titulo'));
        $cargo->setEscolaridade(post('escolaridade'));
        $perm = [];
        foreach (Titulacao::codigos() as $cod) {
            if (!empty($_POST['tit'][$cod])) {
                $perm[] = $cod;
            }
        }
        $cargo->setTitulacoesPermitidas($perm);
        $cargo->setAtivo(!empty($_POST['ativo']));
        if (comprimento($cargo->getTitulo()) < 3 || comprimento($cargo->getTitulo()) > 120) {
            $erros[] = 'Informe o título do cargo.';
        }
        if (!Titulacao::valido($cargo->getEscolaridade())) {
            $erros[] = 'Selecione a escolaridade mínima.';
        }
        if (!$perm) {
            $erros[] = 'Marque ao menos uma titulação aceita.';
        }
        if (!$erros) {
            if ($cargo->getID() > 0) {
                $dao->atualizar($cargo);
                Auditoria::registrar('CARGO_ALTERADO', 'cargo', $cargo->getID(), $cargo->getTitulo());
            } else {
                $dao->inserir($cargo);
                Auditoria::registrar('CARGO_CRIADO', 'cargo', $cargo->getID(), $cargo->getTitulo());
            }
            flash('sucesso', 'Cargo salvo.');
            redirecionar('admin/cargos.php');
        }
    }
}
$tituloPagina = $cargo->getID() > 0 ? 'Editar cargo' : 'Novo cargo';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1><?= e($tituloPagina) ?></h1>
<?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="caixa" style="max-width:640px">
  <?= csrfCampo() ?>
  <label for="titulo">Título do cargo</label>
  <input type="text" id="titulo" name="titulo" value="<?= e($cargo->getTitulo()) ?>" required maxlength="120">
  <label for="escolaridade">Escolaridade/habilitação mínima exigida (1º critério de classificação)</label>
  <select id="escolaridade" name="escolaridade"><?= opcoesSelect(Titulacao::todos(), $cargo->getEscolaridade()) ?></select>
  <label>Titulações aceitas na inscrição</label>
  <?php foreach (Titulacao::todos() as $cod => $rot): ?>
    <label class="check"><input type="checkbox" name="tit[<?= e($cod) ?>]" value="1" <?= $cargo->permiteTitulacao($cod) ? 'checked' : '' ?>> <?= e($rot) ?></label>
  <?php endforeach; ?>
  <label class="check" style="margin-top:12px"><input type="checkbox" name="ativo" value="1" <?= $cargo->getAtivo() ? 'checked' : '' ?>> Cargo ativo (aparece para inscrição)</label>
  <p><button type="submit">Salvar</button> <a class="botao sec" href="<?= e(url('admin/cargos.php')) ?>">Voltar</a></p>
</form>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
