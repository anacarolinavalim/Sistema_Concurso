<?php
class Inscricao
{
    const STATUS_ATIVA = 'Ativa';
    const STATUS_CANCELADA = 'Cancelada';
    const STATUS_DESCLASSIFICADA = 'Desclassificada';

    private int $id = 0;
    private int $candidatoId = 0;
    private int $cargoId = 0;
    private int $tempoServicoDias = 0;
    private string $nivelTitulacao = '';
    private string $dataInscricao = '';
    private string $ultimaAlteracao = '';
    private string $status = self::STATUS_ATIVA;
    private int $versaoAtual = 1;
    private string $codigoValidacao = '';

    public static function fromArray(array $r): Inscricao
    {
        $i = new Inscricao();
        $i->id = (int)$r['id'];
        $i->candidatoId = (int)$r['candidato_id'];
        $i->cargoId = (int)$r['cargo_id'];
        $i->tempoServicoDias = (int)$r['tempo_servico_dias'];
        $i->nivelTitulacao = (string)$r['nivel_titulacao'];
        $i->dataInscricao = (string)$r['data_inscricao'];
        $i->ultimaAlteracao = (string)$r['ultima_alteracao'];
        $i->status = (string)$r['status'];
        $i->versaoAtual = (int)$r['versao_atual'];
        $i->codigoValidacao = (string)$r['codigo_validacao'];
        return $i;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setCandidatoId($v) { $this->candidatoId = (int)$v; }
    public function getCandidatoId(): int { return $this->candidatoId; }
    public function setCargoId($v) { $this->cargoId = (int)$v; }
    public function getCargoId(): int { return $this->cargoId; }
    public function setTempoServicoDias($v) { $this->tempoServicoDias = (int)$v; }
    public function getTempoServicoDias(): int { return $this->tempoServicoDias; }
    public function setNivelTitulacao($v) { $this->nivelTitulacao = (string)$v; }
    public function getNivelTitulacao(): string { return $this->nivelTitulacao; }
    public function setDataInscricao($v) { $this->dataInscricao = (string)$v; }
    public function getDataInscricao(): string { return $this->dataInscricao; }
    public function setUltimaAlteracao($v) { $this->ultimaAlteracao = (string)$v; }
    public function getUltimaAlteracao(): string { return $this->ultimaAlteracao; }
    public function setStatus($v) { $this->status = (string)$v; }
    public function getStatus(): string { return $this->status; }
    public function setVersaoAtual($v) { $this->versaoAtual = (int)$v; }
    public function getVersaoAtual(): int { return $this->versaoAtual; }
    public function setCodigoValidacao($v) { $this->codigoValidacao = (string)$v; }
    public function getCodigoValidacao(): string { return $this->codigoValidacao; }

    public function estaAtiva(): bool
    {
        return $this->status === self::STATUS_ATIVA;
    }

    // RF02.2 / RN01 – true se o candidato ainda pode ter uma nova inscrição ativa (máx. 2 por CPF)
    public function validarLimiteInscricoes(): bool
    {
        return (new InscricaoDAO())->contarAtivasPorCandidato($this->candidatoId) < MAX_INSCRICOES;
    }

    public function criarInscricao(): int
    {
        return (new InscricaoDAO())->inserir($this);
    }

    public function atualizarInscricao(): bool
    {
        return (new InscricaoDAO())->atualizar($this);
    }

    // RF02.5 – PDF da versão mais recente da inscrição
    public function gerarComprovantePDF(): string
    {
        $v = (new VersaoInscricaoDAO())->buscar($this->id, $this->versaoAtual);
        if ($v === null) {
            throw new RuntimeException('Versão da inscrição não encontrada.');
        }
        return PdfGenerator::comprovante(
            json_decode($v['dados_json'], true),
            (int)$v['versao'],
            (string)$v['codigo_validacao'],
            (string)$v['data_registro'],
            urlCompleta('validar.php?codigo=' . rawurlencode($v['codigo_validacao']))
        );
    }
}
