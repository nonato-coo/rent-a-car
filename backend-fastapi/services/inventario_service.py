"""Define la clase 'ServicioInventario'

Métodos:
    calcular_disponibles(vehiculo_id: int) -> int
    registrar_compra(vehiculo_id, cantidad, costo_unitario, proveedor, fecha) -> dict
    actualizar_compra(compra_id, vehiculo_id, cantidad, costo_unitario, proveedor, fecha) -> dict
    eliminar_compra(compra_id: int) -> None
    _ajustar_stock(vehiculo_id: int, delta: int) -> int
"""

from datetime import datetime
from domain.compra import Compra
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
            int: Unidades disponibles. Retorna 0 si el vehículo no existe
                o si el resultado es negativo.
        """

        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            return 0
        ocupados = self.alquiler_repo.contar_alquileres_activos_vehiculo(vehiculo_id)
        return max(vehiculo.stock_total - ocupados, 0)

    def _ajustar_stock(self, vehiculo_id: int, delta: int) -> int:
        """Suma o resta unidades al stock de un vehículo y retorna el nuevo stock.

        Raises:
            ValueError: Si el vehículo no existe o si el stock resultante
                quedaría por debajo de las unidades alquiladas.
        """

        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            raise ValueError("El vehículo no existe.")

        nuevo_stock = vehiculo.stock_total + delta
        ocupados = self.alquiler_repo.contar_alquileres_activos_vehiculo(vehiculo_id)
        if nuevo_stock < ocupados:
            raise ValueError("El stock no puede quedar por debajo de las unidades alquiladas.")

        self.vehiculo_repo.actualizar_stock(vehiculo_id, nuevo_stock)
        return nuevo_stock

    def registrar_compra(self, vehiculo_id: int, cantidad: int, costo_unitario: float,
                         proveedor: str = "Proveedor General", fecha: str = None) -> dict:
        """Registra una compra de vehículos y aumenta el inventario.

        Args:
            vehiculo_id (int): Identificador del vehículo adquirido.
            cantidad (int): Unidades compradas.
            costo_unitario (float): Costo de cada unidad.
            proveedor (str): Proveedor de la compra. Por defecto, "Proveedor General".
            fecha (str): Fecha YYYY-MM-DD. Si se omite, se usa la fecha actual.

        Returns:
            dict: Identificador de la compra, el vehículo, el nuevo stock total
                y las unidades disponibles.

        Raises:
            ValueError: Si el vehículo no existe o los datos de la compra no son válidos.
        """

        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            raise ValueError("El vehículo no existe.")

        fecha = fecha or datetime.now().strftime("%Y-%m-%d")
        compra = Compra(vehiculo_id=vehiculo_id, cantidad=cantidad,
                        costo_unitario=costo_unitario, proveedor=proveedor, fecha=fecha)
        compra_id = self.compra_repo.crear(compra)

        vehiculo.agregar_stock(cantidad)
        self.vehiculo_repo.actualizar_stock(vehiculo_id, vehiculo.stock_total)

        return {
            "compra_id": compra_id,
            "vehiculo_id": vehiculo_id,
            "nuevo_stock_total": vehiculo.stock_total,
            "disponibles": self.calcular_disponibles(vehiculo_id)
        }

    def actualizar_compra(self, compra_id: int, vehiculo_id: int = None, cantidad: int = None,
                          costo_unitario: float = None, proveedor: str = None,
                          fecha: str = None) -> dict:
        """Modifica una compra y reajusta el stock de los vehículos afectados.

        Solo se cambian los campos recibidos. Si cambia la cantidad, el stock se
        ajusta por la diferencia; si cambia el vehículo, se descuenta del anterior
        y se suma al nuevo.

        Returns:
            dict: La compra actualizada.

        Raises:
            ValueError: Si la compra o el vehículo no existen, los datos no son
                válidos o el stock quedaría por debajo de las unidades alquiladas.
        """

        actual = self.compra_repo.obtener_por_id(compra_id)
        if not actual:
            raise ValueError("La compra no existe.")

        nueva = Compra(
            compra_id = compra_id,
            vehiculo_id = vehiculo_id if vehiculo_id is not None else actual.vehiculo_id,
            cantidad = cantidad if cantidad is not None else actual.cantidad,
            costo_unitario = costo_unitario if costo_unitario is not None else actual.costo_unitario,
            proveedor = proveedor if proveedor is not None else actual.proveedor,
            fecha = fecha if fecha is not None else actual.fecha
        )

        if nueva.vehiculo_id == actual.vehiculo_id:
            if nueva.cantidad != actual.cantidad:
                self._ajustar_stock(actual.vehiculo_id, nueva.cantidad - actual.cantidad)
        else:
            if not self.vehiculo_repo.obtener_por_id(nueva.vehiculo_id):
                raise ValueError("El vehículo no existe.")
            self._ajustar_stock(actual.vehiculo_id, -actual.cantidad)
            self._ajustar_stock(nueva.vehiculo_id, nueva.cantidad)

        self.compra_repo.actualizar(nueva)
        return nueva.to_dict()

    def eliminar_compra(self, compra_id: int) -> None:
        """Elimina una compra y descuenta sus unidades del stock del vehículo.

        Raises:
            ValueError: Si la compra no existe o el stock quedaría por debajo
                de las unidades alquiladas.
        """

        compra = self.compra_repo.obtener_por_id(compra_id)
        if not compra:
            raise ValueError("La compra no existe.")

        if self.vehiculo_repo.obtener_por_id(compra.vehiculo_id):
            self._ajustar_stock(compra.vehiculo_id, -compra.cantidad)

        self.compra_repo.eliminar(compra_id)
