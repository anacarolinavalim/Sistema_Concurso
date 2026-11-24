<?php
class DocumentoInscricaoDAO extends DAO
{
    public function listarPorInscricao(int $inscricaoId): array
    {
        $st = $this->pdo->prepare('SELECT * FROM documento_inscricao WHERE inscricao_id = ? ORDER BY id');
        $st->execute([$inscricaoId]);
        $lista = [];
        foreach ($st->fetchAll() as $r) {
            $lista[] = DocumentoInscricao::fromArray($r);
        }
        return $lista;
    }

    public function buscarPorId(int $id): ?DocumentoInscricao
    {
        $st = $this->pdo->prepare('SELECT * FROM documento_inscricao WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ? DocumentoInscricao::fromArray($r) : null;
    }

    public function inserir(DocumentoInscricao $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO documento_inscricao (inscricao_id, tipo_documento, nome_original, caminho_arquivo, data_upload) VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$d->getInscricaoId(), $d->getTipoDocumento(), $d->getNomeOriginal(), $d->getCaminhoArquivo(), $d->getDataUpload()]);
        $id = (int)$this->pdo->lastInsertId();
        $d->setID($id);
        return $id;
    }

    public function atualizarArquivo(DocumentoInscricao $d): bool
    {
        $st = $this->pdo->prepare('UPDATE documento_inscricao SET nome_original = ?, caminho_arquivo = ?, data_upload = ? WHERE id = ?');
        return $st->execute([$d->getNomeOriginal(), $d->getCaminhoArquivo(), $d->getDataUpload(), $d->getID()]);
    }
}
