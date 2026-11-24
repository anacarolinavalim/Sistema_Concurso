<?php
class AdminDAO extends DAO
{
    public function buscarPorId(int $id): ?Admin
    {
        $st = $this->pdo->prepare('SELECT * FROM administrador WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? Admin::fromArray($r) : null;
    }

    public function buscarPorEmail(string $email): ?Admin
    {
        $st = $this->pdo->prepare('SELECT * FROM administrador WHERE email = ?');
        $st->execute([$email]);
        $r = $st->fetch();
        return $r ? Admin::fromArray($r) : null;
    }

    public function inserir(Admin $a): int
    {
        $st = $this->pdo->prepare('INSERT INTO administrador (nome, email, senha_hash, data_criacao) VALUES (?, ?, ?, ?)');
        $st->execute([$a->getNome(), $a->getEmail(), $a->getSenha(), $a->getDataCriacao()]);
        $id = (int)$this->pdo->lastInsertId();
        $a->setID($id);
        return $id;
    }

    public function atualizarSenha(int $id, string $hash): bool
    {
        $st = $this->pdo->prepare('UPDATE administrador SET senha_hash = ? WHERE id = ?');
        return $st->execute([$hash, $id]);
    }

    public function listar(): array
    {
        return $this->pdo->query('SELECT id, nome, email, data_criacao FROM administrador ORDER BY nome')->fetchAll();
    }

    public function contar(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM administrador')->fetchColumn();
    }
}
