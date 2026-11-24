<?php
class ConferenciaLeilao
{
    const CONFERIDO = 'Conferido';
    const DESCLASSIFICADO = 'Desclassificado';

    private int $id = 0;
    private int $leilaoId = 0;
    private int $inscricaoId = 0;
    private string $itensConferidos = '';   // tipos de documento separados por vírgula
    private string $status = self::CONFERIDO;
    private string $observacao = '';
    private int $avaliadorId = 0;
    private string $dataConferencia = '';

    public static function fromArray(array $r): ConferenciaLeilao
    {
        $c = new ConferenciaLeilao();
        $c->id = (int)$r['id'];
        $c->leilaoId = (int)$r['leilao_id'];
        $c->inscricaoId = (int)$r['inscricao_id'];
        $c->itensConferidos = (string)$r['itens_conferidos'];
        $c->status = (string)$r['status'];
        $c->observacao = (string)($r['observacao'] ?? '');
        $c->avaliadorId = (int)($r['avaliador_id'] ?? 0);
        $c->dataConferencia = (string)$r['data_conferencia'];
        return $c;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setLeilaoId($v) { $this->leilaoId = (int)$v; }
    public function getLeilaoId(): int { return $this->leilaoId; }
    public function setInscricaoId($v) { $this->inscricaoId = (int)$v; }
    public function getInscricaoId(): int { return $this->inscricaoId; }
    public function setItensConferidos(array $itens) { $this->itensConferidos = implode(',', $itens); }
    public function getItensConferidos(): array
    {
        return $this->itensConferidos === '' ? [] : explode(',', $this->itensConferidos);
    }
    public function setStatus($v) { $this->status = (string)$v; }
    public function getStatus(): string { return $this->status; }
    public function setObservacao($v) { $this->observacao = (string)$v; }
    public function getObservacao(): string { return $this->observacao; }
    public function setAvaliadorId($v) { $this->avaliadorId = (int)$v; }
    public function getAvaliadorId(): int { return $this->avaliadorId; }
    public function setDataConferencia($v) { $this->dataConferencia = (string)$v; }
    public function getDataConferencia(): string { return $this->dataConferencia; }
}
