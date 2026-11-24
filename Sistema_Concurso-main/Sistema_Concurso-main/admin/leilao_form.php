<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$dao = new LeilaoVagaDAO();
$id = (int)get('id');
$leilao = $id > 0 ? $dao->buscarPorId($id) : new LeilaoVaga();
if ($leilao === null) {
    flash('erro', 'Leilão não encontrado.');
    redirecionar('admin/leiloes.php');
}
$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } else {
        $leilao->setCargoId((int)post('cargo_id'));
        $leilao->setEscolaridade(post('escolaridade'));
        $leilao->setHorario(post('horario'));
        $leilao->setVagasOfertadas((int)post('vagas'));
        $leilao->setDataLeilao(post('data_leilao'));
        $leilao->setAnoLetivo((int)post('ano_letivo'));
        if ((new CargoDAO())->buscarPorId($leilao->getCargoId()) === null) {
            $erros[] = 'Selecione o cargo.';
        }
        if (!Titulacao::valido($leilao->getEscolaridade())) {
            $erros[] = 'Selecione a escolaridade.';
        }
        if (comprimento($leilao->getHorario()) < 3 || comprimento($leilao->getHorario()) > 80) {
            $erros[] = 'Informe o horário (3 a 80 caracteres).';
        }
        if ($leilao->getVagasOfertadas() < 1 || $leilao->getVagasOfertadas() > 999) {
            $erros[] = 'Informe a quantidade de vagas (1 a 999).';
        }
        if (!Validador::dataValida($leilao->getDataLeilao())) {
            $erros[] = 'Data do leilão inválida.';
        }
        if (!in_array($leilao->getAnoLetivo(), [2027, 2028], true)) {
            $erros[] = 'O ano letivo deve ser 2027 ou 2028.';
        }
        if ($id > 0 && !$erros) {
            $ocupadas = (new ContratoLeilaoDAO())->contarOcupadas($id);
            if ($leilao->getVagasOfertadas() < $ocupadas) {
                $erros[] = 'Já existem ' . $ocupadas . ' vagas preenchidas; não é possível ofertar menos.';
            }
        }
        if (!$erros) {
            if ($id > 0) {
                $dao->atualizar($leilao);
                Auditoria::registrar('LEILAO_ALTERADO', 'leilao_vaga', $id, '');
            } else {
                $leilao->setStatus(LeilaoVaga::STATUS_AGENDADO);
                $dao->inserir($leilao);
                Auditoria::registrar('LEILAO_CRIADO', 'leilao_vaga', $leilao->getID(), 'Cargo ' . $leilao->getCargoId() . '; vagas ' . $leilao->getVagasOfertadas());
            }
            flash('sucesso', 'Leilão salvo.');
            redirecionar('admin/leilao_chamada.php?id=' . $leilao->getID());
        }
    }
}
$opcCargos = ['' => 'Selecione...'];
foreach (Cargo::listarCargos(true) as $c) {
    $opcCargos[$c->getID()] = $c->getTitulo();
}
$tituloPagina = $id > 0 ? 'Editar leilão' : 'Novo leilão';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1><?= e($tituloPagina) ?></h1>
<?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="caixa" style="max-width:640px">
  <?= csrfCampo() ?>
  <label for="cargo_id">Cargo</label>
  <select id="cargo_id" name="cargo_id" required><?= opcoesSelect($opcCargos, $leilao->getCargoId() ?: '') ?></select>
  <label for="escolaridade">Escolaridade da vaga</label>
  <select id="escolaridade" name="escolaridade"><?= opcoesSelect(Titulacao::todos(), $leilao->getEscolaridade() ?: 'medio') ?></select>
  <label for="horario">Horário</label>
  <input type="text" id="horario" name="horario" value="<?= e($leilao->getHorario()) ?>" required maxlength="80" placeholder="Ex.: 7h às 13h, segunda a sexta">
  <div class="linha-campos">
    <div><label for="vagas">Vagas ofertadas</label><input type="number" id="vagas" name="vagas" min="1" max="999" value="<?= (int)$leilao->getVagasOfertadas() ?>" required></div>
    <div><label for="data_leilao">Data do leilão</label><input type="date" id="data_leilao" name="data_leilao" value="<?= e($leilao->getDataLeilao()) ?>" required></div>
    <div><label for="ano_letivo">Ano letivo</label><select id="ano_letivo" name="ano_letivo"><?= opcoesSelect([2027 => '2027', 2028 => '2028'], $leilao->getAnoLetivo()) ?></select></div>
  </div>
  <p><button type="submit">Salvar</button> <a class="botao sec" href="<?= e(url('admin/leiloes.php')) ?>">Voltar</a></p>
</form>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
