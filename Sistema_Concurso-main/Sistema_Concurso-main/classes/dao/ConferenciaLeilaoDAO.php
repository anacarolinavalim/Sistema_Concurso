<?php
class ConferenciaLeilaoDAO extends DAO
{
    public function buscar(int $leilaoId, int $inscricaoId): ?ConferenciaLeilao
    {
        $st = $this->pdo->prepare('SELECT * FROM conferencia_leilao WHERE leilao_id = ? AND inscricao_id = ?');
        $st->execute([$leilaoId, $inscricaoId]);
        $r = $st->fetch();
        return $r ? ConferenciaLeilao::fromArray($r) : null;
    }

    // Insere ou atualiza (uma conferência por leilão + inscrição)
    public function salvar(ConferenciaLeilao $c): void
    {
        $itens = implode(',', $c->getItensConferidos());
        $obs = $c->getObservacao() !== '' ? $c->getObservacao() : null;
        $avaliador = $c->getAvaliadorId() > 0 ? $c->getAvaliadorId() : null;
        if ($c->getID() > 0) {
            $st = $this->pdo->prepare(
                'UPDATE conferencia_leilao SET itens_conferidos = ?, status = ?, observacao = ?, avaliador_id = ?, data_conferencia = ? WHERE id = ?'
            );
            $st->execute([$itens, $c->getStatus(), $obs, $avaliador, $c->getDataConferencia(), $c->getID()]);
            return;
        }
        $st = $this->pdo->prepare(
            'INSERT INTO conferencia_leilao (leilao_id, inscricao_id, itens_conferidos, status, observacao, avaliador_id, data_conferencia)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$c->getLeilaoId(), $c->getInscricaoId(), $itens, $c->getStatus(), $obs, $avaliador, $c->getDataConferencia()]);
        $c->setID((int)$this->pdo->lastInsertId());
    }

    // Mapa inscricao_id => ConferenciaLeilao
    public function listarPorLeilao(int $leilaoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM conferencia_leilao WHERE leilao_id = ?');
        $st->execute([$leilaoId]);
        $mapa = [];
        foreach ($st->fetchAll() as $r) {
            $mapa[(int)$r['inscricao_id']] = ConferenciaLeilao::fromArray($r);
        }
        return $mapa;
    }
}
