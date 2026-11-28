"""Define el esquema de la cotización.

Clases: CotizacionRequest
"""

from pydantic import BaseModel

class CotizacionRequest(BaseModel):
    """Define los datos necesarios para cotizar un alquiler.

    Attributes:
        vehiculo_id (int): Vehículo que se desea alquilar.
        fecha_inicio (str): Fecha de inicio en formato YYYY-MM-DD.
        fecha_fin (str): Fecha de fin en formato YYYY-MM-DD.
    """

    vehiculo_id: int
    fecha_inicio: str
    fecha_fin: str
