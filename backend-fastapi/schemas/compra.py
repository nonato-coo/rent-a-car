"""Define los esquemas de compras.

Clases: CompraCreate | CompraUpdate
"""

from typing import Optional
from pydantic import BaseModel

class CompraCreate(BaseModel):
    """Define los datos para registrar una compra de vehículos.

    Attributes:
        vehiculo_id (int): Vehículo adquirido.
        cantidad (int): Unidades compradas (debe ser mayor a 0).
        costo_unitario (float): Costo de cada unidad.
        proveedor (str): Proveedor. Por defecto, "Proveedor General".
        fecha (Optional[str]): Fecha YYYY-MM-DD. Si se omite, se usa la fecha actual.
    """

    vehiculo_id: int
    cantidad: int
    costo_unitario: float
    proveedor: str = "Proveedor General"
    fecha: Optional[str] = None

class CompraUpdate(BaseModel):
    """Define los campos que pueden modificarse de una compra existente.

    Attributes:
        vehiculo_id (Optional[int]): Nuevo vehículo.
        cantidad (Optional[int]): Nueva cantidad.
        costo_unitario (Optional[float]): Nuevo costo unitario.
        proveedor (Optional[str]): Nuevo proveedor.
        fecha (Optional[str]): Nueva fecha (YYYY-MM-DD).
    """

    vehiculo_id: Optional[int] = None
    cantidad: Optional[int] = None
    costo_unitario: Optional[float] = None
    proveedor: Optional[str] = None
    fecha: Optional[str] = None
