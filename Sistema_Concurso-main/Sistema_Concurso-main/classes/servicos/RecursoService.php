<?php
// RF04 – gestão de recursos protocolados presencialmente.
class RecursoService
{
    public static function registrar(int $inscricaoId, string $descricao): array
    {
        $erros = [];
        if (!Calendario::janelaRecursoAberta()) {
            $erros[] = 'Fora do prazo de recursos (' . dataBr(Calendario::cfg('recurso_data_inicio')) . ' e '
                . dataBr(Calendario::cfg('recurso_data_fim')) . ', das ' . Calendario::cfg('recurso_hora_inicio')
                . ' às ' . Calendario::cfg('recurso_hora_fim') . ').';
        }
        if (comprimento($descricao) < 10) {
            $erros[] = 'Descreva o recurso (mínimo de 10 caracteres).';
        }
        $insc = (new InscricaoDAO())->buscarPorId($inscricaoId);
        if ($insc === null || !$insc->estaAtiva()) {
            $erros[] = 'A inscrição informada não existe ou não está ativa.';
        }
        $dao = new RecursoDAO();
        if ($insc !== null && $dao->existePorInscricao($inscricaoId)) {
            $erros[] = 'Já existe um recurso protocolado para esta inscrição.';
        }
        if ($erros) {
            return ['erros' => $erros, 'id' => null];
        }

        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $r = new Recurso();
            $r->setInscricaoId($inscricaoId);
            $r->setDescricao($descricao);
            $r->setDataProtocolo(Calendario::agora()->format('Y-m-d H:i:s'));
            $r->setRegistradoPor(Sessao::adminId());
            $r->cadastrarProtocolo();
            Auditoria::registrar('RECURSO_PROTOCOLADO', 'recurso', $r->getID(), 'Protocolo ' . $r->getNumeroProtocolo() . ' (inscrição ' . $inscricaoId . ')');
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao registrar recurso: ' . $e->getMessage());
            return ['erros' => ['Não foi possível registrar o recurso.'], 'id' => null];
        }
        return ['erros' => [], 'id' => $r->getID()];
    }

    public static function emitirParecer(int $recursoId, string $parecer, string $justificativa): ?string
    {
        if (!in_array($parecer, [Recurso::DEFERIDO, Recurso::INDEFERIDO], true)) {
            return 'Parecer inválido.';
        }
        if (comprimento($justificativa) < 5) {
            return 'Informe a justificativa do parecer.';
        }
        $r = (new RecursoDAO())->buscarPorId($recursoId);
        if ($r === null) {
            return 'Recurso não encontrado.';
        }
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $r->atualizarParecer($parecer, $justificativa);
            Auditoria::registrar('RECURSO_PARECER', 'recurso', $recursoId, $r->getNumeroProtocolo() . ' - ' . $parecer);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao emitir parecer: ' . $e->getMessage());
            return 'Não foi possível salvar o parecer.';
        }
        return null;
    }
}
