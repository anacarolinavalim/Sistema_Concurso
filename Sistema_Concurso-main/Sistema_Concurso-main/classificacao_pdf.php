<?php
require_once __DIR__ . '/includes/bootstrap.php';
$id = (int)get('id');
$dao = new ClassificacaoDAO();
$c = $dao->buscarPorId($id);
if ($c === null) {
    http_response_code(404);
    exit('Classificação não encontrada.');
}
$ehAdmin = Sessao::admin() !== null;
if (!$ehAdmin) {
    $ultima = $dao->ultima($c['tipo']);
    if (!Calendario::classificacaoVisivel($c['tipo']) || $ultima === null || (int)$ultima['id'] !== $id) {
        http_response_code(403);
        exit('Classificação ainda não publicada.');
    }
}
$porCargo = [];
foreach ($dao->itens($id) as $r) {
    $porCargo[$r['titulo_cargo']][] = $r;
}
$pdf = PdfGenerator::classificacao($c, $porCargo);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="classificacao-' . strtolower($c['tipo']) . '-' . $id . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
