<?php
class InscricaoDAO extends DAO
{
    public function buscarPorId(int $id): ?Inscricao
    {
        $st = $this->pdo->prepare('SELECT * FROM inscricao WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? Inscricao::fromArray($r) : null;
    }

    public function inserir(Inscricao $i): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO inscricao (candidato_id, cargo_id, tempo_servico_dias, nivel_titulacao, data_inscricao, ultima_alteracao, status, versao_atual, codigo_validacao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $i->getCandidatoId(), $i->getCargoId(), $i->getTempoServicoDias(), $i->getNivelTitulacao(),
            $i->getDataInscricao(), $i->getUltimaAlteracao(), $i->getStatus(), $i->getVersaoAtual(), $i->getCodigoValidacao(),
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $i->setID($id);
        return $id;
    }

    public function atualizar(Inscricao $i): bool
    {
        $st = $this->pdo->prepare(
            'UPDATE inscricao SET cargo_id = ?, tempo_servico_dias = ?, nivel_titulacao = ?, ultima_alteracao = ?, status = ?, versao_atual = ?, codigo_validacao = ?
             WHERE id = ?'
        );
        return $st->execute([
            $i->getCargoId(), $i->getTempoServicoDias(), $i->getNivelTitulacao(), $i->getUltimaAlteracao(),
            $i->getStatus(), $i->getVersaoAtual(), $i->getCodigoValidacao(), $i->getID(),
        ]);
    }

    public function atualizarStatus(int $id, string $status, string $ultimaAlteracao): bool
    {
        $st = $this->pdo->prepare('UPDATE inscricao SET status = ?, ultima_alteracao = ? WHERE id = ?');
        return $st->execute([$status, $ultimaAlteracao, $id]);
    }

    public function contarAtivasPorCandidato(int $candidatoId): int
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM inscricao WHERE candidato_id = ? AND status = 'Ativa'");
        $st->execute([$candidatoId]);
        return (int)$st->fetchColumn();
    }

    public function existeAtivaMesmoCargo(int $candidatoId, int $cargoId, int $ignorarId = 0): bool
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM inscricao WHERE candidato_id = ? AND cargo_id = ? AND status = 'Ativa' AND id <> ?");
        $st->execute([$candidatoId, $cargoId, $ignorarId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function listarPorCandidato(int $candidatoId): array
    {
        $st = $this->pdo->prepare(
            'SELECT i.*, g.titulo_cargo FROM inscricao i JOIN cargo g ON g.id = i.cargo_id
             WHERE i.candidato_id = ? ORDER BY i.id DESC'
        );
        $st->execute([$candidatoId]);
        return $st->fetchAll();
    }

    public function buscarDetalhe(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT i.*, g.titulo_cargo, g.escolaridade_minima, c.nome, c.cpf, c.email, c.telefone,
                    c.data_nascimento, c.endereco, c.rg, c.titulo_eleitor
             FROM inscricao i
             JOIN cargo g ON g.id = i.cargo_id
             JOIN candidato c ON c.id = i.candidato_id
             WHERE i.id = ?'
        );
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function listarParaAdmin(int $cargoId, string $status, string $busca): array
    {
        $sql = 'SELECT i.id, i.status, i.tempo_servico_dias, i.nivel_titulacao, i.data_inscricao, i.versao_atual,
                       g.titulo_cargo, c.nome, c.cpf
                FROM inscricao i
                JOIN cargo g ON g.id = i.cargo_id
                JOIN candidato c ON c.id = i.candidato_id
                WHERE 1 = 1';
        $p = [];
        if ($cargoId > 0) {
            $sql .= ' AND i.cargo_id = ?';
            $p[] = $cargoId;
        }
        if ($status !== '') {
            $sql .= ' AND i.status = ?';
            $p[] = $status;
        }
        if ($busca !== '') {
            $digitos = somenteDigitos($busca);
            if ($digitos !== '') {
                $sql .= ' AND (c.nome LIKE ? OR c.cpf LIKE ?)';
                $p[] = '%' . $busca . '%';
                $p[] = '%' . $digitos . '%';
            } else {
                $sql .= ' AND c.nome LIKE ?';
                $p[] = '%' . $busca . '%';
            }
        }
        $sql .= ' ORDER BY i.id DESC LIMIT 300';
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public function listarAtivasPorCpf(string $cpf): array
    {
        $st = $this->pdo->prepare(
            "SELECT i.id, i.status, g.titulo_cargo, c.nome, c.cpf
             FROM inscricao i
             JOIN cargo g ON g.id = i.cargo_id
             JOIN candidato c ON c.id = i.candidato_id
             WHERE c.cpf = ? AND i.status = 'Ativa' ORDER BY i.id"
        );
        $st->execute([$cpf]);
        return $st->fetchAll();
    }

    public function contarPorStatus(string $status): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM inscricao WHERE status = ?');
        $st->execute([$status]);
        return (int)$st->fetchColumn();
    }
}
