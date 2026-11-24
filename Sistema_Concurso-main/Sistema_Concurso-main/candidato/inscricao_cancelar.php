<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$cand = Sessao::exigirCandidato();
if (!ehPost() || !csrfValido()) {
    flash('erro', 'Requisição inválida.');
    redirecionar('candidato/painel.php');
}
$erro = InscricaoService::cancelar($cand, (int)post('id'));
if ($erro !== null) {
    flash('erro', $erro);
} else {
    flash('sucesso', 'Inscrição cancelada. Um novo comprovante (com a situação "Cancelada") foi registrado.');
}
redirecionar('candidato/painel.php');
