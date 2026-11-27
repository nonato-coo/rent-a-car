<?php
require_once __DIR__ . '/../config.php';

class Auth {
    public static function estaAutenticado(): bool {
        return !empty($_SESSION['token']) || !empty($_SESSION['usuario']);
    }

    public static function requerirLogin(): void {
        if (!self::estaAutenticado()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function iniciarSesion(array $datosUsuario, ?string $token = null): void {
        $_SESSION['usuario'] = $datosUsuario;
        if ($token) {
            $_SESSION['token'] = $token;
        }
    }

    public static function cerrarSesion(): void {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}