<?php
require_once __DIR__ . '/ApiClient.php';

class Recursos
{
    private string $endpoint;

    public function __construct(string $endpoint)
    {
        $this->endpoint = '/' . trim($endpoint, '/');
    }

    public function listar(): array
    {
        $res = ApiClient::get($this->endpoint . '/');
        if (!$res['ok'] || !is_array($res['data'])) {
            $res = ApiClient::get($this->endpoint);
        }
        if (!$res['ok'] || !is_array($res['data'])) {
            return [];
        }

        $data = $res['data'];
        if (array_is_list($data)) {
            return $data;
        }
        foreach (['vehiculos', 'compras', 'clientes', 'usuarios', 'alquileres', 'pagos', 'facturas', 'data', 'items'] as $clave) {
            if (isset($data[$clave]) && is_array($data[$clave])) {
                return $data[$clave];
            }
        }
        return [];
    }

    public function obtenerPorId(int $id): ?array
    {
        $res = ApiClient::get($this->endpoint . '/' . $id);
        return ($res['ok'] && is_array($res['data'])) ? $res['data'] : null;
    }

    public function crear(array $datos): array
    {
        return ApiClient::post($this->endpoint, $datos);
    }

    public function actualizar(int $id, array $datos): array
    {
        return ApiClient::put($this->endpoint . '/' . $id, $datos);
    }

    public function eliminar(int $id): array
    {
        return ApiClient::delete($this->endpoint . '/' . $id);
    }

    public function filtrar(array $registros, callable $criterio): array
    {
        return array_values(array_filter($registros, $criterio));
    }

    public function transformar(array $registros, callable $transformador): array
    {
        return array_map($transformador, $registros);
    }

    public function sumarCampo(array $registros, string $campo): float
    {
        return (float) array_reduce(
            $registros,
            fn(float $acumulado, array $item): float => $acumulado + (float) ($item[$campo] ?? 0),
            0.0
        );
    }
}