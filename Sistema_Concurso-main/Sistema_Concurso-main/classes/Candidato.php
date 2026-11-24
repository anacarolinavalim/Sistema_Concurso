<?php
class Candidato extends Usuario
{
    private string $cpf = '';
    private string $telefone = '';
    private string $dataNascimento = '';
    private string $endereco = '';
    private string $rg = '';
    private string $tituloEleitor = '';

    public function __construct(int $id = 0, string $nome = '', string $email = '', string $senhaHash = '', string $cpf = '')
    {
        parent::__construct($id, $nome, $email, $senhaHash);
        $this->cpf = $cpf;
    }

    public static function fromArray(array $r): Candidato
    {
        $c = new Candidato((int)$r['id'], (string)$r['nome'], (string)$r['email'], (string)$r['senha_hash'], (string)$r['cpf']);
        $c->telefone = (string)$r['telefone'];
        $c->dataNascimento = (string)$r['data_nascimento'];
        $c->endereco = (string)$r['endereco'];
        $c->rg = (string)$r['rg'];
        $c->tituloEleitor = (string)$r['titulo_eleitor'];
        $c->setDataCriacao((string)$r['data_criacao']);
        return $c;
    }

    public function setCpf($v) { $this->cpf = (string)$v; }
    public function getCpf(): string { return $this->cpf; }
    public function setTelefone($v) { $this->telefone = (string)$v; }
    public function getTelefone(): string { return $this->telefone; }
    public function setDataNascimento($v) { $this->dataNascimento = (string)$v; }
    public function getDataNascimento(): string { return $this->dataNascimento; }
    public function setEndereco($v) { $this->endereco = (string)$v; }
    public function getEndereco(): string { return $this->endereco; }
    public function setRg($v) { $this->rg = (string)$v; }
    public function getRg(): string { return $this->rg; }
    public function setTituloEleitor($v) { $this->tituloEleitor = (string)$v; }
    public function getTituloEleitor(): string { return $this->tituloEleitor; }

    // RF01.2 – idade mínima de 18 anos na data da inscrição
    public function verificarIdadeMinima(?DateTime $referencia = null): bool
    {
        $referencia = $referencia ?? Calendario::agora();
        return Validador::idadeEm($this->dataNascimento, $referencia) >= IDADE_MINIMA;
    }

    // Validação dos dados pessoais (RF01.3). Retorna lista de erros (vazia = ok).
    public function validarDados(): array
    {
        $erros = [];
        $palavras = preg_split('/\s+/', trim($this->nome), -1, PREG_SPLIT_NO_EMPTY);
        if (count($palavras) < 2 || comprimento($this->nome) > 150) {
            $erros[] = 'Informe o nome completo.';
        }
        $cpfOk = Validador::cpf($this->cpf);
        if (!$cpfOk) {
            $erros[] = 'CPF inválido.';
        }
        $emailOk = Validador::email($this->email);
        if (!$emailOk) {
            $erros[] = 'E-mail inválido.';
        }
        if (!Validador::dataValida($this->dataNascimento)) {
            $erros[] = 'Data de nascimento inválida.';
        } elseif (!$this->verificarIdadeMinima()) {
            $erros[] = 'É necessário ter no mínimo ' . IDADE_MINIMA . ' anos para se inscrever.';
        }
        if (!Validador::telefone($this->telefone)) {
            $erros[] = 'Telefone inválido (informe DDD + número).';
        }
        if (comprimento(trim($this->endereco)) < 8 || comprimento($this->endereco) > 255) {
            $erros[] = 'Informe o endereço de residência completo.';
        }
        if (comprimento(trim($this->rg)) < 5 || comprimento($this->rg) > 20) {
            $erros[] = 'RG/CIN inválido.';
        }
        if (!preg_match('/^\d{12}$/', $this->tituloEleitor)) {
            $erros[] = 'O título de eleitor deve ter 12 dígitos.';
        }
        $dao = new CandidatoDAO();
        if ($cpfOk && $this->id === 0 && $dao->cpfEmUso($this->cpf)) {
            $erros[] = 'Já existe um cadastro com este CPF.';
        }
        if ($emailOk && $dao->emailEmUso($this->email, $this->id)) {
            $erros[] = 'Já existe um cadastro com este e-mail.';
        }
        return $erros;
    }

    // RF01.1 – autocadastro. Retorna lista de erros (vazia = cadastro realizado).
    public function cadastrar(string $senhaPlana): array
    {
        $erros = $this->validarDados();
        $erroSenha = Validador::senha($senhaPlana);
        if ($erroSenha !== null) {
            $erros[] = $erroSenha;
        }
        if ($erros) {
            return $erros;
        }
        $this->senhaHash = self::gerarHash($senhaPlana);
        $this->setDataCriacao(date('Y-m-d H:i:s'));
        try {
            (new CandidatoDAO())->inserir($this);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                return ['Já existe um cadastro com este CPF ou e-mail.'];
            }
            throw $ex;
        }
        return [];
    }

    // Autentica por CPF ou e-mail. Retorna o candidato ou null.
    public static function autenticar(string $login, string $senha): ?Candidato
    {
        $dao = new CandidatoDAO();
        $login = trim($login);
        if (strpos($login, '@') !== false) {
            $c = $dao->buscarPorEmail(strtolower($login));
        } else {
            $c = $dao->buscarPorCpf(somenteDigitos($login));
        }
        if ($c !== null && $c->verificarSenha($senha)) {
            return $c;
        }
        return null;
    }

    public function login(string $senha): bool
    {
        if (!$this->verificarSenha($senha)) {
            return false;
        }
        Sessao::loginCandidato($this);
        Auditoria::registrar('LOGIN', 'candidato', $this->id, 'Login de candidato');
        return true;
    }

    public function logout(): void
    {
        Auditoria::registrar('LOGOUT', 'candidato', $this->id, 'Logout de candidato');
        Sessao::sairCandidato();
    }

    public function alterarSenha(string $senhaAtual, string $novaSenha): bool
    {
        if (!$this->verificarSenha($senhaAtual) || Validador::senha($novaSenha) !== null) {
            return false;
        }
        $hash = self::gerarHash($novaSenha);
        (new CandidatoDAO())->atualizarSenha($this->id, $hash);
        $this->senhaHash = $hash;
        Auditoria::registrar('SENHA_ALTERADA', 'candidato', $this->id, 'Senha alterada pelo candidato');
        return true;
    }

    public function exibirPainel(): void
    {
        redirecionar('candidato/painel.php');
    }

    public function gerenciarInscricoes(): array
    {
        return (new InscricaoDAO())->listarPorCandidato($this->id);
    }

    // Só é possível excluir o cadastro se não houver nenhuma inscrição vinculada (integridade do processo).
    public function deletarCadastro(): bool
    {
        if ($this->gerenciarInscricoes()) {
            return false;
        }
        $ok = (new CandidatoDAO())->excluir($this->id);
        if ($ok) {
            Auditoria::registrar('CADASTRO_EXCLUIDO', 'candidato', $this->id, 'Cadastro excluído a pedido do titular (LGPD)');
            Sessao::sairCandidato();
        }
        return $ok;
    }
}
