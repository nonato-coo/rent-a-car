"""service_inventario.py"""

from datetime import datetime
from repositories.vehiculo_repository import VehiculoRepository
from repositories.alquiler_repository import AlquilerRepository
from repositories.compra_repository import CompraRepository


class ServicioInventario:
    """Gestiona el inventario de vehículos y calcula su disponibilidad."""

    def __init__(self):
        """Inicializa los repositorios necesarios para administrar el inventario."""

        self.vehiculo_repo = VehiculoRepository()
        self.alquiler_repo = AlquilerRepository()
        self.compra_repo = CompraRepository()

    def calcular_disponibles(self, vehiculo_id: int) -> int:
        """Calcula las unidades disponibles de un vehículo.

        Resta las unidades comprometidas en alquileres activos o reservados
        del inventario total registrado.

        Args:
            vehiculo_id (int): Identificador del vehículo que se desea consultar.

        Returns:
            int: Cantidad de unidades disponibles. Retorna 0 si el vehículo
                no existe o si el resultado es negativo.
        """

        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            return 0
        ocupados = self.alquiler_repo.contar_alquileres_activos_vehiculo(vehiculo_id)
        disponibles = vehiculo.stock_total - ocupados
        return max(disponibles, 0)

    def registrar_compra(self, vehiculo_id: int, cantidad: int, costo_unitario: float,
                         proveedor: str = "Proveedor General" ) -> dict:
        """Registra una compra de vehículos y actualiza el inventario.

        Verifica la existencia del vehículo y la validez de la cantidad.
        Luego registra la compra, incrementa el stock y actualiza
        la información del vehículo en la base de datos.

        Args:
            vehiculo_id (int): Identificador del vehículo adquirido.
            cantidad (int): Número de unidades compradas.
            costo_unitario (float): Costo individual de cada unidad.
            proveedor (str): Nombre del proveedor de la compra.
                Por defecto, utiliza "Proveedor General".

        Returns:
            dict: Información de la compra, incluyendo su identificador,
                el vehículo, el nuevo stock total y las unidades disponibles.

        Raises:
            ValueError: Si el vehículo no existe o si la cantidad
                de unidades es menor o igual a cero.
        """

        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            raise ValueError("El vehículo no existe.")
        if cantidad <= 0:
            raise ValueError("La cantidad debe ser mayor a cero.")

        fecha_hoy = datetime.now().strftime("%Y-%m-%d")
        compra_id = self.compra_repo.crear(vehiculo_id, cantidad, costo_unitario,
                                           proveedor, fecha_hoy)

        vehiculo.agregar_stock(cantidad)
        self.vehiculo_repo.actualizar_stock(vehiculo_id, vehiculo.stock_total)

        return {
            "compra_id": compra_id,
            "vehiculo_id": vehiculo_id,
            "nuevo_stock_total": vehiculo.stock_total,
            "disponibles": self.calcular_disponibles(vehiculo_id)
        }
