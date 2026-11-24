<?php
class ClassificacaoDAO extends DAO
{
    public function criar(string $tipo, int $adminId): int
    {
        $st = $this->pdo->prepare('INSERT INTO classificacao (tipo, data_geracao, gerado_por) VALUES (?, ?, ?)');
        $st->execute([$tipo, Calendario::agora()->format('Y-m-d H:i:s'), $adminId > 0 ? $adminId : null]);
        return (int)$this->pdo->lastInsertId();
    }

    public function inserirItem(int $classificacaoId, int $cargoId, array $r): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO classificacao_item (classificacao_id, cargo_id, posicao, inscricao_id, candidato_id, nome, cpf, data_nascimento, tempo_servico_dias, nivel_titulacao, habilitado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $classificacaoId, $cargoId, (int)$r['posicao'], (int)$r['inscricao_id'], (int)$r['candidato_id'],
            $r['nome'], $r['cpf'], $r['data_nascimento'], (int)$r['tempo_servico_dias'], $r['nivel_titulacao'], (int)$r['habilitado'],
        ]);
    }

    public function ultima(string $tipo): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM classificacao WHERE tipo = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$tipo]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM classificacao WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function listar(): array
    {
        return $this->pdo->query(
            'SELECT c.*, (SELECT COUNT(*) FROM classificacao_item x WHERE x.classificacao_id = c.id) AS total
             FROM classificacao c ORDER BY c.id DESC LIMIT 50'
        )->fetchAll();
    }

    // Itens do snapshot com a situação ATUAL da inscrição (coluna "status")
    public function itens(int $classificacaoId, int $cargoId = 0): array
    {
        $sql = 'SELECT x.*, i.status, g.titulo_cargo
                FROM classificacao_item x
                JOIN inscricao i ON i.id = x.inscricao_id
                JOIN cargo g ON g.id = x.cargo_id
                WHERE x.classificacao_id = ?';
        $p = [$classificacaoId];
        if ($cargoId > 0) {
            $sql .= ' AND x.cargo_id = ?';
            $p[] = $cargoId;
        }
        $sql .= ' ORDER BY g.titulo_cargo, x.posicao';
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public function cargosDaClassificacao(int $classificacaoId): array
    {
        $st = $this->pdo->prepare(
            'SELECT DISTINCT g.id, g.titulo_cargo FROM classificacao_item x JOIN cargo g ON g.id = x.cargo_id
             WHERE x.classificacao_id = ? ORDER BY g.titulo_cargo'
        );
        $st->execute([$classificacaoId]);
        return $st->fetchAll();
    }
}
