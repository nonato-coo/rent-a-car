<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Recursos.php';
require_once __DIR__ . '/src/Helpers.php';

Auth::requerirLogin();

$usuario = $_SESSION['usuario'] ?? [];
$nombreUsuario = $usuario['nombre'] ?? ($usuario['email'] ?? 'Administrador');

$servicioVehiculos  = new Recursos('/vehiculos');
$servicioUsuarios   = new Recursos('/usuarios');
$servicioClientes   = new Recursos('/clientes');
$servicioAlquileres = new Recursos('/alquileres');

$vehiculos  = $servicioVehiculos->listar();
$usuarios   = $servicioUsuarios->listar();
$clientes   = $servicioClientes->listar();
$alquileres = $servicioAlquileres->listar();

// Programación funcional: contar vehículos disponibles con array_filter
$disponibles = $servicioVehiculos->filtrar(
    $vehiculos,
    fn(array $v): bool => !empty($v['habilitado']) && (int)($v['disponibles'] ?? 0) > 0
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        nav { background: #1e293b; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        nav a { color: #e2e8f0; text-decoration: none; margin-right: 1rem; font-weight: bold; }
        nav a:hover { color: #60a5fa; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 0.5rem; }
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
        <div>
            <span>Hola, <strong><?= e($nombreUsuario) ?></strong></span> |
            <a href="/logout.php" style="color:#fca5a5; margin-left:0.5rem;">Cerrar sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="margin-bottom: 1.5rem;">
            <h1 style="margin-top:0;">Panel de Administración — Rent a Car</h1>
            <p>Conectado a la API REST en <code>http://127.0.0.1:8000</code>.</p>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Usuarios</h3>
                <p><strong><?= count($usuarios) ?></strong> registrados</p>
                <a href="/cruds/usuarios/listar.php" class="btn">Administrar</a>
            </div>
            <div class="card">
                <h3>Clientes</h3>
                <p><strong><?= count($clientes) ?></strong> registrados</p>
                <a href="/cruds/clientes/listar.php" class="btn">Administrar</a>
            </div>
            <div class="card">
                <h3>Flota Vehicular</h3>
                <p><strong><?= count($vehiculos) ?></strong> modelos (<?= count($disponibles) ?> disponibles)</p>
                <a href="/cruds/vehiculos/listar.php" class="btn">Administrar</a>
            </div>
            <div class="card">
                <h3>Alquileres</h3>
                <p><strong><?= count($alquileres) ?></strong> registrados</p>
                <a href="/cruds/alquileres/listar.php" class="btn">Administrar</a>
            </div>
            <div class="card">
                <h3>Mantenimiento</h3>
                <p>Control de órdenes de mantenimiento y estado de la flota.</p>
                <a href="/cruds/mantenimientos/listar.php" class="btn">Abrir</a>
            </div>
        </div>
    </div>
</body>
</html>