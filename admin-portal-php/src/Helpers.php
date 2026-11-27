<?php

function e($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function extraerErrorApi($data, string $porDefecto = 'Ocurrió un error al comunicarse con la API.'): string {
    if (is_array($data)) {
        if (isset($data['detail'])) {
            if (is_string($data['detail'])) {
                return $data['detail'];
            }
            if (is_array($data['detail'])) {
                $mensajes = [];
                foreach ($data['detail'] as $err) {
                    $campo = isset($err['loc']) ? end($err['loc']) : 'campo';
                    $msg   = $err['msg'] ?? 'inválido';
                    $mensajes[] = "$campo: $msg";
                }
                return implode(' | ', $mensajes);
            }
        }
        if (isset($data['mensaje']) && is_string($data['mensaje'])) {
            return $data['mensaje'];
        }
    }
    return $porDefecto;
}