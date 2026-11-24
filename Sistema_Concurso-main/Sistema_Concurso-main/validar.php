<?php
require_once __DIR__ . '/includes/bootstrap.php';
$codigo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', get('codigo')));
$resultado = null;
$consultou = $codigo !== '';
if ($consultou && strlen($codigo) === 12) {
    $formatado = substr($codigo, 0, 4) . '-' . substr($codigo, 4, 4) . '-' . substr($codigo, 8, 4);
    $v = (new VersaoInscricaoDAO())->buscarPorCodigo($formatado);
    if ($v !== null) {
        $resultado = ['v' => $v, 'dados' => json_decode($v['dados_json'], true), 'codigo' => $formatado];
    }
}
$tituloPagina = 'Validar Comprovante';
require RAIZ . '/includes/cabecalho.php';
?>
<h1>Validar Comprovante de Inscrição</h1>
<form method="get" class="caixa" style="max-width:520px">
  <label for="codigo">Código de validação</label>
  <input type="text" id="codigo" name="codigo" value="<?= e(get('codigo')) ?>" placeholder="XXXX-XXXX-XXXX" maxlength="20" required>
  <p><button type="submit">Validar</button></p>
</form>
<?php if ($consultou && $resultado === null): ?>
  <div class="alerta erro">Código não encontrado. Confira os caracteres digitados.</div>
<?php elseif ($resultado !== null):
    $d = $resultado['dados'];
    $atual = (int)$resultado['v']['versao'] === (int)$resultado['v']['versao_atual'];
?>
  <div class="alerta sucesso"><strong>Comprovante autêntico.</strong>
    <?= $atual ? 'Esta é a versão mais recente da inscrição.' : 'Atenção: esta versão foi substituída por uma alteração posterior (versão atual: ' . (int)$resultado['v']['versao_atual'] . ').' ?></div>
  <div class="tabela-rolagem"><table>
    <tr><th>Código</th><td><?= e($resultado['codigo']) ?></td></tr>
    <tr><th>Versão</th><td><?= (int)$resultado['v']['versao'] ?></td></tr>
    <tr><th>Emitido em</th><td><?= e(dataHoraBr($resultado['v']['data_registro'])) ?></td></tr>
    <tr><th>Candidato</th><td><?= e($d['candidato']['nome'] ?? '') ?> (CPF <?= e(mascararCpf((string)($d['candidato']['cpf'] ?? ''))) ?>)</td></tr>
    <tr><th>Cargo</th><td><?= e($d['cargo']['titulo'] ?? '') ?></td></tr>
    <tr><th>Situação na versão</th><td><?= e($d['status'] ?? '') ?></td></tr>
  </table></div>
<?php endif; ?>
<?php require RAIZ . '/includes/rodape.php'; ?>
