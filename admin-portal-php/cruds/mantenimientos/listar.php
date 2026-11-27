<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$servicioVehiculos = new Recursos('/vehiculos');
$vehiculos = $servicioVehiculos->listar();

$archivoOrdenes = __DIR__ . '/../../data/mantenimientos.json';
$ordenes = [];
if (is_file($archivoOrdenes)) {
    $decoded = json_decode((string) file_get_contents($archivoOrdenes), true);
    if (is_array($decoded)) {
        $ordenes = $decoded;
    }
}

$busqueda = trim($_GET['q'] ?? '');
$stockMaxRaw = trim($_GET['stock_max'] ?? '');
$stockMax = '';
$avisoFiltro = '';

if ($stockMaxRaw !== '') {
    if (!ctype_digit($stockMaxRaw)) {
        $avisoFiltro = 'El stock máximo debe ser un número entero mayor o igual a 0 (no se permiten números negativos).';
    } else {
        $stockMax = (string) (int) $stockMaxRaw;
    }
}

if ($busqueda !== '') {
    $needle = mb_strtolower($busqueda);
    $vehiculos = $servicioVehiculos->filtrar(
        $vehiculos,
        fn(array $v): bool =>
            str_contains(mb_strtolower((string) ($v['marca'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($v['modelo'] ?? '')), $needle)
    );
}

if ($avisoFiltro === '' && $stockMax !== '') {
    $maxStockVal = (int) $stockMax;
    $vehiculos = $servicioVehiculos->filtrar(
        $vehiculos,
        fn(array $v): bool => (int) ($v['stock_total'] ?? 0) <= $maxStockVal
    );
}

$flotaFormateada = $servicioVehiculos->transformar($vehiculos, function (array $v) use ($ordenes): array {
    $vid = (int) ($v['id'] ?? 0);
    $ordenesVehiculo = array_filter(
        $ordenes,
        fn(array $o): bool => (int) ($o['vehiculo_id'] ?? 0) === $vid
    );
    $v['ordenes_registradas'] = count($ordenesVehiculo);
    $v['estado_mantenimiento'] = count($ordenesVehiculo) > 0 ? 'Con mantenimiento' : 'Sin órdenes';
    return $v;
});

$totalStock = (int) $servicioVehiculos->sumarCampo($flotaFormateada, 'stock_total');
$costoTotalOrdenes = (float) array_reduce(
    $ordenes,
    fn(float $acc, array $o): float => $acc + max(0.0, (float) ($o['costo'] ?? 0)),
    0.0
);

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimientos — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 0.9rem; font-weight: bold; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem; }
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
            <a href="/cruds/mantenimientos/listar.php">Mantenimientos</a>
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
                    <h2 style="margin:0;">Gestión de Mantenimientos</h2>
                    <small style="color:#64748b;">Vehículos: <?= count($flotaFormateada) ?> | Stock total: <?= $totalStock ?> | Costo órdenes: $<?= number_format($costoTotalOrdenes, 2) ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/mantenimientos/listar.php" style="display:flex; gap:0.4rem; margin:0; flex-wrap:wrap;">
                        <input type="text" name="q" placeholder="Marca o modelo..." value="<?= e($busqueda) ?>" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <input type="number" min="0" name="stock_max" placeholder="Stock máx..." value="<?= e($stockMax) ?>" style="width:125px; padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                    </form>
                    <a href="/cruds/mantenimientos/crear.php" class="btn">+ Nuevo Mantenimiento</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehículo</th>
                        <th>Año</th>
                        <th>Stock</th>
                        <th>Órdenes</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($flotaFormateada)): ?>
                        <tr><td colspan="6">No hay vehículos registrados o coincidentes.</td></tr>
                    <?php else: foreach ($flotaFormateada as $v): ?>
                        <tr>
                            <td><?= e($v['id'] ?? '') ?></td>
                            <td><?= e(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?></td>
                            <td><?= e($v['anio'] ?? '') ?></td>
                            <td><?= e($v['stock_total'] ?? 0) ?></td>
                            <td><?= e($v['ordenes_registradas']) ?></td>
                            <td><?= e($v['estado_mantenimiento']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Órdenes de Mantenimiento Registradas (<?= count($ordenes) ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehículo ID</th>
                        <th>Fecha</th>
                        <th>Detalle</th>
                        <th>Costo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ordenes)): ?>
                        <tr><td colspan="6">No hay órdenes de mantenimiento registradas todavía.</td></tr>
                    <?php else: foreach ($ordenes as $o): ?>
                        <tr>
                            <td><?= e($o['id'] ?? '') ?></td>
                            <td><?= e($o['vehiculo_id'] ?? '') ?></td>
                            <td><?= e($o['fecha'] ?? '') ?></td>
                            <td><?= e($o['detalle'] ?? '') ?></td>
                            <td><strong>$<?= e(number_format(max(0.0, (float) ($o['costo'] ?? 0)), 2)) ?></strong></td>
                            <td style="display:flex; gap:0.4rem;">
                                <a href="/cruds/mantenimientos/editar.php?id=<?= e($o['id'] ?? 0) ?>" class="btn btn-warn">Editar</a>
                                <form method="POST" action="/cruds/mantenimientos/eliminar.php" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= e($o['id'] ?? 0) ?>">
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