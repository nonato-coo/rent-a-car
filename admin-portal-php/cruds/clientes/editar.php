<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    header('Location: /cruds/clientes/listar.php?err=' . urlencode('ID de cliente no válido.'));
    exit;
}

$servicio = new Recursos('/clientes');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre'    => trim($_POST['nombre'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'telefono'  => trim($_POST['telefono'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
    ];
    $res = $servicio->actualizar($id, $datos);
    if ($res['ok']) {
        header('Location: /cruds/clientes/listar.php?msg=' . urlencode('Cliente actualizado correctamente.'));
        exit;
    }
    $error = extraerErrorApi($res['data'], 'Error al actualizar cliente (' . $res['status'] . ').');
} else {
    $lista = $servicio->listar();
    $encontrados = $servicio->filtrar($lista, fn(array $c): bool => (int)($c['id'] ?? 0) === $id);
    $datos = $encontrados[0] ?? null;
    if (!$datos) {
        header('Location: /cruds/clientes/listar.php?err=' . urlencode('No se encontró el cliente #' . $id));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente #<?= e($id) ?> — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.6rem 1.2rem; background: #d97706; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container"><div class="card">
        <h2 style="margin-top:0;">Editar Cliente #<?= e($id) ?></h2>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="/cruds/clientes/editar.php?id=<?= e($id) ?>">
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <label>Nombre completo</label><input type="text" name="nombre" value="<?= e($datos['nombre'] ?? '') ?>" required>
            <label>Correo electrónico</label><input type="email" name="email" value="<?= e($datos['email'] ?? '') ?>" required>
            <label>Teléfono</label><input type="text" name="telefono" value="<?= e($datos['telefono'] ?? '') ?>" required>
            <label>Dirección</label><input type="text" name="direccion" value="<?= e($datos['direccion'] ?? '') ?>" required>
            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn">Guardar Cambios</button>
                <a href="/cruds/clientes/listar.php" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div></div>
</body>
</html>