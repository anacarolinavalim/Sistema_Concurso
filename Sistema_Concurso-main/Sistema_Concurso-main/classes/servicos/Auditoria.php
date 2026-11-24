<?php
// RNF02/RNF04 – log auditável: quem, quando, o quê e IP.
class Auditoria
{
    public static function registrar(string $acao, string $entidade, ?int $entidadeId = null, string $detalhes = ''): void
    {
        $a = Sessao::ator();
        (new LogDAO())->inserir($a['tipo'], $a['id'], $a['nome'], $acao, $entidade, $entidadeId, $detalhes, ipCliente());
    }
}
