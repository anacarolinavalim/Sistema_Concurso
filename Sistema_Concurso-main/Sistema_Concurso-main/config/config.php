<?php
// =====================================================================
//  CONFIGURAÇÃO GERAL – ajuste os valores abaixo para a sua equipe
// =====================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'BANCO');
define('DB_USER', 'USUÁRIO');
define('DB_PASS', 'SENHA');
define('DB_CHARSET', 'utf8mb4');

// Caminho do site dentro do domínio, SEM barra no final.
define('BASE_URL', 'URL/BASE');

define('SITE_NOME', 'Educação – Carmo de Minas');
define('TIMEZONE', 'America/Sao_Paulo');
define('DEBUG', false);               // true apenas durante o desenvolvimento

// Regras do edital
define('IDADE_MINIMA', 18);
define('MAX_INSCRICOES', 2);
define('DIAS_SUSPENSAO', 120);

// Sistema
define('RAIZ', dirname(__DIR__));
define('UPLOAD_DIR', RAIZ . '/uploads/');
define('UPLOAD_MAX_BYTES', 5 * 1024 * 1024);   // 5 MB por arquivo
define('SESSAO_TIMEOUT', 1800);                // 30 min de inatividade
