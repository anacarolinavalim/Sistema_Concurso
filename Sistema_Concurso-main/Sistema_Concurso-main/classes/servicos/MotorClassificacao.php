<?php
// RF03.1 – motor de ordenação conforme os critérios de desempate (Anexo III), em ordem rigorosa:
//   1) habilitação/escolaridade mínima do cargo; 2) maior tempo de serviço na rede municipal;
//   3) maior titulação; 4) maior idade (RN05). Último recurso: ordem de inscrição (determinismo).
class MotorClassificacao
{
    public function classificarCargo(Cargo $cargo): array
    {
        $pdo = Database::conectar();
        $st = $pdo->prepare(
            "SELECT i.id AS inscricao_id, i.candidato_id, i.tempo_servico_dias, i.nivel_titulacao, i.status,
                    c.nome, c.cpf, c.data_nascimento
             FROM inscricao i
             JOIN candidato c ON c.id = i.candidato_id
             WHERE i.cargo_id = ? AND i.status = 'Ativa'"
        );
        $st->execute([$cargo->getID()]);
        $linhas = $st->fetchAll();

        foreach ($linhas as $k => $r) {
            $linhas[$k]['rank_titulacao'] = Titulacao::rank($r['nivel_titulacao']);
            $linhas[$k]['habilitado'] = $cargo->candidatoHabilitado($r['nivel_titulacao']) ? 1 : 0;
        }

        usort($linhas, function (array $a, array $b): int {
            return $this->comparar($a, $b);
        });

        $posicao = 1;
        foreach ($linhas as $k => $r) {
            $linhas[$k]['posicao'] = $posicao++;
        }
        return $linhas;
    }

    private function comparar(array $a, array $b): int
    {
        $c = (int)$b['habilitado'] <=> (int)$a['habilitado'];
        if ($c !== 0) {
            return $c;
        }
        $c = (int)$b['tempo_servico_dias'] <=> (int)$a['tempo_servico_dias'];
        if ($c !== 0) {
            return $c;
        }
        $c = (int)$b['rank_titulacao'] <=> (int)$a['rank_titulacao'];
        if ($c !== 0) {
            return $c;
        }
        // Datas ISO (AAAA-MM-DD): a menor (mais antiga) vem primeiro
        $c = strcmp((string)$a['data_nascimento'], (string)$b['data_nascimento']);
        if ($c !== 0) {
            return $c;
        }
        return (int)$a['inscricao_id'] <=> (int)$b['inscricao_id'];
    }

    // Gera e grava a classificação (snapshot) de todos os cargos. Retorna o ID da classificação.
    public function gerarSnapshot(string $tipo, int $adminId): int
    {
        $pdo = Database::conectar();
        $dao = new ClassificacaoDAO();
        try {
            $pdo->beginTransaction();
            $id = $dao->criar($tipo, $adminId);
            $total = 0;
            foreach ((new CargoDAO())->listarTodos(false) as $cargo) {
                foreach ($this->classificarCargo($cargo) as $r) {
                    $dao->inserirItem($id, $cargo->getID(), $r);
                    $total++;
                }
            }
            Auditoria::registrar('CLASSIFICACAO_' . strtoupper($tipo), 'classificacao', $id, 'Classificação ' . $tipo . ' gerada com ' . $total . ' inscrições');
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
