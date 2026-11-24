<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$id = (int)get('id');
$d = (new InscricaoDAO())->buscarDetalhe($id);
if ($d === null) {
    flash('erro', 'Inscrição não encontrada.');
    redirecionar('admin/inscricoes.php');
}
$docs = (new DocumentoInscricaoDAO())->listarPorInscricao($id);
$versoes = (new VersaoInscricaoDAO())->listarPorInscricao($id);
$recurso = (new RecursoDAO())->buscarPorInscricao($id);
$tituloPagina = 'Inscrição #' . $id;
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Inscrição nº <?= $id ?> <?= badgeStatus($d['status']) ?></h1>
<p><a href="<?= e(url('admin/inscricoes.php')) ?>">&larr; Voltar à lista</a></p>
<div class="tabela-rolagem"><table>
  <tr><th>Candidato</th><td><?= e($d['nome']) ?></td><th>CPF</th><td><?= e(formatarCpf($d['cpf'])) ?></td></tr>
  <tr><th>Nascimento</th><td><?= e(dataBr($d['data_nascimento'])) ?></td><th>RG/CIN</th><td><?= e($d['rg']) ?></td></tr>
  <tr><th>Título de eleitor</th><td><?= e($d['titulo_eleitor']) ?></td><th>Telefone</th><td><?= e(formatarTelefone($d['telefone'])) ?></td></tr>
  <tr><th>E-mail</th><td><?= e($d['email']) ?></td><th>Endereço</th><td><?= e($d['endereco']) ?></td></tr>
  <tr><th>Cargo</th><td><?= e($d['titulo_cargo']) ?></td><th>Escolaridade mínima</th><td><?= e(Titulacao::rotulo($d['escolaridade_minima'])) ?></td></tr>
  <tr><th>Tempo de serviço</th><td><?= e(tempoServicoTexto((int)$d['tempo_servico_dias'])) ?></td><th>Titulação</th><td><?= e(Titulacao::rotulo($d['nivel_titulacao'])) ?></td></tr>
  <tr><th>Inscrição em</th><td><?= e(dataHoraBr($d['data_inscricao'])) ?></td><th>Última alteração</th><td><?= e(dataHoraBr($d['ultima_alteracao'])) ?></td></tr>
  <tr><th>Versão atual</th><td><?= (int)$d['versao_atual'] ?></td><th>Código de validação</th><td><?= e($d['codigo_validacao']) ?></td></tr>
</table></div>

<h2>Recurso</h2>
<?php if ($recurso): ?>
  <p>Protocolo <strong><?= e($recurso->getNumeroProtocolo()) ?></strong> – <?= badgeStatus($recurso->getParecerDeferimento()) ?>
     <a class="botao sec pequeno" href="<?= e(url('admin/recurso_ver.php?id=' . $recurso->getID())) ?>">Abrir recurso</a></p>
<?php elseif ($d['status'] === 'Ativa'): ?>
  <p>Sem recurso. <a class="botao sec pequeno" href="<?= e(url('admin/recurso_novo.php?inscricao_id=' . $id)) ?>">Registrar recurso</a></p>
<?php else: ?>
  <p>Sem recurso.</p>
<?php endif; ?>

<h2>Documentos</h2>
<?php if (!$docs): ?><p>Nenhum documento.</p><?php else: ?>
  <ul>
    <?php foreach ($docs as $doc): ?>
      <li><?= e($doc->getRotulo()) ?> – <a href="<?= e(url('admin/arquivo.php?id=' . $doc->getID())) ?>" target="_blank" rel="noopener"><?= e($doc->getNomeOriginal()) ?></a> (<?= e(dataBr($doc->getDataUpload())) ?>)</li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<h2>Histórico de versões (auditoria)</h2>
<div class="tabela-rolagem"><table>
  <tr><th>Versão</th><th>Data/hora</th><th>Código</th><th>Quem registrou</th><th>IP</th></tr>
  <?php foreach ($versoes as $v): ?>
    <tr><td><?= (int)$v['versao'] ?></td><td><?= e(dataHoraBr($v['data_registro'])) ?></td><td><?= e($v['codigo_validacao']) ?></td>
        <td><?= e($v['usuario_nome']) ?> (<?= e($v['usuario_tipo']) ?>)</td><td><?= e($v['ip']) ?></td></tr>
  <?php endforeach; ?>
</table></div>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
