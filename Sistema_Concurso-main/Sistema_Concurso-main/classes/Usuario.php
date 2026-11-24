<?php
abstract class Usuario
{
    protected int $id = 0;
    protected string $nome = '';
    protected string $email = '';
    protected string $senhaHash = '';
    protected string $dataCriacao = '';

    public function __construct(int $id = 0, string $nome = '', string $email = '', string $senhaHash = '')
    {
        $this->id = $id;
        $this->nome = $nome;
        $this->email = $email;
        $this->senhaHash = $senhaHash;
        $this->dataCriacao = date('Y-m-d H:i:s');
    }

    public function setID($id) { $this->id = (int)$id; }
    public function getID(): int { return $this->id; }
    public function setNome($nome) { $this->nome = (string)$nome; }
    public function getNome(): string { return $this->nome; }
    public function setEmail($email) { $this->email = (string)$email; }
    public function getEmail(): string { return $this->email; }
    public function setSenha($senhaHash) { $this->senhaHash = (string)$senhaHash; }
    public function getSenha(): string { return $this->senhaHash; }
    public function setDataCriacao($data) { $this->dataCriacao = (string)$data; }
    public function getDataCriacao(): string { return $this->dataCriacao; }

    public function verificarSenha(string $senha): bool
    {
        return $this->senhaHash !== '' && password_verify($senha, $this->senhaHash);
    }

    public static function gerarHash(string $senha): string
    {
        return password_hash($senha, PASSWORD_DEFAULT);
    }

    // Métodos comuns (README): login, logout, alterarSenha e exibirPainel
    abstract public function login(string $senha): bool;

    abstract public function logout(): void;

    abstract public function alterarSenha(string $senhaAtual, string $novaSenha): bool;

    abstract public function exibirPainel(): void;
}
