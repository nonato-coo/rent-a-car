"""Define la clase 'Compra', hereda de `Entidad`.

Atributos: id (int) | vehiculo_id (int) | cantidad (int) | costo_unitario (float) | proveedor (str) | fecha (str)
Métodos: costo_total() -> float | to_dict() -> dict
"""

from domain.entidad import Entidad

class Compra(Entidad):
    """Representa la compra de unidades de un vehículo que aumenta el inventario."""

    def __init__(self, vehiculo_id: int, cantidad: int, costo_unitario: float,
                 proveedor: str, fecha: str, compra_id: int = None):
        """Inicializa una nueva compra validando la cantidad y el costo."""

        super().__init__(compra_id)

        if cantidad <= 0:
            raise ValueError("La cantidad debe ser mayor a 0")

        if costo_unitario < 0:
            raise ValueError("El costo unitario no puede ser negativo")

        self.vehiculo_id = vehiculo_id
        self.cantidad = cantidad
        self.costo_unitario = costo_unitario
        self.proveedor = proveedor
        self.fecha = fecha

    def costo_total(self) -> float:
        """Calcula el costo total de la compra (cantidad x costo unitario)."""
        return self.cantidad * self.costo_unitario

    def to_dict(self) -> dict:
        """Convierte la compra en diccionario, incluyendo su costo total."""
        return {
            "id": self.id,
            "vehiculo_id": self.vehiculo_id,
            "cantidad": self.cantidad,
            "costo_unitario": self.costo_unitario,
            "proveedor": self.proveedor,
            "fecha": self.fecha,
            "costo_total": self.costo_total()
        }
