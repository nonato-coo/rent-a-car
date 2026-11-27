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

$idRaw = trim($_GET['id'] ?? $_POST['id'] ?? '');
$idInt = filter_var($idRaw, FILTER_VALIDATE_INT);

if ($idInt === false || $idInt <= 0) {
    header('Location: /cruds/compras/listar.php?err=' . urlencode('ID de compra inválido.'));
    exit;
}

$rawCompra = $servicio->obtenerPorId($idInt);
if (is_array($rawCompra) && isset($rawCompra['compra']) && is_array($rawCompra['compra'])) {
    $compra = $rawCompra['compra'];
} elseif (is_array($rawCompra) && isset($rawCompra['data']) && is_array($rawCompra['data'])) {
    $compra = $rawCompra['data'];
} else {
    $compra = is_array($rawCompra) ? $rawCompra : null;
}

if (!$compra) {
    header('Location: /cruds/compras/listar.php?err=' . urlencode('La compra solicitada no existe.'));
    exit;
}

$error = '';
$datos = [
    'fecha'          => (string) ($compra['fecha'] ?? date('Y-m-d')),
    'vehiculo_id'    => (string) ($compra['vehiculo_id'] ?? ''),
    'proveedor'      => (string) ($compra['proveedor'] ?? ''),
    'concepto'       => (string) ($compra['concepto'] ?? ''),
    'cantidad'       => (string) ($compra['cantidad'] ?? 1),
    'costo_unitario' => (string) ($compra['costo_unitario'] ?? $compra['precio_unitario'] ?? 0),
    'estado'         => (string) ($compra['estado'] ?? 'pendiente'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['fecha']          = trim($_POST['fecha'] ?? '');
    $datos['vehiculo_id']    = trim($_POST['vehiculo_id'] ?? '');
    $datos['proveedor']      = trim($_POST['proveedor'] ?? '');
    $datos['concepto']       = trim($_POST['concepto'] ?? '');
    $datos['cantidad']       = trim($_POST['cantidad'] ?? '1');
    $datos['costo_unitario'] = trim($_POST['costo_unitario'] ?? '0');
    $datos['estado']         = trim($_POST['estado'] ?? 'pendiente');

    $estadosValidos = ['completada', 'pendiente', 'anulada'];
    $vehiculoId  = filter_var($datos['vehiculo_id'], FILTER_VALIDATE_INT);
    $cantidadNum = filter_var($datos['cantidad'], FILTER_VALIDATE_INT);
    $costoNum    = is_numeric($datos['costo_unitario']) ? (float) $datos['costo_unitario'] : -1;

    if ($datos['fecha'] === '') {
        $error = 'La fecha es obligatoria.';
    } elseif ($datos['proveedor'] === '') {
        $error = 'El proveedor es obligatorio.';
    } elseif ($datos['concepto'] === '') {
        $error = 'El concepto es obligatorio.';
    } elseif ($cantidadNum === false || $cantidadNum <= 0) {
        $error = 'La cantidad debe ser un número entero mayor que cero.';
    } elseif ($costoNum < 0) {
        $error = 'El costo unitario no puede ser negativo.';
    } elseif (!in_array($datos['estado'], $estadosValidos, true)) {
        $error = 'El estado seleccionado no es válido.';
    } else {
        $total = round($cantidadNum * $costoNum, 2);
        $payload = [
            'fecha'           => $datos['fecha'],
            'proveedor'       => $datos['proveedor'],
            'concepto'        => $datos['concepto'],
            'cantidad'        => $cantidadNum,
            'costo_unitario'  => round($costoNum, 2),
            'precio_unitario' => round($costoNum, 2),
            'total'           => $total,
            'estado'          => $datos['estado'],
        ];
        if ($vehiculoId !== false && $vehiculoId > 0) {
            $payload['vehiculo_id'] = $vehiculoId;
        }

        $res = $servicio->actualizar($idInt, $payload);

        if (!($res['ok'] ?? false)) {
            $error = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'No se pudo actualizar la compra en el servidor (' . ($res['status'] ?? 0) . ').'));
        } else {
            header('Location: /cruds/compras/listar.php?msg=' . urlencode('Compra actualizada correctamente.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Compra — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 1rem; font-weight: bold; }
        .container { max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .field { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.35rem; font-weight: bold; font-size: 0.9rem; }
        input, select { width: 100%; box-sizing: border-box; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; }
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
            <h2 style="margin-top:0;">Editar Compra #<?= e($idInt) ?></h2>

            <form method="POST" action="/cruds/compras/editar.php?id=<?= e($idInt) ?>">
                <input type="hidden" name="id" value="<?= e($idInt) ?>">

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
                    <label for="vehiculo_id">Vehículo de flota (opcional)</label>
                    <select id="vehiculo_id" name="vehiculo_id">
                        <option value="">-- Compra general / sin vincular vehículo --</option>
                        <?php foreach ($vehiculos as $v): ?>
                            <?php $vid = (int) ($v['id'] ?? 0); ?>
                            <option value="<?= e($vid) ?>" <?= (int) $datos['vehiculo_id'] === $vid ? 'selected' : '' ?>>
                                #<?= e($vid) ?> — <?= e(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?> (Stock actual: <?= e($v['stock_total'] ?? 0) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="proveedor">Proveedor</label>
                    <input type="text" id="proveedor" name="proveedor" value="<?= e($datos['proveedor']) ?>" required>
                </div>

                <div class="field">
                    <label for="concepto">Concepto</label>
                    <input type="text" id="concepto" name="concepto" value="<?= e($datos['concepto']) ?>" required>
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
                    <button type="submit" class="btn">Actualizar Compra</button>
                    <a href="/cruds/compras/listar.php" class="btn btn-sec">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>