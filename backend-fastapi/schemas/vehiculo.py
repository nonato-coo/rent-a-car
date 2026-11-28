"""Define los esquemas de vehículos.

Clases: VehiculoCreate | VehiculoUpdate
"""

from typing import Optional
from pydantic import BaseModel

class VehiculoCreate(BaseModel):
    """Define los datos necesarios para registrar un nuevo vehículo.

    Attributes:
        marca (str): Marca del vehículo.
        modelo (str): Modelo del vehículo.
        anio (int): Año de fabricación.
        categoria (str): Categoría del vehículo.
        precio_por_dia (float): Precio diario del alquiler.
        stock_total (int): Unidades iniciales. Por defecto, 0.
        habilitado (int): 1 si se puede alquilar, 0 si no. Por defecto, 1.
    """

    marca: str
    modelo: str
    anio: int
    categoria: str
    precio_por_dia: float
    stock_total: int = 0
    habilitado: int = 1

class VehiculoUpdate(BaseModel):
    """Define los campos que pueden modificarse de un vehículo existente.

    El stock no se modifica aquí: aumenta mediante el registro de compras.

    Attributes:
        marca (Optional[str]): Nueva marca.
        modelo (Optional[str]): Nuevo modelo.
        anio (Optional[int]): Nuevo año de fabricación.
        categoria (Optional[str]): Nueva categoría.
        precio_por_dia (Optional[float]): Nuevo precio diario.
        habilitado (Optional[int]): 1 para habilitar, 0 para deshabilitar.
    """

    marca: Optional[str] = None
    modelo: Optional[str] = None
    anio: Optional[int] = None
    categoria: Optional[str] = None
    precio_por_dia: Optional[float] = None
    habilitado: Optional[int] = None
