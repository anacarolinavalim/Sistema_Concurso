<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$dao = new ConfiguracaoDAO();

function cfgParaInput(string $v): string
{
    return $v === '' ? '' : str_replace(' ', 'T', substr($v, 0, 16));
}

// Converte "AAAA-MM-DDTHH:MM" em "AAAA-MM-DD HH:MM:SS" (ou null se inválido)
function inputParaCfg(string $v, string $segundos): ?string
{
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/', $v, $m)) {
        return null;
    }
    $s = $m[1] . ' ' . $m[2] . ':' . $segundos;
    return Validador::dataHoraValida($s, 'Y-m-d H:i:s') ? $s : null;
}

$erros = [];
if (ehPost()) {
    if (!csrfValido()) {
        $erros[] = 'Sessão expirada. Recarregue a página.';
    } else {
        $ini = inputParaCfg(post('inscricao_inicio'), '00');
        $fim = inputParaCfg(post('inscricao_fim'), '59');
        $sim = post('data_simulada') === '' ? '' : inputParaCfg(post('data_simulada'), '00');
        $prel = post('preliminar_data');
        $rdi = post('recurso_data_inicio');
        $rdf = post('recurso_data_fim');
        $rhi = post('recurso_hora_inicio');
        $rhf = post('recurso_hora_fim');
        $def = post('definitiva_data');

        if ($ini === null) { $erros[] = 'Início das inscrições inválido.'; }
        if ($fim === null) { $erros[] = 'Fim das inscrições inválido.'; }
        if ($sim === null) { $erros[] = 'Data simulada inválida.'; }
        foreach (['Classificação preliminar' => $prel, 'Início dos recursos' => $rdi, 'Fim dos recursos' => $rdf, 'Classificação definitiva' => $def] as $rot => $dt) {
            if (!Validador::dataValida($dt)) { $erros[] = 'Data inválida: ' . $rot . '.'; }
        }
        foreach (['Hora inicial dos recursos' => $rhi, 'Hora final dos recursos' => $rhf] as $rot => $h) {
            if (!Validador::dataHoraValida($h, 'H:i')) { $erros[] = 'Hora inválida: ' . $rot . '.'; }
        }
        if (!$erros) {
            if ($ini >= $fim) { $erros[] = 'O fim das inscrições deve ser posterior ao início.'; }
            if ($prel < substr($fim, 0, 10)) { $erros[] = 'A classificação preliminar deve ser após o fim das inscrições.'; }
            if ($rdi > $rdf) { $erros[] = 'A data inicial dos recursos não pode ser posterior à final.'; }
            if ($rhi >= $rhf) { $erros[] = 'A hora final dos recursos deve ser posterior à inicial.'; }
            if ($def < $rdf) { $erros[] = 'A classificação definitiva deve ser após o fim dos recursos.'; }
        }
        if (!$erros) {
            $novos = [
                'inscricao_inicio' => $ini, 'inscricao_fim' => $fim, 'preliminar_data' => $prel,
                'recurso_data_inicio' => $rdi, 'recurso_data_fim' => $rdf, 'recurso_hora_inicio' => $rhi,
                'recurso_hora_fim' => $rhf, 'definitiva_data' => $def, 'data_simulada' => (string)$sim,
            ];
            $log = [];
            foreach ($novos as $k => $v) {
                if (Calendario::cfg($k) !== $v) {
                    $log[] = $k . ': "' . Calendario::cfg($k) . '" -> "' . $v . '"';
                    $dao->definir($k, $v);
                }
            }
            Auditoria::registrar('CONFIGURACAO_ALTERADA', 'configuracao', null, $log ? implode('; ', $log) : 'Sem alterações');
            flash('sucesso', 'Configurações salvas.');
            redirecionar('admin/configuracao.php');
        }
    }
}
$c = function (string $k): string {
    return Calendario::cfg($k);
};
$tituloPagina = 'Configurações';
require RAIZ . '/includes/admin_cabecalho.php';
?>
<h1>Datas do edital</h1>
<?php if ($erros): ?><div class="alerta erro"><ul><?php foreach ($erros as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="caixa" style="max-width:700px">
  <?= csrfCampo() ?>
  <div class="linha-campos">
    <div><label for="inscricao_inicio">Início das inscrições</label>
      <input type="datetime-local" id="inscricao_inicio" name="inscricao_inicio" value="<?= e(ehPost() ? post('inscricao_inicio') : cfgParaInput($c('inscricao_inicio'))) ?>" required></div>
    <div><label for="inscricao_fim">Fim das inscrições <span class="ajuda">(vale até os 59 s do minuto)</span></label>
      <input type="datetime-local" id="inscricao_fim" name="inscricao_fim" value="<?= e(ehPost() ? post('inscricao_fim') : cfgParaInput($c('inscricao_fim'))) ?>" required></div>
  </div>
  <label for="preliminar_data">Publicação da classificação preliminar</label>
  <input type="date" id="preliminar_data" name="preliminar_data" value="<?= e(ehPost() ? post('preliminar_data') : $c('preliminar_data')) ?>" required>
  <div class="linha-campos">
    <div><label for="recurso_data_inicio">Recursos – data inicial</label>
      <input type="date" id="recurso_data_inicio" name="recurso_data_inicio" value="<?= e(ehPost() ? post('recurso_data_inicio') : $c('recurso_data_inicio')) ?>" required></div>
    <div><label for="recurso_data_fim">Recursos – data final</label>
      <input type="date" id="recurso_data_fim" name="recurso_data_fim" value="<?= e(ehPost() ? post('recurso_data_fim') : $c('recurso_data_fim')) ?>" required></div>
    <div><label for="recurso_hora_inicio">Recursos – hora inicial (cada dia)</label>
      <input type="time" id="recurso_hora_inicio" name="recurso_hora_inicio" value="<?= e(ehPost() ? post('recurso_hora_inicio') : $c('recurso_hora_inicio')) ?>" required></div>
    <div><label for="recurso_hora_fim">Recursos – hora final (cada dia)</label>
      <input type="time" id="recurso_hora_fim" name="recurso_hora_fim" value="<?= e(ehPost() ? post('recurso_hora_fim') : $c('recurso_hora_fim')) ?>" required></div>
  </div>
  <label for="definitiva_data">Publicação do resultado dos recursos e da classificação definitiva</label>
  <input type="date" id="definitiva_data" name="definitiva_data" value="<?= e(ehPost() ? post('definitiva_data') : $c('definitiva_data')) ?>" required>

  <h2>Somente para testes</h2>
  <label for="data_simulada">Data/hora simulada <span class="ajuda">(deixe em branco em produção; o sistema passa a usar este relógio para prazos e fases)</span></label>
  <input type="datetime-local" id="data_simulada" name="data_simulada" value="<?= e(ehPost() ? post('data_simulada') : cfgParaInput($c('data_simulada'))) ?>">
  <p><button type="submit">Salvar</button></p>
</form>
<?php require RAIZ . '/includes/admin_rodape.php'; ?>
