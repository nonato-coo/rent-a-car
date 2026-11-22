"""vehiculos_router.py"""

from typing import Optional

from fastapi import APIRouter, HTTPException
from repositories.vehiculo_repository import VehiculoRepository
from services.servicio_inventario import ServicioInventario

router = APIRouter(prefix="/vehiculos", tags=["Vehículos"])
vehiculo_repo = VehiculoRepository()
inventario_service = ServicioInventario()


@router.get("/")
def listar_vehiculos(
    marca: Optional[str] = None,
    anio: Optional[int] = None,
    categoria: Optional[str] = None,
    solo_disponibles: bool = False
):
    """Obtiene la lista de vehículos registrados con filtros opcionales.

    Args:
        marca (Optional[str]): Marca por la que se desea filtrar.
        anio (Optional[int]): Año de fabricación por el que se desea filtrar.
        categoria (Optional[str]): Categoría por la que se desea filtrar.
        solo_disponibles (bool): Indica si se deben mostrar únicamente
            los vehículos que tienen unidades disponibles.

    Returns:
        list[dict]: Lista de vehículos con sus datos y cantidad de unidades
            disponibles, aplicando los filtros especificados.
    """

    vehiculos = vehiculo_repo.listar_todos()
    resultado = []

    for v in vehiculos:
        disponibles = inventario_service.calcular_disponibles(v.id)
        data = v.to_dict()
        data["disponibles"] = disponibles

        if marca and v.marca.lower() != marca.lower():
            continue
        if anio and v.anio != anio:
            continue
        if categoria and v.categoria.lower() != categoria.lower():
            continue
        if solo_disponibles and not v.puede_alquilarse(disponibles):
            continue

        resultado.append(data)

    return resultado

@router.get("/{vehiculo_id}")
def obtener_vehiculo(vehiculo_id: int):
    """Obtiene la información de un vehículo específico.

    Args:
        vehiculo_id (int): Identificador del vehículo que se desea consultar.

    Returns:
        dict: Datos del vehículo junto con su cantidad de unidades disponibles.

    Raises:
        HTTPException: Si no existe un vehículo con el identificador indicado.
            En ese caso, devuelve el código HTTP 404.
    """

    vehiculo = vehiculo_repo.obtener_por_id(vehiculo_id)
    if not vehiculo:
        raise HTTPException(status_code=404, detail="Vehículo no encontrado")
    data = vehiculo.to_dict()
    data["disponibles"] = inventario_service.calcular_disponibles(vehiculo.id)
    return data
