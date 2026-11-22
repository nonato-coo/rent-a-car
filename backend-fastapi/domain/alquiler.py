"""alquiler.py"""

from datetime import datetime
from domain.entidad import Entidad

class Alquiler(Entidad):
    """Representa un alquiler de vehículo y administra su información y estado."""

    ESTADOS_PERMITIDOS = ["reservado", "activo", "devuelto", "cancelado"]

    def __init__(self, cliente_id: int, vehiculo_id: int,
                 fecha_inicio: str, fecha_fin: str,
                 estado: str = "reservado", id_alquiler: int = None):
        """Inicializa un alquiler con los datos del cliente, vehículo,
        período de alquiler y estado correspondiente.

        Si el estado proporcionado no es válido, se establece como
        'reservado' por defecto.
        """
        super().__init__(id_alquiler)
        self.cliente_id = cliente_id
        self.vehiculo_id = vehiculo_id
        self.fecha_inicio = fecha_inicio
        self.fecha_fin = fecha_fin
        self._estado = estado if estado in self.ESTADOS_PERMITIDOS else "reservado"

    @property
    def estado(self) -> str:
        """Retorna el estado actual del alquiler."""
        return self._estado

    def calcular_dias(self) -> int:
        """Calcula la duración del alquiler en días.

        Convierte las fechas de inicio y fin al formato de fecha correspondiente
        y obtiene la diferencia entre ambas. Si la duración es menor a un día,
        establece un mínimo de un día. Si las fechas tienen un formato inválido,
        retorna un día por defecto.
        """
        formato = "%Y-%m-%d"
        try:
            f1 = datetime.strptime(self.fecha_inicio, formato)
            f2 = datetime.strptime(self.fecha_fin, formato)
            dias = (f2 - f1).days
            return max(dias, 1)  # Al menos se cobra 1 día
        except ValueError:
            return 1

    def cambiar_estado(self, nuevo_estado: str) -> None:
        """Actualiza el estado del alquiler si pertenece a los estados permitidos.

        Argumentos:
            nuevo_estado (str): Nuevo estado que se asignará al alquiler.

        Raises:
            ValueError: Si el estado proporcionado no está permitido.
        """
        if nuevo_estado in self.ESTADOS_PERMITIDOS:
            self._estado = nuevo_estado
        else:
            raise ValueError(f"Estado no válido: {nuevo_estado}")

    def to_dict(self) -> dict:
        """Convierte los atributos del alquiler en un diccionario.

        Incluye el identificador del alquiler, los identificadores del cliente
        y vehículo, las fechas del período y el estado actual.
        """
        return {
            "id": self.id,
            "cliente_id": self.cliente_id,
            "vehiculo_id": self.vehiculo_id,
            "fecha_inicio": self.fecha_inicio,
            "fecha_fin": self.fecha_fin,
            "estado": self._estado
        }
