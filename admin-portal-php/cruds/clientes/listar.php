<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$servicio = new Recursos('/clientes');
$clientes = $servicio->listar();

// Filtro por búsqueda de texto con programación funcional (array_filter)
$busqueda = trim($_GET['q'] ?? '');
if ($busqueda !== '') {
    $needle = mb_strtolower($busqueda);
    $clientes = $servicio->filtrar(
        $clientes,
        fn(array $c): bool =>
            str_contains(mb_strtolower((string) ($c['nombre'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($c['email'] ?? '')), $needle) ||
            str_contains(mb_strtolower((string) ($c['telefono'] ?? '')), $needle)
    );
}

$mensaje = $_GET['msg'] ?? '';
$error   = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 1rem; font-weight: bold; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .btn { padding: 0.5rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.9rem; display: inline-block; }
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
        </div>
        <div><a href="/logout.php" style="color:#fca5a5;">Cerrar sesión</a></div>
    </nav>

    <div class="container">
        <?php if ($mensaje !== ''): ?><div class="alert-ok"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>

        <div class="card">
            <div class="header-row">
                <div>
                    <h2 style="margin:0;">Gestión de Clientes</h2>
                    <small style="color:#64748b;">Total clientes: <?= count($clientes) ?></small>
                </div>
                <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                    <form method="GET" action="/cruds/clientes/listar.php" style="display:flex; gap:0.4rem; margin:0;">
                        <input type="text" name="q" placeholder="Buscar nombre, correo o teléfono..." value="<?= e($busqueda) ?>" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                        <button type="submit" class="btn btn-sec">Buscar</button>
                    </form>
                    <a href="/cruds/clientes/crear.php" class="btn">+ Nuevo Cliente</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="5">No hay clientes registrados.</td></tr>
                    <?php else: foreach ($clientes as $c): ?>
                        <tr>
                            <td><?= e($c['id'] ?? '') ?></td>
                            <td><?= e($c['nombre'] ?? '') ?></td>
                            <td><?= e($c['email'] ?? '') ?></td>
                            <td><?= e($c['telefono'] ?? '') ?></td>
                            <td><?= e($c['direccion'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>