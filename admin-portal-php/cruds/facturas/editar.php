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

$servicio = new Recursos('/facturas');
$idRaw = trim($_GET['id'] ?? $_POST['id'] ?? '');
$id = filter_var($idRaw, FILTER_VALIDATE_INT);

if ($id === false || $id <= 0) {
    header('Location: /cruds/facturas/listar.php?err=' . urlencode('ID de factura inválido.'));
    exit;
}

$rawFactura = $servicio->obtenerPorId($id);
if (is_array($rawFactura) && isset($rawFactura['factura']) && is_array($rawFactura['factura'])) {
    $factura = $rawFactura['factura'];
} elseif (is_array($rawFactura) && isset($rawFactura['data']) && is_array($rawFactura['data'])) {
    $factura = $rawFactura['data'];
} else {
    $factura = is_array($rawFactura) ? $rawFactura : null;
}

if (!$factura) {
    header('Location: /cruds/facturas/listar.php?err=' . urlencode('La factura solicitada no existe.'));
    exit;
}

if (strtolower(trim((string) ($factura['estado'] ?? 'pendiente'))) !== 'pendiente') {
    header('Location: /cruds/facturas/listar.php?err=' . urlencode('Solo se pueden editar facturas en estado pendiente.'));
    exit;
}

$clientes = $extraerLista((new Recursos('/clientes'))->listar(), 'clientes');

$detallesExistentes = is_array($factura['detalles'] ?? null) ? $factura['detalles'] : [];
$detalleTexto = !empty($detallesExistentes)
    ? implode(', ', array_filter(array_map(fn(array $d): string => trim((string) ($d['descripcion'] ?? '')), $detallesExistentes)))
    : 'Servicio de alquiler';

$error = '';
$datos = [
    'fecha'       => substr((string) ($factura['fecha'] ?? $factura['fecha_emision'] ?? date('Y-m-d')), 0, 10),
    'cliente_id'  => (string) ($factura['cliente_id'] ?? ''),
    'alquiler_id' => (string) ($factura['alquiler_id'] ?? ''),
    'subtotal'    => (string) ($factura['subtotal'] ?? $factura['total'] ?? 0),
    'impuesto'    => (string) ($factura['impuesto'] ?? 0),
    'estado'      => strtolower(trim((string) ($factura['estado'] ?? 'pendiente'))),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['fecha']      = trim($_POST['fecha'] ?? '');
    $datos['cliente_id'] = trim($_POST['cliente_id'] ?? '');
    $datos['subtotal']   = trim($_POST['subtotal'] ?? '0');
    $datos['impuesto']   = trim($_POST['impuesto'] ?? '0');
    $datos['estado']     = strtolower(trim($_POST['estado'] ?? 'pendiente'));

    $estadosValidos = ['pendiente', 'pagada', 'anulada'];
    $clienteId = filter_var($datos['cliente_id'], FILTER_VALIDATE_INT);
    $subtotalNum = is_numeric($datos['subtotal']) ? (float) $datos['subtotal'] : -1;
    $impuestoNum = is_numeric($datos['impuesto']) ? (float) $datos['impuesto'] : -1;

    if ($datos['fecha'] === '') {
        $error = 'La fecha es obligatoria.';
    } elseif ($clienteId === false || $clienteId <= 0) {
        $error = 'Debes seleccionar un cliente válido.';
    } elseif ($subtotalNum < 0) {
        $error = 'El subtotal no puede ser negativo.';
    } elseif ($impuestoNum < 0) {
        $error = 'El impuesto no puede ser negativo.';
    } elseif (!in_array($datos['estado'], $estadosValidos, true)) {
        $error = 'El estado seleccionado no es válido.';
    } else {
        $total = round($subtotalNum + $impuestoNum, 2);

        $payload = [
            'fecha'      => $datos['fecha'],
            'cliente_id' => $clienteId,
            'subtotal'   => round($subtotalNum, 2),
            'impuesto'   => round($impuestoNum, 2),
            'total'      => $total,
            'estado'     => $datos['estado'],
        ];
        if ((int) $datos['alquiler_id'] > 0) {
            $payload['alquiler_id'] = (int) $datos['alquiler_id'];
        }

        $res = $servicio->actualizar($id, $payload);

        if (!($res['ok'] ?? false)) {
            $error = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'Error al actualizar la factura (' . ($res['status'] ?? 0) . ').'));
        } else {
            header('Location: /cruds/facturas/listar.php?msg=' . urlencode('Factura actualizada correctamente.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Factura — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 1rem; font-weight: bold; }
        .container { max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.9rem; color: #475569; }
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
            <a href="/cruds/facturas/listar.php">← Volver a Facturas</a>
        </div>
        <div><a href="/logout.php" style="color:#fca5a5;">Cerrar sesión</a></div>
    </nav>

    <div class="container">
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>

        <div class="card">
            <h2 style="margin-top:0;">Editar Factura #<?= e($id) ?></h2>

            <div class="info-box">
                <strong>Detalle registrado:</strong> <?= e($detalleTexto) ?>
                <?php if ((int) $datos['alquiler_id'] > 0): ?>
                    | <strong>Alquiler:</strong> #<?= e($datos['alquiler_id']) ?>
                <?php endif; ?>
            </div>

            <form method="POST" action="/cruds/facturas/editar.php?id=<?= e($id) ?>">
                <input type="hidden" name="id" value="<?= e($id) ?>">

                <div class="row-2">
                    <div class="field">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="<?= e($datos['fecha']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="pendiente" <?= $datos['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="pagada" <?= $datos['estado'] === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                            <option value="anulada" <?= $datos['estado'] === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="cliente_id">Cliente</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Seleccione un cliente...</option>
                        <?php foreach ($clientes as $c): ?>
                            <?php $cid = (int) ($c['id'] ?? 0); ?>
                            <option value="<?= e($cid) ?>" <?= (int) $datos['cliente_id'] === $cid ? 'selected' : '' ?>>
                                #<?= e($cid) ?> — <?= e($c['nombre'] ?? $c['nombre_completo'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row-2">
                    <div class="field">
                        <label for="subtotal">Subtotal ($)</label>
                        <input type="number" id="subtotal" name="subtotal" min="0" step="0.01" value="<?= e($datos['subtotal']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="impuesto">Impuesto ($)</label>
                        <input type="number" id="impuesto" name="impuesto" min="0" step="0.01" value="<?= e($datos['impuesto']) ?>" required>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn">Actualizar Factura</button>
                    <a href="/cruds/facturas/listar.php" class="btn btn-sec">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>