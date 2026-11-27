<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ApiClient.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: /cruds/alquileres/listar.php?err=' . urlencode('ID de alquiler no válido.'));
    exit;
}

$extraerLista = static function ($raw, string $clave): array {
    if (is_array($raw) && isset($raw[$clave]) && is_array($raw[$clave])) {
        return $raw[$clave];
    }
    if (is_array($raw) && isset($raw['data']) && is_array($raw['data'])) {
        return $raw['data'];
    }
    return is_array($raw) ? $raw : [];
};

$servicio = new Recursos('/alquileres');
$todos = $extraerLista($servicio->listar(), 'alquileres');
$coincidencias = array_values($servicio->filtrar(
    $todos,
    fn(array $item): bool => (int) ($item['id'] ?? 0) === $id
));
$alquiler = $coincidencias[0] ?? null;

if (!is_array($alquiler)) {
    header('Location: /cruds/alquileres/listar.php?err=' . urlencode('No se encontró el alquiler #' . $id . '.'));
    exit;
}

$clientes = $extraerLista((new Recursos('/clientes'))->listar(), 'clientes');
$vehiculos = $extraerLista((new Recursos('/vehiculos'))->listar(), 'vehiculos');

$cid = (int) ($alquiler['cliente_id'] ?? 0);
$vid = (int) ($alquiler['vehiculo_id'] ?? 0);

$clienteMatch = array_values(array_filter($clientes, fn(array $c): bool => (int) ($c['id'] ?? 0) === $cid));
$vehiculoMatch = array_values(array_filter($vehiculos, fn(array $v): bool => (int) ($v['id'] ?? 0) === $vid));

$clienteTexto = trim((string) ($alquiler['cliente_nombre'] ?? ($clienteMatch[0]['nombre'] ?? '')));
$clienteTexto = $clienteTexto !== '' ? ($clienteTexto . ' (#' . $cid . ')') : ('#' . $cid);

$vehiculoTexto = trim((string) ($alquiler['vehiculo_nombre'] ?? trim((string) (($vehiculoMatch[0]['marca'] ?? '') . ' ' . ($vehiculoMatch[0]['modelo'] ?? '')))));
$vehiculoTexto = $vehiculoTexto !== '' ? ($vehiculoTexto . ' (#' . $vid . ')') : ('#' . $vid);

$totalMostrado = 0.0;
foreach (['total', 'costo_total', 'precio_total', 'monto_total', 'monto', 'subtotal', 'importe'] as $clave) {
    if (isset($alquiler[$clave]) && is_numeric($alquiler[$clave]) && (float) $alquiler[$clave] > 0) {
        $totalMostrado = (float) $alquiler[$clave];
        break;
    }
}
if ($totalMostrado <= 0) {
    $precioDia = (float) ($vehiculoMatch[0]['precio_por_dia'] ?? $vehiculoMatch[0]['precio_dia'] ?? 0);
    if ($precioDia > 0) {
        $fIni = trim((string) ($alquiler['fecha_inicio'] ?? ''));
        $fFin = trim((string) ($alquiler['fecha_fin'] ?? ''));
        $dias = 1;
        if ($fIni !== '' && $fFin !== '') {
            try {
                $d1 = new DateTimeImmutable(substr($fIni, 0, 10));
                $d2 = new DateTimeImmutable(substr($fFin, 0, 10));
                $dias = max(1, (int) $d1->diff($d2)->days);
            } catch (Throwable $e) {
                $dias = 1;
            }
        }
        $cantidad = max(1, (int) ($alquiler['cantidad'] ?? $alquiler['unidades'] ?? 1));
        $totalMostrado = round($dias * $precioDia * $cantidad, 2);
    }
}

$estadosPermitidos = ['reservado', 'activo', 'devuelto', 'cancelado'];
$estadoActual = strtolower(trim((string) ($alquiler['estado'] ?? 'reservado')));
if (!in_array($estadoActual, $estadosPermitidos, true)) {
    $estadoActual = 'reservado';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevoEstado = strtolower(trim((string) ($_POST['estado'] ?? '')));
    if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
        $error = 'Estado no válido.';
    } else {
        $res = ApiClient::put('/alquileres/' . $id . '/estado', ['estado' => $nuevoEstado]);
        if (!$res['ok'] && in_array((int) ($res['status'] ?? 0), [404, 405], true)) {
            $res = ApiClient::put('/alquileres/' . $id, ['estado' => $nuevoEstado]);
        }
        if ($res['ok']) {
            $msg = $nuevoEstado === 'devuelto'
                ? 'Devolución registrada: el alquiler #' . $id . ' ahora está devuelto.'
                : 'Estado del alquiler #' . $id . ' actualizado a ' . $nuevoEstado . '.';
            header('Location: /cruds/alquileres/listar.php?msg=' . urlencode($msg));
            exit;
        }
        $error = extraerErrorApi($res['data'], 'Error al actualizar estado (' . $res['status'] . ').');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado / Devolución — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.9rem; margin-bottom: 1rem; font-size: 0.95rem; line-height: 1.6; }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 style="margin-top:0;">Actualizar Estado / Registrar Devolución (#<?= e($id) ?>)</h2>
            <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>

            <div class="info-box">
                <div><strong>Cliente:</strong> <?= e($clienteTexto) ?></div>
                <div><strong>Vehículo:</strong> <?= e($vehiculoTexto) ?></div>
                <div><strong>Fechas:</strong> <?= e($alquiler['fecha_inicio'] ?? '') ?> al <?= e($alquiler['fecha_fin'] ?? '') ?></div>
                <div><strong>Total:</strong> $<?= e(number_format($totalMostrado, 2)) ?></div>
            </div>

            <form method="POST" action="/cruds/alquileres/editar.php?id=<?= e($id) ?>" autocomplete="off">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <label>Estado del alquiler</label>
                <select name="estado" required>
                    <?php foreach ($estadosPermitidos as $est): ?>
                        <option value="<?= e($est) ?>" <?= $estadoActual === $est ? 'selected' : '' ?>>
                            <?= e(ucfirst($est)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                    <button type="submit" class="btn">Guardar Estado</button>
                    <a href="/cruds/alquileres/listar.php" class="btn btn-sec">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>