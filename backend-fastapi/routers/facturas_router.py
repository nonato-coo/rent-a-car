"""Define los endpoints del CRUD de facturas.

Endpoints:
    GET /facturas | GET /facturas/{id} | POST /facturas
    PUT /facturas/{id} | DELETE /facturas/{id}

Subtotal, impuesto y total se calculan a partir de los detalles.
"""

from fastapi import APIRouter, HTTPException
from domain.factura import Factura, DetalleFactura
from schemas.factura import FacturaCreate, FacturaUpdate, DetalleFacturaCreate
from repositories.factura_repository import FacturaRepository
from repositories.alquiler_repository import AlquilerRepository
from repositories.cliente_repository import ClienteRepository
from utils.funciones import sumar_montos

router = APIRouter(tags=["Facturas"])
factura_repo = FacturaRepository()
alquiler_repo = AlquilerRepository()
cliente_repo = ClienteRepository()

def _obtener_o_404(factura_id: int) -> Factura:
    """Retorna la factura o responde 404 si no existe."""

    factura = factura_repo.obtener_por_id(factura_id)
    if not factura:
        raise HTTPException(status_code=404, detail="Factura no encontrada")
    return factura

def _construir_detalles(detalles: list[DetalleFacturaCreate]) -> list[DetalleFactura]:
    """Convierte los detalles recibidos en objetos de dominio; exige al menos uno."""

    if not detalles:
        raise HTTPException(status_code=400, detail="La factura debe tener al menos un detalle.")
    return [DetalleFactura(descripcion=d.descripcion, cantidad=d.cantidad,
                           precio_unitario=d.precio_unitario) for d in detalles]

def _verificar_referencias(alquiler_id: int, cliente_id: int) -> None:
    """Responde 404 si el alquiler o el cliente asociados no existen."""

    if not alquiler_repo.obtener_por_id(alquiler_id):
        raise HTTPException(status_code=404, detail="Alquiler no encontrado")
    if not cliente_repo.obtener_por_id(cliente_id):
        raise HTTPException(status_code=404, detail="Cliente no encontrado")

@router.get("/facturas")
@router.get("/facturas/", include_in_schema=False)
def listar_facturas():
    """Obtiene las facturas con sus detalles y la suma total facturada."""

    facturas = sorted(factura_repo.listar_todos(), key=lambda f: f.id, reverse=True)
    return {
        "facturas": [f.to_dict() for f in facturas],
        "total_facturado": sumar_montos([f.total for f in facturas])
    }

@router.get("/facturas/{factura_id}")
def obtener_factura(factura_id: int):
    """Obtiene una factura con sus detalles; responde 404 si no existe."""

    return _obtener_o_404(factura_id).to_dict()

@router.post("/facturas")
@router.post("/facturas/", include_in_schema=False)
def crear_factura(data: FacturaCreate):
    """Crea una factura para un alquiler y cliente existentes."""

    _verificar_referencias(data.alquiler_id, data.cliente_id)
    factura = Factura(alquiler_id=data.alquiler_id, cliente_id=data.cliente_id,
                      fecha=data.fecha, detalles=_construir_detalles(data.detalles))
    factura.id = factura_repo.crear(factura)
    return factura.to_dict()

@router.put("/facturas/{factura_id}")
def actualizar_factura(factura_id: int, data: FacturaUpdate):
    """Actualiza los campos recibidos; si se envían detalles, reemplazan a los anteriores."""

    actual = _obtener_o_404(factura_id)
    alquiler_id = data.alquiler_id if data.alquiler_id is not None else actual.alquiler_id
    cliente_id = data.cliente_id if data.cliente_id is not None else actual.cliente_id
    _verificar_referencias(alquiler_id, cliente_id)

    detalles = _construir_detalles(data.detalles) if data.detalles is not None else actual.detalles
    editada = Factura(id_factura=factura_id, alquiler_id=alquiler_id, cliente_id=cliente_id,
                      fecha=data.fecha if data.fecha is not None else actual.fecha, detalles=detalles)
    factura_repo.actualizar(editada)
    return editada.to_dict()

@router.delete("/facturas/{factura_id}")
def eliminar_factura(factura_id: int):
    """Elimina una factura junto con sus detalles."""

    _obtener_o_404(factura_id)
    factura_repo.eliminar(factura_id)
    return {"mensaje": "Factura eliminada", "id": factura_id}
