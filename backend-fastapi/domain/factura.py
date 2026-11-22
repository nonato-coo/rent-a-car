"""factura.py"""

from domain.entidad import Entidad
from functools import reduce

class DetalleFactura(Entidad):
    """Representa un concepto individual incluido en una factura."""

    def __init__(self, descripcion: str, cantidad: int,
                 precio_unitario: float, id_detalle_factura: int = None):
        """Inicializa un detalle de factura y calcula su subtotal
        según la cantidad y el precio unitario.
        """
        super().__init__(id_detalle_factura)
        self.descripcion = descripcion
        self.cantidad = cantidad
        self.precio_unitario = precio_unitario
        self.subtotal = cantidad * precio_unitario

    def to_dict(self) -> dict:
        """Convierte los atributos del detalle de factura en un diccionario."""
        return {
            "id": self.id,
            "descripcion": self.descripcion,
            "cantidad": self.cantidad,
            "precio_unitario": self.precio_unitario,
            "subtotal": self.subtotal
        }


class Factura(Entidad):
    """Representa una factura y administra sus detalles y cálculos monetarios."""

    def __init__(self, alquiler_id: int, cliente_id: int,
                 fecha: str, detalles: list[DetalleFactura] = None,
                 impuesto_tasa: float = 0.13, id_factura: int = None):
        """Inicializa una factura con los datos del alquiler, cliente,
        fecha de emisión, detalles e impuesto aplicable.

        Si no se proporcionan detalles, se inicializa una lista vacía.
        Al finalizar la inicialización, calcula los importes de la factura.
        """
        super().__init__(id_factura)
        self.alquiler_id = alquiler_id
        self.cliente_id = cliente_id
        self.fecha = fecha
        self.detalles = detalles if detalles else []
        self.impuesto_tasa = impuesto_tasa
        self.subtotal = 0.0
        self.impuesto = 0.0
        self.total = 0.0
        self.calcular_totales()

    def calcular_totales(self) -> None:
        """Calcula y actualiza los importes generales de la factura.

        Suma los subtotales de todos los detalles para obtener el subtotal
        general, calcula el impuesto correspondiente y determina el total
        final a pagar.
        """
        self.subtotal = reduce(
            lambda acc, detalle: acc + detalle.subtotal,
            self.detalles,
            0
        )
        self.impuesto = self.subtotal * self.impuesto_tasa
        self.total = self.subtotal + self.impuesto

    def to_dict(self) -> dict:
        """Convierte los atributos de la factura en un diccionario.

        Incluye los importes calculados y una lista con los detalles
        convertidos individualmente en diccionarios.
        """
        return {
            "id": self.id,
            "alquiler_id": self.alquiler_id,
            "cliente_id": self.cliente_id,
            "fecha": self.fecha,
            "subtotal": round(self.subtotal, 2),
            "impuesto": round(self.impuesto, 2),
            "total": round(self.total, 2),
            "detalles": list(map(lambda d: d.to_dict(), self.detalles))
        }
