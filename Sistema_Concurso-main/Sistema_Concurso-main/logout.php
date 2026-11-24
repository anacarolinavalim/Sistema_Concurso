<?php
require_once __DIR__ . '/includes/bootstrap.php';
$c = Sessao::candidato();
if ($c !== null) {
    $c->logout();
}
flash('sucesso', 'Você saiu do sistema.');
redirecionar('index.php');
