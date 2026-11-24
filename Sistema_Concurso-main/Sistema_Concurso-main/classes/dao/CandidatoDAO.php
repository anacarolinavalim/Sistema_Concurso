<?php
class CandidatoDAO extends DAO
{
    public function buscarPorId(int $id): ?Candidato
    {
        $st = $this->pdo->prepare('SELECT * FROM candidato WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? Candidato::fromArray($r) : null;
    }

    public function buscarPorCpf(string $cpf): ?Candidato
    {
        $st = $this->pdo->prepare('SELECT * FROM candidato WHERE cpf = ?');
        $st->execute([$cpf]);
        $r = $st->fetch();
        return $r ? Candidato::fromArray($r) : null;
    }

    public function buscarPorEmail(string $email): ?Candidato
    {
        $st = $this->pdo->prepare('SELECT * FROM candidato WHERE email = ?');
        $st->execute([$email]);
        $r = $st->fetch();
        return $r ? Candidato::fromArray($r) : null;
    }

    public function inserir(Candidato $c): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO candidato (cpf, nome, data_nascimento, telefone, email, endereco, rg, titulo_eleitor, senha_hash, data_criacao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $c->getCpf(), $c->getNome(), $c->getDataNascimento(), $c->getTelefone(), $c->getEmail(),
            $c->getEndereco(), $c->getRg(), $c->getTituloEleitor(), $c->getSenha(), $c->getDataCriacao(),
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $c->setID($id);
        return $id;
    }

    public function atualizarDados(Candidato $c): bool
    {
        $st = $this->pdo->prepare(
            'UPDATE candidato SET nome = ?, data_nascimento = ?, telefone = ?, email = ?, endereco = ?, rg = ?, titulo_eleitor = ?
             WHERE id = ?'
        );
        return $st->execute([
            $c->getNome(), $c->getDataNascimento(), $c->getTelefone(), $c->getEmail(),
            $c->getEndereco(), $c->getRg(), $c->getTituloEleitor(), $c->getID(),
        ]);
    }

    public function atualizarSenha(int $id, string $hash): bool
    {
        $st = $this->pdo->prepare('UPDATE candidato SET senha_hash = ? WHERE id = ?');
        return $st->execute([$hash, $id]);
    }

    public function emailEmUso(string $email, int $ignorarId = 0): bool
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM candidato WHERE email = ? AND id <> ?');
        $st->execute([$email, $ignorarId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function cpfEmUso(string $cpf): bool
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM candidato WHERE cpf = ?');
        $st->execute([$cpf]);
        return (int)$st->fetchColumn() > 0;
    }

    public function excluir(int $id): bool
    {
        $st = $this->pdo->prepare('DELETE FROM candidato WHERE id = ?');
        return $st->execute([$id]);
    }

    public function contar(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM candidato')->fetchColumn();
    }
}
