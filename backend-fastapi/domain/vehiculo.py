"""vehiculo.py"""

from domain.entidad import Entidad
class Vehiculo(Entidad):
    """Representa un vehículo y sus características dentro del sistema de alquiler."""

    def __init__(self, marca: str, modelo: str, anio: int, categoria: str,
                 precio_por_dia: float, stock_total: int = 0,
                 habilitado: int = 1, id_vehiculo: int = None):
        """Inicializa un vehículo con sus datos de identificación, características,
        precio de alquiler y estado de disponibilidad.
        """
        super().__init__(id_vehiculo)
        self.marca = marca
        self.modelo = modelo
        self.anio = anio
        self.categoria = categoria
        self.precio_por_dia = precio_por_dia
        self._stock_total = stock_total
        self._habilitado = habilitado

    @property
    def stock_total(self) -> int:
        """Retorna la cantidad total de unidades registradas para este vehículo."""
        return self._stock_total

    @property
    def habilitado(self) -> int:
        """Retorna el estado de habilitación del vehículo para su alquiler."""
        return self._habilitado

    def agregar_stock(self, cantidad: int) -> None:
        """Incrementa el inventario del vehículo según la cantidad especificada.

        Solo se agregan unidades cuando la cantidad indicada es positiva.
        """
        if cantidad > 0:
            self._stock_total += cantidad

    def puede_alquilarse(self, disponibles: int) -> bool:
        """Determina si el vehículo puede ser alquilado.

        Comprueba que el vehículo esté habilitado y que exista al menos
        una unidad disponible para alquiler.
        """
        return self._habilitado == 1 and disponibles > 0

    def to_dict(self) -> dict:
        """Retorna los atributos del vehículo en un diccionario.

        El diccionario incluye su identificador, características,
        precio por día, inventario total y estado de habilitación.
        """
        return {
            "id": self.id,
            "marca": self.marca,
            "modelo": self.modelo,
            "anio": self.anio,
            "categoria": self.categoria,
            "precio_por_dia": self.precio_por_dia,
            "stock_total": self._stock_total,
            "habilitado": self._habilitado
        }
