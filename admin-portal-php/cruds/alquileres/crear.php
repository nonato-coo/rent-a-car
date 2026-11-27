<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ApiClient.php';
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

$recursosClientes  = new Recursos('/clientes');
$recursosVehiculos = new Recursos('/vehiculos');

$clientes  = $extraerLista($recursosClientes->listar(), 'clientes');
$vehiculos = $extraerLista($recursosVehiculos->listar(), 'vehiculos');

$estaDisponible = static function (array $v): bool {
    $conStock = (int) ($v['stock_total'] ?? 0) > 0;
    $habilitado = !array_key_exists('habilitado', $v) || !empty($v['habilitado']);
    $disponibleFlag = !array_key_exists('disponible', $v) || ($v['disponible'] !== false && (int) $v['disponible'] !== 0);
    return $conStock && $habilitado && $disponibleFlag;
};

$vehiculosDisponibles = $recursosVehiculos->filtrar($vehiculos, $estaDisponible);

$error = '';
$datos = [
    'cliente_busqueda' => '',
    'cliente_id'       => '',
    'vehiculo_id'      => '',
    'cantidad'         => '1',
    'fecha_inicio'     => date('Y-m-d'),
    'fecha_fin'        => date('Y-m-d', strtotime('+1 day')),
    'metodo_pago'      => 'efectivo',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clienteBusqueda = trim((string) ($_POST['cliente_busqueda'] ?? ''));
    $vehiculoId      = (int) ($_POST['vehiculo_id'] ?? 0);
    $cantidadRaw     = trim((string) ($_POST['cantidad'] ?? '1'));
    $fechaInicio     = trim((string) ($_POST['fecha_inicio'] ?? ''));
    $fechaFin        = trim((string) ($_POST['fecha_fin'] ?? ''));
    $metodoPago      = trim((string) ($_POST['metodo_pago'] ?? 'efectivo'));

    $cantidad = filter_var($cantidadRaw, FILTER_VALIDATE_INT);
    if ($cantidad === false || $cantidad <= 0) {
        $error = 'La cantidad debe ser un número entero mayor que 0.';
        $cantidad = 1;
    }

    $clienteResueltoId = 0;
    if ($error === '') {
        if ($clienteBusqueda === '') {
            $error = 'Debes escribir el nombre, correo o ID del cliente.';
        } elseif (ctype_digit($clienteBusqueda)) {
            $idCandidato = (int) $clienteBusqueda;
            $porId = array_values($recursosClientes->filtrar(
                $clientes,
                fn(array $c): bool => (int) ($c['id'] ?? 0) === $idCandidato
            ));
            if (count($porId) === 1) {
                $clienteResueltoId = (int) ($porId[0]['id'] ?? 0);
            } else {
                $error = 'No se encontró un cliente con el ID #' . $idCandidato . '.';
            }
        } else {
            $needle = mb_strtolower($clienteBusqueda);
            $exactosEmail = array_values($recursosClientes->filtrar(
                $clientes,
                fn(array $c): bool => mb_strtolower(trim((string) ($c['email'] ?? ''))) === $needle
            ));

            if (count($exactosEmail) === 1) {
                $clienteResueltoId = (int) ($exactosEmail[0]['id'] ?? 0);
            } else {
                $coincidencias = array_values($recursosClientes->filtrar(
                    $clientes,
                    fn(array $c): bool =>
                        str_contains(mb_strtolower((string) ($c['nombre'] ?? '')), $needle) ||
                        str_contains(mb_strtolower((string) ($c['email'] ?? '')), $needle)
                ));

                if (count($coincidencias) === 1) {
                    $clienteResueltoId = (int) ($coincidencias[0]['id'] ?? 0);
                } elseif (count($coincidencias) > 1) {
                    $error = 'Hay varios clientes que coinciden con "' . $clienteBusqueda . '". Escribe su correo o ID exacto.';
                } else {
                    $error = 'No se encontró ningún cliente con "' . $clienteBusqueda . '".';
                }
            }
        }
    }

    $vehiculoSeleccionado = null;
    if ($error === '') {
        $encontrados = array_values($recursosVehiculos->filtrar(
            $vehiculos,
            fn(array $v): bool => (int) ($v['id'] ?? 0) === $vehiculoId
        ));
        $vehiculoSeleccionado = $encontrados[0] ?? null;
        if (!is_array($vehiculoSeleccionado)) {
            $error = 'Debes seleccionar un vehículo válido.';
        } elseif (!$estaDisponible($vehiculoSeleccionado)) {
            $error = 'El vehículo seleccionado no está disponible para alquiler.';
        } else {
            $stockDisp = (int) ($vehiculoSeleccionado['stock_total'] ?? 0);
            if ($cantidad > $stockDisp) {
                $error = 'La cantidad (' . $cantidad . ') supera el stock disponible (' . $stockDisp . ').';
            }
        }
    }

    if ($error === '') {
        if ($fechaInicio === '' || $fechaFin === '' || $fechaFin < $fechaInicio) {
            $error = 'La fecha de fin no puede ser menor que la fecha de inicio.';
        }
    }

    $datos = [
        'cliente_busqueda' => $clienteBusqueda,
        'cliente_id'       => $clienteResueltoId > 0 ? (string) $clienteResueltoId : '',
        'vehiculo_id'      => $vehiculoId > 0 ? (string) $vehiculoId : '',
        'cantidad'         => (string) $cantidad,
        'fecha_inicio'     => $fechaInicio,
        'fecha_fin'        => $fechaFin,
        'metodo_pago'      => $metodoPago,
    ];

    if ($error === '') {
        $d1 = new DateTimeImmutable($fechaInicio);
        $d2 = new DateTimeImmutable($fechaFin);
        $dias = max(1, (int) $d1->diff($d2)->days);
        $precioDia = (float) ($vehiculoSeleccionado['precio_por_dia'] ?? 0);
        $totalEstimado = round($dias * $precioDia * $cantidad, 2);

        $payload = [
            'cliente_id'   => $clienteResueltoId,
            'vehiculo_id'  => $vehiculoId,
            'cantidad'     => $cantidad,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'metodo_pago'  => $metodoPago,
            'total'        => $totalEstimado,
        ];

        $res = ApiClient::post('/reservas', $payload);
        if (!$res['ok'] && in_array((int) ($res['status'] ?? 0), [404, 405], true)) {
            $res = ApiClient::post('/alquileres', $payload);
        }
        if ($res['ok']) {
            header('Location: /cruds/alquileres/listar.php?msg=' . urlencode('Reserva registrada y stock actualizado.'));
            exit;
        }
        $error = extraerErrorApi($res['data'], 'Error al registrar reserva (' . $res['status'] . ').');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Reserva — Rent a Car</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1e293b; }
        .container { max-width: 620px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        label { display: block; font-weight: bold; margin-top: 1rem; margin-bottom: 0.3rem; }
        input, select { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        small.hint { display: block; color: #64748b; margin-top: 0.25rem; font-size: 0.82rem; }
        .btn { padding: 0.6rem 1.2rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 0.95rem; display: inline-block; }
        .btn-sec { background: #64748b; }
        .alert-err { background: #fee2e2; color: #b91c1c; padding: 0.8rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 style="margin-top:0;">Registrar Nueva Reserva</h2>
            <?php if ($error !== ''): ?><div class="alert-err"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" action="/cruds/alquileres/crear.php" autocomplete="off">
                <label>Cliente (nombre, correo o ID)</label>
                <input
                    type="text"
                    name="cliente_busqueda"
                    value="<?= e($datos['cliente_busqueda']) ?>"
                    placeholder="Ej. Ana, ana@rentacar.com o 1"
                    autocomplete="off"
                    required
                >
                <small class="hint">Escribe el nombre, correo o ID del cliente sin usar menú desplegable.</small>

                <label>Vehículo disponible</label>
                <select name="vehiculo_id" required>
                    <option value="">Seleccione un vehículo...</option>
                    <?php foreach ($vehiculosDisponibles as $v): ?>
                        <?php $vid = (int) ($v['id'] ?? 0); ?>
                        <option value="<?= e($vid) ?>" <?= (int) $datos['vehiculo_id'] === $vid ? 'selected' : '' ?>>
                            #<?= e($vid) ?> — <?= e(($v['marca'] ?? '') . ' ' . ($v['modelo'] ?? '')) ?> ($<?= e(number_format((float) ($v['precio_por_dia'] ?? 0), 2)) ?>/día | Stock: <?= e($v['stock_total'] ?? 0) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($vehiculosDisponibles)): ?>
                    <small class="hint" style="color:#b91c1c;">No hay vehículos disponibles en este momento.</small>
                <?php endif; ?>

                <label>Cantidad de unidades</label>
                <input
                    type="number"
                    name="cantidad"
                    min="1"
                    step="1"
                    value="<?= e($datos['cantidad']) ?>"
                    autocomplete="off"
                    required
                >

                <label>Fecha de inicio</label>
                <input type="date" name="fecha_inicio" value="<?= e($datos['fecha_inicio']) ?>" required>

                <label>Fecha de fin</label>
                <input type="date" name="fecha_fin" value="<?= e($datos['fecha_fin']) ?>" required>

                <label>Método de pago</label>
                <select name="metodo_pago" required>
                    <option value="efectivo" <?= $datos['metodo_pago'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                    <option value="tarjeta" <?= $datos['metodo_pago'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                    <option value="transferencia" <?= $datos['metodo_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                </select>

                <div style="margin-top: 1.5rem; display:flex; gap:0.5rem;">
                    <button type="submit" class="btn">Guardar Reserva</button>
                    <a href="/cruds/alquileres/listar.php" class="btn btn-sec">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>