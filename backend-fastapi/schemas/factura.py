"""Define los esquemas de facturas.

Clases: DetalleFacturaCreate | FacturaCreate | FacturaUpdate
"""

from typing import Optional
from pydantic import BaseModel

class DetalleFacturaCreate(BaseModel):
    """Define una línea de detalle de una factura.

    El subtotal de cada línea se calcula (cantidad x precio_unitario).

    Attributes:
        descripcion (str): Descripción del concepto facturado.
        cantidad (int): Cantidad facturada.
        precio_unitario (float): Precio de cada unidad.
    """

    descripcion: str
    cantidad: int
    precio_unitario: float

class FacturaCreate(BaseModel):
    """Define los datos para crear una factura.

    Subtotal, impuesto y total no se reciben: se calculan a partir de los detalles.

    Attributes:
        alquiler_id (int): Alquiler al que corresponde la factura.
        cliente_id (int): Cliente facturado.
        fecha (str): Fecha de emisión en formato YYYY-MM-DD.
        detalles (list[DetalleFacturaCreate]): Líneas de la factura.
    """

    alquiler_id: int
    cliente_id: int
    fecha: str
    detalles: list[DetalleFacturaCreate]

class FacturaUpdate(BaseModel):
    """Define los campos que pueden modificarse de una factura existente.

    Attributes:
        alquiler_id (Optional[int]): Nuevo alquiler asociado.
        cliente_id (Optional[int]): Nuevo cliente.
        fecha (Optional[str]): Nueva fecha de emisión (YYYY-MM-DD).
        detalles (Optional[list[DetalleFacturaCreate]]): Nuevas líneas; reemplazan a las anteriores.
    """

    alquiler_id: Optional[int] = None
    cliente_id: Optional[int] = None
    fecha: Optional[str] = None
    detalles: Optional[list[DetalleFacturaCreate]] = None
