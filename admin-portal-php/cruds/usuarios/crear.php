<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ApiClient.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$error = '';
$datos = ['nombre' => '', 'email' => '', 'password' => '', 'rol' => 'cliente', 'telefono' => '', 'direccion' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre'    => trim($_POST['nombre'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'password'  => $_POST['password'] ?? '',
        'rol'       => trim($_POST['rol'] ?? 'cliente'),
        'telefono'  => trim($_POST['telefono'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
    ];
    $res = ApiClient::post('/registro', $datos);
    if ($res['ok']) {
        header('Location: /cruds/usuarios/listar.php?msg=' . urlencode('Usuario registrado correctamente.'));
        exit;
    }
    $error = extraerErrorApi($res['data'], 'Error al registrar usuario (' . $res['status'] . ').');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container"><div class="card">
        <h2 style="margin-top:0;">Registrar Nuevo Usuario</h2>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="/cruds/usuarios/crear.php" autocomplete="off">
            <label>Nombre completo</label>
            <input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" autocomplete="off" required>

            <label>Correo electrónico</label>
            <input type="email" name="email" value="<?= e($datos['email']) ?>" autocomplete="off" required>

            <label>Contraseña</label>
            <input type="password" name="password" autocomplete="new-password" required>

            <label>Rol</label>
            <select name="rol" required>
                <option value="cliente" <?= $datos['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                <option value="admin" <?= $datos['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>

            <label>Teléfono (para Cliente)</label>
            <input type="text" name="telefono" value="<?= e($datos['telefono']) ?>" autocomplete="off">

            <label>Dirección (para Cliente)</label>
            <input type="text" name="direccion" value="<?= e($datos['direccion']) ?>" autocomplete="off">

            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn">Guardar Usuario</button>
                <a href="/cruds/usuarios/listar.php" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div></div>
</body>
</html>