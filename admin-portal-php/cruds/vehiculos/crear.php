<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$servicio = new Recursos('/vehiculos');
$error = '';
$datos = ['marca' => '', 'modelo' => '', 'anio' => date('Y'), 'categoria' => 'Sedán', 'precio_por_dia' => '', 'stock_total' => 1, 'habilitado' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'marca'          => trim($_POST['marca'] ?? ''),
        'modelo'         => trim($_POST['modelo'] ?? ''),
        'anio'           => (int)($_POST['anio'] ?? date('Y')),
        'categoria'      => trim($_POST['categoria'] ?? 'Sedán'),
        'precio_por_dia' => (float)($_POST['precio_por_dia'] ?? 0),
        'stock_total'    => (int)($_POST['stock_total'] ?? 1),
        'habilitado'     => isset($_POST['habilitado']) ? 1 : 0,
    ];
    $res = $servicio->crear($datos);
    if ($res['ok']) {
        header('Location: /cruds/vehiculos/listar.php?msg=' . urlencode('Vehículo creado correctamente.'));
        exit;
    }
    $error = extraerErrorApi($res['data'], 'Error al crear vehículo (' . $res['status'] . ').');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Vehículo — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container"><div class="card">
        <h2 style="margin-top:0;">Registrar Nuevo Vehículo</h2>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="/cruds/vehiculos/crear.php">
            <label>Marca</label><input type="text" name="marca" value="<?= e($datos['marca']) ?>" required>
            <label>Modelo</label><input type="text" name="modelo" value="<?= e($datos['modelo']) ?>" required>
            <label>Año</label><input type="number" name="anio" min="1990" max="2030" value="<?= e($datos['anio']) ?>" required>
            <label>Categoría</label>
            <select name="categoria" required>
                <?php foreach (['Sedán', 'SUV', 'Compacto', 'Pickup', 'Deportivo'] as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $datos['categoria'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Precio por día ($)</label><input type="number" step="0.01" min="1" name="precio_por_dia" value="<?= e($datos['precio_por_dia']) ?>" required>
            <label>Stock total</label><input type="number" min="0" name="stock_total" value="<?= e($datos['stock_total']) ?>" required>
            <div style="margin-top: 1rem;">
                <label style="display:inline-flex; align-items:center; gap:0.4rem; margin:0;">
                    <input type="checkbox" name="habilitado" value="1" <?= $datos['habilitado'] ? 'checked' : '' ?>>
                    Habilitado para alquiler
                </label>
            </div>
            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn">Guardar Vehículo</button>
                <a href="/cruds/vehiculos/listar.php" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div></div>
</body>
</html>