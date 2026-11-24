<?php
// Regras de negócio da inscrição (RF02, RN01, RN02, RN03, RNF04).
class InscricaoService
{
    public static function gerarCodigo(): string
    {
        $h = strtoupper(bin2hex(random_bytes(6)));
        return substr($h, 0, 4) . '-' . substr($h, 4, 4) . '-' . substr($h, 8, 4);
    }

    // Cria (id = null) ou altera a inscrição do candidato. Retorna ['erros' => [], 'id' => ?int]
    public static function salvar(Candidato $cand, ?int $inscricaoId, array $post, array $files): array
    {
        $erros = [];
        if (!Calendario::inscricoesAbertas()) {
            return ['erros' => ['Fora do prazo de inscrição: não é possível incluir ou alterar inscrições.'], 'id' => null];
        }

        $inscDao = new InscricaoDAO();
        $docDao = new DocumentoInscricaoDAO();
        $insc = null;

        if ($inscricaoId !== null) {
            $insc = $inscDao->buscarPorId($inscricaoId);
            if ($insc === null || $insc->getCandidatoId() !== $cand->getID()) {
                return ['erros' => ['Inscrição não encontrada.'], 'id' => null];
            }
            if (!$insc->estaAtiva()) {
                return ['erros' => ['Somente inscrições ativas podem ser alteradas.'], 'id' => null];
            }
        }

        // Cargo
        $cargoId = (int)($post['cargo_id'] ?? 0);
        $cargo = (new CargoDAO())->buscarPorId($cargoId);
        if ($cargo === null || !$cargo->getAtivo()) {
            $erros[] = 'Selecione um cargo válido.';
        }

        // Idade mínima na data da inscrição (RF01.2)
        if (!$cand->verificarIdadeMinima()) {
            $erros[] = 'É necessário ter no mínimo ' . IDADE_MINIMA . ' anos na data da inscrição.';
        }

        // Limite de inscrições (RN01) e cargo repetido
        if ($insc === null) {
            $tmp = new Inscricao();
            $tmp->setCandidatoId($cand->getID());
            if (!$tmp->validarLimiteInscricoes()) {
                $erros[] = 'Limite atingido: cada CPF pode ter no máximo ' . MAX_INSCRICOES . ' inscrições ativas.';
            }
        }
        if ($cargo !== null && $inscDao->existeAtivaMesmoCargo($cand->getID(), $cargoId, $insc ? $insc->getID() : 0)) {
            $erros[] = 'Você já possui uma inscrição ativa para este cargo.';
        }

        // Trava de cargo em recurso (RF04.2 / RN03)
        if ($insc !== null && $cargo !== null && $cargoId !== $insc->getCargoId() && Recurso::impedirTrocaCargo($insc->getID())) {
            $erros[] = 'Não é possível trocar o cargo de uma inscrição com recurso protocolado.';
        }

        // Tempo de serviço
        $anosTxt = trim((string)($post['tempo_anos'] ?? '0'));
        $diasTxt = trim((string)($post['tempo_dias'] ?? '0'));
        $tempoTotal = 0;
        if (!ctype_digit($anosTxt === '' ? '0' : $anosTxt) || !ctype_digit($diasTxt === '' ? '0' : $diasTxt)) {
            $erros[] = 'Informe o tempo de serviço com números inteiros (anos e dias).';
        } else {
            $anos = (int)$anosTxt;
            $dias = (int)$diasTxt;
            if ($anos > 60 || $dias > 364) {
                $erros[] = 'Tempo de serviço inválido (anos até 60 e dias de 0 a 364).';
            } else {
                $tempoTotal = $anos * 365 + $dias;
            }
        }

        // Titulação
        $nivel = (string)($post['nivel_titulacao'] ?? '');
        if (!Titulacao::valido($nivel)) {
            $erros[] = 'Selecione a titulação.';
        } elseif ($cargo !== null && !$cargo->permiteTitulacao($nivel)) {
            $erros[] = 'A titulação selecionada não é aceita para o cargo escolhido.';
        }

        // Documentos (RF02.3)
        $existentes = [];
        if ($insc !== null) {
            foreach ($docDao->listarPorInscricao($insc->getID()) as $d) {
                $existentes[$d->getTipoDocumento()] = $d;
            }
        }
        $novos = [];
        foreach (DocumentoInscricao::TIPOS as $tipo => $rotulo) {
            $f = $files['doc_' . $tipo] ?? null;
            $enviado = is_array($f) && (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
            if ($enviado) {
                $msg = DocumentoInscricao::validarAnexo($f);
                if ($msg !== null) {
                    $erros[] = $rotulo . ': ' . $msg;
                } else {
                    $novos[$tipo] = $f;
                }
            } else {
                $obrigatorio = ($tipo !== 'contagem_tempo') || $tempoTotal > 0;
                if ($obrigatorio && !isset($existentes[$tipo])) {
                    $erros[] = $rotulo . ': anexe o comprovante.';
                }
            }
        }

        if ($erros) {
            return ['erros' => $erros, 'id' => null];
        }

        $pdo = Database::conectar();
        $movidos = [];
        $antigos = [];
        try {
            $pdo->beginTransaction();
            $agora = Calendario::agora()->format('Y-m-d H:i:s');
            $nova = ($insc === null);
            if ($nova) {
                $insc = new Inscricao();
                $insc->setCandidatoId($cand->getID());
                $insc->setDataInscricao($agora);
                $insc->setStatus(Inscricao::STATUS_ATIVA);
                $insc->setVersaoAtual(1);
            } else {
                $insc->setVersaoAtual($insc->getVersaoAtual() + 1);
            }
            $insc->setCodigoValidacao(self::gerarCodigo());
            $insc->setCargoId($cargoId);
            $insc->setTempoServicoDias($tempoTotal);
            $insc->setNivelTitulacao($nivel);
            $insc->setUltimaAlteracao($agora);
            if ($nova) {
                $insc->criarInscricao();
            } else {
                $insc->atualizarInscricao();
            }

            foreach ($novos as $tipo => $f) {
                $caminho = DocumentoInscricao::salvarArquivo($f, $insc->getID());
                $movidos[] = $caminho;
                if (isset($existentes[$tipo])) {
                    $d = $existentes[$tipo];
                    $antigos[] = $d->getCaminhoArquivo();
                    $d->setCaminhoArquivo($caminho);
                    $d->setNomeOriginal(nomeArquivoSeguro((string)$f['name']));
                    $d->setDataUpload($agora);
                    $docDao->atualizarArquivo($d);
                } else {
                    $d = new DocumentoInscricao();
                    $d->setInscricaoId($insc->getID());
                    $d->setTipoDocumento($tipo);
                    $d->setCaminhoArquivo($caminho);
                    $d->setNomeOriginal(nomeArquivoSeguro((string)$f['name']));
                    $d->setDataUpload($agora);
                    $docDao->inserir($d);
                }
            }

            self::registrarVersao($insc->getID());
            Auditoria::registrar(
                $nova ? 'INSCRICAO_CRIADA' : 'INSCRICAO_ALTERADA',
                'inscricao',
                $insc->getID(),
                'cargo=' . $cargo->getTitulo() . '; tempo_dias=' . $tempoTotal . '; titulacao=' . $nivel . '; versao=' . $insc->getVersaoAtual()
            );
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($movidos as $c) {
                DocumentoInscricao::removerArquivo($c);
            }
            error_log('Erro ao salvar inscrição: ' . $e->getMessage());
            return ['erros' => ['Não foi possível salvar a inscrição. Tente novamente.'], 'id' => null];
        }

        foreach ($antigos as $c) {
            DocumentoInscricao::removerArquivo($c);
        }
        return ['erros' => [], 'id' => $insc->getID()];
    }

    // Grava o "retrato" da inscrição (RNF04) com a versão e o código atuais já salvos na tabela.
    public static function registrarVersao(int $inscricaoId): void
    {
        $d = (new InscricaoDAO())->buscarDetalhe($inscricaoId);
        if ($d === null) {
            throw new RuntimeException('Inscrição não encontrada para versionamento.');
        }
        $docs = [];
        foreach ((new DocumentoInscricaoDAO())->listarPorInscricao($inscricaoId) as $doc) {
            $docs[] = [
                'tipo' => $doc->getTipoDocumento(),
                'nome_original' => $doc->getNomeOriginal(),
                'data_upload' => $doc->getDataUpload(),
            ];
        }
        $dados = [
            'candidato' => [
                'nome' => $d['nome'], 'cpf' => $d['cpf'], 'email' => $d['email'], 'telefone' => $d['telefone'],
                'data_nascimento' => $d['data_nascimento'], 'endereco' => $d['endereco'], 'rg' => $d['rg'],
                'titulo_eleitor' => $d['titulo_eleitor'],
            ],
            'cargo' => ['id' => (int)$d['cargo_id'], 'titulo' => $d['titulo_cargo']],
            'tempo_servico_dias' => (int)$d['tempo_servico_dias'],
            'nivel_titulacao' => $d['nivel_titulacao'],
            'status' => $d['status'],
            'data_inscricao' => $d['data_inscricao'],
            'ultima_alteracao' => $d['ultima_alteracao'],
            'documentos' => $docs,
        ];
        $a = Sessao::ator();
        (new VersaoInscricaoDAO())->inserir(
            $inscricaoId,
            (int)$d['versao_atual'],
            (string)$d['codigo_validacao'],
            json_encode($dados, JSON_UNESCAPED_UNICODE),
            date('Y-m-d H:i:s'),
            ipCliente(),
            $a['tipo'],
            $a['id'],
            $a['nome']
        );
    }

    // Avança a versão (novo código/comprovante) após mudança de status ou ajuste administrativo.
    public static function avancarVersao(int $inscricaoId, string $ultimaAlteracao): void
    {
        $dao = new InscricaoDAO();
        $i = $dao->buscarPorId($inscricaoId);
        if ($i === null) {
            throw new RuntimeException('Inscrição não encontrada.');
        }
        $i->setVersaoAtual($i->getVersaoAtual() + 1);
        $i->setCodigoValidacao(self::gerarCodigo());
        $i->setUltimaAlteracao($ultimaAlteracao);
        $dao->atualizar($i);
        self::registrarVersao($inscricaoId);
    }

    // RF02.4 – cancelamento (exclusão lógica) dentro do prazo
    public static function cancelar(Candidato $cand, int $inscricaoId): ?string
    {
        if (!Calendario::inscricoesAbertas()) {
            return 'Fora do prazo de inscrição: não é possível cancelar inscrições.';
        }
        $dao = new InscricaoDAO();
        $i = $dao->buscarPorId($inscricaoId);
        if ($i === null || $i->getCandidatoId() !== $cand->getID()) {
            return 'Inscrição não encontrada.';
        }
        if (!$i->estaAtiva()) {
            return 'Esta inscrição não está ativa.';
        }
        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $agora = Calendario::agora()->format('Y-m-d H:i:s');
            $dao->atualizarStatus($inscricaoId, Inscricao::STATUS_CANCELADA, $agora);
            self::avancarVersao($inscricaoId, $agora);
            Auditoria::registrar('INSCRICAO_CANCELADA', 'inscricao', $inscricaoId, 'Cancelada pelo candidato');
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao cancelar inscrição: ' . $e->getMessage());
            return 'Não foi possível cancelar a inscrição.';
        }
        return null;
    }

    // RF04.3 – ajuste operacional pela administração, com base em recurso deferido. O cargo NUNCA é alterado aqui.
    public static function ajusteAdmin(int $inscricaoId, array $post): array
    {
        $inscDao = new InscricaoDAO();
        $insc = $inscDao->buscarPorId($inscricaoId);
        if ($insc === null) {
            return ['Inscrição não encontrada.'];
        }
        $recurso = (new RecursoDAO())->buscarPorInscricao($inscricaoId);
        if ($recurso === null || $recurso->getParecerDeferimento() !== Recurso::DEFERIDO) {
            return ['O ajuste de dados só é permitido para inscrições com recurso deferido.'];
        }
        $cargo = (new CargoDAO())->buscarPorId($insc->getCargoId());
        $candDao = new CandidatoDAO();
        $cand = $candDao->buscarPorId($insc->getCandidatoId());
        if ($cargo === null || $cand === null) {
            return ['Dados da inscrição incompletos.'];
        }

        $antes = [
            'nome' => $cand->getNome(), 'nascimento' => $cand->getDataNascimento(), 'telefone' => $cand->getTelefone(),
            'email' => $cand->getEmail(), 'endereco' => $cand->getEndereco(), 'rg' => $cand->getRg(),
            'titulo' => $cand->getTituloEleitor(), 'tempo_dias' => $insc->getTempoServicoDias(), 'titulacao' => $insc->getNivelTitulacao(),
        ];

        $cand->setNome(trim((string)($post['nome'] ?? '')));
        $cand->setDataNascimento(trim((string)($post['data_nascimento'] ?? '')));
        $cand->setTelefone(somenteDigitos((string)($post['telefone'] ?? '')));
        $cand->setEmail(strtolower(trim((string)($post['email'] ?? ''))));
        $cand->setEndereco(trim((string)($post['endereco'] ?? '')));
        $cand->setRg(trim((string)($post['rg'] ?? '')));
        $cand->setTituloEleitor(somenteDigitos((string)($post['titulo_eleitor'] ?? '')));
        $erros = $cand->validarDados();

        $anosTxt = trim((string)($post['tempo_anos'] ?? '0'));
        $diasTxt = trim((string)($post['tempo_dias'] ?? '0'));
        $tempoTotal = $insc->getTempoServicoDias();
        if (!ctype_digit($anosTxt === '' ? '0' : $anosTxt) || !ctype_digit($diasTxt === '' ? '0' : $diasTxt) || (int)$anosTxt > 60 || (int)$diasTxt > 364) {
            $erros[] = 'Tempo de serviço inválido (anos até 60 e dias de 0 a 364).';
        } else {
            $tempoTotal = (int)$anosTxt * 365 + (int)$diasTxt;
        }
        $nivel = (string)($post['nivel_titulacao'] ?? '');
        if (!Titulacao::valido($nivel) || !$cargo->permiteTitulacao($nivel)) {
            $erros[] = 'Titulação inválida para o cargo da inscrição.';
        }
        if ($erros) {
            return $erros;
        }

        $depois = [
            'nome' => $cand->getNome(), 'nascimento' => $cand->getDataNascimento(), 'telefone' => $cand->getTelefone(),
            'email' => $cand->getEmail(), 'endereco' => $cand->getEndereco(), 'rg' => $cand->getRg(),
            'titulo' => $cand->getTituloEleitor(), 'tempo_dias' => $tempoTotal, 'titulacao' => $nivel,
        ];
        $mudancas = [];
        foreach ($depois as $campo => $valor) {
            if ((string)$antes[$campo] !== (string)$valor) {
                $mudancas[] = $campo . ': "' . $antes[$campo] . '" -> "' . $valor . '"';
            }
        }
        if (!$mudancas) {
            return ['Nenhum dado foi alterado.'];
        }

        $pdo = Database::conectar();
        try {
            $pdo->beginTransaction();
            $agora = Calendario::agora()->format('Y-m-d H:i:s');
            $candDao->atualizarDados($cand);
            $insc->setTempoServicoDias($tempoTotal);
            $insc->setNivelTitulacao($nivel);
            $insc->setUltimaAlteracao($agora);
            $inscDao->atualizar($insc);
            self::avancarVersao($inscricaoId, $agora);
            foreach ($inscDao->listarPorCandidato($cand->getID()) as $outra) {
                if ((int)$outra['id'] !== $inscricaoId && $outra['status'] === Inscricao::STATUS_ATIVA) {
                    self::avancarVersao((int)$outra['id'], $agora);
                }
            }
            Auditoria::registrar('AJUSTE_ADMIN_RECURSO', 'inscricao', $inscricaoId,
                'Protocolo ' . $recurso->getNumeroProtocolo() . '; ' . implode('; ', $mudancas));
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro no ajuste administrativo: ' . $e->getMessage());
            return ['Não foi possível salvar o ajuste.'];
        }
        return [];
    }
}
