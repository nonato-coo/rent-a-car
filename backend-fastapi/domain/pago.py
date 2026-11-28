"""Define la clase 'Pago', hereda de `Entidad`.

Atributos: id (int) | alquiler_id (int) | monto (float, property) | fecha (str) | metodo (str)
Métodos: to_dict() -> dict
"""

from domain.entidad import Entidad

class Pago(Entidad):
    """Representa un pago simulado asociado a un alquiler."""

    def __init__(self, alquiler_id: int, monto: float, fecha: str, metodo: str, pago_id: int = None):
        """Inicializa un nuevo pago validando que el monto sea positivo."""

        super().__init__(pago_id)

        if monto <= 0:
            raise ValueError("El monto del pago debe ser mayor a 0")

        self.alquiler_id = alquiler_id
        self._monto = monto
        self.fecha = fecha
        self.metodo = metodo

    @property
    def monto(self) -> float:
        """Retorna el monto del pago."""
        return self._monto

    def to_dict(self) -> dict:
        """Convierte el pago en diccionario."""
        return {
            "id": self.id,
            "alquiler_id": self.alquiler_id,
            "monto": self._monto,
            "fecha": self.fecha,
            "metodo": self.metodo
        }
