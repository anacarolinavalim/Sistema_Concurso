<?php
// Cabeçalho público/candidato. Espera: $tituloPagina (opcional)
$tituloPagina = $tituloPagina ?? 'Processo Seletivo';
$_cabCand = Sessao::candidato();
$_cabAdm = Sessao::admin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($tituloPagina) ?> – <?= e(SITE_NOME) ?></title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<a class="pular" href="#conteudo">Ir para o conteúdo</a>
<header class="topo">
  <div class="container topo-marca">
    <a class="marca" href="<?= e(url('index.php')) ?>" aria-label="Início">
      <img src="<?= e(url('assets/img/logo-prefeitura.png')) ?>" alt="Prefeitura de Carmo de Minas" class="logo logo-pref">
      <span class="logo-div" aria-hidden="true"></span>
      <img src="<?= e(url('assets/img/logo-educacao.png')) ?>" alt="Departamento de Educação – Carmo de Minas" class="logo logo-edu">
    </a>
  </div>
  <div class="topo-menu"><div class="container">
    <nav aria-label="Menu principal">
      <a href="<?= e(url('index.php')) ?>">Início</a>
      <?php if ($_cabCand): ?>
        <a href="<?= e(url('candidato/painel.php')) ?>">Meu Painel</a>
      <?php else: ?>
        <a href="<?= e(url('cadastro.php')) ?>">Cadastre-se</a>
        <a href="<?= e(url('login.php')) ?>">Entrar</a>
      <?php endif; ?>
      <a href="<?= e(url('resultados.php')) ?>">Consultar Resultados</a>
      <a href="<?= e(url('validar.php')) ?>">Validar Comprovante</a>
      <a href="#contato">Contato</a>
      <?php if ($_cabAdm): ?>
        <a href="<?= e(url('admin/index.php')) ?>">Administração</a>
      <?php endif; ?>
      <?php if ($_cabCand): ?>
        <a href="<?= e(url('logout.php')) ?>">Sair</a>
      <?php endif; ?>
    </nav>
  </div></div>
</header>
<main id="conteudo" class="container">
<?php foreach (flashes() as $_f): ?>
  <div class="alerta <?= e($_f[0]) ?>" role="alert"><?= e($_f[1]) ?></div>
<?php endforeach; ?>
