<?php
// RF05 – leilão de vagas, check-in documental, atribuição, desistência e suspensão de 120 dias.
class LeilaoService
{
    private static function carregar(int $leilaoId, ?string &$erro): ?LeilaoVaga
    {
        $l = (new LeilaoVagaDAO())->buscarPorId($leilaoId);
        if ($l === null) {
            $erro = 'Leilão não encontrado.';
        }
        return $l;
    }

    public static function alterarStatus(int $leilaoId, string $novo): ?string
    {
        $erro = null;
        $l = self::carregar($leilaoId, $erro);
        if ($l === null) {
            return $erro;
        }
        if (!in_array($novo, [LeilaoVaga::STATUS_AGENDADO, LeilaoVaga::STATUS_EM_ANDAMENTO, LeilaoVaga::STATUS_ENCERRADO], true)) {
            return 'Status inválido.';
        }
        if ($novo === LeilaoVaga::STATUS_EM_ANDAMENTO) {
            $l->iniciarChamada();
        } else {
            (new LeilaoVagaDAO())->atualizarStatus($leilaoId, $novo);
        }
        Auditoria::registrar('LEILAO_STATUS', 'leilao_vaga', $leilaoId, 'Novo status: ' . $novo);
        return null;
    }

    // Monta a lista de chamada (ordem de classificação) com a situação de cada candidato.
    public static function montarChamada(LeilaoVaga $l): array
    {
        $cargo = (new CargoDAO())->buscarPorId($l->getCargoId());
        if ($cargo === null) {
            return ['fonte' => 'Cargo não encontrado', 'linhas' => []];
        }
        $classDao = new ClassificacaoDAO();
        $fonte = 'Classificação ao vivo (ainda não há classificação publicada)';
        $itens = null;
        foreach (['Definitiva', 'Preliminar'] as $tipo) {
            $c = $classDao->ultima($tipo);
            if ($c !== null) {
                $itens = $classDao->itens((int)$c['id'], $l->getCargoId());
                $fonte = 'Classificação ' . $tipo . ' gerada em ' . dataHoraBr($c['data_geracao']);
                break;
            }
        }
        if ($itens === null) {
            $itens = (new MotorClassificacao())->classificarCargo($cargo);
        }

        $hoje = Calendario::hoje();
        $contDao = new ContratoLeilaoDAO();
        $conf = (new ConferenciaLeilaoDAO())->listarPorLeilao($l->getID());
        $contratados = $contDao->candidatosContratadosNoLeilao($l->getID());
        $suspensos = (new PunicaoCandidatoDAO())->ativasPorCargo($l->getCargoId(), $hoje);
        $outros = $contDao->ativosOutrosLeiloes($l->getID());

        foreach ($itens as $k => $r) {
            $cid = (int)$r['candidato_id'];
            $iid = (int)$r['inscricao_id'];
            $itens[$k]['conferencia'] = isset($conf[$iid]) ? $conf[$iid]->getStatus() : null;
            $itens[$k]['conferencia_obs'] = isset($conf[$iid]) ? $conf[$iid]->getObservacao() : '';
            $itens[$k]['contratado'] = $contratados[$cid] ?? null;
            $itens[$k]['suspenso_ate'] = $suspensos[$cid] ?? null;
            $itens[$k]['outro_contrato'] = $outros[$cid] ?? null;
        }
        return ['fonte' => $fonte, 'linhas' => $itens];
    }

    // Validações comuns às operações sobre uma inscrição dentro de um leilão.
    private static function validarContexto(int $leilaoId, int $inscricaoId, ?string &$erro, ?LeilaoVaga &$leilao, ?Inscricao &$insc): bool
    {
        $leilao = self::carregar($leilaoId, $erro);
        if ($leilao === null) {
            return false;
        }
        if ($leilao->getStatus() !== LeilaoVaga::STATUS_EM_ANDAMENTO) {
            $erro = 'O leilão precisa estar "Em andamento" (clique em "Iniciar chamada").';
            return false;
        }
        $insc = (new InscricaoDAO())->buscarPorId($inscricaoId);
        if ($insc === null || $insc->getCargoId() !== $leilao->getCargoId()) {
            $erro = 'A inscrição não pertence ao cargo deste leilão.';
            return false;
        }
        if (!$insc->estaAtiva()) {
            $erro = 'A inscrição não está ativa (cancelada ou desclassificada).';
            return false;
        }
        return true;
    }

    // RF05.2 – check-in e conferência documental
    public static function conferir(int $leilaoId, int $inscricaoId, array $itens, string $observacao): ?string
    {
        $erro = null;
        $leilao = null;
        $insc = null;
        if (!self::validarContexto($leilaoId, $inscricaoId, $erro, $leilao, $insc)) {
            return $erro;
        }
        $exigidos = array_keys(DocumentoInscricao::TIPOS);
        if ($insc->getTempoServicoDias() === 0) {
            $exigidos = array_values(array_diff($exigidos, ['contagem_tempo']));
        }
        $marcados = array_values(array_intersect($exigidos, $itens));
        if (count($marcados) !== count($exigidos)) {
            return 'Confirme a apresentação de TODOS os documentos originais e cópias (frente e verso). '
                . 'Se houver documentação incompleta ou divergente, desclassifique o candidato.';
        }
        $dao = new ConferenciaLeilaoDAO();
        $c = $dao->buscar($leilaoId, $inscricaoId);
        if ($c === null) {
            $c = new ConferenciaLeilao();
            $c->setLeilaoId($leilaoId);
            $c->setInscricaoId($inscricaoId);
        }
        $c->setItensConferidos($marcados);
        $c->setStatus(ConferenciaLeilao::CONFERIDO);
        $c->setObservacao(cortar($observacao, 250));
        $c->setAvaliadorId(Sessao::adminId());
        $c->setDataConferencia(Calendario::agora()->format('Y-m-d H:i:s'));
        $dao->salvar($c);
        Auditoria::registrar('LEILAO_CHECKIN', 'inscricao', $inscricaoId, 'Leilão ' . $leilaoId . ': documentos conferidos');
        return null;
    }

    // RF05.3 – desclassificação por omissão, documentação incompleta ou divergência
    public static function desclassificar(int $leilaoId, int $inscricaoId, string $motivo): ?string
    {
        $erro = null;
        $leilao = null;
        $insc = null;
        if (!self::validarContexto($leilaoId, $inscricaoId, $erro, $leilao, $insc)) {
            return $erro;
        }
        if (comprimento($motivo) < 5) {
            return 'Informe o motivo da desclassificação.';
        }
        if ((new ContratoLeilaoDAO())->candidatoContratadoNoLeilao($leilaoId, $insc->getCandidatoId())) {
            return 'O candidato já foi contratado neste leilão.';
        }
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $agora = Calendario::agora()->format('Y-m-d H:i:s');
            (new InscricaoDAO())->atualizarStatus($inscricaoId, Inscricao::STATUS_DESCLASSIFICADA, $agora);
            $dao = new ConferenciaLeilaoDAO();
            $c = $dao->buscar($leilaoId, $inscricaoId);
            if ($c === null) {
                $c = new ConferenciaLeilao();
                $c->setLeilaoId($leilaoId);
                $c->setInscricaoId($inscricaoId);
            }
            $c->setStatus(ConferenciaLeilao::DESCLASSIFICADO);
            $c->setObservacao(cortar($motivo, 250));
            $c->setAvaliadorId(Sessao::adminId());
            $c->setDataConferencia($agora);
            $dao->salvar($c);
            InscricaoService::avancarVersao($inscricaoId, $agora);
            Auditoria::registrar('LEILAO_DESCLASSIFICACAO', 'inscricao', $inscricaoId, 'Leilão ' . $leilaoId . ': ' . $motivo);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao desclassificar: ' . $e->getMessage());
            return 'Não foi possível desclassificar o candidato.';
        }
        return null;
    }

    // RF05.4 – atribuição da vaga (e desistência declarada de contrato anterior, se houver)
    public static function atribuirVaga(int $leilaoId, int $inscricaoId): ?string
    {
        $erro = null;
        $leilao = null;
        $insc = null;
        if (!self::validarContexto($leilaoId, $inscricaoId, $erro, $leilao, $insc)) {
            return $erro;
        }
        $conf = (new ConferenciaLeilaoDAO())->buscar($leilaoId, $inscricaoId);
        if ($conf === null || $conf->getStatus() !== ConferenciaLeilao::CONFERIDO) {
            return 'Realize o check-in e a conferência documental antes de atribuir a vaga.';
        }
        $candId = $insc->getCandidatoId();
        $contDao = new ContratoLeilaoDAO();
        if ($contDao->candidatoContratadoNoLeilao($leilaoId, $candId)) {
            return 'O candidato já foi contratado neste leilão.';
        }
        $punicao = (new PunicaoCandidatoDAO())->bloqueioAtivo($candId, $leilao->getCargoId(), Calendario::hoje());
        if ($punicao !== null) {
            return 'Candidato suspenso para este cargo até ' . dataBr($punicao->getDataFim()) . ' (RN04).';
        }

        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $trava = $pdo->prepare('SELECT id FROM leilao_vaga WHERE id = ? FOR UPDATE');
            $trava->execute([$leilaoId]);
            $trava->fetchAll();
            if ($contDao->contarOcupadas($leilaoId) >= $leilao->getVagasOfertadas()) {
                $pdo->rollBack();
                return 'Todas as vagas deste leilão já foram preenchidas.';
            }
            $hoje = Calendario::hoje();
            foreach ($contDao->listarAtivosDoCandidato($candId) as $anterior) {
                $contDao->registrarEncerramento(
                    (int)$anterior['id'], ContratoLeilao::DESISTIDO, 'Declarada', $hoje,
                    'Desistência declarada ao vencer vaga no leilão #' . $leilaoId
                );
                Auditoria::registrar('CONTRATO_DESISTENCIA_DECLARADA', 'contrato_leilao', (int)$anterior['id'],
                    'Candidato ' . $candId . ' assumiu nova vaga (leilão ' . $leilaoId . '); sem suspensão');
            }
            $c = new ContratoLeilao();
            $c->setLeilaoId($leilaoId);
            $c->setCandidatoId($candId);
            $c->setInscricaoId($inscricaoId);
            $c->setDataContratacao(Calendario::agora()->format('Y-m-d H:i:s'));
            $c->setStatus(ContratoLeilao::ATIVO);
            $contDao->inserir($c);
            Auditoria::registrar('VAGA_ATRIBUIDA', 'contrato_leilao', $c->getID(),
                'Leilão ' . $leilaoId . '; candidato ' . $candId . '; inscrição ' . $inscricaoId);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao atribuir vaga: ' . $e->getMessage());
            return 'Não foi possível atribuir a vaga.';
        }
        return null;
    }

    // RF05.5 / RN04 – desistência/abandono do contratado: contrato "Desistido" + suspensão de 120 dias no cargo
    public static function registrarDesistencia(int $contratoId, string $dataDesistencia, string $motivo): ?string
    {
        $contDao = new ContratoLeilaoDAO();
        $contrato = $contDao->buscarPorId($contratoId);
        if ($contrato === null || $contrato->getStatus() !== ContratoLeilao::ATIVO) {
            return 'Contrato não encontrado ou não está ativo.';
        }
        if (!Validador::dataValida($dataDesistencia)) {
            return 'Data da desistência inválida.';
        }
        if ($dataDesistencia > Calendario::hoje()) {
            return 'A data formal da desistência não pode ser futura.';
        }
        if (comprimento($motivo) < 5) {
            return 'Informe o motivo da desistência.';
        }
        $leilao = (new LeilaoVagaDAO())->buscarPorId($contrato->getLeilaoId());
        if ($leilao === null) {
            return 'Leilão do contrato não encontrado.';
        }
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $contDao->registrarEncerramento($contratoId, ContratoLeilao::DESISTIDO, 'Abandono', $dataDesistencia, cortar($motivo, 250));
            $p = new PunicaoCandidato();
            $p->setCandidatoId($contrato->getCandidatoId());
            $p->setCargoId($leilao->getCargoId());
            $p->setContratoId($contratoId);
            $p->setMotivo(cortar($motivo, 250));
            $p->aplicarSuspensao120Dias($dataDesistencia);
            Auditoria::registrar('CONTRATO_DESISTENCIA', 'contrato_leilao', $contratoId,
                'Suspensão de ' . DIAS_SUSPENSAO . ' dias até ' . $p->getDataFim() . ' (cargo ' . $leilao->getCargoId() . ')');
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao registrar desistência: ' . $e->getMessage());
            return 'Não foi possível registrar a desistência.';
        }
        return null;
    }

    public static function encerrarContrato(int $contratoId): ?string
    {
        $dao = new ContratoLeilaoDAO();
        $c = $dao->buscarPorId($contratoId);
        if ($c === null || $c->getStatus() !== ContratoLeilao::ATIVO) {
            return 'Contrato não encontrado ou não está ativo.';
        }
        $dao->registrarEncerramento($contratoId, ContratoLeilao::ENCERRADO, null, null, 'Contrato encerrado ao término da designação');
        Auditoria::registrar('CONTRATO_ENCERRADO', 'contrato_leilao', $contratoId, 'Encerramento normal');
        return null;
    }
}
