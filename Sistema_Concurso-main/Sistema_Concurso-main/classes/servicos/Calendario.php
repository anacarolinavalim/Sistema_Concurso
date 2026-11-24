<?php
// Prazos do edital. Todas as regras de data/hora passam por aqui.
// A "data simulada" (opcional, em Admin > Configurações) permite testar as fases antes de 2027.
class Calendario
{
    const PADROES = [
        'inscricao_inicio'    => '2027-01-08 00:00:00',
        'inscricao_fim'       => '2027-01-10 23:59:59',
        'preliminar_data'     => '2027-01-14',
        'recurso_data_inicio' => '2027-01-16',
        'recurso_data_fim'    => '2027-01-17',
        'recurso_hora_inicio' => '12:00',
        'recurso_hora_fim'    => '16:30',
        'definitiva_data'     => '2027-01-20',
        'data_simulada'       => '',
    ];

    public static function cfg(string $chave): string
    {
        return (new ConfiguracaoDAO())->obter($chave, self::PADROES[$chave] ?? '');
    }

    public static function agora(): DateTime
    {
        $s = self::cfg('data_simulada');
        if ($s !== '') {
            $d = DateTime::createFromFormat('Y-m-d H:i:s', $s);
            if ($d instanceof DateTime) {
                return $d;
            }
        }
        return new DateTime();
    }

    public static function hoje(): string
    {
        return self::agora()->format('Y-m-d');
    }

    public static function simulada(): bool
    {
        return self::cfg('data_simulada') !== '';
    }

    private static function dt(string $chave): DateTime
    {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', self::cfg($chave));
        return $d instanceof DateTime ? $d : new DateTime(self::PADROES[$chave]);
    }

    public static function inscricaoInicio(): DateTime
    {
        return self::dt('inscricao_inicio');
    }

    public static function inscricaoFim(): DateTime
    {
        return self::dt('inscricao_fim');
    }

    // RN02 – edições permitidas apenas dentro do prazo (até 23h59 de 10/01/2027)
    public static function inscricoesAbertas(): bool
    {
        $a = self::agora();
        return $a >= self::dt('inscricao_inicio') && $a <= self::dt('inscricao_fim');
    }

    public static function inscricoesEncerradas(): bool
    {
        return self::agora() > self::dt('inscricao_fim');
    }

    // RF04.1 – recursos: 16 e 17/01/2027, das 12h às 16h30
    public static function janelaRecursoAberta(): bool
    {
        $a = self::agora();
        $dia = $a->format('Y-m-d');
        $hora = $a->format('H:i');
        return $dia >= self::cfg('recurso_data_inicio') && $dia <= self::cfg('recurso_data_fim')
            && $hora >= self::cfg('recurso_hora_inicio') && $hora <= self::cfg('recurso_hora_fim');
    }

    public static function recursosEncerrados(): bool
    {
        $fim = DateTime::createFromFormat('Y-m-d H:i', self::cfg('recurso_data_fim') . ' ' . self::cfg('recurso_hora_fim'));
        return $fim instanceof DateTime && self::agora() > $fim;
    }

    // A classificação só aparece para o público a partir da data programada.
    public static function classificacaoVisivel(string $tipo): bool
    {
        $chave = ($tipo === 'Definitiva') ? 'definitiva_data' : 'preliminar_data';
        return self::hoje() >= self::cfg($chave);
    }

    public static function faseAtual(): string
    {
        $a = self::agora();
        $hoje = $a->format('Y-m-d');
        if ($a < self::dt('inscricao_inicio')) {
            return 'Aguardando a abertura das inscrições';
        }
        if (self::inscricoesAbertas()) {
            return 'Inscrições abertas';
        }
        if ($hoje < self::cfg('preliminar_data')) {
            return 'Inscrições encerradas – aguarde a classificação preliminar';
        }
        if ($hoje < self::cfg('recurso_data_inicio')) {
            return 'Classificação preliminar publicada';
        }
        if (!self::recursosEncerrados()) {
            return 'Período de recursos';
        }
        if ($hoje < self::cfg('definitiva_data')) {
            return 'Recursos em análise – aguarde a classificação definitiva';
        }
        return 'Classificação definitiva publicada – leilão de vagas';
    }
}
