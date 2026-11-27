<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';

Auth::requerirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /cruds/mantenimientos/listar.php?err=' . urlencode('Método no permitido para eliminar.'));
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: /cruds/mantenimientos/listar.php?err=' . urlencode('ID de mantenimiento no válido.'));
    exit;
}

$archivoOrdenes = __DIR__ . '/../../data/mantenimientos.json';
$ordenes = [];
if (is_file($archivoOrdenes)) {
    $decoded = json_decode((string) file_get_contents($archivoOrdenes), true);
    if (is_array($decoded)) {
        $ordenes = $decoded;
    }
}

$restantes = array_values(array_filter(
    $ordenes,
    fn(array $o): bool => (int) ($o['id'] ?? 0) !== $id
));

if (count($restantes) === count($ordenes)) {
    header('Location: /cruds/mantenimientos/listar.php?err=' . urlencode('No se encontró la orden #' . $id . '.'));
    exit;
}

file_put_contents($archivoOrdenes, json_encode($restantes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
header('Location: /cruds/mantenimientos/listar.php?msg=' . urlencode('Orden #' . $id . ' eliminada correctamente.'));
exit;