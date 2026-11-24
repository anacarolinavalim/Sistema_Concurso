<?php
class LeilaoVaga
{
    const STATUS_AGENDADO = 'Agendado';
    const STATUS_EM_ANDAMENTO = 'Em andamento';
    const STATUS_ENCERRADO = 'Encerrado';

    private int $id = 0;
    private int $cargoId = 0;
    private string $escolaridade = '';
    private string $horario = '';
    private int $vagasOfertadas = 1;
    private string $status = self::STATUS_AGENDADO;
    private string $dataLeilao = '';
    private int $anoLetivo = 2027;

    public static function fromArray(array $r): LeilaoVaga
    {
        $l = new LeilaoVaga();
        $l->id = (int)$r['id'];
        $l->cargoId = (int)$r['cargo_id'];
        $l->escolaridade = (string)$r['escolaridade'];
        $l->horario = (string)$r['horario'];
        $l->vagasOfertadas = (int)$r['vagas_ofertadas'];
        $l->status = (string)$r['status'];
        $l->dataLeilao = (string)$r['data_leilao'];
        $l->anoLetivo = (int)$r['ano_letivo'];
        return $l;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setCargoId($v) { $this->cargoId = (int)$v; }
    public function getCargoId(): int { return $this->cargoId; }
    public function setEscolaridade($v) { $this->escolaridade = (string)$v; }
    public function getEscolaridade(): string { return $this->escolaridade; }
    public function setHorario($v) { $this->horario = (string)$v; }
    public function getHorario(): string { return $this->horario; }
    public function setVagasOfertadas($v) { $this->vagasOfertadas = (int)$v; }
    public function getVagasOfertadas(): int { return $this->vagasOfertadas; }
    public function setStatus($v) { $this->status = (string)$v; }
    public function getStatus(): string { return $this->status; }
    public function setDataLeilao($v) { $this->dataLeilao = (string)$v; }
    public function getDataLeilao(): string { return $this->dataLeilao; }
    public function setAnoLetivo($v) { $this->anoLetivo = (int)$v; }
    public function getAnoLetivo(): int { return $this->anoLetivo; }

    // RF05.1 – abre a chamada do leilão
    public function iniciarChamada(): bool
    {
        if ($this->status === self::STATUS_EM_ANDAMENTO) {
            return true;
        }
        $this->status = self::STATUS_EM_ANDAMENTO;
        return (new LeilaoVagaDAO())->atualizarStatus($this->id, $this->status);
    }

    // RF05.2 – check-in e conferência documental de um candidato neste leilão
    public function realizarCheckInDocumental(int $inscricaoId, array $itens, string $observacao): ?string
    {
        return LeilaoService::conferir($this->id, $inscricaoId, $itens, $observacao);
    }
}
