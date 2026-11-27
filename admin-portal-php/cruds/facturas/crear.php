<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$extraerLista = static function ($raw, string $clave): array {
    if (is_array($raw) && isset($raw[$clave]) && is_array($raw[$clave])) {
        return $raw[$clave];
    }
    if (is_array($raw) && isset($raw['data']) && is_array($raw['data'])) {
        return $raw['data'];
    }
    return is_array($raw) ? $raw : [];
};

$servicio = new Recursos('/compras');
$recursosVehiculos = new Recursos('/vehiculos');
$vehiculos = $extraerLista($recursosVehiculos->listar(), 'vehiculos');

$error = '';
$datos = [
    'fecha'          => date('Y-m-d'),
    'vehiculo_id'    => '',
    'proveedor'      => '',
    'concepto'       => '',
    'cantidad'       => '1',
    'costo_unitario' => '',
    'estado'         => 'completada',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['fecha']          = trim($_POST['fecha'] ?? date('Y-m-d'));
    $datos['vehiculo_id']    = trim($_POST['vehiculo_id'] ?? '');
    $datos['proveedor']      = trim($_POST['proveedor'] ?? '');
    $datos['concepto']       = trim($_POST['concepto'] ?? '');
    $datos['cantidad']       = trim($_POST['cantidad'] ?? '1');
    $datos['costo_unitario'] = trim($_POST['costo_unitario'] ?? '');
    $datos['estado']         = trim($_POST['estado'] ?? 'completada');

    $estadosValidos = ['completada', 'pendiente', 'anulada'];
    $vehiculoId  = filter_var($datos['vehiculo_id'], FILTER_VALIDATE_INT);
    $cantidadNum = filter_var($datos['cantidad'], FILTER_VALIDATE_INT);
    $costoNum    = is_numeric($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : -1;

    $vehiculoSeleccionado = null;
    if ($vehiculoId !== false && $vehiculoId > 0) {
        $encontrados = array_values($recursosVehiculos->filtrar(
            $vehiculos,
            fn(array $v): bool => (int) ($v['id'] ?? 0) === $vehiculoId
        ));
        $vehiculoSeleccionado = $encontrados[0] ?? null;
    }

    if ($datos['fecha'] === '') {
        $error = 'La fecha es obligatoria.';
    } elseif ($vehiculoId === false || $vehiculoId <= 0 || !is_array($vehiculoSeleccionado)) {
        $error = 'Debes seleccionar un vehículo válido para incrementar su inventario.';
    } elseif ($datos['proveedor'] === '') {
        $error = 'El proveedor es obligatorio.';
    } elseif ($cantidadNum === false || $cantidadNum <= 0) {
        $error = 'La cantidad debe ser un número entero mayor que cero.';
    } elseif ($costoNum < 0) {
        $error = 'El costo unitario no puede ser negativo.';
    } elseif (!in_array($datos['estado'], $estadosValidos, true)) {
        $error = 'El estado seleccionado no es válido.';
    } else {
        $conceptoFinal = $datos['concepto'] !== ''
            ? $datos['concepto']
            : 'Compra de flota: ' . trim(($vehiculoSeleccionado['marca'] ?? '') . ' ' . ($vehiculoSeleccionado['modelo'] ?? ''));

        $payload = [
            'vehiculo_id'    => $vehiculoId,
            'cantidad'       => $cantidadNum,
            'costo_unitario' => round($costoNum, 2),
            'proveedor'      => $datos['proveedor'],
            'concepto'       => $conceptoFinal,
            'fecha'          => $datos['fecha'],
            'estado'         => $datos['estado'],
        ];

        $res = $servicio->crear($payload);

        if (!($res['ok'] ?? false)) {
            $error = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'No se pudo registrar la compra (' . ($res['status'] ?? 0) . ').'));
        } else {
            $msgExtra = $datos['estado'] !== 'anulada'
                ? ' Inventario incrementado (+' . $cantidadNum . ').'
                : '';
            header('Location: /cruds/compras/listar.php?msg=' . urlencode('Compra registrada correctamente.' . $msgExtra));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Compra — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 1rem; font-weight: bold; }
        .container { max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .field { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.35rem; font-weight: bold; font-size: 0.9rem; }
        input, select { width: 100%; box-sizing: border-box; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; }
        small.hint { display: block; color: #64748b; margin-top: 0.25rem; font-size: 0.82rem; }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .actions { display: flex; gap: 0.6rem; margin-top: 1.25rem; }
        .btn { padding: 0.55rem 1.1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav>
        <div>
            <a href="/index.php">Inicio</a>
            <a href="/cruds/compras/listar.php">← Volver a Compras</a>
        </div>
        <div><a href="/logout.php" style="color:#fca5a5;">Cerrar sesión</a></div>
    </nav>

    <div class="container">
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>

        <div class="card">
            <h2 style="margin-top:0;">Registrar Compra</h2>

            <form method="POST" action="/cruds/compras/crear.php">
                <div class="row-2">
                    <div class="field">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="<?= e($datos['fecha']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="completada" <?= $datos['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
                            <option value="pendiente" <?= $datos['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="anulada" <?= $datos['estado'] === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="vehiculo_id">Vehículo de flota (incrementa stock automáticamente)</label>
                    <select id="vehiculo_id" name="vehiculo_id" required>
                        <option value="">Seleccione un vehículo...</option>
                        <?php foreach ($vehiculos as $v): ?>
                            <?php $vid = (int) ($v['id'] ?? 0); ?>
                            <option value="<?= e($vid) ?>" <?= (int) $datos['vehiculo_id'] === $vid ? 'selected' : '' ?>>
                                #<?= e($vid) ?> — <?= e(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?> (Stock actual: <?= e($v['stock_total'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="hint">Al guardar en estado "Completada" o "Pendiente", la API suma la cantidad al stock del vehículo.</small>
                </div>

                <div class="field">
                    <label for="proveedor">Proveedor</label>
                    <input type="text" id="proveedor" name="proveedor" value="<?= e($datos['proveedor']) ?>" required>
                </div>

                <div class="field">
                    <label for="concepto">Concepto</label>
                    <input type="text" id="concepto" name="concepto" value="<?= e($datos['concepto']) ?>" placeholder="Ej. Adquisición de unidades para flota" required>
                </div>

                <div class="row-2">
                    <div class="field">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" step="1" value="<?= e($datos['cantidad']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="costo_unitario">Costo unitario ($)</label>
                        <input type="number" id="costo_unitario" name="costo_unitario" min="0" step="0.01" value="<?= e($datos['costo_unitario']) ?>" required>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn">Guardar Compra</button>
                    <a href="/cruds/compras/listar.php" class="btn btn-sec">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>