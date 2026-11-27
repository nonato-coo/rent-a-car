<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cruds/compras/listar.php?err=' . urlencode('Usa el botón de la tabla para realizar esta acción.'));
    exit;
}

$idRaw = trim($_POST['id'] ?? '');
$idInt = filter_var($idRaw, FILTER_VALIDATE_INT);
$accion = trim($_POST['accion'] ?? 'anular');

if ($idInt === false || $idInt <= 0) {
    header('Location: /cruds/compras/listar.php?err=' . urlencode('ID de compra inválido.'));
    exit;
}

$servicio = new Recursos('/compras');
$rawCompra = $servicio->obtenerPorId($idInt);
if (is_array($rawCompra) && isset($rawCompra['compra']) && is_array($rawCompra['compra'])) {
    $compra = $rawCompra['compra'];
} elseif (is_array($rawCompra) && isset($rawCompra['data']) && is_array($rawCompra['data'])) {
    $compra = $rawCompra['data'];
} else {
    $compra = is_array($rawCompra) ? $rawCompra : null;
}

if (!$compra) {
    header('Location: /cruds/compras/listar.php?err=' . urlencode('La compra no existe.'));
    exit;
}

if ($accion === 'eliminar') {
    if (((string) ($compra['estado'] ?? '')) !== 'pendiente') {
        header('Location: /cruds/compras/listar.php?err=' . urlencode('Solo se pueden eliminar compras pendientes; las demás conviene anularlas.'));
        exit;
    }

    $res = $servicio->eliminar($idInt);
    if (!($res['ok'] ?? false)) {
        $msgErr = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'No se pudo eliminar la compra.'));
        header('Location: /cruds/compras/listar.php?err=' . urlencode($msgErr));
        exit;
    }

    header('Location: /cruds/compras/listar.php?msg=' . urlencode('Compra eliminada correctamente.'));
    exit;
}

$compra['estado'] = 'anulada';
unset($compra['id']);
$res = $servicio->actualizar($idInt, $compra);

if (!($res['ok'] ?? false)) {
    $msgErr = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'No se pudo anular la compra.'));
    header('Location: /cruds/compras/listar.php?err=' . urlencode($msgErr));
    exit;
}

header('Location: /cruds/compras/listar.php?msg=' . urlencode('Compra anulada correctamente.'));
exit;