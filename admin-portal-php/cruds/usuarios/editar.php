<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    header('Location: /cruds/usuarios/listar.php?err=' . urlencode('ID de usuario no válido.'));
    exit;
}

$servicio = new Recursos('/usuarios');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email'  => trim($_POST['email'] ?? ''),
        'rol'    => trim($_POST['rol'] ?? 'cliente'),
    ];
    if (trim($_POST['password'] ?? '') !== '') {
        $datos['password'] = $_POST['password'];
    }
    $res = $servicio->actualizar($id, $datos);
    if ($res['ok']) {
        header('Location: /cruds/usuarios/listar.php?msg=' . urlencode('Usuario actualizado correctamente.'));
        exit;
    }
    $error = extraerErrorApi($res['data'], 'Error al actualizar usuario (' . $res['status'] . ').');
} else {
    $lista = $servicio->listar();
    $encontrados = $servicio->filtrar($lista, fn(array $u): bool => (int)($u['id'] ?? 0) === $id);
    $datos = $encontrados[0] ?? null;
    if (!$datos) {
        header('Location: /cruds/usuarios/listar.php?err=' . urlencode('No se encontró el usuario #' . $id));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario #<?= e($id) ?> — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.6rem 1.2rem; background: #d97706; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container"><div class="card">
        <h2 style="margin-top:0;">Editar Usuario #<?= e($id) ?></h2>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="/cruds/usuarios/editar.php?id=<?= e($id) ?>">
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <label>Nombre completo</label><input type="text" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
            <label>Correo electrónico</label><input type="email" name="email" value="<?= e($datos['email'] ?? '') ?>" required>
            <label>Nueva contraseña (opcional)</label><input type="password" name="password">
            <label>Rol</label>
            <select name="rol" required>
                <option value="cliente" <?= ($datos['rol'] ?? '') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                <option value="admin" <?= ($datos['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
            </select>
            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn">Guardar Cambios</button>
                <a href="/cruds/usuarios/listar.php" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div></div>
</body>
</html>