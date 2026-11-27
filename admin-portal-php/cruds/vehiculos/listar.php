<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$servicio = new Recursos('/vehiculos');
$raw = $servicio->listar();
if (is_array($raw) && isset($raw['vehiculos']) && is_array($raw['vehiculos'])) {
    $vehiculos = $raw['vehiculos'];
} elseif (is_array($raw) && isset($raw['data']) && is_array($raw['data'])) {
    $vehiculos = $raw['data'];
} elseif (is_array($raw)) {
    $vehiculos = $raw;
} else {
    $vehiculos = [];
}

$busqueda = trim($_GET['q'] ?? '');
$soloDisponibles = isset($_GET['disponibles']) && $_GET['disponibles'] === '1';

$precioMaxRaw = trim($_GET['precio_max'] ?? '');
$precioMinRaw = trim($_GET['precio_min'] ?? '');
$precioMax = '';
$precioMin = '';
$avisoFiltro = '';

if ($precioMinRaw !== '') {
    if (!is_numeric($precioMinRaw) || (float) $precioMinRaw < 0) {
        $avisoFiltro = 'El precio mínimo no puede ser un número negativo.';
    } else {
        $precioMin = (string) (float) $precioMinRaw;
    }
}

if ($avisoFiltro === '' && $precioMaxRaw !== '') {
    if (!is_numeric($precioMaxRaw) || (float) $precioMaxRaw < 0) {
        $avisoFiltro = 'El precio máximo no puede ser un número negativo.';
    } else {
        $precioMax = (string) (float) $precioMaxRaw;
    }
}

if ($avisoFiltro === '' && $precioMin !== '' && $precioMax !== '' && (float) $precioMax < (float) $precioMin) {
    $avisoFiltro = 'El precio máximo no puede ser menor que el precio mínimo.';
}

if ($busqueda !== '') {
    $needle = mb_strtolower($busqueda);
    $vehiculos = $servicio->filtrar(
        $vehiculos,
        fn(array $v): bool =>
            str_contains(mb_strtolower((string) ($v['marca'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($v['modelo'] ?? '')), $needle)
    );
}

if ($soloDisponibles) {
    $vehiculos = $servicio->filtrar(
        $vehiculos,
        fn(array $v): bool => (int) ($v['stock_total'] ?? 0) > 0 && (($v['disponible'] ?? true) !== false)
    );
}

if ($avisoFiltro === '' && $precioMin !== '') {
    $minVal = (float) $precioMin;
    $vehiculos = $servicio->filtrar(
        $vehiculos,
        fn(array $v): bool => ((float) ($v['precio_por_dia'] ?? 0)) >= $minVal
    );
}

if ($avisoFiltro === '' && $precioMax !== '') {
    $maxVal = (float) $precioMax;
    $vehiculos = $servicio->filtrar(
        $vehiculos,
        fn(array $v): bool => ((float) ($v['precio_por_dia'] ?? 0)) <= $maxVal
    );
}

$stockTotal = (int) $servicio->sumarCampo($vehiculos, 'stock_total');

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vehículos — Rent a Car</title>
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
        <?php if ($avisoFiltro !== ''): ?><div class="alert-err"><?= e($avisoFiltro) ?></div><?php endif; ?>

        <div class="card">
            <div class="header-row">
                <div>
                    <h2 style="margin:0;">Gestión de Vehículos</h2>
                    <small style="color:#64748b;">Modelos: <?= count($vehiculos) ?> | Stock total: <?= $stockTotal ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/vehiculos/listar.php" style="display:flex; gap:0.4rem; margin:0; flex-wrap:wrap; align-items:center;">
                        <input type="text" name="q" placeholder="Marca o modelo..." value="<?= e($busqueda) ?>" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <input type="number" step="0.01" min="0" name="precio_min" placeholder="Precio mín. ($)" value="<?= e($precioMin) ?>" style="width:125px; padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <input type="number" step="0.01" min="0" name="precio_max" placeholder="Precio máx. ($)" value="<?= e($precioMax) ?>" style="width:125px; padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <label style="font-size:0.85rem; display:flex; align-items:center; gap:0.25rem;">
                            <input type="checkbox" name="disponibles" value="1" <?= $soloDisponibles ? 'checked' : '' ?>>
                            Con stock
                        </label>
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                        <a href="/cruds/vehiculos/listar.php" class="btn btn-sec">Limpiar</a>
                    </form>
                    <a href="/cruds/vehiculos/crear.php" class="btn">+ Nuevo Vehículo</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Año</th>
                        <th>Precio / Día</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehiculos)): ?>
                        <tr><td colspan="7">No hay vehículos registrados.</td></tr>
                    <?php else: foreach ($vehiculos as $v): ?>
                        <tr>
                            <td><?= e($v['id'] ?? '') ?></td>
                            <td><?= e($v['marca'] ?? '') ?></td>
                            <td><?= e($v['modelo'] ?? '') ?></td>
                            <td><?= e($v['anio'] ?? '') ?></td>
                            <td>$<?= e(number_format((float) ($v['precio_por_dia'] ?? 0), 2)) ?></td>
                            <td><?= e($v['stock_total'] ?? 0) ?></td>
                            <td style="display:flex; gap:0.4rem;">
                                <a href="/cruds/vehiculos/editar.php?id=<?= e($v['id']) ?>" class="btn btn-warn">Editar</a>
                                <form method="POST" action="/cruds/vehiculos/eliminar.php" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= e($v['id']) ?>">
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