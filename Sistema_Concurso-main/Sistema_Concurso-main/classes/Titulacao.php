<?php
// Níveis de escolaridade/titulação e sua ordem de grandeza (usada no desempate).
class Titulacao
{
    const NIVEIS = [
        'fundamental'  => ['Ensino Fundamental', 0],
        'medio'        => ['Nível Médio', 1],
        'secretariado' => ['Secretariado', 2],
        'graduacao'    => ['Ensino Superior / Graduação', 3],
        'pos'          => ['Pós-Graduação', 4],
        'mestrado'     => ['Mestrado', 5],
    ];

    public static function codigos(): array
    {
        return array_keys(self::NIVEIS);
    }

    public static function todos(): array
    {
        $r = [];
        foreach (self::NIVEIS as $codigo => $dados) {
            $r[$codigo] = $dados[0];
        }
        return $r;
    }

    public static function valido(string $codigo): bool
    {
        return isset(self::NIVEIS[$codigo]);
    }

    public static function rotulo(string $codigo): string
    {
        return self::NIVEIS[$codigo][0] ?? $codigo;
    }

    public static function rank(string $codigo): int
    {
        return self::NIVEIS[$codigo][1] ?? 0;
    }
}
