<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
$id = (int)get('id');
$insc = (new InscricaoDAO())->buscarPorId($id);
if ($insc === null || $insc->getCandidatoId() !== $cand->getID()) {
    http_response_code(404);
    exit('Inscrição não encontrada.');
}
$versao = (int)get('v', (string)$insc->getVersaoAtual());
$v = (new VersaoInscricaoDAO())->buscar($id, $versao);
if ($v === null) {
    http_response_code(404);
    exit('Versão não encontrada.');
}
$pdf = PdfGenerator::comprovante(
    json_decode($v['dados_json'], true),
    (int)$v['versao'],
    (string)$v['codigo_validacao'],
    (string)$v['data_registro'],
    urlCompleta('validar.php?codigo=' . rawurlencode($v['codigo_validacao']))
);
Auditoria::registrar('COMPROVANTE_PDF', 'inscricao', $id, 'Versão ' . (int)$v['versao']);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="comprovante-' . $id . '-v' . (int)$v['versao'] . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
