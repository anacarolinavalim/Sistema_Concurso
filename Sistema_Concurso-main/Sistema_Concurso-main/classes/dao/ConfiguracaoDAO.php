<?php
class ConfiguracaoDAO extends DAO
{
    private static ?array $cache = null;

    private function carregar(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach ($this->pdo->query('SELECT chave, valor FROM configuracao')->fetchAll() as $r) {
                self::$cache[$r['chave']] = (string)$r['valor'];
            }
        }
        return self::$cache;
    }

    public function obter(string $chave, string $padrao = ''): string
    {
        $c = $this->carregar();
        return $c[$chave] ?? $padrao;
    }

    public function definir(string $chave, string $valor): void
    {
        $st = $this->pdo->prepare('REPLACE INTO configuracao (chave, valor) VALUES (?, ?)');
        $st->execute([$chave, $valor]);
        self::$cache = null;
    }
}
