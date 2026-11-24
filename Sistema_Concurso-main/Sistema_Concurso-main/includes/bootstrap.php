<?php
require_once __DIR__ . '/../config/config.php';

date_default_timezone_set(TIMEZONE);
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('log_errors', '1');

spl_autoload_register(function (string $classe): void {
    foreach (['classes', 'classes/dao', 'classes/servicos'] as $pasta) {
        $arquivo = RAIZ . '/' . $pasta . '/' . $classe . '.php';
        if (is_file($arquivo)) {
            require_once $arquivo;
            return;
        }
    }
});

set_exception_handler(function (Throwable $e): void {
    error_log($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if (DEBUG) {
        echo '<pre>' . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . '</pre>';
    } else {
        echo '<h1>Erro interno</h1><p>Não foi possível concluir a operação. Tente novamente em instantes.</p>';
    }
    exit;
});

require_once RAIZ . '/includes/funcoes.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

Sessao::iniciar();
