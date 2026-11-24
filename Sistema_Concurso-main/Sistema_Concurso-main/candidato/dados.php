<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
$bloqueado = Calendario::inscricoesEncerradas();
$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($bloqueado) {
        $erros[] = 'Após o prazo de inscrição os dados só podem ser alterados pela administração, mediante recurso.';
    } else {
        $antes = $cand->getNome() . '|' . $cand->getEmail() . '|' . $cand->getTelefone() . '|' . $cand->getDataNascimento();
        $cand->setNome(post('nome'));
        $cand->setDataNascimento(post('data_nascimento'));
        $cand->setTelefone(somenteDigitos(post('telefone')));
        $cand->setEmail(strtolower(post('email')));
        $cand->setEndereco(post('endereco'));
        $cand->setRg(post('rg'));
        $cand->setTituloEleitor(somenteDigitos(post('titulo_eleitor')));
        $erros = $cand->validarDados();
        if (!$erros) {
            (new CandidatoDAO())->atualizarDados($cand);
            $_SESSION['cand_nome'] = $cand->getNome();
            Auditoria::registrar('DADOS_ALTERADOS', 'candidato', $cand->getID(), 'Antes: ' . $antes);
            flash('sucesso', 'Dados atualizados.');
            redirecionar('candidato/dados.php');
        }
    }
}
$tituloPagina = 'Meus dados';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Meus dados</h1>
<?php if ($bloqueado): ?><div class="alerta aviso">O prazo de inscrição terminou: os dados estão bloqueados para edição.</div><?php endif; ?>
<?php if ($erros): ?><div class="alerta erro" role="alert"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="caixa">
  <?= csrfCampo() ?>
  <fieldset <?= $bloqueado ? 'disabled' : '' ?> style="border:0;padding:0;margin:0">
  <label>CPF</label>
  <input type="text" value="<?= e(formatarCpf($cand->getCpf())) ?>" disabled>
  <label for="nome">Nome completo</label>
  <input type="text" id="nome" name="nome" value="<?= e($cand->getNome()) ?>" required maxlength="150">
  <div class="linha-campos">
    <div><label for="data_nascimento">Data de nascimento</label>
      <input type="date" id="data_nascimento" name="data_nascimento" value="<?= e($cand->getDataNascimento()) ?>" required></div>
    <div><label for="telefone">Telefone</label>
      <input type="tel" id="telefone" name="telefone" value="<?= e(formatarTelefone($cand->getTelefone())) ?>" required maxlength="16"></div>
  </div>
  <label for="email">E-mail</label>
  <input type="email" id="email" name="email" value="<?= e($cand->getEmail()) ?>" required maxlength="150">
  <label for="endereco">Endereço de residência</label>
  <input type="text" id="endereco" name="endereco" value="<?= e($cand->getEndereco()) ?>" required maxlength="255">
  <div class="linha-campos">
    <div><label for="rg">RG / CIN</label>
      <input type="text" id="rg" name="rg" value="<?= e($cand->getRg()) ?>" required maxlength="20"></div>
    <div><label for="titulo_eleitor">Título de eleitor</label>
      <input type="text" id="titulo_eleitor" name="titulo_eleitor" value="<?= e($cand->getTituloEleitor()) ?>" required maxlength="14"></div>
  </div>
  <p><button type="submit">Salvar</button> <a class="botao sec" href="<?= e(url('candidato/painel.php')) ?>">Voltar</a></p>
  </fieldset>
</form>
<?php require RAIZ . '/includes/rodape.php'; ?>
