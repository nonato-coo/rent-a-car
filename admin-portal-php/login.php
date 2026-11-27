<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/ApiClient.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Helpers.php';

if (Auth::estaAutenticado()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Por favor, completa tu correo y contraseña.';
    } else {
        $respuesta = ApiClient::post('/login', [
            'email'    => $email,
            'password' => $password,
        ]);

        if ($respuesta['ok'] && is_array($respuesta['data'])) {
            $datos   = $respuesta['data'];
            $token   = $datos['access_token'] ?? ($datos['token'] ?? null);
            $usuario = $datos['usuario'] ?? ['email' => $email];

            Auth::iniciarSesion($usuario, $token);
            header('Location: /index.php');
            exit;
        } else {
            $error = extraerErrorApi($respuesta['data'], 'Credenciales incorrectas o error de conexión (' . $respuesta['status'] . ').');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión — Rent a Car Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 360px; }
        h2 { margin-top: 0; color: #1e293b; }
        label { display: block; margin-top: 1rem; font-weight: bold; color: #334155; }
        input { width: 100%; padding: 0.6rem; margin-top: 0.3rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; margin-top: 1.5rem; padding: 0.7rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .error { background: #fee2e2; color: #b91c1c; padding: 0.7rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Rent a Car — Admin</h2>

        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/login.php">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>