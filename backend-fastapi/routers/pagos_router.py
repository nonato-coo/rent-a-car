"""Define los endpoints del CRUD de pagos (simulados).

Endpoints:
    GET /pagos | GET /pagos/{id} | POST /pagos
    PUT /pagos/{id} | DELETE /pagos/{id}
"""

from fastapi import APIRouter, HTTPException
from domain.pago import Pago
from schemas.pago import PagoCreate, PagoUpdate
from repositories.pago_repository import PagoRepository
from repositories.alquiler_repository import AlquilerRepository

router = APIRouter(tags=["Pagos"])
pago_repo = PagoRepository()
alquiler_repo = AlquilerRepository()

def _obtener_o_404(pago_id: int) -> Pago:
    """Retorna el pago o responde 404 si no existe."""

    pago = pago_repo.obtener_por_id(pago_id)
    if not pago:
        raise HTTPException(status_code=404, detail="Pago no encontrado")
    return pago

def _verificar_alquiler(alquiler_id: int) -> None:
    """Responde 404 si el alquiler asociado no existe."""

    if not alquiler_repo.obtener_por_id(alquiler_id):
        raise HTTPException(status_code=404, detail="Alquiler no encontrado")

@router.get("/pagos")
@router.get("/pagos/", include_in_schema=False)
def listar_pagos():
    """Obtiene todos los pagos, del más reciente al más antiguo."""

    pagos = sorted(pago_repo.listar_todos(), key=lambda p: p.id, reverse=True)
    return [p.to_dict() for p in pagos]

@router.get("/pagos/{pago_id}")
def obtener_pago(pago_id: int):
    """Obtiene un pago por su id; responde 404 si no existe."""

    return _obtener_o_404(pago_id).to_dict()

@router.post("/pagos")
@router.post("/pagos/", include_in_schema=False)
def crear_pago(data: PagoCreate):
    """Registra un pago para un alquiler existente."""

    _verificar_alquiler(data.alquiler_id)
    try:
        pago = Pago(alquiler_id=data.alquiler_id, monto=data.monto, fecha=data.fecha, metodo=data.metodo)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e
    pago.id = pago_repo.crear(pago)
    return pago.to_dict()

@router.put("/pagos/{pago_id}")
def actualizar_pago(pago_id: int, data: PagoUpdate):
    """Actualiza los campos recibidos de un pago."""

    actual = _obtener_o_404(pago_id)
    alquiler_id = data.alquiler_id if data.alquiler_id is not None else actual.alquiler_id
    _verificar_alquiler(alquiler_id)
    try:
        editado = Pago(pago_id=pago_id, alquiler_id=alquiler_id,
                       monto=data.monto if data.monto is not None else actual.monto,
                       fecha=data.fecha if data.fecha is not None else actual.fecha,
                       metodo=data.metodo if data.metodo is not None else actual.metodo)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e)) from e
    pago_repo.actualizar(editado)
    return editado.to_dict()

@router.delete("/pagos/{pago_id}")
def eliminar_pago(pago_id: int):
    """Elimina un pago por su id."""

    _obtener_o_404(pago_id)
    pago_repo.eliminar(pago_id)
    return {"mensaje": "Pago eliminado", "id": pago_id}
