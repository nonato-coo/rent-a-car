"""Define los esquemas de pagos.

Clases: PagoCreate | PagoUpdate
"""

from typing import Optional
from pydantic import BaseModel

class PagoCreate(BaseModel):
    """Define los datos para registrar un pago simulado.

    Attributes:
        alquiler_id (int): Alquiler al que corresponde el pago.
        monto (float): Monto pagado (debe ser mayor a 0).
        fecha (str): Fecha del pago en formato YYYY-MM-DD.
        metodo (str): Método de pago. Por defecto, "Tarjeta".
    """

    alquiler_id: int
    monto: float
    fecha: str
    metodo: str = "Tarjeta"

class PagoUpdate(BaseModel):
    """Define los campos que pueden modificarse de un pago existente.

    Attributes:
        alquiler_id (Optional[int]): Nuevo alquiler asociado.
        monto (Optional[float]): Nuevo monto.
        fecha (Optional[str]): Nueva fecha (YYYY-MM-DD).
        metodo (Optional[str]): Nuevo método de pago.
    """

    alquiler_id: Optional[int] = None
    monto: Optional[float] = None
    fecha: Optional[str] = None
    metodo: Optional[str] = None
