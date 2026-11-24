<?php
class DocumentoInscricao
{
    const TIPOS = [
        'identidade'             => 'Identidade / CIN',
        'cpf'                    => 'CPF',
        'titulo_eleitor'         => 'Título de Eleitor',
        'comprovante_votacao'    => 'Comprovante de Votação',
        'comprovante_residencia' => 'Comprovante de Residência',
        'diploma'                => 'Diploma / Histórico (titulação)',
        'contagem_tempo'         => 'Contagem de Tempo de Serviço',
    ];

    const MIMES = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
    ];

    private int $id = 0;
    private int $inscricaoId = 0;
    private string $tipoDocumento = '';
    private string $caminhoArquivo = '';
    private string $nomeOriginal = '';
    private string $dataUpload = '';

    public static function fromArray(array $r): DocumentoInscricao
    {
        $d = new DocumentoInscricao();
        $d->id = (int)$r['id'];
        $d->inscricaoId = (int)$r['inscricao_id'];
        $d->tipoDocumento = (string)$r['tipo_documento'];
        $d->caminhoArquivo = (string)$r['caminho_arquivo'];
        $d->nomeOriginal = (string)$r['nome_original'];
        $d->dataUpload = (string)$r['data_upload'];
        return $d;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setInscricaoId($v) { $this->inscricaoId = (int)$v; }
    public function getInscricaoId(): int { return $this->inscricaoId; }
    public function setTipoDocumento($v) { $this->tipoDocumento = (string)$v; }
    public function getTipoDocumento(): string { return $this->tipoDocumento; }
    public function setCaminhoArquivo($v) { $this->caminhoArquivo = (string)$v; }
    public function getCaminhoArquivo(): string { return $this->caminhoArquivo; }
    public function setNomeOriginal($v) { $this->nomeOriginal = (string)$v; }
    public function getNomeOriginal(): string { return $this->nomeOriginal; }
    public function setDataUpload($v) { $this->dataUpload = (string)$v; }
    public function getDataUpload(): string { return $this->dataUpload; }

    public function getRotulo(): string
    {
        return self::TIPOS[$this->tipoDocumento] ?? $this->tipoDocumento;
    }

    public function caminhoAbsoluto(): string
    {
        return UPLOAD_DIR . $this->caminhoArquivo;
    }

    private static function detectarMime(array $f): string
    {
        if (class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            return (string)$fi->file($f['tmp_name']);
        }
        $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
        $mapa = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        return $mapa[$ext] ?? '';
    }

    // Retorna a mensagem de erro ou null se o anexo é válido.
    public static function validarAnexo(array $f): ?string
    {
        $erro = $f['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) {
            return 'arquivo maior que o permitido.';
        }
        if ($erro !== UPLOAD_ERR_OK) {
            return 'falha no envio do arquivo.';
        }
        if ((int)$f['size'] > UPLOAD_MAX_BYTES) {
            return 'arquivo maior que ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB.';
        }
        if (!is_uploaded_file($f['tmp_name'])) {
            return 'envio inválido.';
        }
        if (!isset(self::MIMES[self::detectarMime($f)])) {
            return 'formato inválido (use PDF, JPG ou PNG).';
        }
        return null;
    }

    // Move o arquivo para a pasta protegida e devolve o caminho relativo.
    public static function salvarArquivo(array $f, int $inscricaoId): string
    {
        $ext = self::MIMES[self::detectarMime($f)] ?? null;
        if ($ext === null) {
            throw new RuntimeException('Tipo de arquivo não permitido.');
        }
        $dir = UPLOAD_DIR . $inscricaoId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar a pasta de upload.');
        }
        $nome = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $nome)) {
            throw new RuntimeException('Não foi possível gravar o arquivo enviado.');
        }
        return $inscricaoId . '/' . $nome;
    }

    public static function removerArquivo(string $caminho): void
    {
        $abs = UPLOAD_DIR . $caminho;
        if ($caminho !== '' && strpos($caminho, '..') === false && is_file($abs)) {
            @unlink($abs);
        }
    }
}
