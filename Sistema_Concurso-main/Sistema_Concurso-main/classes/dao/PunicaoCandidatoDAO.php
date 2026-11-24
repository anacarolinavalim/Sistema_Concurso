<?php
class PunicaoCandidatoDAO extends DAO
{
    public function inserir(PunicaoCandidato $p): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO punicao_candidato (candidato_id, cargo_id, contrato_id, data_inicio, data_fim, motivo) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            $p->getCandidatoId(), $p->getCargoId(), $p->getContratoId() > 0 ? $p->getContratoId() : null,
            $p->getDataInicio(), $p->getDataFim(), $p->getMotivo(),
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $p->setID($id);
        return $id;
    }

    public function bloqueioAtivo(int $candidatoId, int $cargoId, string $data): ?PunicaoCandidato
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM punicao_candidato WHERE candidato_id = ? AND cargo_id = ? AND data_inicio <= ? AND data_fim >= ?
             ORDER BY data_fim DESC LIMIT 1'
        );
        $st->execute([$candidatoId, $cargoId, $data, $data]);
        $r = $st->fetch();
        return $r ? PunicaoCandidato::fromArray($r) : null;
    }

    // Mapa candidato_id => data_fim das suspensões vigentes para o cargo
    public function ativasPorCargo(int $cargoId, string $data): array
    {
        $st = $this->pdo->prepare(
            'SELECT candidato_id, MAX(data_fim) AS data_fim FROM punicao_candidato
             WHERE cargo_id = ? AND data_inicio <= ? AND data_fim >= ? GROUP BY candidato_id'
        );
        $st->execute([$cargoId, $data, $data]);
        $mapa = [];
        foreach ($st->fetchAll() as $r) {
            $mapa[(int)$r['candidato_id']] = $r['data_fim'];
        }
        return $mapa;
    }

    public function listar(): array
    {
        return $this->pdo->query(
            'SELECT p.*, c.nome, c.cpf, g.titulo_cargo
             FROM punicao_candidato p
             JOIN candidato c ON c.id = p.candidato_id
             JOIN cargo g ON g.id = p.cargo_id
             ORDER BY p.data_fim DESC, p.id DESC'
        )->fetchAll();
    }

    public function contarAtivas(string $data): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM punicao_candidato WHERE data_inicio <= ? AND data_fim >= ?');
        $st->execute([$data, $data]);
        return (int)$st->fetchColumn();
    }
}
