<?php
class Admin extends Usuario
{
    public function __construct(int $id = 0, string $nome = '', string $email = '', string $senhaHash = '')
    {
        parent::__construct($id, $nome, $email, $senhaHash);
    }

    public static function fromArray(array $r): Admin
    {
        $a = new Admin((int)$r['id'], (string)$r['nome'], (string)$r['email'], (string)$r['senha_hash']);
        $a->setDataCriacao((string)$r['data_criacao']);
        return $a;
    }

    // Retorna lista de erros (vazia = administrador cadastrado).
    public function cadastrar(string $senhaPlana): array
    {
        $erros = [];
        if (comprimento(trim($this->nome)) < 3 || comprimento($this->nome) > 150) {
            $erros[] = 'Informe o nome do administrador.';
        }
        if (!Validador::email($this->email)) {
            $erros[] = 'E-mail inválido.';
        } elseif ((new AdminDAO())->buscarPorEmail($this->email) !== null) {
            $erros[] = 'Já existe um administrador com este e-mail.';
        }
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
            (new AdminDAO())->inserir($this);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                return ['Já existe um administrador com este e-mail.'];
            }
            throw $ex;
        }
        return [];
    }

    public static function autenticar(string $email, string $senha): ?Admin
    {
        $a = (new AdminDAO())->buscarPorEmail(strtolower(trim($email)));
        if ($a !== null && $a->verificarSenha($senha)) {
            return $a;
        }
        return null;
    }

    public function login(string $senha): bool
    {
        if (!$this->verificarSenha($senha)) {
            return false;
        }
        Sessao::loginAdmin($this);
        Auditoria::registrar('LOGIN', 'administrador', $this->id, 'Login de administrador');
        return true;
    }

    public function logout(): void
    {
        Auditoria::registrar('LOGOUT', 'administrador', $this->id, 'Logout de administrador');
        Sessao::sairAdmin();
    }

    public function alterarSenha(string $senhaAtual, string $novaSenha): bool
    {
        if (!$this->verificarSenha($senhaAtual) || Validador::senha($novaSenha) !== null) {
            return false;
        }
        $hash = self::gerarHash($novaSenha);
        (new AdminDAO())->atualizarSenha($this->id, $hash);
        $this->senhaHash = $hash;
        Auditoria::registrar('SENHA_ALTERADA', 'administrador', $this->id, 'Senha alterada pelo administrador');
        return true;
    }

    public function exibirPainel(): void
    {
        redirecionar('admin/index.php');
    }
}
