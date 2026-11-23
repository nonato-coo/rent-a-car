"""service_reservas.py"""

from datetime import datetime
from domain.alquiler import Alquiler
from domain.factura import Factura, DetalleFactura
from repositories.vehiculo_repository import VehiculoRepository
from repositories.alquiler_repository import AlquilerRepository
from repositories.factura_repository import FacturaRepository
from services.inventario_service import ServicioInventario

class ServicioReservas:
    """Coordina el proceso de reserva de vehículos, facturación y registro de pagos."""

    def __init__(self):
        """Inicializa los repositorios y servicios necesarios para gestionar las reservas."""
        self.vehiculo_repo = VehiculoRepository()
        self.alquiler_repo = AlquilerRepository()
        self.factura_repo = FacturaRepository()
        self.inventario_service = ServicioInventario()

    def crear_reserva_completa(self, cliente_id: int, vehiculo_id: int, fecha_inicio: str,
                               fecha_fin: str, metodo_pago: str = "Tarjeta Simulada") -> dict:
        """Registra una reserva, genera su factura y registra el pago correspondiente.

        Primero verifica la existencia y disponibilidad del vehículo.
        Luego crea el alquiler, calcula el costo, genera la factura
        y registra el pago asociado.

        Args:
            cliente_id (int): Identificador del cliente que realiza la reserva.
            vehiculo_id (int): Identificador del vehículo que se desea alquilar.
            fecha_inicio (str): Fecha de inicio del alquiler.
            fecha_fin (str): Fecha de finalización del alquiler.
            metodo_pago (str): Método utilizado para registrar el pago.
                Por defecto, utiliza "Tarjeta Simulada".

        Returns:
            dict: Diccionario con la información del alquiler, la factura
                y el identificador del pago registrado.

        Raises:
            ValueError: Si el vehículo no existe, no está habilitado
                o no tiene unidades disponibles.
        """

        vehiculo = self.vehiculo_repo.obtener_por_id(vehiculo_id)
        if not vehiculo:
            raise ValueError("Vehículo no encontrado.")

        disponibles = self.inventario_service.calcular_disponibles(vehiculo_id)
        if not vehiculo.puede_alquilarse(disponibles):
            raise ValueError("El vehículo no está habilitado o no tiene unidades disponibles.")

        alquiler = Alquiler(cliente_id=cliente_id, vehiculo_id=vehiculo_id,
                            fecha_inicio=fecha_inicio, fecha_fin=fecha_fin, estado="reservado")
        dias = alquiler.calcular_dias()
        alquiler_id = self.alquiler_repo.crear(alquiler)
        alquiler.id = alquiler_id

        fecha_hoy = datetime.now().strftime("%Y-%m-%d")
        descripcion = f"Alquiler {vehiculo.marca} {vehiculo.modelo} ({dias} días)"
        detalle = DetalleFactura(descripcion=descripcion, cantidad=dias,
                                 precio_unitario=vehiculo.precio_por_dia)
        factura = Factura(alquiler_id=alquiler_id, cliente_id=cliente_id,
                          fecha=fecha_hoy, detalles=[detalle])
        factura_id = self.factura_repo.crear(factura)
        factura.id = factura_id

        pago_id = self.factura_repo.registrar_pago(alquiler_id=alquiler_id, monto=factura.total,
                                                   fecha=fecha_hoy, metodo=metodo_pago)

        return {
            "alquiler": alquiler.to_dict(),
            "factura": factura.to_dict(),
            "pago_id": pago_id
        }
