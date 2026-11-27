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

$servicio = new Recursos('/pagos');
$alquileres = $extraerLista((new Recursos('/alquileres'))->listar(), 'alquileres');
$clientes   = $extraerLista((new Recursos('/clientes'))->listar(), 'clientes');
$vehiculos  = $extraerLista((new Recursos('/vehiculos'))->listar(), 'vehiculos');
$facturas   = $extraerLista((new Recursos('/facturas'))->listar(), 'facturas');
$pagosExist = $extraerLista($servicio->listar(), 'pagos');

$mapaClientes = array_reduce($clientes, function (array $acc, array $c): array {
    $cid = (int) ($c['id'] ?? 0);
    if ($cid > 0) {
        $acc[$cid] = trim((string) ($c['nombre'] ?? $c['nombre_completo'] ?? ('Cliente #' . $cid)));
    }
    return $acc;
}, []);

$mapaVehiculos = array_reduce($vehiculos, function (array $acc, array $v): array {
    $vid = (int) ($v['id'] ?? 0);
    if ($vid > 0) {
        $acc[$vid] = [
            'texto'  => trim((string) (($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? ''))),
            'precio' => (float) ($v['precio_por_dia'] ?? $v['precio_dia'] ?? 0),
        ];
    }
    return $acc;
}, []);

$pagosPorAlquiler = array_reduce($pagosExist, function (array $acc, array $p): array {
    $aid = (int) ($p['alquiler_id'] ?? 0);
    if ($aid > 0 && strtolower(trim((string) ($p['estado'] ?? 'completado'))) !== 'anulado') {
        $acc[$aid] = ($acc[$aid] ?? 0.0) + (float) ($p['monto'] ?? 0);
    }
    return $acc;
}, []);

$facturasPorAlquiler = array_reduce($facturas, function (array $acc, array $f): array {
    $aid = (int) ($f['alquiler_id'] ?? 0);
    if ($aid > 0) {
        $acc[$aid] = $f;
    }
    return $acc;
}, []);

$calcularMontoAlquiler = static function (array $a) use ($mapaVehiculos, $facturasPorAlquiler): float {
    foreach (['total', 'costo_total', 'precio_total', 'monto_total', 'monto', 'subtotal'] as $k) {
        if (isset($a[$k]) && is_numeric($a[$k]) && (float) $a[$k] > 0) {
            return (float) $a[$k];
        }
    }
    $aid = (int) ($a['id'] ?? 0);
    if ($aid > 0 && isset($facturasPorAlquiler[$aid])) {
        $valF = (float) ($facturasPorAlquiler[$aid]['total'] ?? 0);
        if ($valF > 0) {
            return $valF;
        }
    }
    $vid = (int) ($a['vehiculo_id'] ?? 0);
    $precioDia = (float) ($mapaVehiculos[$vid]['precio'] ?? 0);
    if ($precioDia <= 0) {
        return 0.0;
    }
    $dias = 1;
    $fIni = trim((string) ($a['fecha_inicio'] ?? ''));
    $fFin = trim((string) ($a['fecha_fin'] ?? ''));
    if ($fIni !== '' && $fFin !== '') {
        try {
            $d1 = new DateTimeImmutable(substr($fIni, 0, 10));
            $d2 = new DateTimeImmutable(substr($fFin, 0, 10));
            $dias = max(1, (int) $d1->diff($d2)->days);
        } catch (Throwable $e) {
            $dias = 1;
        }
    }
    $cant = max(1, (int) ($a['cantidad'] ?? 1));
    return round($dias * $precioDia * $cant, 2);
};

$alquileresCobrables = array_values(array_filter(
    $alquileres,
    static function (array $a) use ($pagosPorAlquiler, $calcularMontoAlquiler): bool {
        $aid = (int) ($a['id'] ?? 0);
        $estado = strtolower(trim((string) ($a['estado'] ?? '')));
        if ($estado === 'cancelado') {
            return false;
        }
        $totalAlq = $calcularMontoAlquiler($a);
        $pagado = (float) ($pagosPorAlquiler[$aid] ?? 0);
        return $totalAlq <= 0 || $pagado < ($totalAlq - 0.01);
    }
));

$error = '';
$datos = [
    'alquiler_id' => trim((string) ($_GET['alquiler_id'] ?? '')),
    'monto'       => '',
    'metodo'      => 'Tarjeta',
    'fecha'       => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['alquiler_id'] = trim((string) ($_POST['alquiler_id'] ?? ''));
    $datos['monto']       = trim((string) ($_POST['monto'] ?? ''));
    $datos['metodo']      = trim((string) ($_POST['metodo'] ?? 'Tarjeta'));
    $datos['fecha']       = trim((string) ($_POST['fecha'] ?? date('Y-m-d')));

    $alquilerId = (int) $datos['alquiler_id'];
    $montoVal   = is_numeric($datos['monto']) ? round((float) $datos['monto'], 2) : 0.0;

    $alqEncontrado = array_values(array_filter($alquileres, fn(array $a): bool => (int) ($a['id'] ?? 0) === $alquilerId));
    $alq = $alqEncontrado[0] ?? null;

    if ($alquilerId <= 0 || !is_array($alq)) {
        $error = 'Debes seleccionar un alquiler válido.';
    } elseif (strtolower(trim((string) ($alq['estado'] ?? ''))) === 'cancelado') {
        $error = 'No se pueden registrar pagos para un alquiler cancelado.';
    } elseif ($montoVal <= 0) {
        $error = 'El monto del pago debe ser mayor a $0.00.';
    } elseif ($datos['fecha'] === '') {
        $error = 'La fecha del pago es obligatoria.';
    } else {
        $totalAlq = $calcularMontoAlquiler($alq);
        $yaPagado = (float) ($pagosPorAlquiler[$alquilerId] ?? 0);
        if ($totalAlq > 0 && ($yaPagado + $montoVal) > ($totalAlq + 0.01)) {
            $saldo = max(0, round($totalAlq - $yaPagado, 2));
            $error = 'El monto excede el saldo pendiente ($' . number_format($saldo, 2) . ').';
        } else {
            $payload = [
                'alquiler_id' => $alquilerId,
                'monto'       => $montoVal,
                'metodo'      => $datos['metodo'],
                'fecha'       => $datos['fecha'],
            ];
            $res = $servicio->crear($payload);
            if ($res['ok']) {
                // Si el alquiler quedó saldado y tiene factura, marcar factura como pagada
                if (isset($facturasPorAlquiler[$alquilerId]) && ($totalAlq <= 0 || ($yaPagado + $montoVal) >= ($totalAlq - 0.01))) {
                    $facId = (int) ($facturasPorAlquiler[$alquilerId]['id'] ?? 0);
                    if ($facId > 0) {
                        (new Recursos('/facturas'))->actualizar($facId, ['estado' => 'pagada']);
                    }
                }
                header('Location: /cruds/pagos/listar.php?msg=' . urlencode('Pago registrado correctamente.'));
                exit;
            }
            $error = extraerErrorApi($res['data'], 'Error al registrar pago (' . $res['status'] . ').');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Pago — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 620px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        small.hint { display: block; color: #64748b; margin-top: 0.25rem; font-size: 0.82rem; }
        .btn { padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container"><div class="card">
        <h2 style="margin-top:0;">Registrar Nuevo Pago</h2>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="/cruds/pagos/crear.php">
            <label>Alquiler con saldo pendiente</label>
            <select name="alquiler_id" id="alquiler_id" required>
                <option value="">Seleccione alquiler...</option>
                <?php foreach ($alquileresCobrables as $a):
                    $aid = (int) ($a['id'] ?? 0);
                    $cid = (int) ($a['cliente_id'] ?? 0);
                    $vid = (int) ($a['vehiculo_id'] ?? 0);
                    $clienteNom = $mapaClientes[$cid] ?? ('Cliente #' . $cid);
                    $vehiculoNom = $mapaVehiculos[$vid]['texto'] ?? ('Vehículo #' . $vid);
                    $totalAlq = $calcularMontoAlquiler($a);
                    $pagado = (float) ($pagosPorAlquiler[$aid] ?? 0);
                    $saldo = max(0, round($totalAlq - $pagado, 2));
                ?>
                    <option value="<?= e($aid) ?>" data-saldo="<?= e($saldo) ?>" <?= (int) $datos['alquiler_id'] === $aid ? 'selected' : '' ?>>
                        Alquiler #<?= e($aid) ?> — <?= e($clienteNom) ?> (<?= e($vehiculoNom) ?>) — Saldo: $<?= e(number_format($saldo, 2)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($alquileresCobrables)): ?>
                <small class="hint" style="color:#b91c1c;">No hay alquileres activos/reservados con saldo pendiente.</small>
            <?php endif; ?>

            <label>Monto ($)</label>
            <input type="number" step="0.01" min="0.01" name="monto" id="monto" value="<?= e($datos['monto']) ?>" required>

            <label>Método de pago</label>
            <select name="metodo" required>
                <?php foreach (['Tarjeta', 'Efectivo', 'Transferencia'] as $m): ?>
                    <option value="<?= e($m) ?>" <?= $datos['metodo'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= e($datos['fecha']) ?>" required>

            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn">Guardar Pago</button>
                <a href="/cruds/pagos/listar.php" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div></div>
    <script>
        (function () {
            const sel = document.getElementById('alquiler_id');
            const monto = document.getElementById('monto');
            const autocompletar = () => {
                const opt = sel.options[sel.selectedIndex];
                if (opt && opt.dataset.saldo && parseFloat(opt.dataset.saldo) > 0 && !monto.value) {
                    monto.value = parseFloat(opt.dataset.saldo).toFixed(2);
                }
            };
            sel.addEventListener('change', () => {
                const opt = sel.options[sel.selectedIndex];
                if (opt && opt.dataset.saldo && parseFloat(opt.dataset.saldo) > 0) {
                    monto.value = parseFloat(opt.dataset.saldo).toFixed(2);
                }
            });
            autocompletar();
        })();
    </script>
</body>
</html>