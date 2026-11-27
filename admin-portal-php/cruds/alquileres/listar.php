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

$servicio = new Recursos('/alquileres');
$alquileres = $extraerLista($servicio->listar(), 'alquileres');

$clientes = $extraerLista((new Recursos('/clientes'))->listar(), 'clientes');
$mapaClientes = array_reduce(
    $clientes,
    function (array $acc, array $c): array {
        $cid = (int) ($c['id'] ?? 0);
        if ($cid > 0) {
            $nombre = trim((string) ($c['nombre'] ?? $c['nombre_completo'] ?? ''));
            $acc[$cid] = $nombre !== '' ? $nombre : ('Cliente #' . $cid);
        }
        return $acc;
    },
    []
);

$vehiculos = $extraerLista((new Recursos('/vehiculos'))->listar(), 'vehiculos');
$mapaVehiculos = array_reduce(
    $vehiculos,
    function (array $acc, array $v): array {
        $vid = (int) ($v['id'] ?? 0);
        if ($vid > 0) {
            $desc = trim((string) (($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')));
            $acc[$vid] = [
                'texto'  => $desc !== '' ? ($desc . ' (#' . $vid . ')') : ('Vehículo #' . $vid),
                'precio' => (float) ($v['precio_por_dia'] ?? $v['precio_dia'] ?? $v['precio'] ?? 0),
            ];
        }
        return $acc;
    },
    []
);

$facturas = $extraerLista((new Recursos('/facturas'))->listar(), 'facturas');
$mapaFacturasPorAlquiler = array_reduce(
    $facturas,
    function (array $acc, array $f): array {
        $aid = (int) ($f['alquiler_id'] ?? $f['reserva_id'] ?? 0);
        if ($aid > 0) {
            $val = (float) ($f['total'] ?? $f['subtotal'] ?? $f['monto_total'] ?? $f['monto'] ?? 0);
            if ($val > 0) {
                $acc[$aid] = $val;
            }
        }
        return $acc;
    },
    []
);

$calcularMontoAlquiler = static function (array $a, array $mapaVehiculos, array $mapaFacturasPorAlquiler): float {
    $clavesMonto = ['total', 'costo_total', 'precio_total', 'monto_total', 'monto', 'subtotal', 'importe'];
    foreach ($clavesMonto as $clave) {
        if (isset($a[$clave]) && is_numeric($a[$clave]) && (float) $a[$clave] > 0) {
            return (float) $a[$clave];
        }
    }

    $aid = (int) ($a['id'] ?? 0);
    if ($aid > 0 && isset($mapaFacturasPorAlquiler[$aid]) && $mapaFacturasPorAlquiler[$aid] > 0) {
        return (float) $mapaFacturasPorAlquiler[$aid];
    }

    $vid = (int) ($a['vehiculo_id'] ?? 0);
    $precioDia = (float) ($a['precio_por_dia'] ?? ($mapaVehiculos[$vid]['precio'] ?? 0));
    if ($precioDia <= 0) {
        return 0.0;
    }

    $fIni = trim((string) ($a['fecha_inicio'] ?? $a['fecha'] ?? ''));
    $fFin = trim((string) ($a['fecha_fin'] ?? ''));
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

    $cantidad = max(1, (int) ($a['cantidad'] ?? $a['unidades'] ?? 1));
    return round($dias * $precioDia * $cantidad, 2);
};

$alquileres = $servicio->transformar($alquileres, function (array $a) use ($mapaClientes, $mapaVehiculos, $mapaFacturasPorAlquiler, $calcularMontoAlquiler): array {
    $cid = (int) ($a['cliente_id'] ?? 0);
    $vid = (int) ($a['vehiculo_id'] ?? 0);
    $monto = $calcularMontoAlquiler($a, $mapaVehiculos, $mapaFacturasPorAlquiler);

    $clienteNombre = trim((string) ($a['cliente_nombre'] ?? ''));
    if ($clienteNombre === '') {
        $clienteNombre = $mapaClientes[$cid] ?? ($cid > 0 ? 'Cliente #' . $cid : '');
    }

    $vehiculoNombre = trim((string) ($a['vehiculo_nombre'] ?? ''));
    if ($vehiculoNombre === '') {
        $vehiculoNombre = $mapaVehiculos[$vid]['texto'] ?? ($vid > 0 ? 'Vehículo #' . $vid : '');
    }

    return $a + [
        '_cliente'      => $clienteNombre,
        '_vehiculo'     => $vehiculoNombre,
        '_estado'       => (string) ($a['estado'] ?? ''),
        '_monto'        => $monto,
        '_fecha_inicio' => $a['fecha_inicio'] ?? $a['fecha'] ?? '',
        '_fecha_fin'    => $a['fecha_fin'] ?? '',
    ];
});

$estadoFiltro = trim($_GET['estado'] ?? '');
if ($estadoFiltro !== '') {
    $alquileres = $servicio->filtrar(
        $alquileres,
        fn(array $a): bool => mb_strtolower((string) ($a['_estado'] ?? '')) === mb_strtolower($estadoFiltro)
    );
}

$totalAlquileres = $servicio->sumarCampo($alquileres, '_monto');

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alquileres — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 0.9rem; font-weight: bold; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .btn { padding: 0.5rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; display: inline-block; }
        .btn-warn { background: #d97706; }
        .btn-danger { background: #dc2626; }
        .btn-sec { background: #64748b; }
        .alert-ok { background: #dcfce7; color: #166534; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav>
        <div>
            <a href="/index.php">Inicio</a>
            <a href="/cruds/usuarios/listar.php">Usuarios</a>
            <a href="/cruds/clientes/listar.php">Clientes</a>
            <a href="/cruds/vehiculos/listar.php">Vehículos</a>
            <a href="/cruds/alquileres/listar.php">Alquileres</a>
            <a href="/cruds/compras/listar.php">Compras</a>
            <a href="/cruds/pagos/listar.php">Pagos</a>
            <a href="/cruds/facturas/listar.php">Facturas</a>
            <a href="/cruds/mantenimientos/listar.php">Mantenimiento</a>
        </div>
        <div><a href="/logout.php" style="color:#fca5a5;">Cerrar sesión</a></div>
    </nav>
    <div class="container">
        <?php if ($mensaje !== ''): ?><div class="alert-ok"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <div class="card">
            <div class="header-row">
                <div>
                    <h2 style="margin:0;">Gestión de Alquileres</h2>
                    <small style="color:#64748b;">Alquileres: <?= count($alquileres) ?> | Monto total: $<?= number_format($totalAlquileres, 2) ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/alquileres/listar.php" autocomplete="off" style="display:flex; gap:0.4rem; margin:0; align-items:center;">
                        <select name="estado" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">Todos los estados</option>
                            <?php foreach (['reservado', 'activo', 'devuelto', 'cancelado'] as $est): ?>
                                <option value="<?= e($est) ?>" <?= $estadoFiltro === $est ? 'selected' : '' ?>><?= e(ucfirst($est)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                        <a href="/cruds/alquileres/listar.php" class="btn btn-sec">Limpiar</a>
                    </form>
                    <a href="/cruds/alquileres/crear.php" class="btn">+ Nuevo Alquiler</a>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alquileres)): ?>
                        <tr><td colspan="8">No hay alquileres registrados.</td></tr>
                    <?php else: foreach ($alquileres as $a): ?>
                        <tr>
                            <td><?= e($a['id'] ?? '') ?></td>
                            <td><?= e($a['_cliente']) ?></td>
                            <td><?= e($a['_vehiculo']) ?></td>
                            <td><?= e($a['_fecha_inicio']) ?></td>
                            <td><?= e($a['_fecha_fin']) ?></td>
                            <td><?= e(ucfirst($a['_estado'])) ?></td>
                            <td>$<?= e(number_format((float) $a['_monto'], 2)) ?></td>
                            <td style="display:flex; gap:0.4rem;">
                                <a href="/cruds/alquileres/editar.php?id=<?= e($a['id']) ?>" class="btn btn-warn">Editar</a>
                                <form method="POST" action="/cruds/alquileres/eliminar.php" style="margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar este alquiler?');">
                                    <input type="hidden" name="id" value="<?= e($a['id']) ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>