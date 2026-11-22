"""modelos.py"""

from typing import Optional

from pydantic import BaseModel

class CompraCreate(BaseModel):
    """Define los datos necesarios para registrar una compra de vehículos.

    Attributes:
        vehiculo_id (int): Identificador del vehículo adquirido.
        cantidad (int): Cantidad de unidades compradas.
        costo_unitario (float): Costo individual de cada unidad.
        proveedor (Optional[str]): Nombre del proveedor. Por defecto,
            utiliza "Proveedor General".
    """

    vehiculo_id: int
    cantidad: int
    costo_unitario: float
    proveedor: Optional[str] = "Proveedor General"


class CotizacionRequest(BaseModel):
    """Define los datos necesarios para solicitar una cotización de alquiler.

    Attributes:
        vehiculo_id (int): Identificador del vehículo que se desea alquilar.
        fecha_inicio (str): Fecha de inicio del alquiler en formato YYYY-MM-DD.
        fecha_fin (str): Fecha de finalización del alquiler en formato YYYY-MM-DD.
    """

    vehiculo_id: int
    fecha_inicio: str  # formato YYYY-MM-DD
    fecha_fin: str     # formato YYYY-MM-DD


class ReservaCreate(BaseModel):
    """Define los datos necesarios para crear una reserva de vehículo.

    Attributes:
        cliente_id (int): Identificador del cliente que realiza la reserva.
        vehiculo_id (int): Identificador del vehículo que se desea reservar.
        fecha_inicio (str): Fecha de inicio del alquiler en formato YYYY-MM-DD.
        fecha_fin (str): Fecha de finalización del alquiler en formato YYYY-MM-DD.
        metodo_pago (Optional[str]): Método de pago utilizado. Por defecto,
            utiliza "Tarjeta Simulada".
    """
    
    cliente_id: int
    vehiculo_id: int
    fecha_inicio: str  # formato YYYY-MM-DD
    fecha_fin: str     # formato YYYY-MM-DD
    metodo_pago: Optional[str] = "Tarjeta Simulada"
