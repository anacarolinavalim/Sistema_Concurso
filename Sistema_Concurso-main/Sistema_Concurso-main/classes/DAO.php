<?php
// Classe base de todos os DAOs (Data Access Objects).
// Regra: DAOs nunca abrem transação; quem controla a transação é a camada de serviço.
abstract class DAO
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conectar();
    }
}
