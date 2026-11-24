<?php
class Cargo
{
    private int $id = 0;
    private string $titulo = '';
    private string $escolaridadeRequerida = 'medio';
    private string $titulacoesPermitidas = '';   // códigos separados por vírgula
    private bool $ativo = true;

    public function __construct(int $id = 0, string $titulo = '', string $escolaridadeRequerida = 'medio', bool $ativo = true)
    {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->escolaridadeRequerida = $escolaridadeRequerida;
        $this->ativo = $ativo;
    }

    public static function fromArray(array $r): Cargo
    {
        $c = new Cargo((int)$r['id'], (string)$r['titulo_cargo'], (string)$r['escolaridade_minima'], (bool)(int)$r['ativo']);
        $c->titulacoesPermitidas = (string)$r['titulacoes_permitidas'];
        return $c;
    }

    public function setID($id) { $this->id = (int)$id; }
    public function getID(): int { return $this->id; }
    public function setTitulo($titulo) { $this->titulo = (string)$titulo; }
    public function getTitulo(): string { return $this->titulo; }
    public function setEscolaridade($e) { $this->escolaridadeRequerida = (string)$e; }
    public function getEscolaridade(): string { return $this->escolaridadeRequerida; }
    public function setAtivo($ativo) { $this->ativo = (bool)$ativo; }
    public function getAtivo(): bool { return $this->ativo; }

    public function getTitulacoesPermitidas(): array
    {
        $lista = array_map('trim', explode(',', $this->titulacoesPermitidas));
        return array_values(array_filter($lista, function ($v) {
            return $v !== '';
        }));
    }

    public function setTitulacoesPermitidas(array $lista): void
    {
        $this->titulacoesPermitidas = implode(',', $lista);
    }

    public function permiteTitulacao(string $nivel): bool
    {
        return in_array($nivel, $this->getTitulacoesPermitidas(), true);
    }

    // Habilitação/escolaridade mínima exigida para o cargo (1º critério de classificação)
    public function candidatoHabilitado(string $nivel): bool
    {
        return Titulacao::rank($nivel) >= Titulacao::rank($this->escolaridadeRequerida);
    }

    public static function listarCargos(bool $apenasAtivos = true): array
    {
        return (new CargoDAO())->listarTodos($apenasAtivos);
    }

    public static function obterPorId(int $id): ?Cargo
    {
        return (new CargoDAO())->buscarPorId($id);
    }
}
