<?php
// Gerador de PDF simples (somente texto, fonte Helvetica), sem dependências externas.
class PdfGenerator
{
    private const LARGURA = 595;
    private const ALTURA = 842;
    private const MARGEM = 50;

    private array $paginas = [''];
    private float $y;

    public function __construct()
    {
        $this->y = self::ALTURA - self::MARGEM;
    }

    public function novaPagina(): void
    {
        $this->paginas[] = '';
        $this->y = self::ALTURA - self::MARGEM;
    }

    private function converter(string $s): string
    {
        $s = str_replace(["\r", "\n", "\t"], ' ', $s);
        $c = function_exists('iconv') ? @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s) : false;
        if ($c === false) {
            $c = preg_replace('/[^\x20-\x7E]/', '?', $s);
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $c);
    }

    private function n(float $v): string
    {
        return number_format($v, 2, '.', '');
    }

    public function texto(string $s, float $x, float $y, int $tam = 10, bool $negrito = false): void
    {
        $i = count($this->paginas) - 1;
        $this->paginas[$i] .= 'BT /' . ($negrito ? 'F2' : 'F1') . ' ' . $tam . ' Tf '
            . $this->n($x) . ' ' . $this->n($y) . ' Td (' . $this->converter($s) . ") Tj ET\n";
    }

    public function escrever(string $s, int $tam = 10, bool $negrito = false, float $x = self::MARGEM): void
    {
        if ($this->y < self::MARGEM + 20) {
            $this->novaPagina();
        }
        $this->texto($s, $x, $this->y, $tam, $negrito);
        $this->y -= $tam * 1.6;
    }

    // Escreve texto longo quebrando em linhas de até $max caracteres
    public function paragrafo(string $s, int $max = 85, int $tam = 10, bool $negrito = false): void
    {
        foreach (explode("\n", wordwrap($s, $max, "\n", false)) as $linha) {
            $this->escrever($linha, $tam, $negrito);
        }
    }

    public function espaco(float $h): void
    {
        $this->y -= $h;
    }

    public function linha(): void
    {
        $i = count($this->paginas) - 1;
        $yy = $this->y + 4;
        $this->paginas[$i] .= '0.6 w ' . $this->n(self::MARGEM) . ' ' . $this->n($yy) . ' m '
            . $this->n(self::LARGURA - self::MARGEM) . ' ' . $this->n($yy) . " l S\n";
        $this->y -= 6;
    }

    public function linhaTabela(array $celulas, array $xs, int $tam = 9, bool $negrito = false, ?array $cabecalho = null): void
    {
        if ($this->y < self::MARGEM + 20) {
            $this->novaPagina();
            if ($cabecalho !== null) {
                $this->celulas($cabecalho, $xs, $tam, true);
            }
        }
        $this->celulas($celulas, $xs, $tam, $negrito);
    }

    private function celulas(array $celulas, array $xs, int $tam, bool $negrito): void
    {
        foreach ($celulas as $i => $c) {
            $this->texto((string)$c, (float)$xs[$i], $this->y, $tam, $negrito);
        }
        $this->y -= $tam * 1.6;
    }

    public function saida(): string
    {
        $n = count($this->paginas);
        $streams = [];
        foreach ($this->paginas as $i => $conteudo) {
            $rodape = 'BT /F1 8 Tf ' . $this->n(self::MARGEM) . ' 28 Td ('
                . $this->converter('Página ' . ($i + 1) . ' de ' . $n . ' - ' . SITE_NOME) . ") Tj ET\n";
            $streams[] = $conteudo . $rodape;
        }

        $kids = [];
        for ($i = 0; $i < $n; $i++) {
            $kids[] = (5 + 2 * $i) . ' 0 R';
        }
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($streams as $i => $stream) {
            $objs[5 + 2 * $i] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::LARGURA . ' ' . self::ALTURA . '] '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . (6 + 2 * $i) . ' 0 R >>';
            $objs[6 + 2 * $i] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }
        ksort($objs);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $num => $corpo) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $corpo . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf('%010d 00000 n ', $off) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    // RF02.5 – comprovante de inscrição
    public static function comprovante(array $dados, int $versao, string $codigo, string $registro, string $urlValidacao): string
    {
        $p = new self();
        $cand = $dados['candidato'] ?? [];
        $p->escrever(SITE_NOME, 11, true);
        $p->escrever('Departamento Municipal de Educação - Processo Seletivo de Designação 2027/2028', 9);
        $p->espaco(6);
        $p->linha();
        $p->escrever('COMPROVANTE DE INSCRIÇÃO', 16, true);
        $p->espaco(4);
        $p->escrever('Código de validação: ' . $codigo, 12, true);
        $p->escrever('Versão do comprovante: ' . $versao, 10);
        $p->escrever('Carimbo de data/hora: ' . dataHoraBr($registro), 10);
        $p->escrever('Validação online: ' . $urlValidacao, 8);
        $p->espaco(4);
        $p->linha();

        $p->escrever('Dados do candidato', 12, true);
        $p->escrever('Nome: ' . ($cand['nome'] ?? ''));
        $p->escrever('CPF: ' . formatarCpf((string)($cand['cpf'] ?? '')));
        $p->escrever('RG/CIN: ' . ($cand['rg'] ?? '') . '     Título de eleitor: ' . ($cand['titulo_eleitor'] ?? ''));
        $p->escrever('Data de nascimento: ' . dataBr($cand['data_nascimento'] ?? null));
        $p->escrever('Telefone: ' . formatarTelefone((string)($cand['telefone'] ?? '')) . '     E-mail: ' . ($cand['email'] ?? ''));
        $p->paragrafo('Endereço: ' . ($cand['endereco'] ?? ''));
        $p->espaco(4);
        $p->linha();

        $p->escrever('Dados da inscrição', 12, true);
        $p->paragrafo('Cargo: ' . ($dados['cargo']['titulo'] ?? ''));
        $p->escrever('Tempo de serviço na Rede Municipal (até 31/12/2026): ' . tempoServicoTexto((int)($dados['tempo_servico_dias'] ?? 0)));
        $p->escrever('Titulação: ' . Titulacao::rotulo((string)($dados['nivel_titulacao'] ?? '')));
        $p->escrever('Situação: ' . ($dados['status'] ?? ''));
        $p->escrever('Data da inscrição: ' . dataHoraBr($dados['data_inscricao'] ?? null));
        $p->escrever('Última alteração: ' . dataHoraBr($dados['ultima_alteracao'] ?? null));
        $p->espaco(4);
        $p->linha();

        $p->escrever('Documentos anexados', 12, true);
        $docs = $dados['documentos'] ?? [];
        if (!$docs) {
            $p->escrever('Nenhum documento anexado.');
        }
        foreach ($docs as $d) {
            $rotulo = DocumentoInscricao::TIPOS[$d['tipo']] ?? $d['tipo'];
            $p->escrever('- ' . $rotulo . ' (' . cortar((string)$d['nome_original'], 40) . ', enviado em ' . dataBr($d['data_upload']) . ')', 9);
        }
        $p->espaco(8);
        $p->linha();
        $p->paragrafo('Este comprovante reflete os dados informados na data/hora acima. Cada alteração da inscrição gera um novo comprovante, com novo código. '
            . 'Confira a autenticidade no endereço de validação indicado (o QR Code correspondente está na versão em tela). '
            . 'Os dados pessoais são tratados exclusivamente para este processo seletivo, conforme a LGPD.', 90, 8);
        return $p->saida();
    }

    // RF03.2 – relatório de classificação. $itensPorCargo: [titulo_cargo => linhas]
    public static function classificacao(array $classificacao, array $itensPorCargo): string
    {
        $p = new self();
        $p->escrever(SITE_NOME, 11, true);
        $p->escrever('Classificação ' . $classificacao['tipo'] . ' - Processo Seletivo de Designação 2027/2028', 14, true);
        $p->escrever('Gerada em ' . dataHoraBr($classificacao['data_geracao']), 9);
        $p->espaco(4);
        $p->linha();

        $xs = [50, 85, 290, 365, 405, 470];
        $cab = ['Pos.', 'Candidato', 'CPF', 'Hab.', 'Tempo', 'Titulação'];
        if (!$itensPorCargo) {
            $p->escrever('Nenhuma inscrição classificada.');
        }
        foreach ($itensPorCargo as $titulo => $linhas) {
            $p->espaco(6);
            $p->paragrafo((string)$titulo . ' (' . count($linhas) . ' classificado(s))', 80, 11, true);
            $p->linhaTabela($cab, $xs, 8, true);
            foreach ($linhas as $r) {
                $dias = (int)$r['tempo_servico_dias'];
                $p->linhaTabela([
                    $r['posicao'] . 'º',
                    cortar((string)$r['nome'], 36),
                    mascararCpf((string)$r['cpf']),
                    ((int)$r['habilitado'] ? 'Sim' : 'Não'),
                    intdiv($dias, 365) . 'a ' . ($dias % 365) . 'd',
                    cortar(Titulacao::rotulo((string)$r['nivel_titulacao']), 18),
                ], $xs, 8, false, $cab);
            }
        }
        return $p->saida();
    }
}
