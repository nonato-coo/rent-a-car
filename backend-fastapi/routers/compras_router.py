"""Define los endpoints del CRUD de compras (el stock se ajusta automáticamente).

Endpoints:
    GET /compras | GET /compras/{id} | POST /compras
    PUT /compras/{id} | DELETE /compras/{id}
"""

from fastapi import APIRouter, HTTPException
from schemas.compra import CompraCreate, CompraUpdate
from core.excepciones import NoEncontradoError
from repositories.compra_repository import CompraRepository
from services.inventario_service import ServicioInventario

router = APIRouter(tags=["Compras"])
compra_repo = CompraRepository()
inventario_service = ServicioInventario()

def _error_http(e: ValueError) -> HTTPException:
    """Traduce un error del servicio a HTTP 404 (no encontrado) o 400 (dato inválido)."""

    return HTTPException(status_code=404 if isinstance(e, NoEncontradoError) else 400, detail=str(e))

@router.get("/compras")
@router.get("/compras/", include_in_schema=False)
def listar_compras():
    """Obtiene todas las compras, de la más reciente a la más antigua."""

    compras = sorted(compra_repo.listar_todos(), key=lambda c: c.id, reverse=True)
    return [c.to_dict() for c in compras]

@router.get("/compras/{compra_id}")
def obtener_compra(compra_id: int):
    """Obtiene una compra por su id; responde 404 si no existe."""

    compra = compra_repo.obtener_por_id(compra_id)
    if not compra:
        raise HTTPException(status_code=404, detail="Compra no encontrada")
    return compra.to_dict()

@router.post("/compras")
@router.post("/compras/", include_in_schema=False)
def registrar_compra(data: CompraCreate):
    """Registra una compra y aumenta el stock del vehículo."""

    try:
        return inventario_service.registrar_compra(data.vehiculo_id, data.cantidad,
                                                   data.costo_unitario, data.proveedor, data.fecha)
    except ValueError as e:
        raise _error_http(e) from e

@router.put("/compras/{compra_id}")
def actualizar_compra(compra_id: int, data: CompraUpdate):
    """Actualiza una compra y reajusta el stock de los vehículos afectados."""

    try:
        return inventario_service.actualizar_compra(compra_id, data.vehiculo_id, data.cantidad,
                                                    data.costo_unitario, data.proveedor, data.fecha)
    except ValueError as e:
        raise _error_http(e) from e

@router.delete("/compras/{compra_id}")
def eliminar_compra(compra_id: int):
    """Elimina una compra y descuenta sus unidades del stock."""

    try:
        inventario_service.eliminar_compra(compra_id)
    except ValueError as e:
        raise _error_http(e) from e
    return {"mensaje": "Compra eliminada", "id": compra_id}
