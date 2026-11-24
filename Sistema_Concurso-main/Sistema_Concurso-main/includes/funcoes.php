<?php
// ---------------------------------------------------------------------
// Funções auxiliares globais
// ---------------------------------------------------------------------
function e($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

function urlCompleta(string $caminho = ''): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . url($caminho);
}

function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

function ehPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'][] = [$tipo, $mensagem];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function csrfValido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function post(string $campo, string $padrao = ''): string
{
    $v = $_POST[$campo] ?? $padrao;
    return is_string($v) ? trim($v) : $padrao;
}

function get(string $campo, string $padrao = ''): string
{
    $v = $_GET[$campo] ?? $padrao;
    return is_string($v) ? trim($v) : $padrao;
}

function ipCliente(): string
{
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function comprimento(string $s): int
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

function cortar(string $s, int $max): string
{
    if (comprimento($s) <= $max) {
        return $s;
    }
    return function_exists('mb_substr') ? mb_substr($s, 0, $max - 1, 'UTF-8') . '…' : substr($s, 0, $max - 1) . '...';
}

function somenteDigitos(string $s): string
{
    return preg_replace('/\D/', '', $s);
}

function formatarCpf(string $cpf): string
{
    $c = somenteDigitos($cpf);
    if (strlen($c) !== 11) {
        return $cpf;
    }
    return substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9, 2);
}

function mascararCpf(string $cpf): string
{
    $c = somenteDigitos($cpf);
    if (strlen($c) !== 11) {
        return '***';
    }
    return '***.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-**';
}

function formatarTelefone(string $t): string
{
    $d = somenteDigitos($t);
    if (strlen($d) === 11) {
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
    }
    if (strlen($d) === 10) {
        return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
    }
    return $t;
}

function dataBr(?string $data): string
{
    if ($data === null || $data === '' || strpos($data, '0000-00-00') === 0) {
        return '-';
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y', $ts) : '-';
}

function dataHoraBr(?string $data): string
{
    if ($data === null || $data === '' || strpos($data, '0000-00-00') === 0) {
        return '-';
    }
    $ts = strtotime($data);
    return $ts ? date('d/m/Y H:i:s', $ts) : '-';
}

function tempoServicoTexto(int $dias): string
{
    $anos = intdiv($dias, 365);
    $resto = $dias % 365;
    return $anos . ' ano(s) e ' . $resto . ' dia(s) (' . $dias . ' dias)';
}

function nomeArquivoSeguro(string $nome): string
{
    $nome = basename(str_replace('\\', '/', $nome));
    $nome = preg_replace('/[^A-Za-z0-9._ -]/', '_', $nome);
    return substr($nome === '' ? 'arquivo' : $nome, 0, 200);
}

function opcoesSelect(array $opcoes, $selecionado): string
{
    $html = '';
    foreach ($opcoes as $valor => $rotulo) {
        $sel = ((string)$valor === (string)$selecionado) ? ' selected' : '';
        $html .= '<option value="' . e($valor) . '"' . $sel . '>' . e($rotulo) . '</option>';
    }
    return $html;
}

function badge(string $texto, string $classe = ''): string
{
    return '<span class="badge ' . e($classe) . '">' . e($texto) . '</span>';
}

function badgeStatus(string $status): string
{
    $mapa = [
        'Ativa' => 'ok', 'Ativo' => 'ok', 'Deferido' => 'ok', 'Conferido' => 'ok', 'Encerrado' => 'neutro',
        'Cancelada' => 'neutro', 'Agendado' => 'neutro', 'Pendente' => 'aviso', 'Em andamento' => 'aviso',
        'Desclassificada' => 'erro', 'Desclassificado' => 'erro', 'Desistido' => 'erro', 'Indeferido' => 'erro',
    ];
    return badge($status, $mapa[$status] ?? 'neutro');
}

function tabelaClassificacao(array $itens): void
{
    if (!$itens) {
        echo '<p>Nenhum candidato classificado para este cargo.</p>';
        return;
    }
    echo '<div class="tabela-rolagem"><table><thead><tr><th>Pos.</th><th>Candidato</th><th>CPF</th>'
        . '<th>Habilitado</th><th>Tempo de serviço</th><th>Titulação</th></tr></thead><tbody>';
    foreach ($itens as $r) {
        echo '<tr><td>' . (int)$r['posicao'] . 'º</td><td>' . e($r['nome']) . '</td><td>' . e(mascararCpf($r['cpf']))
            . '</td><td>' . ((int)$r['habilitado'] ? 'Sim' : 'Não') . '</td><td>'
            . e(tempoServicoTexto((int)$r['tempo_servico_dias'])) . '</td><td>'
            . e(Titulacao::rotulo($r['nivel_titulacao'])) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

// Envia um documento anexado (somente para quem já foi autorizado pela página chamadora).
function enviarArquivoDocumento(DocumentoInscricao $doc): void
{
    $abs = realpath($doc->caminhoAbsoluto());
    $base = realpath(UPLOAD_DIR);
    if ($abs === false || $base === false || strpos($abs, $base) !== 0 || !is_file($abs)) {
        http_response_code(404);
        exit('Arquivo não encontrado.');
    }
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    $tipos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
    header('Content-Type: ' . ($tipos[$ext] ?? 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . $doc->getTipoDocumento() . '.' . $ext . '"');
    header('Content-Length: ' . filesize($abs));
    header('X-Content-Type-Options: nosniff');
    readfile($abs);
    exit;
}
