"""Define los endpoints del CRUD de alquileres.

Endpoints:
    GET /alquileres | GET /alquileres/{id} | POST /alquileres (crea reserva completa)
    PUT /alquileres/{id} | PUT /alquileres/{id}/estado | DELETE /alquileres/{id}
"""

from fastapi import APIRouter, HTTPException
from schemas.alquiler import ReservaCreate, AlquilerUpdate, EstadoAlquilerUpdate
from core.excepciones import NoEncontradoError
from repositories.alquiler_repository import AlquilerRepository
from services.reservas_service import ServicioReservas

router = APIRouter(tags=["Alquileres"])
alquiler_repo = AlquilerRepository()
reservas_service = ServicioReservas()

def _error_http(e: ValueError) -> HTTPException:
    """Traduce un error del servicio a HTTP 404 (no encontrado) o 400 (dato inválido)."""

    return HTTPException(status_code=404 if isinstance(e, NoEncontradoError) else 400, detail=str(e))

@router.get("/alquileres")
@router.get("/alquileres/", include_in_schema=False)
def listar_alquileres():
    """Obtiene todos los alquileres, del más reciente al más antiguo."""

    alquileres = sorted(alquiler_repo.listar_todos(), key=lambda a: a.id, reverse=True)
    return [a.to_dict() for a in alquileres]

@router.get("/alquileres/{alquiler_id}")
def obtener_alquiler(alquiler_id: int):
    """Obtiene un alquiler por su id; responde 404 si no existe."""

    alquiler = alquiler_repo.obtener_por_id(alquiler_id)
    if not alquiler:
        raise HTTPException(status_code=404, detail="Alquiler no encontrado")
    return alquiler.to_dict()

@router.post("/alquileres")
@router.post("/alquileres/", include_in_schema=False)
def crear_alquiler(data: ReservaCreate):
    """Crea un alquiler con su factura y pago (igual que POST /reservas)."""

    try:
        return reservas_service.crear_reserva_completa(data.cliente_id, data.vehiculo_id,
                                                       data.fecha_inicio, data.fecha_fin, data.metodo_pago)
    except ValueError as e:
        raise _error_http(e) from e

@router.put("/alquileres/{alquiler_id}/estado")
def cambiar_estado_alquiler(alquiler_id: int, data: EstadoAlquilerUpdate):
    """Cambia el estado de un alquiler respetando las transiciones permitidas."""

    try:
        return reservas_service.cambiar_estado(alquiler_id, data.estado)
    except ValueError as e:
        raise _error_http(e) from e

@router.put("/alquileres/{alquiler_id}")
def actualizar_alquiler(alquiler_id: int, data: AlquilerUpdate):
    """Actualiza los campos recibidos de un alquiler."""

    try:
        return reservas_service.actualizar_alquiler(alquiler_id, data.cliente_id, data.vehiculo_id,
                                                    data.fecha_inicio, data.fecha_fin, data.estado)
    except ValueError as e:
        raise _error_http(e) from e

@router.delete("/alquileres/{alquiler_id}")
def eliminar_alquiler(alquiler_id: int):
    """Elimina un alquiler junto con sus pagos y facturas."""

    try:
        reservas_service.eliminar_alquiler(alquiler_id)
    except ValueError as e:
        raise _error_http(e) from e
    return {"mensaje": "Alquiler eliminado", "id": alquiler_id}
