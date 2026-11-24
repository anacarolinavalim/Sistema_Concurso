<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
$doc = (new DocumentoInscricaoDAO())->buscarPorId((int)get('id'));
$insc = $doc ? (new InscricaoDAO())->buscarPorId($doc->getInscricaoId()) : null;
if ($doc === null || $insc === null || $insc->getCandidatoId() !== $cand->getID()) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}
enviarArquivoDocumento($doc);
