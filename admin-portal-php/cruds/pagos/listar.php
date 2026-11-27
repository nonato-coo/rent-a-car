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
$pagos = $extraerLista($servicio->listar(), 'pagos');

$alquileres = $extraerLista((new Recursos('/alquileres'))->listar(), 'alquileres');
$clientes   = $extraerLista((new Recursos('/clientes'))->listar(), 'clientes');
$vehiculos  = $extraerLista((new Recursos('/vehiculos'))->listar(), 'vehiculos');

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
        $acc[$vid] = trim((string) (($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')));
    }
    return $acc;
}, []);

$mapaAlquileres = array_reduce($alquileres, function (array $acc, array $a) use ($mapaClientes, $mapaVehiculos): array {
    $aid = (int) ($a['id'] ?? 0);
    if ($aid > 0) {
        $cid = (int) ($a['cliente_id'] ?? 0);
        $vid = (int) ($a['vehiculo_id'] ?? 0);
        $cli = $mapaClientes[$cid] ?? ('Cliente #' . $cid);
        $veh = $mapaVehiculos[$vid] ?? ('Vehículo #' . $vid);
        $acc[$aid] = 'Alquiler #' . $aid . ' — ' . $cli . ' (' . $veh . ')';
    }
    return $acc;
}, []);

$pagos = $servicio->transformar($pagos, function (array $p) use ($mapaAlquileres): array {
    $aid = (int) ($p['alquiler_id'] ?? 0);
    return $p + [
        '_alquiler' => $mapaAlquileres[$aid] ?? ($aid > 0 ? 'Alquiler #' . $aid : '—'),
        '_metodo'   => (string) ($p['metodo'] ?? $p['metodo_pago'] ?? '—'),
        '_fecha'    => substr((string) ($p['fecha'] ?? ''), 0, 10),
        '_monto'    => (float) ($p['monto'] ?? 0),
    ];
});

$busqueda = trim($_GET['q'] ?? '');
$metodoFiltro = trim($_GET['metodo'] ?? '');

if ($busqueda !== '') {
    $needle = mb_strtolower($busqueda);
    $pagos = $servicio->filtrar(
        $pagos,
        fn(array $p): bool =>
            str_contains(mb_strtolower((string) ($p['_alquiler'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($p['_metodo'] ?? '')), $needle)
    );
}

if ($metodoFiltro !== '') {
    $pagos = $servicio->filtrar(
        $pagos,
        fn(array $p): bool => mb_strtolower((string) ($p['_metodo'] ?? '')) === mb_strtolower($metodoFiltro)
    );
}

$totalPagos = (float) $servicio->sumarCampo($pagos, '_monto');
$hayFiltros = $busqueda !== '' || $metodoFiltro !== '';

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagos — Rent a Car</title>
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
                    <h2 style="margin:0;">Gestión de Pagos</h2>
                    <small style="color:#64748b;">Registros: <?= count($pagos) ?> | Total recaudado: $<?= e(number_format($totalPagos, 2)) ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/pagos/listar.php" style="display:flex; gap:0.4rem; margin:0; flex-wrap:wrap; align-items:center;">
                        <input type="text" name="q" placeholder="Cliente, vehículo o #alquiler..." value="<?= e($busqueda) ?>" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <select name="metodo" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">Todos los métodos</option>
                            <option value="Tarjeta" <?= strcasecmp($metodoFiltro, 'Tarjeta') === 0 ? 'selected' : '' ?>>Tarjeta</option>
                            <option value="Efectivo" <?= strcasecmp($metodoFiltro, 'Efectivo') === 0 ? 'selected' : '' ?>>Efectivo</option>
                            <option value="Transferencia" <?= strcasecmp($metodoFiltro, 'Transferencia') === 0 ? 'selected' : '' ?>>Transferencia</option>
                        </select>
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                        <?php if ($hayFiltros): ?>
                            <a href="/cruds/pagos/listar.php" class="btn btn-sec">Limpiar</a>
                        <?php endif; ?>
                    </form>
                    <a href="/cruds/pagos/crear.php" class="btn">+ Nuevo Pago</a>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Alquiler / Cliente</th>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagos)): ?>
                        <tr><td colspan="6"><?= $hayFiltros ? 'No se encontraron pagos con los filtros aplicados.' : 'No hay pagos registrados.' ?></td></tr>
                    <?php else: foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= e($p['id'] ?? '') ?></td>
                            <td><?= e($p['_alquiler']) ?></td>
                            <td><?= e($p['_fecha']) ?></td>
                            <td><?= e(ucfirst($p['_metodo'])) ?></td>
                            <td><strong>$<?= e(number_format((float) $p['_monto'], 2)) ?></strong></td>
                            <td style="display:flex; gap:0.4rem;">
                                <a href="/cruds/pagos/editar.php?id=<?= e($p['id'] ?? '') ?>" class="btn btn-warn">Editar</a>
                                <form method="POST" action="/cruds/pagos/eliminar.php" style="margin:0;" onsubmit="return confirm('¿Seguro que deseas eliminar este pago?');">
                                    <input type="hidden" name="id" value="<?= e($p['id'] ?? '') ?>">
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