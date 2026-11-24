<?php
class LeilaoVagaDAO extends DAO
{
    public function inserir(LeilaoVaga $l): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO leilao_vaga (cargo_id, escolaridade, horario, vagas_ofertadas, status, data_leilao, ano_letivo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$l->getCargoId(), $l->getEscolaridade(), $l->getHorario(), $l->getVagasOfertadas(),
            $l->getStatus(), $l->getDataLeilao(), $l->getAnoLetivo()]);
        $id = (int)$this->pdo->lastInsertId();
        $l->setID($id);
        return $id;
    }

    public function atualizar(LeilaoVaga $l): bool
    {
        $st = $this->pdo->prepare(
            'UPDATE leilao_vaga SET cargo_id = ?, escolaridade = ?, horario = ?, vagas_ofertadas = ?, data_leilao = ?, ano_letivo = ?
             WHERE id = ?'
        );
        return $st->execute([$l->getCargoId(), $l->getEscolaridade(), $l->getHorario(), $l->getVagasOfertadas(),
            $l->getDataLeilao(), $l->getAnoLetivo(), $l->getID()]);
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $st = $this->pdo->prepare('UPDATE leilao_vaga SET status = ? WHERE id = ?');
        return $st->execute([$status, $id]);
    }

    public function buscarPorId(int $id): ?LeilaoVaga
    {
        $st = $this->pdo->prepare('SELECT * FROM leilao_vaga WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? LeilaoVaga::fromArray($r) : null;
    }

    public function listar(): array
    {
        return $this->pdo->query(
            "SELECT lv.*, g.titulo_cargo,
                    (SELECT COUNT(*) FROM contrato_leilao ct WHERE ct.leilao_id = lv.id AND ct.status IN ('Ativo','Encerrado')) AS ocupadas
             FROM leilao_vaga lv
             JOIN cargo g ON g.id = lv.cargo_id
             ORDER BY lv.data_leilao DESC, lv.id DESC"
        )->fetchAll();
    }
}
