<?php
class PunicaoCandidato
{
    private int $id = 0;
    private int $candidatoId = 0;
    private int $cargoId = 0;
    private int $contratoId = 0;
    private string $dataInicio = '';
    private string $dataFim = '';
    private string $motivo = '';

    public static function fromArray(array $r): PunicaoCandidato
    {
        $p = new PunicaoCandidato();
        $p->id = (int)$r['id'];
        $p->candidatoId = (int)$r['candidato_id'];
        $p->cargoId = (int)$r['cargo_id'];
        $p->contratoId = (int)($r['contrato_id'] ?? 0);
        $p->dataInicio = (string)$r['data_inicio'];
        $p->dataFim = (string)$r['data_fim'];
        $p->motivo = (string)$r['motivo'];
        return $p;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setCandidatoId($v) { $this->candidatoId = (int)$v; }
    public function getCandidatoId(): int { return $this->candidatoId; }
    public function setCargoId($v) { $this->cargoId = (int)$v; }
    public function getCargoId(): int { return $this->cargoId; }
    public function setContratoId($v) { $this->contratoId = (int)$v; }
    public function getContratoId(): int { return $this->contratoId; }
    public function getDataInicio(): string { return $this->dataInicio; }
    public function getDataFim(): string { return $this->dataFim; }
    public function setMotivo($v) { $this->motivo = (string)$v; }
    public function getMotivo(): string { return $this->motivo; }

    // RN04 – DataFimPunicao = data formal da desistência + 120 dias
    public function aplicarSuspensao120Dias(string $dataDesistencia): int
    {
        $this->dataInicio = $dataDesistencia;
        $this->dataFim = (new DateTime($dataDesistencia))->modify('+' . DIAS_SUSPENSAO . ' days')->format('Y-m-d');
        return (new PunicaoCandidatoDAO())->inserir($this);
    }

    // true se o candidato está suspenso para o cargo na data informada (padrão: hoje)
    public function verificarBloqueioAtivo(?string $data = null): bool
    {
        $data = $data ?? Calendario::hoje();
        return (new PunicaoCandidatoDAO())->bloqueioAtivo($this->candidatoId, $this->cargoId, $data) !== null;
    }
}
