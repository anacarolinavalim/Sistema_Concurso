<?php
class ContratoLeilaoDAO extends DAO
{
    public function inserir(ContratoLeilao $c): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO contrato_leilao (leilao_id, candidato_id, inscricao_id, data_contratacao, status, tipo_encerramento, data_desistencia, observacao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $c->getLeilaoId(), $c->getCandidatoId(), $c->getInscricaoId(), $c->getDataContratacao(), $c->getStatus(),
            $c->getTipoEncerramento() !== '' ? $c->getTipoEncerramento() : null,
            $c->getDataDesistencia() !== '' ? $c->getDataDesistencia() : null,
            $c->getObservacao() !== '' ? $c->getObservacao() : null,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $c->setID($id);
        return $id;
    }

    public function buscarPorId(int $id): ?ContratoLeilao
    {
        $st = $this->pdo->prepare('SELECT * FROM contrato_leilao WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? ContratoLeilao::fromArray($r) : null;
    }

    // Vagas ocupadas = contratos ativos ou encerrados normalmente (desistências liberam a vaga).
    public function contarOcupadas(int $leilaoId): int
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM contrato_leilao WHERE leilao_id = ? AND status IN ('Ativo','Encerrado')");
        $st->execute([$leilaoId]);
        return (int)$st->fetchColumn();
    }

    public function candidatoContratadoNoLeilao(int $leilaoId, int $candidatoId): bool
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM contrato_leilao WHERE leilao_id = ? AND candidato_id = ? AND status IN ('Ativo','Encerrado')");
        $st->execute([$leilaoId, $candidatoId]);
        return (int)$st->fetchColumn() > 0;
    }

    // Mapa candidato_id => status dos contratos (ativos/encerrados) do leilão
    public function candidatosContratadosNoLeilao(int $leilaoId): array
    {
        $st = $this->pdo->prepare("SELECT candidato_id, status FROM contrato_leilao WHERE leilao_id = ? AND status IN ('Ativo','Encerrado')");
        $st->execute([$leilaoId]);
        $mapa = [];
        foreach ($st->fetchAll() as $r) {
            $mapa[(int)$r['candidato_id']] = $r['status'];
        }
        return $mapa;
    }

    public function listarAtivosDoCandidato(int $candidatoId): array
    {
        $st = $this->pdo->prepare("SELECT id, leilao_id FROM contrato_leilao WHERE candidato_id = ? AND status = 'Ativo'");
        $st->execute([$candidatoId]);
        return $st->fetchAll();
    }

    // Mapa candidato_id => título do cargo, para contratos ativos em OUTROS leilões
    public function ativosOutrosLeiloes(int $leilaoId): array
    {
        $st = $this->pdo->prepare(
            "SELECT ct.candidato_id, g.titulo_cargo
             FROM contrato_leilao ct
             JOIN leilao_vaga lv ON lv.id = ct.leilao_id
             JOIN cargo g ON g.id = lv.cargo_id
             WHERE ct.status = 'Ativo' AND ct.leilao_id <> ?"
        );
        $st->execute([$leilaoId]);
        $mapa = [];
        foreach ($st->fetchAll() as $r) {
            $mapa[(int)$r['candidato_id']] = $r['titulo_cargo'];
        }
        return $mapa;
    }

    public function registrarEncerramento(int $id, string $status, ?string $tipo, ?string $data, ?string $observacao): bool
    {
        $st = $this->pdo->prepare('UPDATE contrato_leilao SET status = ?, tipo_encerramento = ?, data_desistencia = ?, observacao = ? WHERE id = ?');
        return $st->execute([$status, $tipo, $data, $observacao, $id]);
    }

    public function listarPorLeilao(int $leilaoId): array
    {
        $st = $this->pdo->prepare(
            'SELECT ct.*, c.nome, c.cpf FROM contrato_leilao ct JOIN candidato c ON c.id = ct.candidato_id
             WHERE ct.leilao_id = ? ORDER BY ct.id'
        );
        $st->execute([$leilaoId]);
        return $st->fetchAll();
    }

    public function contarAtivos(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM contrato_leilao WHERE status = 'Ativo'")->fetchColumn();
    }
}
