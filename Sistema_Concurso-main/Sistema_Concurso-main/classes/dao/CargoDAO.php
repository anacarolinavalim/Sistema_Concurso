<?php
class CargoDAO extends DAO
{
    public function listarTodos(bool $apenasAtivos = false): array
    {
        $sql = 'SELECT * FROM cargo' . ($apenasAtivos ? ' WHERE ativo = 1' : '') . ' ORDER BY titulo_cargo';
        $lista = [];
        foreach ($this->pdo->query($sql)->fetchAll() as $r) {
            $lista[] = Cargo::fromArray($r);
        }
        return $lista;
    }

    public function buscarPorId(int $id): ?Cargo
    {
        $st = $this->pdo->prepare('SELECT * FROM cargo WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? Cargo::fromArray($r) : null;
    }

    public function inserir(Cargo $c): int
    {
        $st = $this->pdo->prepare('INSERT INTO cargo (titulo_cargo, escolaridade_minima, titulacoes_permitidas, ativo) VALUES (?, ?, ?, ?)');
        $st->execute([$c->getTitulo(), $c->getEscolaridade(), implode(',', $c->getTitulacoesPermitidas()), $c->getAtivo() ? 1 : 0]);
        $id = (int)$this->pdo->lastInsertId();
        $c->setID($id);
        return $id;
    }

    public function atualizar(Cargo $c): bool
    {
        $st = $this->pdo->prepare('UPDATE cargo SET titulo_cargo = ?, escolaridade_minima = ?, titulacoes_permitidas = ?, ativo = ? WHERE id = ?');
        return $st->execute([$c->getTitulo(), $c->getEscolaridade(), implode(',', $c->getTitulacoesPermitidas()), $c->getAtivo() ? 1 : 0, $c->getID()]);
    }

    public function alternarAtivo(int $id): bool
    {
        $st = $this->pdo->prepare('UPDATE cargo SET ativo = 1 - ativo WHERE id = ?');
        return $st->execute([$id]);
    }
}
