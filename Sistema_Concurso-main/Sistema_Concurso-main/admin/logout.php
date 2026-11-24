<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$a = Sessao::admin();
if ($a !== null) {
    $a->logout();
}
flash('sucesso', 'Você saiu da administração.');
redirecionar('admin/login.php');
