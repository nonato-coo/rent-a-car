"""Define los endpoints del CRUD de vehículos y los filtros del catálogo.

Endpoints:
    GET /vehiculos (filtros: marca, anio, categoria, solo_disponibles) | GET /vehiculos/{id}
    POST /vehiculos | PUT /vehiculos/{id} | DELETE /vehiculos/{id}
    GET /marcas | GET /anios
"""

from typing import Optional
from fastapi import APIRouter, HTTPException
from domain.vehiculo import Vehiculo
from schemas.vehiculo import VehiculoCreate, VehiculoUpdate
from repositories.vehiculo_repository import VehiculoRepository
from repositories.alquiler_repository import AlquilerRepository
from repositories.compra_repository import CompraRepository
from services.inventario_service import ServicioInventario
from utils.funciones import extraer_marcas_unicas, extraer_anios_unicos, filtrar_vehiculos_puro

router = APIRouter(tags=["Vehículos"])
vehiculo_repo = VehiculoRepository()
alquiler_repo = AlquilerRepository()
compra_repo = CompraRepository()
inventario_service = ServicioInventario()

def _a_dict(vehiculo: Vehiculo) -> dict:
    """Convierte un vehículo en diccionario e incluye sus unidades disponibles."""

    datos = vehiculo.to_dict()
    datos["disponibles"] = inventario_service.calcular_disponibles(vehiculo.id)
    return datos

def _obtener_o_404(vehiculo_id: int) -> Vehiculo:
    """Retorna el vehículo o responde 404 si no existe."""

    vehiculo = vehiculo_repo.obtener_por_id(vehiculo_id)
    if not vehiculo:
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")
    return vehiculo

@router.get("/vehiculos")
@router.get("/vehiculos/", include_in_schema=False)
def listar_vehiculos(marca: Optional[str] = None, anio: Optional[int] = None,
                     categoria: Optional[str] = None, solo_disponibles: bool = False):
    """Obtiene los vehículos con su disponibilidad, con filtros opcionales."""

    vehiculos = [_a_dict(v) for v in vehiculo_repo.listar_todos()]
    return filtrar_vehiculos_puro(vehiculos, marca, anio, categoria, solo_disponibles)

@router.get("/marcas")
def listar_marcas():
    """Obtiene las marcas únicas de los vehículos (para el filtro del catálogo)."""

    return extraer_marcas_unicas([v.to_dict() for v in vehiculo_repo.listar_todos()])

@router.get("/anios")
def listar_anios():
    """Obtiene los años únicos de los vehículos (para el filtro del catálogo)."""

    return extraer_anios_unicos([v.to_dict() for v in vehiculo_repo.listar_todos()])

@router.get("/vehiculos/{vehiculo_id}")
def obtener_vehiculo(vehiculo_id: int):
    """Obtiene un vehículo por su id; responde 404 si no existe."""

    return _a_dict(_obtener_o_404(vehiculo_id))

@router.post("/vehiculos")
@router.post("/vehiculos/", include_in_schema=False)
def crear_vehiculo(data: VehiculoCreate):
    """Registra un nuevo vehículo."""

    vehiculo = Vehiculo(marca=data.marca, modelo=data.modelo, anio=data.anio,
                        categoria=data.categoria, precio_por_dia=data.precio_por_dia,
                        stock_total=data.stock_total, habilitado=data.habilitado)
    vehiculo.id = vehiculo_repo.crear(vehiculo)
    return _a_dict(vehiculo)

@router.put("/vehiculos/{vehiculo_id}")
def actualizar_vehiculo(vehiculo_id: int, data: VehiculoUpdate):
    """Actualiza los campos recibidos de un vehículo (el stock cambia solo con compras)."""

    actual = _obtener_o_404(vehiculo_id)
    editado = Vehiculo(
        id_vehiculo=vehiculo_id,
        marca=data.marca if data.marca is not None else actual.marca,
        modelo=data.modelo if data.modelo is not None else actual.modelo,
        anio=data.anio if data.anio is not None else actual.anio,
        categoria=data.categoria if data.categoria is not None else actual.categoria,
        precio_por_dia=data.precio_por_dia if data.precio_por_dia is not None else actual.precio_por_dia,
        stock_total=actual.stock_total,
        habilitado=data.habilitado if data.habilitado is not None else actual.habilitado)
    vehiculo_repo.actualizar(editado)
    return _a_dict(editado)

@router.delete("/vehiculos/{vehiculo_id}")
def eliminar_vehiculo(vehiculo_id: int):
    """Elimina un vehículo; responde 400 si tiene alquileres o compras (conviene deshabilitarlo)."""

    _obtener_o_404(vehiculo_id)

    tiene_alquileres = any(a.vehiculo_id == vehiculo_id for a in alquiler_repo.listar_todos())
    tiene_compras = any(c.vehiculo_id == vehiculo_id for c in compra_repo.listar_todos())
    if tiene_alquileres or tiene_compras:
        raise HTTPException(status_code=400,
                            detail="No se puede eliminar: tiene alquileres o compras asociados. Deshabilítalo en su lugar.")

    vehiculo_repo.eliminar(vehiculo_id)
    return {"mensaje": "Vehículo eliminado", "id": vehiculo_id}
