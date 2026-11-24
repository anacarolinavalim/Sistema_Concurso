<?php
class Validador
{
    public static function cpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int)$cpf[$i] * (($t + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int)$cpf[$t] !== $digito) {
                return false;
            }
        }
        return true;
    }

    public static function email(string $email): bool
    {
        return strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function dataValida(string $data): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $data);
        return $d instanceof DateTime && $d->format('Y-m-d') === $data;
    }

    public static function dataHoraValida(string $dataHora, string $formato): bool
    {
        $d = DateTime::createFromFormat($formato, $dataHora);
        return $d instanceof DateTime && $d->format($formato) === $dataHora;
    }

    // Idade completa em anos na data de referência; -1 se a data for inválida ou futura.
    public static function idadeEm(string $nascimento, DateTime $referencia): int
    {
        if (!self::dataValida($nascimento)) {
            return -1;
        }
        $n = new DateTime($nascimento);
        if ($n > $referencia) {
            return -1;
        }
        return (int)$n->diff($referencia)->y;
    }

    public static function telefone(string $telefone): bool
    {
        $d = preg_replace('/\D/', '', $telefone);
        return strlen($d) === 10 || strlen($d) === 11;
    }

    public static function senha(string $senha): ?string
    {
        if (strlen($senha) < 8) {
            return 'A senha deve ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
            return 'A senha deve conter letras e números.';
        }
        return null;
    }
}
