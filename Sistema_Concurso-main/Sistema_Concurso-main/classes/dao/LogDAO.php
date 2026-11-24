<?php
class LogDAO extends DAO
{
    public function inserir(string $tipo, ?int $usuarioId, string $nome, string $acao, string $entidade, ?int $entidadeId, string $detalhes, string $ip): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO log_auditoria (data_hora, usuario_tipo, usuario_id, usuario_nome, acao, entidade, entidade_id, detalhes, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            date('Y-m-d H:i:s'), $tipo, $usuarioId, substr($nome, 0, 150), substr($acao, 0, 60),
            substr($entidade, 0, 40), $entidadeId, $detalhes, $ip,
        ]);
    }

    public function listar(string $acao, string $usuario, string $entidade): array
    {
        $sql = 'SELECT * FROM log_auditoria WHERE 1 = 1';
        $p = [];
        if ($acao !== '') {
            $sql .= ' AND acao LIKE ?';
            $p[] = '%' . $acao . '%';
        }
        if ($usuario !== '') {
            $sql .= ' AND usuario_nome LIKE ?';
            $p[] = '%' . $usuario . '%';
        }
        if ($entidade !== '') {
            $sql .= ' AND entidade = ?';
            $p[] = $entidade;
        }
        $sql .= ' ORDER BY id DESC LIMIT 300';
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }
}
