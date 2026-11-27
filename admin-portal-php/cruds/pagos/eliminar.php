<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Recursos.php';
require_once __DIR__ . '/../../src/Helpers.php';

Auth::requerirLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $servicio = new Recursos('/pagos');
        $res = $servicio->eliminar($id);
        if ($res['ok']) {
            header('Location: /cruds/pagos/listar.php?msg=' . urlencode('Pago eliminado correctamente.'));
            exit;
        }
        $err = extraerErrorApi($res['data'], 'No se pudo eliminar el pago (' . $res['status'] . ').');
        header('Location: /cruds/pagos/listar.php?err=' . urlencode($err));
        exit;
    }
}
header('Location: /cruds/pagos/listar.php');
exit;