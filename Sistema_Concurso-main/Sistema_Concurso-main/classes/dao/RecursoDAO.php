<?php
class RecursoDAO extends DAO
{
    // Insere o recurso e gera o número de protocolo (REC-AAAA-00001) a partir do ID.
    public function inserir(Recurso $r): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO recurso (inscricao_id, numero_protocolo, data_protocolo, descricao, parecer_deferimento, registrado_por)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $provisorio = 'TMP' . bin2hex(random_bytes(8));
        $st->execute([
            $r->getInscricaoId(), $provisorio, $r->getDataProtocolo(), $r->getDescricao(),
            Recurso::PENDENTE, $r->getRegistradoPor() > 0 ? $r->getRegistradoPor() : null,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        $ano = substr($r->getDataProtocolo(), 0, 4);
        $numero = 'REC-' . $ano . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);
        $up = $this->pdo->prepare('UPDATE recurso SET numero_protocolo = ? WHERE id = ?');
        $up->execute([$numero, $id]);
        $r->setID($id);
        $r->setNumeroProtocolo($numero);
        return $id;
    }

    public function buscarPorId(int $id): ?Recurso
    {
        $st = $this->pdo->prepare('SELECT * FROM recurso WHERE id = ?');
        $st->execute([$id]);
        $x = $st->fetch();
        return $x ? Recurso::fromArray($x) : null;
    }

    public function buscarPorInscricao(int $inscricaoId): ?Recurso
    {
        $st = $this->pdo->prepare('SELECT * FROM recurso WHERE inscricao_id = ?');
        $st->execute([$inscricaoId]);
        $x = $st->fetch();
        return $x ? Recurso::fromArray($x) : null;
    }

    public function existePorInscricao(int $inscricaoId): bool
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM recurso WHERE inscricao_id = ?');
        $st->execute([$inscricaoId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function atualizarParecer(Recurso $r): bool
    {
        $st = $this->pdo->prepare('UPDATE recurso SET parecer_deferimento = ?, parecer_justificativa = ?, data_parecer = ? WHERE id = ?');
        return $st->execute([$r->getParecerDeferimento(), $r->getParecerJustificativa(), $r->getDataParecer(), $r->getID()]);
    }

    public function listar(): array
    {
        return $this->pdo->query(
            'SELECT r.id, r.numero_protocolo, r.data_protocolo, r.parecer_deferimento, r.data_parecer,
                    i.id AS inscricao_id, c.nome, c.cpf, g.titulo_cargo
             FROM recurso r
             JOIN inscricao i ON i.id = r.inscricao_id
             JOIN candidato c ON c.id = i.candidato_id
             JOIN cargo g ON g.id = i.cargo_id
             ORDER BY r.id DESC'
        )->fetchAll();
    }

    public function listarPublico(): array
    {
        return $this->pdo->query(
            "SELECT r.numero_protocolo, r.parecer_deferimento, r.data_parecer, c.nome, g.titulo_cargo
             FROM recurso r
             JOIN inscricao i ON i.id = r.inscricao_id
             JOIN candidato c ON c.id = i.candidato_id
             JOIN cargo g ON g.id = i.cargo_id
             WHERE r.parecer_deferimento <> 'Pendente'
             ORDER BY g.titulo_cargo, r.numero_protocolo"
        )->fetchAll();
    }

    public function contarPendentes(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM recurso WHERE parecer_deferimento = 'Pendente'")->fetchColumn();
    }
}
