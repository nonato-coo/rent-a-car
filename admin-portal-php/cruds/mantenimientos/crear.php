<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$vehiculos = (new Recursos('/vehiculos'))->listar();
$carpetaData = __DIR__ . '/../../data';
$archivoOrdenes = $carpetaData . '/mantenimientos.json';

$error = '';
$datos = [
    'vehiculo_id' => '',
    'fecha'       => date('Y-m-d'),
    'detalle'     => '',
    'costo'       => '0.00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculoId = (int) ($_POST['vehiculo_id'] ?? 0);
    $fecha = trim($_POST['fecha'] ?? date('Y-m-d'));
    $detalle = trim($_POST['detalle'] ?? '');
    $costoRaw = trim($_POST['costo'] ?? '0');

    $datos = [
        'vehiculo_id' => $vehiculoId,
        'fecha'       => $fecha,
        'detalle'     => $detalle,
        'costo'       => $costoRaw,
    ];

    if ($vehiculoId <= 0) {
        $error = 'Debes seleccionar un vehículo válido.';
    } elseif ($detalle === '') {
        $error = 'El detalle del mantenimiento es obligatorio.';
    } elseif (!is_numeric($costoRaw) || (float) $costoRaw < 0) {
        $error = 'El costo no puede ser un número negativo.';
    } else {
        if (!is_dir($carpetaData)) {
            mkdir($carpetaData, 0777, true);
        }

        $ordenes = [];
        if (is_file($archivoOrdenes)) {
            $decoded = json_decode((string) file_get_contents($archivoOrdenes), true);
            if (is_array($decoded)) {
                $ordenes = $decoded;
            }
        }

        $siguienteId = count($ordenes) + 1;
        $ordenes[] = [
            'id'          => $siguienteId,
            'vehiculo_id' => $vehiculoId,
            'fecha'       => $fecha,
            'detalle'     => $detalle,
            'costo'       => round((float) $costoRaw, 2),
        ];

        file_put_contents($archivoOrdenes, json_encode($ordenes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: /cruds/mantenimientos/listar.php?msg=' . urlencode('Orden de mantenimiento registrada correctamente.'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Mantenimiento — Rent a Car</title>
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
    <div class="container">
        <div class="card">
            <h2 style="margin-top:0;">Registrar Mantenimiento</h2>
            <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" action="/cruds/mantenimientos/crear.php">
                <label>Vehículo</label>
                <select name="vehiculo_id" required>
                    <option value="">Seleccione un vehículo...</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <?php $vid = (int) ($v['id'] ?? 0); ?>
                        <option value="<?= e($vid) ?>" <?= (int) $datos['vehiculo_id'] === $vid ? 'selected' : '' ?>>
                            #<?= e($vid) ?> — <?= e(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?> (Stock: <?= e($v['stock_total'] ?? 0) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Fecha</label>
                <input type="date" name="fecha" value="<?= e($datos['fecha']) ?>" required>

                <label>Detalle del mantenimiento</label>
                <input type="text" name="detalle" placeholder="Ej. Cambio de aceite y revisión de frenos" value="<?= e($datos['detalle']) ?>" required>

                <label>Costo ($)</label>
                <input type="number" step="0.01" min="0" name="costo" value="<?= e($datos['costo']) ?>" required>

                <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                    <button type="submit" class="btn">Guardar Mantenimiento</button>
                    <a href="/cruds/mantenimientos/listar.php" class="btn btn-sec">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>