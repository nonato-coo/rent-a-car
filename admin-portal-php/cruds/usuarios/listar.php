<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$servicio = new Recursos('/usuarios');
$usuarios =$servicio->listar();

$rolFiltro = trim($_GET['rol'] ?? '');
if ($rolFiltro !== '') {
    $usuarios =$servicio->filtrar($usuarios, fn(array$u): bool => ($u['rol'] ?? '') ===$rolFiltro);
}

$usuariosFormateados =$servicio->transformar($usuarios, function (array$u): array {
    $u['rol_etiqueta'] = (($u['rol'] ?? '') === 'admin') ? 'Administrador' : 'Cliente';
    return $u;
});

$totalAdmins = array_reduce($usuariosFormateados,
    fn(int $acc, array$u): int => $acc + (($u['rol'] ?? '') === 'admin' ? 1 : 0),
    0
);

$mensaje =$_GET['msg'] ?? '';
$error   =$_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios — Rent a Car</title>
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
        </div>
        <div><a href="/logout.php" style="color:#fca5a5;">Cerrar sesión</a></div>
    </nav>
    <div class="container">
        <?php if ($mensaje !== ''): ?><div class="alert-ok"><?= e($mensaje) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <div class="card">
            <div class="header-row">
                <div>
                    <h2 style="margin:0;">Gestión de Usuarios</h2>
                    <small style="color:#64748b;">Total: <?= count($usuariosFormateados) ?> \vert{} Administradores: <?=$totalAdmins ?></small>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <form method="GET" action="/cruds/usuarios/listar.php" style="display:flex; gap:0.4rem; margin:0;">
                        <select name="rol" style="padding:0.45rem; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">Todos</option>
                            <option value="admin" <?= $rolFiltro === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="cliente" <?= $rolFiltro === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                        </select>
                        <button type="submit" class="btn btn-sec">Filtrar</button>
                    </form>
                    <a href="/cruds/usuarios/crear.php" class="btn">+ Nuevo Usuario</a>
                </div>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($usuariosFormateados)): ?>
                        <tr><td colspan="5">No hay usuarios registrados.</td></tr>
                    <?php else: foreach ($usuariosFormateados as$u): ?>
                        <tr>
                            <td><?= e($u['id'] ?? '') ?></td>
                            <td><?= e($u['nombre'] ?? '') ?></td>
                            <td><?= e($u['email'] ?? '') ?></td>
                            <td><?= e($u['rol_etiqueta']) ?></td>
                            <td style="display:flex; gap:0.4rem;">
                                <a href="/cruds/usuarios/editar.php?id=<?= e($u['id']) ?>" class="btn btn-warn">Editar</a>
                                <form method="POST" action="/cruds/usuarios/eliminar.php" style="margin:0;">
                                    <input type="hidden" name="id" value="<?= e($u['id']) ?>">
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