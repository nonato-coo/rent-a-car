"""Define los esquemas de alquileres y reservas.

Clases: ReservaCreate | AlquilerUpdate | EstadoAlquilerUpdate
"""

from typing import Optional
from pydantic import BaseModel

class ReservaCreate(BaseModel):
    """Define los datos necesarios para crear una reserva (alquiler, factura y pago).

    Attributes:
        cliente_id (int): Cliente que realiza la reserva.
        vehiculo_id (int): Vehículo que se desea reservar.
        fecha_inicio (str): Fecha de inicio en formato YYYY-MM-DD.
        fecha_fin (str): Fecha de fin en formato YYYY-MM-DD.
        metodo_pago (str): Método de pago. Por defecto, "Tarjeta Simulada".
    """

    cliente_id: int
    vehiculo_id: int
    fecha_inicio: str
    fecha_fin: str
    metodo_pago: str = "Tarjeta Simulada"

class AlquilerUpdate(BaseModel):
    """Define los campos que pueden modificarse de un alquiler existente.

    Attributes:
        cliente_id (Optional[int]): Nuevo cliente.
        vehiculo_id (Optional[int]): Nuevo vehículo.
        fecha_inicio (Optional[str]): Nueva fecha de inicio (YYYY-MM-DD).
        fecha_fin (Optional[str]): Nueva fecha de fin (YYYY-MM-DD).
        estado (Optional[str]): Nuevo estado: "reservado", "activo", "devuelto" o "cancelado".
    """

    cliente_id: Optional[int] = None
    vehiculo_id: Optional[int] = None
    fecha_inicio: Optional[str] = None
    fecha_fin: Optional[str] = None
    estado: Optional[str] = None

class EstadoAlquilerUpdate(BaseModel):
    """Define el nuevo estado que se asignará a un alquiler.

    Attributes:
        estado (str): "reservado", "activo", "devuelto" o "cancelado".
    """

    estado: str
