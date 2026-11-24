<?php
class ContratoLeilao
{
    const ATIVO = 'Ativo';
    const DESISTIDO = 'Desistido';
    const ENCERRADO = 'Encerrado';

    private int $id = 0;
    private int $leilaoId = 0;
    private int $candidatoId = 0;
    private int $inscricaoId = 0;
    private string $dataContratacao = '';
    private string $status = self::ATIVO;
    private string $tipoEncerramento = '';   // '', 'Declarada' ou 'Abandono'
    private string $dataDesistencia = '';
    private string $observacao = '';

    public static function fromArray(array $r): ContratoLeilao
    {
        $c = new ContratoLeilao();
        $c->id = (int)$r['id'];
        $c->leilaoId = (int)$r['leilao_id'];
        $c->candidatoId = (int)$r['candidato_id'];
        $c->inscricaoId = (int)$r['inscricao_id'];
        $c->dataContratacao = (string)$r['data_contratacao'];
        $c->status = (string)$r['status'];
        $c->tipoEncerramento = (string)($r['tipo_encerramento'] ?? '');
        $c->dataDesistencia = (string)($r['data_desistencia'] ?? '');
        $c->observacao = (string)($r['observacao'] ?? '');
        return $c;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setLeilaoId($v) { $this->leilaoId = (int)$v; }
    public function getLeilaoId(): int { return $this->leilaoId; }
    public function setCandidatoId($v) { $this->candidatoId = (int)$v; }
    public function getCandidatoId(): int { return $this->candidatoId; }
    public function setInscricaoId($v) { $this->inscricaoId = (int)$v; }
    public function getInscricaoId(): int { return $this->inscricaoId; }
    public function setDataContratacao($v) { $this->dataContratacao = (string)$v; }
    public function getDataContratacao(): string { return $this->dataContratacao; }
    public function setStatus($v) { $this->status = (string)$v; }
    public function getStatus(): string { return $this->status; }
    public function setTipoEncerramento($v) { $this->tipoEncerramento = (string)$v; }
    public function getTipoEncerramento(): string { return $this->tipoEncerramento; }
    public function setDataDesistencia($v) { $this->dataDesistencia = (string)$v; }
    public function getDataDesistencia(): string { return $this->dataDesistencia; }
    public function setObservacao($v) { $this->observacao = (string)$v; }
    public function getObservacao(): string { return $this->observacao; }

    // RF05.4 – atribui a vaga ao candidato vencedor (valida regras e registra desistência declarada, se houver)
    public static function atribuirVaga(int $leilaoId, int $inscricaoId): ?string
    {
        return LeilaoService::atribuirVaga($leilaoId, $inscricaoId);
    }

    // RF05.5 – registra o abandono/desistência do contratado e aplica a suspensão de 120 dias
    public function registrarDesistencia(string $dataDesistencia, string $motivo): ?string
    {
        return LeilaoService::registrarDesistencia($this->id, $dataDesistencia, $motivo);
    }
}
