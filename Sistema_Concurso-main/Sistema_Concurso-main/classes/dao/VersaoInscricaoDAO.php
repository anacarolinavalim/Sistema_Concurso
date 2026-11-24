<?php
class VersaoInscricaoDAO extends DAO
{
    public function inserir(int $inscricaoId, int $versao, string $codigo, string $json, string $dataRegistro,
                            string $ip, string $usuarioTipo, ?int $usuarioId, string $usuarioNome): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO inscricao_versao (inscricao_id, versao, codigo_validacao, dados_json, data_registro, ip, usuario_tipo, usuario_id, usuario_nome)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$inscricaoId, $versao, $codigo, $json, $dataRegistro, $ip, $usuarioTipo, $usuarioId, $usuarioNome]);
        return (int)$this->pdo->lastInsertId();
    }

    public function buscar(int $inscricaoId, int $versao): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM inscricao_versao WHERE inscricao_id = ? AND versao = ?');
        $st->execute([$inscricaoId, $versao]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function listarPorInscricao(int $inscricaoId): array
    {
        $st = $this->pdo->prepare(
            'SELECT id, versao, codigo_validacao, data_registro, ip, usuario_tipo, usuario_nome
             FROM inscricao_versao WHERE inscricao_id = ? ORDER BY versao DESC'
        );
        $st->execute([$inscricaoId]);
        return $st->fetchAll();
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT v.*, i.versao_atual FROM inscricao_versao v JOIN inscricao i ON i.id = v.inscricao_id
             WHERE v.codigo_validacao = ?'
        );
        $st->execute([$codigo]);
        $r = $st->fetch();
        return $r ?: null;
    }
}
