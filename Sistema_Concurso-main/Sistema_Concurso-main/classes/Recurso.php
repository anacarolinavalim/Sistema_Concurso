<?php
class Recurso
{
    const PENDENTE = 'Pendente';
    const DEFERIDO = 'Deferido';
    const INDEFERIDO = 'Indeferido';

    private int $id = 0;
    private int $inscricaoId = 0;
    private string $numeroProtocolo = '';
    private string $dataProtocolo = '';
    private string $descricao = '';
    private string $parecerDeferimento = self::PENDENTE;
    private string $parecerJustificativa = '';
    private string $dataParecer = '';
    private int $registradoPor = 0;

    public static function fromArray(array $r): Recurso
    {
        $x = new Recurso();
        $x->id = (int)$r['id'];
        $x->inscricaoId = (int)$r['inscricao_id'];
        $x->numeroProtocolo = (string)$r['numero_protocolo'];
        $x->dataProtocolo = (string)$r['data_protocolo'];
        $x->descricao = (string)$r['descricao'];
        $x->parecerDeferimento = (string)$r['parecer_deferimento'];
        $x->parecerJustificativa = (string)($r['parecer_justificativa'] ?? '');
        $x->dataParecer = (string)($r['data_parecer'] ?? '');
        $x->registradoPor = (int)($r['registrado_por'] ?? 0);
        return $x;
    }

    public function setID($v) { $this->id = (int)$v; }
    public function getID(): int { return $this->id; }
    public function setInscricaoId($v) { $this->inscricaoId = (int)$v; }
    public function getInscricaoId(): int { return $this->inscricaoId; }
    public function setNumeroProtocolo($v) { $this->numeroProtocolo = (string)$v; }
    public function getNumeroProtocolo(): string { return $this->numeroProtocolo; }
    public function setDataProtocolo($v) { $this->dataProtocolo = (string)$v; }
    public function getDataProtocolo(): string { return $this->dataProtocolo; }
    public function setDescricao($v) { $this->descricao = (string)$v; }
    public function getDescricao(): string { return $this->descricao; }
    public function setParecerDeferimento($v) { $this->parecerDeferimento = (string)$v; }
    public function getParecerDeferimento(): string { return $this->parecerDeferimento; }
    public function setParecerJustificativa($v) { $this->parecerJustificativa = (string)$v; }
    public function getParecerJustificativa(): string { return $this->parecerJustificativa; }
    public function setDataParecer($v) { $this->dataParecer = (string)$v; }
    public function getDataParecer(): string { return $this->dataParecer; }
    public function setRegistradoPor($v) { $this->registradoPor = (int)$v; }
    public function getRegistradoPor(): int { return $this->registradoPor; }

    public function cadastrarProtocolo(): int
    {
        return (new RecursoDAO())->inserir($this);
    }

    public function atualizarParecer(string $parecer, string $justificativa): bool
    {
        $this->parecerDeferimento = $parecer;
        $this->parecerJustificativa = $justificativa;
        $this->dataParecer = Calendario::agora()->format('Y-m-d H:i:s');
        return (new RecursoDAO())->atualizarParecer($this);
    }

    // RF04.2 / RN03 – true quando a inscrição possui recurso e, portanto, não pode trocar de cargo.
    public static function impedirTrocaCargo(int $inscricaoId): bool
    {
        return (new RecursoDAO())->existePorInscricao($inscricaoId);
    }
}
