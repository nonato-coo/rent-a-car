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
$facturas = $extraerLista($servicio->listar(), 'facturas');

$clientes = $extraerLista((new Recursos('/clientes'))->listar(), 'clientes');
$mapaClientes = array_reduce(
    $clientes,
    function (array $acc, array $c): array {
        $cid = (int) ($c['id'] ?? 0);
        if ($cid > 0) {
            $nombre = trim((string) ($c['nombre'] ?? $c['nombre_completo'] ?? ''));
            $acc[$cid] = $nombre !== '' ? ($nombre . ' (#' . $cid . ')') : ('Cliente #' . $cid);
        }
        return $acc;
    },
    []
);

$facturas = $servicio->transformar($facturas, function (array $f) use ($mapaClientes, $servicio): array {
    $fid = (int) ($f['id'] ?? 0);
    if ($fid > 0) {
        $real = $servicio->obtenerPorId($fid);
        if (is_array($real) && isset($real['estado'])) {
            $f = array_merge($f, $real);
        }
    }

    $cid = (int) ($f['cliente_id'] ?? 0);
    $clienteNombre = trim((string) ($f['cliente'] ?? $f['cliente_nombre'] ?? ''));
    if ($clienteNombre === '' || ctype_digit($clienteNombre)) {
        $clienteNombre = $mapaClientes[$cid] ?? ($cid > 0 ? 'Cliente #' . $cid : '—');
    }

    $detalles = is_array($f['detalles'] ?? null) ? $f['detalles'] : [];
    $detalleTexto = trim((string) ($f['concepto'] ?? ''));
    if ($detalleTexto === '' && !empty($detalles)) {
        $detalleTexto = implode(', ', array_filter(array_map(
            fn(array $d): string => trim((string) ($d['descripcion'] ?? $d['concepto'] ?? '')),
            $detalles
        )));
    }
    if ($detalleTexto === '') {
        $detalleTexto = count($detalles) > 0 ? (count($detalles) . ' ítem(s)') : '—';
    }

    $total = (float) ($f['total'] ?? 0);
    $impuesto = (float) ($f['impuesto'] ?? 0);
    $subtotal = isset($f['subtotal']) ? (float) $f['subtotal'] : round($total - $impuesto, 2);

    return $f + [
        '_numero'   => (string) ($f['numero'] ?? ('FAC-' . ($f['id'] ?? ''))),
        '_fecha'    => substr((string) ($f['fecha'] ?? $f['fecha_emision'] ?? ''), 0, 10),
        '_cliente'  => $clienteNombre,
        '_detalle'  => $detalleTexto,
        '_subtotal' => $subtotal,
        '_impuesto' => $impuesto,
        '_total'    => $total,
        '_estado'   => strtolower(trim((string) ($f['estado'] ?? 'pendiente'))),
    ];
});

$busqueda = trim($_GET['q'] ?? '');
$estadoFiltro = trim($_GET['estado'] ?? '');
$montoMinRaw = trim($_GET['monto_min'] ?? '');
$montoMin = '';
$avisoFiltro = '';

if ($montoMinRaw !== '') {
    if (!is_numeric($montoMinRaw) || (float) $montoMinRaw < 0) {
        $avisoFiltro = 'El monto mínimo no puede ser negativo.';
    } else {
        $montoMin = (string) (float) $montoMinRaw;
    }
}

if ($busqueda !== '') {
    $needle = mb_strtolower($busqueda);
    $facturas = $servicio->filtrar(
        $facturas,
        fn(array $f): bool =>
            str_contains(mb_strtolower((string) ($f['_numero'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($f['_cliente'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($f['_detalle'] ?? '')), $needle)
    );
}

if ($estadoFiltro !== '') {
    $facturas = $servicio->filtrar(
        $facturas,
        fn(array $f): bool => ((string) ($f['_estado'] ?? '')) === mb_strtolower($estadoFiltro)
    );
}

if ($avisoFiltro === '' && $montoMin !== '') {
    $minVal = (float) $montoMin;
    $facturas = $servicio->filtrar(
        $facturas,
        fn(array $f): bool => ((float) ($f['_total'] ?? 0)) >= $minVal
    );
}

$facturasActivas = $servicio->filtrar(
    $facturas,
    fn(array $f): bool => ((string) ($f['_estado'] ?? '')) !== 'anulada'
);
$totalFacturado = (float) $servicio->sumarCampo($facturasActivas, '_total');
$hayFiltros = $busqueda !== '' || $estadoFiltro !== '' || $montoMinRaw !== '';

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturas — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 0.9rem; font-weight: bold; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.7rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .btn { padding: 0.45rem 0.85rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.88rem; display: inline-block; }
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
        <?php if ($avisoFiltro !== ''): ?><div class="alert-err"><?= e($avisoFiltro) ?></div><?php endif; ?>

        <div class="card">
            <div class="header-row">
                <div>
                    <h2 style="margin:0;">Gestión de Facturas</h2>
                    <small style="color:#64748b;">Registros: <?= count($facturas) ?> | Total facturado activo: $<?= e(number_format($totalFacturado, 2)) ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/facturas/listar.php" style="display:flex; gap:0.4rem; margin:0; flex-wrap:wrap; align-items:center;">
                        <input type="text" name="q" placeholder="Número, cliente o detalle..." value="<?= e($busqueda) ?>" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <select name="estado" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="pagada" <?= $estadoFiltro === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                            <option value="anulada" <?= $estadoFiltro === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                        </select>
                        <input type="number" step="0.01" min="0" name="monto_min" placeholder="Monto mín. ($)" value="<?= e($montoMinRaw) ?>" style="width:130px; padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                        <?php if ($hayFiltros): ?>
                            <a href="/cruds/facturas/listar.php" class="btn btn-sec">Limpiar</a>
                        <?php endif; ?>
                    </form>
                    <a href="/cruds/facturas/crear.php" class="btn">+ Nueva Factura</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Detalle</th>
                        <th>Subtotal</th>
                        <th>Impuesto</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facturas)): ?>
                        <tr>
                            <td colspan="9">
                                <?= $hayFiltros ? 'No se encontraron facturas con los filtros aplicados.' : 'No hay facturas registradas.' ?>
                            </td>
                        </tr>
                    <?php else: foreach ($facturas as $f): ?>
                        <?php $estado = $f['_estado']; ?>
                        <tr>
                            <td><?= e($f['_numero']) ?></td>
                            <td><?= e($f['_fecha']) ?></td>
                            <td><?= e($f['_cliente']) ?></td>
                            <td><?= e($f['_detalle']) ?></td>
                            <td>$<?= e(number_format((float) $f['_subtotal'], 2)) ?></td>
                            <td>$<?= e(number_format((float) $f['_impuesto'], 2)) ?></td>
                            <td>$<?= e(number_format((float) $f['_total'], 2)) ?></td>
                            <td><?= e(ucfirst($estado)) ?></td>
                            <td style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                <?php if ($estado === 'pendiente'): ?>
                                    <a href="/cruds/facturas/editar.php?id=<?= e($f['id'] ?? '') ?>" class="btn btn-warn">Editar</a>
                                    <form method="POST" action="/cruds/facturas/eliminar.php" style="margin:0;" onsubmit="return confirm('¿Eliminar esta factura pendiente?');">
                                        <input type="hidden" name="id" value="<?= e($f['id'] ?? '') ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                <?php elseif ($estado !== 'anulada'): ?>
                                    <form method="POST" action="/cruds/facturas/eliminar.php" style="margin:0;" onsubmit="return confirm('¿Anular esta factura?');">
                                        <input type="hidden" name="id" value="<?= e($f['id'] ?? '') ?>">
                                        <input type="hidden" name="accion" value="anular">
                                        <button type="submit" class="btn btn-danger">Anular</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.85rem;">Sin acciones</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>