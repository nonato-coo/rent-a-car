<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cruds/facturas/listar.php?err=' . urlencode('Usa el botón de la tabla para realizar esta acción.'));
    exit;
}

$idRaw = trim($_POST['id'] ?? '');
$id = filter_var($idRaw, FILTER_VALIDATE_INT);
$accion = trim($_POST['accion'] ?? 'anular');

if ($id === false || $id <= 0) {
    header('Location: /cruds/facturas/listar.php?err=' . urlencode('ID de factura inválido.'));
    exit;
}

$servicio = new Recursos('/facturas');
$rawFactura = $servicio->obtenerPorId($id);
if (is_array($rawFactura) && isset($rawFactura['factura']) && is_array($rawFactura['factura'])) {
    $factura = $rawFactura['factura'];
} elseif (is_array($rawFactura) && isset($rawFactura['data']) && is_array($rawFactura['data'])) {
    $factura = $rawFactura['data'];
} else {
    $factura = is_array($rawFactura) ? $rawFactura : null;
}

if (!$factura) {
    header('Location: /cruds/facturas/listar.php?err=' . urlencode('La factura no existe.'));
    exit;
}

$estadoActual = strtolower(trim((string) ($factura['estado'] ?? 'pendiente')));

if ($accion === 'eliminar') {
    if ($estadoActual !== 'pendiente') {
        header('Location: /cruds/facturas/listar.php?err=' . urlencode('Solo se pueden eliminar facturas pendientes; las demás conviene anularlas.'));
        exit;
    }
    $res = $servicio->eliminar($id);
    if (!($res['ok'] ?? false)) {
        $msgErr = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'No se pudo eliminar la factura.'));
        header('Location: /cruds/facturas/listar.php?err=' . urlencode($msgErr));
        exit;
    }
    header('Location: /cruds/facturas/listar.php?msg=' . urlencode('Factura eliminada correctamente.'));
    exit;
}

$res = $servicio->actualizar($id, ['estado' => 'anulada']);
if (!($res['ok'] ?? false)) {
    $msgErr = extraerErrorApi($res['data'] ?? null, (string) ($res['error'] ?? 'No se pudo anular la factura.'));
    header('Location: /cruds/facturas/listar.php?err=' . urlencode($msgErr));
    exit;
}

header('Location: /cruds/facturas/listar.php?msg=' . urlencode('Factura anulada correctamente.'));
exit;