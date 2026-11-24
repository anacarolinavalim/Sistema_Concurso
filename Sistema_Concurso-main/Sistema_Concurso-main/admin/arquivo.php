<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Sessao::exigirAdmin();
$doc = (new DocumentoInscricaoDAO())->buscarPorId((int)get('id'));
if ($doc === null) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}
Auditoria::registrar('DOCUMENTO_VISUALIZADO', 'documento_inscricao', $doc->getID(), 'Inscrição ' . $doc->getInscricaoId());
enviarArquivoDocumento($doc);
