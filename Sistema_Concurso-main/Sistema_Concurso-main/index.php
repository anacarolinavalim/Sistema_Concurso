<?php
require_once __DIR__ . '/includes/bootstrap.php';
$tituloPagina = 'Início';
$cargos = Cargo::listarCargos(true);
$fase = Calendario::faseAtual();
require RAIZ . '/includes/cabecalho.php';
?>
<section class="hero">
  <span class="hero-selo">Edital nº XX/2027</span>
  <h1 class="hero-titulo">Inscrição ao Processo Seletivo da Educação – 2027</h1>
  <p class="aviso-teste">TODOS OS DADOS DESTE SITE SÃO FICTÍCIOS PARA TESTE!</p>
  <p class="hero-texto">Departamento Municipal de Educação de Carmo de Minas. Contratação temporária (designação 2027/2028) de profissionais da educação.</p>
  <p class="hero-fase"><span class="ponto"></span><strong>Fase atual:</strong> <?= e($fase) ?></p>
  <div class="hero-acoes">
  <?php if (Sessao::candidato()): ?>
    <a class="botao" href="<?= e(url('candidato/painel.php')) ?>">Ir para o meu painel</a>
  <?php else: ?>
    <a class="botao" href="<?= e(url('cadastro.php')) ?>">Começar</a>
    <a class="botao sec" href="<?= e(url('login.php')) ?>">Já tenho cadastro</a>
  <?php endif; ?>
  </div>
</section>

<h2>Cronograma</h2>
<div class="tabela-rolagem">
<table>
  <tr><th>Etapa</th><th>Data</th></tr>
  <tr><td>Inscrições on-line</td><td><?= e(dataBr(Calendario::cfg('inscricao_inicio'))) ?> a <?= e(dataBr(Calendario::cfg('inscricao_fim'))) ?> (até 23h59)</td></tr>
  <tr><td>Classificação preliminar</td><td><?= e(dataBr(Calendario::cfg('preliminar_data'))) ?></td></tr>
  <tr><td>Recursos (presenciais, no Departamento Municipal de Educação)</td><td><?= e(dataBr(Calendario::cfg('recurso_data_inicio'))) ?> e <?= e(dataBr(Calendario::cfg('recurso_data_fim'))) ?>, das <?= e(Calendario::cfg('recurso_hora_inicio')) ?> às <?= e(Calendario::cfg('recurso_hora_fim')) ?></td></tr>
  <tr><td>Classificação definitiva</td><td><?= e(dataBr(Calendario::cfg('definitiva_data'))) ?></td></tr>
  <tr><td>Leilão de vagas</td><td>Durante os anos letivos de 2027 e 2028, conforme convocação</td></tr>
</table>
</div>

<h2>Cargos</h2>
<div class="tabela-rolagem">
<table>
  <tr><th>Cargo</th><th>Escolaridade mínima</th><th>Titulações consideradas</th></tr>
  <?php foreach ($cargos as $c): ?>
    <tr>
      <td><?= e($c->getTitulo()) ?></td>
      <td><?= e(Titulacao::rotulo($c->getEscolaridade())) ?></td>
      <td><?= e(implode(', ', array_map('Titulacao::rotulo', $c->getTitulacoesPermitidas()))) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<h2>Como participar</h2>
<ol>
  <li>Cadastre-se com CPF, e-mail e senha (é necessário ter 18 anos ou mais).</li>
  <li>Escolha o cargo (até <?= (int)MAX_INSCRICOES ?> inscrições ativas por CPF) e informe o tempo de serviço na Rede Municipal de Carmo de Minas até 31/12/2026 e a titulação.</li>
  <li>Anexe os documentos: identidade/CIN, CPF, título de eleitor, comprovante de votação, comprovante de residência, diploma/histórico e contagem de tempo.</li>
  <li>Baixe o comprovante de inscrição (PDF com código de validação). Você pode alterar a inscrição quantas vezes quiser até o fim do prazo.</li>
  <li>Acompanhe a classificação em <a href="<?= e(url('resultados.php')) ?>">Consultar Resultados</a>.</li>
</ol>

<p class="alerta info"><strong>Privacidade (LGPD):</strong> seus dados pessoais são usados apenas para este processo seletivo (Lei nº 13.709/2018). Nas listas públicas, o CPF é exibido de forma parcial.</p>
<?php require RAIZ . '/includes/rodape.php'; ?>
