"""Define la clase 'ServicioReservas'

Métodos:
    crear_reserva_completa(cliente_id, vehiculo_id, fecha_inicio, fecha_fin, metodo_pago) -> dict
    cambiar_estado(alquiler_id: int, nuevo_estado: str) -> dict
    validar_fechas(fecha_inicio: str, fecha_fin: str) -> None
"""

from datetime import datetime
from domain.alquiler import Alquiler
from domain.factura import Factura, DetalleFactura
from domain.pago import Pago
from repositories.vehiculo_repository import VehiculoRepository
from repositories.cliente_repository import ClienteRepository
from repositories.alquiler_repository import AlquilerRepository
from repositories.factura_repository import FacturaRepository
from repositories.pago_repository import PagoRepository
from services.inventario_service import ServicioInventario
from core.excepciones import NoEncontradoError

class ServicioReservas:
    """Coordina la reserva de vehículos, su facturación y el registro de pagos."""

    # Estados a los que puede pasar un alquiler desde cada estado actual.
    TRANSICIONES = {
        "reservado": ["activo", "cancelado"],
        "activo": ["devuelto"],
        "devuelto": [],
        "cancelado": []
    }

    def __init__(self):
        """Inicializa los repositorios y servicios necesarios para gestionar las reservas."""

        self.vehiculo_repo = VehiculoRepository()
        self.cliente_repo = ClienteRepository()
        self.alquiler_repo = AlquilerRepository()
        self.factura_repo = FacturaRepository()
        self.pago_repo = PagoRepository()
        self.inventario_service = ServicioInventario()

    def validar_fechas(self, fecha_inicio: str, fecha_fin: str) -> None:
        """Verifica que las fechas tengan formato YYYY-MM-DD y un rango válido.

        Raises:
            ValueError: Si el formato es incorrecto o la fecha de fin
                es anterior a la de inicio.
        """

        formato = "%Y-%m-%d"
        try:
            inicio = datetime.strptime(fecha_inicio, formato)
            fin = datetime.strptime(fecha_fin, formato)
        except ValueError as e:
            raise ValueError("Las fechas deben tener el formato YYYY-MM-DD.") from e

        if fin < inicio:
            raise ValueError("La fecha de fin no puede ser anterior a la fecha de inicio.")

    def crear_reserva_completa(self, cliente_id: int, vehiculo_id: int, fecha_inicio: str,
                               fecha_fin: str, metodo_pago: str = "Tarjeta Simulada") -> dict:
        """Registra una reserva, genera su factura y registra el pago correspondiente.

        Valida las fechas, el cliente y la disponibilidad del vehículo. Si falla
        la factura o el pago, se deshace lo ya guardado para no dejar datos parciales.

        Args:
            cliente_id (int): Cliente que realiza la reserva.
            vehiculo_id (int): Vehículo que se desea alquilar.
            fecha_inicio (str): Fecha de inicio (YYYY-MM-DD).
            fecha_fin (str): Fecha de fin (YYYY-MM-DD).
            metodo_pago (str): Método de pago. Por defecto, "Tarjeta Simulada".

        Returns:
            dict: Información del alquiler, la factura y el identificador del pago.

        Raises:
            ValueError: Si las fechas no son válidas, el cliente o el vehículo no
                existen, o el vehículo no está habilitado o no tiene unidades.
        """

        self.validar_fechas(fecha_inicio, fecha_fin)
        if not self.cliente_repo.obtener_por_id(cliente_id):
            raise NoEncontradoError("Cliente no encontrado.")
        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            raise NoEncontradoError("Vehículo no encontrado.")

        disponibles = self.inventario_service.calcular_disponibles(vehiculo_id)
        if not vehiculo.puede_alquilarse(disponibles):
            raise ValueError("El vehículo no está habilitado o no tiene unidades disponibles.")

        alquiler = Alquiler(cliente_id=cliente_id, vehiculo_id=vehiculo_id,
                            fecha_inicio=fecha_inicio, fecha_fin=fecha_fin, estado="reservado")
        dias = alquiler.calcular_dias()
        alquiler.id = self.alquiler_repo.crear(alquiler)

        factura_id = None
        try:
            fecha_hoy = datetime.now().strftime("%Y-%m-%d")
            descripcion = f"Alquiler {vehiculo.marca} {vehiculo.modelo} ({dias} días)"
            detalle = DetalleFactura(descripcion=descripcion, cantidad=dias,
                                     precio_unitario=vehiculo.precio_por_dia)
            factura = Factura(alquiler_id=alquiler.id, cliente_id=cliente_id,
                              fecha=fecha_hoy, detalles=[detalle])
            factura_id = self.factura_repo.crear(factura)
            factura.id = factura_id

            pago = Pago(alquiler_id=alquiler.id, monto=factura.total,
                        fecha=fecha_hoy, metodo=metodo_pago)
            pago_id = self.pago_repo.crear(pago)
        except Exception:
            if factura_id:
                self.factura_repo.eliminar(factura_id)
            self.alquiler_repo.eliminar(alquiler.id)
            raise

        return {
            "alquiler": alquiler.to_dict(),
            "factura": factura.to_dict(),
            "pago_id": pago_id
        }

    def cambiar_estado(self, alquiler_id: int, nuevo_estado: str) -> dict:
        """Cambia el estado de un alquiler respetando las transiciones permitidas.

        Args:
            alquiler_id (int): Alquiler que se desea actualizar.
            nuevo_estado (str): Estado al que se desea pasar.

        Returns:
            dict: El alquiler con su nuevo estado.

        Raises:
            ValueError: Si el alquiler no existe o la transición no está permitida.
        """

        alquiler = self.alquiler_repo.obtener_por_id(alquiler_id)
        if not alquiler:
            raise NoEncontradoError("Alquiler no encontrado.")

        if nuevo_estado not in self.TRANSICIONES.get(alquiler.estado, []):
            raise ValueError(f"No se puede cambiar un alquiler de '{alquiler.estado}' a '{nuevo_estado}'.")

        alquiler.cambiar_estado(nuevo_estado)
        self.alquiler_repo.actualizar_estado(alquiler_id, alquiler.estado)
        return alquiler.to_dict()

    def actualizar_alquiler(self, alquiler_id: int, cliente_id: int = None, vehiculo_id: int = None,
                            fecha_inicio: str = None, fecha_fin: str = None, estado: str = None) -> dict:
        """Actualiza los campos recibidos de un alquiler validando datos y transición de estado."""

        actual = self.alquiler_repo.obtener_por_id(alquiler_id)
        if not actual:
            raise NoEncontradoError("Alquiler no encontrado.")

        cliente_id = cliente_id if cliente_id is not None else actual.cliente_id
        vehiculo_id = vehiculo_id if vehiculo_id is not None else actual.vehiculo_id
        fecha_inicio = fecha_inicio or actual.fecha_inicio
        fecha_fin = fecha_fin or actual.fecha_fin
        self.validar_fechas(fecha_inicio, fecha_fin)

        if not self.cliente_repo.obtener_por_id(cliente_id):
            raise NoEncontradoError("Cliente no encontrado.")
        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            raise NoEncontradoError("Vehículo no encontrado.")

        if estado and estado != actual.estado and estado not in self.TRANSICIONES.get(actual.estado, []):
            raise ValueError(f"No se puede cambiar un alquiler de '{actual.estado}' a '{estado}'.")

        if vehiculo_id != actual.vehiculo_id and actual.estado in ("reservado", "activo"):
            if not vehiculo.puede_alquilarse(self.inventario_service.calcular_disponibles(vehiculo_id)):
                raise ValueError("El nuevo vehículo no está habilitado o no tiene unidades disponibles.")

        editado = Alquiler(cliente_id=cliente_id, vehiculo_id=vehiculo_id, fecha_inicio=fecha_inicio,
                           fecha_fin=fecha_fin, estado=actual.estado, id_alquiler=alquiler_id)
        if estado:
            editado.cambiar_estado(estado)
        self.alquiler_repo.actualizar(editado)
        return editado.to_dict()

    def eliminar_alquiler(self, alquiler_id: int) -> None:
        """Elimina un alquiler junto con sus facturas y pagos."""

        if not self.alquiler_repo.obtener_por_id(alquiler_id):
            raise NoEncontradoError("Alquiler no encontrado.")
        for f in self.factura_repo.listar_todos():
            if f.alquiler_id == alquiler_id:
                self.factura_repo.eliminar(f.id)
        for p in self.pago_repo.listar_todos():
            if p.alquiler_id == alquiler_id:
                self.pago_repo.eliminar(p.id)
        self.alquiler_repo.eliminar(alquiler_id)