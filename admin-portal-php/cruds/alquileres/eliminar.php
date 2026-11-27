<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $servicio = new Recursos('/alquileres');
        $res = $servicio->eliminar($id);
        if ($res['ok']) {
            header('Location: /cruds/alquileres/listar.php?msg=' . urlencode('Alquiler eliminado correctamente.'));
            exit;
        }
        $err = extraerErrorApi($res['data'], 'No se pudo eliminar el alquiler (' . $res['status'] . ').');
        header('Location: /cruds/alquileres/listar.php?err=' . urlencode($err));
        exit;
    }
}
header('Location: /cruds/alquileres/listar.php');
exit;