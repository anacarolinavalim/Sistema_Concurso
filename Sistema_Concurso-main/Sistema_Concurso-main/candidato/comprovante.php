<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
$id = (int)get('id');
$insc = (new InscricaoDAO())->buscarPorId($id);
if ($insc === null || $insc->getCandidatoId() !== $cand->getID()) {
    flash('erro', 'Inscrição não encontrada.');
    redirecionar('candidato/painel.php');
}
$versao = (int)get('v', (string)$insc->getVersaoAtual());
$v = (new VersaoInscricaoDAO())->buscar($id, $versao);
if ($v === null) {
    flash('erro', 'Versão do comprovante não encontrada.');
    redirecionar('candidato/painel.php');
}
$d = json_decode($v['dados_json'], true);
$urlValidacao = urlCompleta('validar.php?codigo=' . rawurlencode($v['codigo_validacao']));
$qr = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . rawurlencode($urlValidacao);
$tituloPagina = 'Comprovante de Inscrição';
require RAIZ . '/includes/cabecalho.php';
?>
<h1 class="sem-impressao">Comprovante de Inscrição</h1>
<p class="acoes">
  <button type="button" onclick="window.print()">Imprimir</button>
  <a class="botao" href="<?= e(url('candidato/comprovante_pdf.php?id=' . $id . '&v=' . (int)$v['versao'])) ?>">Baixar PDF</a>
  <a class="botao sec" href="<?= e(url('candidato/painel.php')) ?>">Voltar ao painel</a>
</p>
<?php if ((int)$v['versao'] !== $insc->getVersaoAtual()): ?>
  <div class="alerta aviso">Esta é uma versão anterior. A versão atual é a nº <?= $insc->getVersaoAtual() ?>.</div>
<?php endif; ?>
<section class="comprovante">
  <img src="<?= e($qr) ?>" width="150" height="150" alt="QR Code para validar o comprovante <?= e($v['codigo_validacao']) ?>">
  <h2 style="margin-top:0">COMPROVANTE DE INSCRIÇÃO</h2>
  <p>Processo Seletivo de Designação 2027/2028 – Dep. Municipal de Educação de Carmo de Minas</p>
  <p><strong>Código de validação:</strong> <?= e($v['codigo_validacao']) ?><br>
     <strong>Versão:</strong> <?= (int)$v['versao'] ?><br>
     <strong>Carimbo de data/hora:</strong> <?= e(dataHoraBr($v['data_registro'])) ?><br>
     <strong>Validar em:</strong> <?= e($urlValidacao) ?></p>
  <h3>Candidato</h3>
  <p><?= e($d['candidato']['nome']) ?><br>
     CPF: <?= e(formatarCpf($d['candidato']['cpf'])) ?> – RG/CIN: <?= e($d['candidato']['rg']) ?> – Título: <?= e($d['candidato']['titulo_eleitor']) ?><br>
     Nascimento: <?= e(dataBr($d['candidato']['data_nascimento'])) ?><br>
     <?= e(formatarTelefone($d['candidato']['telefone'])) ?> – <?= e($d['candidato']['email']) ?><br>
     <?= e($d['candidato']['endereco']) ?></p>
  <h3>Inscrição</h3>
  <p>Cargo: <strong><?= e($d['cargo']['titulo']) ?></strong><br>
     Tempo de serviço (até 31/12/2026): <?= e(tempoServicoTexto((int)$d['tempo_servico_dias'])) ?><br>
     Titulação: <?= e(Titulacao::rotulo($d['nivel_titulacao'])) ?><br>
     Situação: <?= e($d['status']) ?><br>
     Data da inscrição: <?= e(dataHoraBr($d['data_inscricao'])) ?> – Última alteração: <?= e(dataHoraBr($d['ultima_alteracao'])) ?></p>
  <h3>Documentos anexados</h3>
  <ul>
    <?php foreach ($d['documentos'] as $doc): ?>
      <li><?= e(DocumentoInscricao::TIPOS[$doc['tipo']] ?? $doc['tipo']) ?> – <?= e($doc['nome_original']) ?> (<?= e(dataBr($doc['data_upload'])) ?>)</li>
    <?php endforeach; ?>
  </ul>
</section>
<?php require RAIZ . '/includes/rodape.php'; ?>
