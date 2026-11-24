<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (Sessao::candidato()) {
    redirecionar('candidato/painel.php');
}
$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $c = new Candidato();
        $c->setNome(post('nome'));
        $c->setCpf(somenteDigitos(post('cpf')));
        $c->setDataNascimento(post('data_nascimento'));
        $c->setTelefone(somenteDigitos(post('telefone')));
        $c->setEmail(strtolower(post('email')));
        $c->setEndereco(post('endereco'));
        $c->setRg(post('rg'));
        $c->setTituloEleitor(somenteDigitos(post('titulo_eleitor')));
        $senha = (string)($_POST['senha'] ?? '');
        if ($senha !== (string)($_POST['senha2'] ?? '')) {
            $erros[] = 'A confirmação de senha não confere.';
        }
        if (empty($_POST['lgpd'])) {
            $erros[] = 'É necessário concordar com o tratamento dos dados pessoais.';
        }
        $erros = array_merge($erros, $c->cadastrar($senha));
        if (!$erros) {
            Sessao::loginCandidato($c);
            Auditoria::registrar('CADASTRO', 'candidato', $c->getID(), 'Autocadastro de candidato');
            flash('sucesso', 'Cadastro realizado com sucesso! Agora você pode fazer sua inscrição.');
            redirecionar('candidato/painel.php');
        }
    }
}
$v = function (string $k): string {
    return e(post($k));
};
$tituloPagina = 'Cadastre-se';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Cadastro de Candidato</h1>
<p>Preencha seus dados pessoais. É necessário ter no mínimo <?= (int)IDADE_MINIMA ?> anos na data da inscrição.</p>
<?php if ($erros): ?>
  <div class="alerta erro" role="alert"><strong>Corrija os itens abaixo:</strong><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" class="caixa" autocomplete="on">
  <?= csrfCampo() ?>
  <label for="nome">Nome completo</label>
  <input type="text" id="nome" name="nome" value="<?= $v('nome') ?>" required maxlength="150" autocomplete="name">
  <div class="linha-campos">
    <div><label for="cpf">CPF</label>
      <input type="text" id="cpf" name="cpf" value="<?= $v('cpf') ?>" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00"></div>
    <div><label for="data_nascimento">Data de nascimento</label>
      <input type="date" id="data_nascimento" name="data_nascimento" value="<?= $v('data_nascimento') ?>" required autocomplete="bday"></div>
  </div>
  <div class="linha-campos">
    <div><label for="telefone">Telefone (com DDD)</label>
      <input type="tel" id="telefone" name="telefone" value="<?= $v('telefone') ?>" required maxlength="16" autocomplete="tel"></div>
    <div><label for="email">E-mail</label>
      <input type="email" id="email" name="email" value="<?= $v('email') ?>" required maxlength="150" autocomplete="email"></div>
  </div>
  <label for="endereco">Endereço de residência</label>
  <input type="text" id="endereco" name="endereco" value="<?= $v('endereco') ?>" required maxlength="255" autocomplete="street-address" placeholder="Rua, número, bairro, cidade/UF, CEP">
  <div class="linha-campos">
    <div><label for="rg">RG / CIN</label>
      <input type="text" id="rg" name="rg" value="<?= $v('rg') ?>" required maxlength="20"></div>
    <div><label for="titulo_eleitor">Título de eleitor <span class="ajuda">(12 dígitos)</span></label>
      <input type="text" id="titulo_eleitor" name="titulo_eleitor" value="<?= $v('titulo_eleitor') ?>" required inputmode="numeric" maxlength="14"></div>
  </div>
  <div class="linha-campos">
    <div><label for="senha">Senha <span class="ajuda">(mín. 8 caracteres, com letras e números)</span></label>
      <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password"></div>
    <div><label for="senha2">Confirmar senha</label>
      <input type="password" id="senha2" name="senha2" required minlength="8" autocomplete="new-password"></div>
  </div>
  <label class="check"><input type="checkbox" name="lgpd" value="1" required <?= !empty($_POST['lgpd']) ? 'checked' : '' ?>>
    Li e concordo com o tratamento dos meus dados pessoais exclusivamente para este processo seletivo, conforme a LGPD (Lei nº 13.709/2018).</label>
  <p><button type="submit">Cadastrar</button> <a class="botao sec" href="<?= e(url('login.php')) ?>">Já tenho cadastro</a></p>
</form>
<?php require RAIZ . '/includes/rodape.php'; ?>
