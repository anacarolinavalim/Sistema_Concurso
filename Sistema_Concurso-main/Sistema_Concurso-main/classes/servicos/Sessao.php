<?php
// Controle de sessão e permissões (Candidato x Administrador).
class Sessao
{
    private static ?Candidato $candidatoCache = null;
    private static ?Admin $adminCache = null;

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('EDU' . substr(md5(BASE_URL), 0, 8));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => BASE_URL . '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();

        $agora = time();
        if (isset($_SESSION['ultimo_acesso']) && ($agora - (int)$_SESSION['ultimo_acesso']) > SESSAO_TIMEOUT) {
            unset($_SESSION['cand_id'], $_SESSION['cand_nome'], $_SESSION['adm_id'], $_SESSION['adm_nome']);
        }
        $_SESSION['ultimo_acesso'] = $agora;
    }

    public static function loginCandidato(Candidato $c): void
    {
        session_regenerate_id(true);
        $_SESSION['cand_id'] = $c->getID();
        $_SESSION['cand_nome'] = $c->getNome();
        self::$candidatoCache = $c;
    }

    public static function loginAdmin(Admin $a): void
    {
        session_regenerate_id(true);
        $_SESSION['adm_id'] = $a->getID();
        $_SESSION['adm_nome'] = $a->getNome();
        self::$adminCache = $a;
    }

    public static function candidatoId(): int
    {
        return (int)($_SESSION['cand_id'] ?? 0);
    }

    public static function adminId(): int
    {
        return (int)($_SESSION['adm_id'] ?? 0);
    }

    public static function candidato(): ?Candidato
    {
        if (self::$candidatoCache === null && self::candidatoId() > 0) {
            self::$candidatoCache = (new CandidatoDAO())->buscarPorId(self::candidatoId());
            if (self::$candidatoCache === null) {
                unset($_SESSION['cand_id'], $_SESSION['cand_nome']);
            }
        }
        return self::$candidatoCache;
    }

    public static function admin(): ?Admin
    {
        if (self::$adminCache === null && self::adminId() > 0) {
            self::$adminCache = (new AdminDAO())->buscarPorId(self::adminId());
            if (self::$adminCache === null) {
                unset($_SESSION['adm_id'], $_SESSION['adm_nome']);
            }
        }
        return self::$adminCache;
    }

    public static function exigirCandidato(): Candidato
    {
        $c = self::candidato();
        if ($c === null) {
            flash('erro', 'Faça login para continuar.');
            redirecionar('login.php');
        }
        return $c;
    }

    public static function exigirAdmin(): Admin
    {
        $a = self::admin();
        if ($a === null) {
            flash('erro', 'Acesso restrito à administração. Faça login.');
            redirecionar('admin/login.php');
        }
        return $a;
    }

    public static function sairCandidato(): void
    {
        unset($_SESSION['cand_id'], $_SESSION['cand_nome']);
        self::$candidatoCache = null;
        session_regenerate_id(true);
    }

    public static function sairAdmin(): void
    {
        unset($_SESSION['adm_id'], $_SESSION['adm_nome']);
        self::$adminCache = null;
        session_regenerate_id(true);
    }

    // Quem está agindo (para os logs de auditoria)
    public static function ator(): array
    {
        if (self::adminId() > 0) {
            return ['tipo' => 'administrador', 'id' => self::adminId(), 'nome' => (string)($_SESSION['adm_nome'] ?? '')];
        }
        if (self::candidatoId() > 0) {
            return ['tipo' => 'candidato', 'id' => self::candidatoId(), 'nome' => (string)($_SESSION['cand_nome'] ?? '')];
        }
        return ['tipo' => 'anonimo', 'id' => null, 'nome' => 'Visitante'];
    }
}
