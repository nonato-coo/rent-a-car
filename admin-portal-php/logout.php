<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/Auth.php';

Auth::cerrarSesion();
header('Location: /login.php');
exit;