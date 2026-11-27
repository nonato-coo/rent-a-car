<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));
if ($id <= 0) {
    header('Location: /cruds/pagos/listar.php?err=' . urlencode('ID de pago no válido.'));
    exit;
}

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
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alquilerId = (int) ($_POST['alquiler_id'] ?? 0);
    $monto      = (float) ($_POST['monto'] ?? 0);
    $metodo     = trim((string) ($_POST['metodo'] ?? 'Tarjeta'));
    $fecha      = trim((string) ($_POST['fecha'] ?? date('Y-m-d')));

    if ($alquilerId <= 0) {
        $error = 'El ID del alquiler no es válido.';
    } elseif ($monto <= 0) {
        $error = 'El monto debe ser mayor que $0.00.';
    } elseif ($fecha === '') {
        $error = 'La fecha es obligatoria.';
    } else {
        $datos = [
            'alquiler_id' => $alquilerId,
            'monto'       => round($monto, 2),
            'metodo'      => $metodo,
            'fecha'       => $fecha,
        ];
        $res = $servicio->actualizar($id, $datos);
        if ($res['ok']) {
            header('Location: /cruds/pagos/listar.php?msg=' . urlencode('Pago actualizado correctamente.'));
            exit;
        }
        $error = extraerErrorApi($res['data'], 'Error al actualizar pago (' . $res['status'] . ').');
    }
} else {
    $lista = $extraerLista($servicio->listar(), 'pagos');
    $encontrados = array_values($servicio->filtrar($lista, fn(array $p): bool => (int) ($p['id'] ?? 0) === $id));
    $datos = $encontrados[0] ?? null;
    if (!$datos) {
        header('Location: /cruds/pagos/listar.php?err=' . urlencode('No se encontró el pago #' . $id));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Pago #<?= e($id) ?> — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.6rem 1.2rem; background: #d97706; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container"><div class="card">
        <h2 style="margin-top:0;">Editar Pago #<?= e($id) ?></h2>
        <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="/cruds/pagos/editar.php?id=<?= e($id) ?>">
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <label>Alquiler ID</label>
            <input type="number" min="1" name="alquiler_id" value="<?= e($datos['alquiler_id'] ?? '') ?>" required>
            <label>Monto ($)</label>
            <input type="number" step="0.01" min="0.01" name="monto" value="<?= e($datos['monto'] ?? 0) ?>" required>
            <label>Método</label>
            <select name="metodo" required>
                <?php
                $metodoActual = strtolower(trim((string) ($datos['metodo'] ?? $datos['metodo_pago'] ?? 'Tarjeta')));
                foreach (['Tarjeta', 'Efectivo', 'Transferencia'] as $m):
                ?>
                    <option value="<?= e($m) ?>" <?= mb_strtolower($m) === $metodoActual ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= e(substr((string) ($datos['fecha'] ?? date('Y-m-d')), 0, 10)) ?>" required>
            <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn">Guardar Cambios</button>
                <a href="/cruds/pagos/listar.php" class="btn btn-sec">Cancelar</a>
            </div>
        </form>
    </div></div>
</body>
</html>