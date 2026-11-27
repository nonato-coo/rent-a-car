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
$compras = $extraerLista($servicio->listar(), 'compras');

$vehiculos = $extraerLista((new Recursos('/vehiculos'))->listar(), 'vehiculos');
$mapaVehiculos = array_reduce(
    $vehiculos,
    function (array $acc, array $v): array {
        $vid = (int) ($v['id'] ?? 0);
        if ($vid > 0) {
            $desc = trim((string) (($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')));
            $acc[$vid] = $desc !== '' ? ($desc . ' (#' . $vid . ')') : ('Vehículo #' . $vid);
        }
        return $acc;
    },
    []
);

$compras = $servicio->transformar($compras, function (array $c) use ($mapaVehiculos): array {
    $vid = (int) ($c['vehiculo_id'] ?? 0);
    $cantidad = (int) ($c['cantidad'] ?? 1);
    $costoUnit = (float) ($c['costo_unitario'] ?? $c['precio_unitario'] ?? 0);
    $total = (float) ($c['total'] ?? ($cantidad * $costoUnit));
    $vehiculoTexto = $vid > 0 ? ($mapaVehiculos[$vid] ?? ('Vehículo #' . $vid)) : 'General';

    return $c + [
        '_vehiculo'       => $vehiculoTexto,
        '_costo_unitario' => $costoUnit,
        '_total'          => $total,
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
    $compras = $servicio->filtrar(
        $compras,
        fn(array $c): bool =>
            str_contains(mb_strtolower((string) ($c['proveedor'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($c['concepto'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($c['_vehiculo'] ?? '')), $needle)
    );
}

if ($estadoFiltro !== '') {
    $compras = $servicio->filtrar(
        $compras,
        fn(array $c): bool => ((string) ($c['estado'] ?? '')) === $estadoFiltro
    );
}

if ($avisoFiltro === '' && $montoMin !== '') {
    $minVal = (float) $montoMin;
    $compras = $servicio->filtrar(
        $compras,
        fn(array $c): bool => ((float) ($c['_total'] ?? 0)) >= $minVal
    );
}

$comprasValidas = $servicio->filtrar(
    $compras,
    fn(array $c): bool => ((string) ($c['estado'] ?? '')) !== 'anulada'
);
$totalCompras = (float) $servicio->sumarCampo($comprasValidas, '_total');
$hayFiltros = $busqueda !== '' || $estadoFiltro !== '' || $montoMinRaw !== '';

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compras — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 0.9rem; font-weight: bold; }
        .container { max-width: 1150px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .btn { padding: 0.5rem 0.9rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; display: inline-block; }
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
                    <h2 style="margin:0;">Gestión de Compras</h2>
                    <small style="color:#64748b;">Registros: <?= count($compras) ?> | Monto activo: $<?= e(number_format($totalCompras, 2)) ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/compras/listar.php" style="display:flex; gap:0.4rem; margin:0; flex-wrap:wrap; align-items:center;">
                        <input type="text" name="q" placeholder="Proveedor, concepto o vehículo..." value="<?= e($busqueda) ?>" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <select name="estado" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">Todos los estados</option>
                            <option value="completada" <?= $estadoFiltro === 'completada' ? 'selected' : '' ?>>Completada</option>
                            <option value="pendiente" <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="anulada" <?= $estadoFiltro === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                        </select>
                        <input type="number" step="0.01" min="0" name="monto_min" placeholder="Monto mín. ($)" value="<?= e($montoMinRaw) ?>" style="width:130px; padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                        <?php if ($hayFiltros): ?>
                            <a href="/cruds/compras/listar.php" class="btn btn-sec">Limpiar</a>
                        <?php endif; ?>
                    </form>
                    <a href="/cruds/compras/crear.php" class="btn">+ Nueva Compra</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Vehículo</th>
                        <th>Proveedor</th>
                        <th>Concepto</th>
                        <th>Cant.</th>
                        <th>Costo Unit.</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($compras)): ?>
                        <tr>
                            <td colspan="10">
                                <?= $hayFiltros ? 'No se encontraron compras con los filtros aplicados.' : 'No hay compras registradas.' ?>
                            </td>
                        </tr>
                    <?php else: foreach ($compras as $c): ?>
                        <?php $estado = (string) ($c['estado'] ?? 'pendiente'); ?>
                        <tr>
                            <td><?= e($c['id'] ?? '') ?></td>
                            <td><?= e($c['fecha'] ?? '') ?></td>
                            <td><?= e($c['_vehiculo']) ?></td>
                            <td><?= e($c['proveedor'] ?? '') ?></td>
                            <td><?= e($c['concepto'] ?? '') ?></td>
                            <td><?= e($c['cantidad'] ?? 1) ?></td>
                            <td>$<?= e(number_format((float) $c['_costo_unitario'], 2)) ?></td>
                            <td>$<?= e(number_format((float) $c['_total'], 2)) ?></td>
                            <td><?= e(ucfirst($estado)) ?></td>
                            <td style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                <a href="/cruds/compras/editar.php?id=<?= e($c['id'] ?? '') ?>" class="btn btn-warn">Editar</a>
                                <?php if ($estado === 'pendiente'): ?>
                                    <form method="POST" action="/cruds/compras/eliminar.php" style="margin:0;" onsubmit="return confirm('¿Eliminar esta compra pendiente?');">
                                        <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                <?php elseif ($estado !== 'anulada'): ?>
                                    <form method="POST" action="/cruds/compras/eliminar.php" style="margin:0;" onsubmit="return confirm('¿Anular esta compra?');">
                                        <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">
                                        <input type="hidden" name="accion" value="anular">
                                        <button type="submit" class="btn btn-danger">Anular</button>
                                    </form>
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